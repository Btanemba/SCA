<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PayrollBankInstruction;
use App\Models\Payroll;
use App\Models\PayrollEntry;
use App\Models\Person;
use App\Services\PayrollAmounts;
use App\Services\PayrollDocuments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    private function authorizeRole(?string $role = null): void
    {
        $current = backpack_user()?->person?->sacRole?->code;
        abort_unless($role ? $current === $role : in_array($current, [Person::ROLE_ACCOUNTANT, Person::ROLE_FOUNDER], true), 403);
    }

    private function event(Payroll $payroll, string $action, array $details = []): void
    {
        $payroll->events()->create(['user_id' => backpack_user()->id, 'action' => $action, 'details' => $details]);
    }

    public function index(Request $request)
    {
        $this->authorizeRole();

        $filters = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
        ]);
        $search = trim((string) $request->query('search', ''));
        $payrolls = Payroll::withSum('entries', 'net_kobo');

        if (isset($filters['month'])) {
            $payrolls->where('month', $filters['month']);
        }
        if (isset($filters['year'])) {
            $payrolls->where('year', $filters['year']);
        }

        if ($search !== '') {
            if (preg_match('/^(?:SCA\/PAY\/)?(\d{4})(?:\/(\d{1,2}))?(?:\/(\d+))?$/i', $search, $referenceParts)) {
                $payrolls->where('year', (int) $referenceParts[1]);
                if (!empty($referenceParts[2])) {
                    $payrolls->where('month', (int) $referenceParts[2]);
                }
                if (!empty($referenceParts[3])) {
                    $payrolls->whereKey((int) $referenceParts[3]);
                }
            } else {
                $payrolls->where(function ($query) use ($search) {
                    $query->where('status', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $number = (int) $search;
                        if (strlen($search) === 4) {
                            $query->orWhere('year', $number);
                        } else {
                            $query->orWhereKey($number);
                        }
                        if ($number >= 1 && $number <= 12) {
                            $query->orWhere('month', $number);
                        }
                    }

                    foreach (range(1, 12) as $month) {
                        if (str_contains(strtolower(Carbon::create(2000, $month, 1)->format('F')), strtolower($search))) {
                            $query->orWhere('month', $month);
                        }
                    }
                });
            }
        }

        return view('payroll.index', ['payrolls' => $payrolls->orderByDesc('year')->orderByDesc('month')->paginate(20)->withQueryString()]);
    }

    public function store(Request $request)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        $data = $request->validate(['year' => 'required|integer|between:2000,2100', 'month' => 'required|integer|between:1,12']);
        $request->validate(['month' => [Rule::unique('payrolls')->where('year', $data['year'])]]);
        $payroll = DB::transaction(function () use ($data) {
            $payroll = Payroll::create($data + ['created_by' => backpack_user()->id]);
            $this->event($payroll, 'created');

            return $payroll;
        });

        return redirect()->route('payroll.show', $payroll)->with('success', 'Payroll draft created.');
    }

    public function show(Payroll $payroll)
    {
        $this->authorizeRole();
        $payroll->load(['entries', 'events.user']);

        return view('payroll.show', compact('payroll'));
    }

    public function destroy(Payroll $payroll)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        DB::transaction(function () use ($payroll) {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            abort_unless($locked->status === 'draft' && ! $locked->submitted_at, 409, 'Only an unsubmitted draft can be deleted.');
            $locked->delete();
        });

        return redirect()->route('payroll.index')->with('success', 'Draft deleted.');
    }

    public function entryForm(Payroll $payroll, ?PayrollEntry $entry = null)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        abort_unless($payroll->editable(), 409, 'This payroll is locked.');
        abort_if($entry && $entry->payroll_id !== $payroll->id, 404);
        $assignedPersonIds = $payroll->entries()
            ->when($entry, fn ($query) => $query->where('id', '!=', $entry->id))
            ->pluck('person_id');
        $staff = Person::whereHas('sacRole', fn ($query) => $query->whereIn('code', [Person::ROLE_STAFF, Person::ROLE_ADMIN, Person::ROLE_ACCOUNTANT, Person::ROLE_SECURITY]))
            ->whereNotIn('id', $assignedPersonIds)
            ->with('sacRole')
            ->orderBy('first_name')->orderBy('last_name')->get();

        return view('payroll.entry', compact('payroll', 'entry', 'staff'));
    }

    public function saveEntry(Request $request, Payroll $payroll, ?PayrollEntry $entry = null)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        abort_if($entry && $entry->payroll_id !== $payroll->id, 404);
        $data = $request->validate([
            'person_id' => ['required', 'integer', Rule::exists('persons', 'id'), Rule::unique('payroll_entries')->where('payroll_id', $payroll->id)->ignore($entry?->id)],
            'basic' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/D'],
            'allowances' => 'nullable|array|max:30',
            'deductions' => 'nullable|array|max:30',
            'allowances.*.label' => 'required|string|max:100',
            'deductions.*.label' => 'required|string|max:100',
            'allowances.*.amount' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/D'],
            'deductions.*.amount' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/D'],
        ]);
        $person = Person::with('sacRole')->findOrFail($data['person_id']);
        abort_unless(in_array($person->sacRole?->code, [Person::ROLE_STAFF, Person::ROLE_ADMIN, Person::ROLE_ACCOUNTANT, Person::ROLE_SECURITY], true), 422, 'Select an eligible employee.');
        $bankDetails = [
            'bank_name' => $person->bank_name,
            'account_name' => $person->account_name,
            'account_number' => $person->account_number,
        ];
        if (! filled($bankDetails['bank_name']) || ! filled($bankDetails['account_name'])
            || ! preg_match('/^\d{10}$/D', (string) $bankDetails['account_number'])) {
            $legacyBankDetails = $entry && $entry->person_id === $person->id ? [
                'bank_name' => $entry->bank_name,
                'account_name' => $entry->account_name,
                'account_number' => $entry->account_number,
            ] : [];
            if (! filled($legacyBankDetails['bank_name'] ?? null) || ! filled($legacyBankDetails['account_name'] ?? null)
                || ! preg_match('/^\d{10}$/D', (string) ($legacyBankDetails['account_number'] ?? ''))) {
                throw ValidationException::withMessages(['person_id' => 'Add valid bank details to this person profile before adding salary.']);
            }
            $bankDetails = $legacyBankDetails;
        }
        $amounts = PayrollAmounts::calculate((string) $data['basic'], $data['allowances'] ?? [], $data['deductions'] ?? []);
        DB::transaction(function () use ($payroll, $entry, $data, $person, $bankDetails, $amounts) {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            abort_unless($locked->editable(), 409, 'This payroll is locked.');
            $values = ['person_id' => $person->id] + $bankDetails
                + ['employee_name' => $person->full_name] + $amounts;
            if ($entry) {
                $saved = $locked->entries()->findOrFail($entry->id);
                $saved->update($values);
            } else {
                $saved = $locked->entries()->create($values);
            }
            $this->event($locked, $entry ? 'entry_updated' : 'entry_added', ['entry_id' => $saved->id, 'employee' => $saved->employee_name]);
        });

        return redirect()->route('payroll.show', $payroll)->with('success', 'Salary entry saved.');
    }

    public function deleteEntry(Payroll $payroll, PayrollEntry $entry)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        abort_unless($entry->payroll_id === $payroll->id, 404);
        DB::transaction(function () use ($payroll, $entry) {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            abort_unless($locked->editable(), 409, 'This payroll is locked.');
            $saved = $locked->entries()->findOrFail($entry->id);
            $this->event($locked, 'entry_deleted', ['employee' => $saved->employee_name]);
            $saved->delete();
        });

        return back()->with('success', 'Salary entry removed.');
    }

    public function transition(Request $request, Payroll $payroll, string $action, PayrollDocuments $documents)
    {
        abort_unless(in_array($action, ['submit', 'approve', 'return', 'paid'], true), 404);
        $this->authorizeRole(in_array($action, ['approve', 'return'], true) ? Person::ROLE_FOUNDER : Person::ROLE_ACCOUNTANT);
        $data = match ($action) {
            'return' => $request->validate(['reason' => 'required|string|max:2000']),
            'paid' => $request->validate(['payment_reference' => 'required|string|max:255', 'payment_date' => 'required|date_format:Y-m-d|before_or_equal:today']),
            default => [],
        };
        DB::transaction(function () use ($payroll, $action, $data, $documents, $request) {
            $locked = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            if ($action === 'submit') {
                abort_unless($locked->editable() && $locked->entries()->exists(), 409, 'Only a non-empty draft or returned payroll can be submitted.');
                if ($locked->status === 'returned') {
                    $data = $request->validate(['response' => 'required|string|max:2000']);
                    $data['return_reason'] = $locked->return_reason;
                }
                $locked->fill(['status' => 'submitted', 'submitted_at' => now(), 'return_reason' => null]);
            } elseif ($action === 'return') {
                abort_unless($locked->status === 'submitted', 409);
                $locked->fill(['status' => 'returned', 'return_reason' => $data['reason']]);
            } elseif ($action === 'approve') {
                abort_unless($locked->status === 'submitted' && $locked->entries()->exists(), 409);
                abort_if($locked->created_by === backpack_user()->id, 403, 'The preparer cannot approve their own payroll.');
                $locked->fill(['status' => 'approved', 'approved_by' => backpack_user()->id, 'approved_at' => now(), 'approval_snapshot' => $documents->snapshot()]);
            } else {
                abort_unless($locked->status === 'approved', 409);
                abort_if($data['payment_date'] < $locked->approved_at->format('Y-m-d'), 422, 'Payment date cannot precede approval.');
                $locked->fill(['status' => 'paid', 'paid_at' => $data['payment_date'], 'payment_reference' => $data['payment_reference']]);
            }
            try {
                $locked->save();
            } catch (\Illuminate\Database\QueryException $exception) {
                logger()->error('Payroll save failed before rollback', [
                    'payroll_id' => $locked->id,
                    'action' => $action,
                    'error_info' => $exception->errorInfo,
                ]);

                throw $exception;
            }
            $this->event($locked, $action, $data);
        });

        return back()->with('success', 'Payroll action completed.');
    }

    public function bankDocument(Request $request, Payroll $payroll, PayrollDocuments $documents)
    {
        $this->authorizeRole();
        $pdf = $documents->bankLetter($payroll);
        $inline = $request->boolean('inline');
        $this->event($payroll, $inline ? 'bank_letter_viewed' : 'bank_letter_downloaded');

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="payroll-'.$payroll->year.'-'.$payroll->month.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    public function emailBank(Request $request, Payroll $payroll, PayrollDocuments $documents)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        abort_unless($payroll->approved(), 403);
        $recipient = $payroll->approval_snapshot['bank_email'] ?? null;
        abort_unless($recipient, 422, 'No bank email was configured at approval. Download the letter for physical delivery.');
        $transport = config('mail.mailers.'.config('mail.default').'.transport');
        abort_if(in_array($transport, ['log', 'array'], true), 422, 'Configure a delivering mail transport before sending payroll to the bank.');
        $request->validate(['confirm_recipient' => ['required', Rule::in([$recipient])]]);
        $pdf = $documents->bankLetter($payroll);
        $this->event($payroll, 'bank_email_requested', ['recipient' => $recipient]);
        try {
            Mail::to($recipient)->send(new PayrollBankInstruction($payroll, $pdf));
        } catch (\Throwable $exception) {
            $this->event($payroll, 'bank_email_failed', ['recipient' => $recipient]);
            report($exception);

            return back()->withErrors(['email' => 'The bank email could not be sent. Check mail configuration before retrying.']);
        }
        $this->event($payroll, 'bank_email_sent', ['recipient' => $recipient]);

        return back()->with('success', 'Bank email handed to the configured mail transport. Payroll is not marked paid.');
    }

    public function myPayslips()
    {
        $personId = backpack_user()?->person?->id;
        abort_unless($personId, 403);
        $entries = PayrollEntry::with('payroll')->where('person_id', $personId)
            ->whereHas('payroll', fn ($query) => $query->whereIn('status', ['approved', 'paid']))
            ->join('payrolls', 'payrolls.id', '=', 'payroll_entries.payroll_id')
            ->orderByDesc('payrolls.year')->orderByDesc('payrolls.month')->select('payroll_entries.*')->paginate(20);

        return view('payroll.mine', compact('entries'));
    }

    public function payslip(Request $request, PayrollEntry $entry, PayrollDocuments $documents)
    {
        $person = backpack_user()?->person;
        $role = $person?->sacRole?->code;
        abort_unless($person && ($entry->person_id === $person->id || in_array($role, [Person::ROLE_ACCOUNTANT, Person::ROLE_FOUNDER], true)), 403);
        $pdf = $documents->payslip($entry);
        $this->event($entry->payroll, 'payslip_downloaded', ['entry_id' => $entry->id]);
        $inline = $request->boolean('inline');

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="payslip-'.$entry->payroll->year.'-'.$entry->payroll->month.'-'.$entry->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
