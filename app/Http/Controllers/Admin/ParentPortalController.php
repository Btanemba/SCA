<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\AbsenceNotice;
use App\Models\StudentWalletTransaction;
use App\Services\ParentChildAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ParentPortalController extends Controller
{
    public function account()
    {
        $parent = backpack_user()?->person;
        abort_unless($parent?->sacRole?->code === Person::ROLE_PARENT, 403);

        return view('parent.account', ['parent' => $parent]);
    }

    public function reportAbsence(Request $request, int $student, ParentChildAccess $access)
    {
        $child = $access->child($student);
        $today = now('Africa/Lagos')->toDateString();
        $data = $request->validate([
            'absence_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($child, $data, $access) {
            Person::query()->lockForUpdate()->findOrFail($child->id);
            $access->child($child->id);
            if ($child->attendances()->whereDate('attendance_date', $data['absence_date'])->exists()) {
                throw ValidationException::withMessages(['absence_date' => 'Attendance has already been recorded for this date.']);
            }
            if ($child->absenceNotices()->whereDate('absence_date', $data['absence_date'])->whereNull('cancelled_at')->exists()) {
                throw ValidationException::withMessages(['absence_date' => 'An absence has already been reported for this date.']);
            }

            AbsenceNotice::create([
                'child_id' => $child->id,
                'absence_date' => $data['absence_date'],
                'reason' => $data['reason'] ?? null,
                'reported_by' => backpack_user()->id,
            ]);
        });

        return redirect()->route('backpack.dashboard')->with('success', 'Absence reported.');
    }

    public function cancelAbsence(int $student, int $notice, ParentChildAccess $access)
    {
        $child = $access->child($student);
        DB::transaction(function () use ($child, $notice, $access) {
            Person::query()->lockForUpdate()->findOrFail($child->id);
            $access->child($child->id);
            $absence = AbsenceNotice::query()->where('child_id', $child->id)->whereKey($notice)
                ->whereNull('cancelled_at')->whereDate('absence_date', '>=', now('Africa/Lagos')->toDateString())
                ->lockForUpdate()->firstOrFail();
            $absence->update(['cancelled_at' => now(), 'cancelled_by' => backpack_user()->id]);
        });

        return redirect()->route('backpack.dashboard')->with('success', 'Absence notice cancelled.');
    }

    public function show(Request $request, int $student, ParentChildAccess $access)
    {
        $child = $access->child($student);
        $data = $request->validate(['month' => 'nullable|date_format:Y-m']);
        $month = $data['month'] ?? now()->format('Y-m');
        $charges = $child->studentFeeCharges()
            ->whereDate('billing_month', $month.'-01')
            ->with(['payments' => fn ($query) => $query->orderByDesc('paid_at')->orderByDesc('id')])
            ->withSum('payments', 'amount_kobo')->orderBy('id')->get();
        $wallet = StudentWalletTransaction::query()->where('student_id', $child->id);
        $walletBalance = (int) (clone $wallet)->where('type', 'credit')->sum('amount_kobo')
            - (int) (clone $wallet)->where('type', 'debit')->sum('amount_kobo');

        return response()->view('parent.child', [
            'child' => $child,
            'month' => $month,
            'charges' => $charges,
            'walletBalance' => $walletBalance,
            'walletTransactions' => $wallet->with(['receipt', 'feePayment.charge'])->latest('id')->limit(30)->get(),
            'attendances' => $child->attendances()->orderByDesc('attendance_date')->orderByDesc('id')->limit(30)->get(),
            'pickupContacts' => $child->pickupContacts()->get(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function photo(int $student, ParentChildAccess $access)
    {
        $child = $access->child($student);
        $disk = Storage::disk('public');
        abort_unless($child->image_path && $disk->exists($child->image_path), 404);
        $contents = $disk->get($child->image_path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true), 404);

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
