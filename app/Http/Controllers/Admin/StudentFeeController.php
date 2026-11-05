<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademyFee;
use App\Models\FeePayment;
use App\Models\Person;
use App\Models\StudentFeeCharge;
use App\Services\PayrollAmounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentFeeController extends Controller
{
    private function authorizeRole(): void
    {
        $role = backpack_user()?->person?->sacRole?->code;
        abort_unless(in_array($role, [Person::ROLE_ACCOUNTANT, Person::ROLE_ADMIN, Person::ROLE_FOUNDER], true), 403);
    }

    private function monthlyFees()
    {
        return AcademyFee::query()
            ->whereNotNull('amount')
            ->whereRaw('LOWER(schedule) LIKE ?', ['%month%']);
    }

    public function index(Request $request)
    {
        $this->authorizeRole();
        $filters = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'status' => ['nullable', Rule::in(['not_charged', 'unpaid', 'part_paid', 'paid'])],
            'search' => 'nullable|string|max:255',
        ]);
        $month = $filters['month'] ?? now()->format('Y-m');
        $billingMonth = $month.'-01';
        $search = trim($filters['search'] ?? '');
        $fees = $this->monthlyFees()->orderBy('sort_order')->orderBy('fee_name')->get();

        $monthlyCharges = StudentFeeCharge::query()
            ->whereDate('billing_month', $billingMonth)
            ->with(['fee', 'payments' => fn ($query) => $query->orderByDesc('paid_at')])
            ->withSum('payments', 'amount_kobo')
            ->get()
            ->groupBy('student_id');

        $students = Person::query()
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'student_id', 'first_name', 'last_name']);

        $filteredStudents = collect();
        foreach ($students as $student) {
            $studentCharges = $monthlyCharges->get($student->id, collect());
            $amountDue = (int) $studentCharges->sum('amount_due_kobo');
            $amountPaid = (int) $studentCharges->sum(fn ($charge) => $charge->paid_amount_kobo);
            $status = $studentCharges->isEmpty()
                ? 'not_charged'
                : ($amountPaid >= $amountDue ? 'paid' : ($amountPaid > 0 ? 'part_paid' : 'unpaid'));

            $student->setRelation('monthCharges', $studentCharges);
            $availableFees = $fees->filter(function ($fee) use ($studentCharges) {
                foreach ($studentCharges as $charge) {
                    if (strtolower(trim($charge->fee_name)) === strtolower(trim($fee->fee_name))) {
                        return false;
                    }
                }

                return true;
            })->values();
            $student->setRelation('availableMonthFees', $availableFees);
            $student->setAttribute('month_due_kobo', $amountDue);
            $student->setAttribute('month_paid_kobo', $amountPaid);
            $student->setAttribute('month_balance_kobo', max(0, $amountDue - $amountPaid));
            $student->setAttribute('month_payment_status', $status);

            if (! empty($filters['status']) && $status !== $filters['status']) {
                continue;
            }

            if ($search !== '') {
                $matchesSearch = stripos($student->full_name, $search) !== false
                    || stripos((string) $student->student_id, $search) !== false
                    || $studentCharges->contains(fn ($charge) => stripos($charge->fee_name, $search) !== false);
                if (! $matchesSearch) {
                    continue;
                }
            }

            $filteredStudents->push($student);
        }
        $students = $filteredStudents;

        $charges = StudentFeeCharge::query()
            ->with(['child', 'fee', 'payments' => fn ($query) => $query->orderByDesc('paid_at')])
            ->withSum('payments', 'amount_kobo')
            ->whereDate('billing_month', $billingMonth)
            ->whereIn('student_id', $students->pluck('id'))
            ->orderBy('student_id')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('student-fees.index', [
            'charges' => $charges,
            'students' => $students,
            'fees' => $fees,
            'month' => $month,
        ]);
    }

    public function createCharge(Request $request)
    {
        $this->authorizeRole();
        $data = $request->validate([
            'student_id' => 'required|integer|exists:persons,id',
            'academy_fee_id' => 'required|integer|exists:academy_fees,id',
            'billing_month' => 'required|date_format:Y-m',
        ]);

        $student = Person::query()
            ->whereKey($data['student_id'])
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->first();
        if (! $student) {
            throw ValidationException::withMessages(['student_id' => 'Choose a student.']);
        }

        $fee = $this->monthlyFees()->find($data['academy_fee_id']);
        if (! $fee) {
            throw ValidationException::withMessages(['academy_fee_id' => 'Choose a monthly fee with an amount.']);
        }

        $billingMonth = $data['billing_month'].'-01';
        $duplicate = StudentFeeCharge::query()
            ->where('student_id', $student->id)
            ->whereDate('billing_month', $billingMonth)
            ->whereRaw('LOWER(TRIM(fee_name)) = ?', [strtolower(trim($fee->fee_name))])
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['academy_fee_id' => 'This child already has this fee recorded for that month.']);
        }

        StudentFeeCharge::create([
            'student_id' => $student->id,
            'academy_fee_id' => $fee->id,
            'billing_month' => $billingMonth,
            'fee_name' => $fee->fee_name,
            'amount_due_kobo' => (int) $fee->amount * 100,
            'created_by' => backpack_user()->id,
        ]);

        return redirect()->route('student-fees.index', ['month' => $data['billing_month']])->with('success', 'Monthly fee charge recorded.');
    }

    public function createChargesForAll(Request $request)
    {
        $this->authorizeRole();
        $data = $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'fees' => 'required|array|min:1',
            'fees.*' => 'required|integer',
        ]);

        $selections = [];
        foreach ($data['fees'] as $studentId => $feeId) {
            if (! ctype_digit((string) $studentId) || (int) $studentId < 1) {
                throw ValidationException::withMessages(['fees' => 'Choose a fee from a student row.']);
            }
            $selections[(int) $studentId] = (int) $feeId;
        }

        $studentIds = array_keys($selections);
        $validStudentIds = Person::query()
            ->whereIn('id', $studentIds)
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if (count($validStudentIds) !== count($studentIds)) {
            throw ValidationException::withMessages(['fees' => 'One or more selected records are not students.']);
        }

        $feeIds = array_values(array_unique(array_values($selections)));
        $fees = $this->monthlyFees()->whereIn('id', $feeIds)->get()->keyBy('id');
        if ($fees->count() !== count($feeIds)) {
            throw ValidationException::withMessages(['fees' => 'One or more selected fees are not configured monthly fees.']);
        }

        $billingMonth = $data['billing_month'].'-01';
        [$createdCount, $createdStudentCount] = DB::transaction(function () use ($selections, $studentIds, $fees, $billingMonth) {
            $existingCharges = StudentFeeCharge::query()
                ->whereIn('student_id', $studentIds)
                ->whereDate('billing_month', $billingMonth)
                ->get(['student_id', 'fee_name']);
            $existingKeys = [];
            foreach ($existingCharges as $existingCharge) {
                $existingKeys[$existingCharge->student_id.':'.strtolower(trim($existingCharge->fee_name))] = true;
            }

            $timestamp = now();
            $rows = [];
            $createdStudentIds = [];
            foreach ($selections as $studentId => $feeId) {
                $fee = $fees->get($feeId);
                $key = $studentId.':'.strtolower(trim($fee->fee_name));
                if (isset($existingKeys[$key])) {
                    continue;
                }

                $rows[] = [
                    'student_id' => $studentId,
                    'academy_fee_id' => $feeId,
                    'billing_month' => $billingMonth,
                    'fee_name' => $fee->fee_name,
                    'amount_due_kobo' => (int) $fee->amount * 100,
                    'created_by' => backpack_user()->id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $createdStudentIds[$studentId] = true;
                $existingKeys[$key] = true;
            }

            $created = 0;
            foreach (array_chunk($rows, 500) as $chunk) {
                $created += DB::table('student_fee_charges')->insertOrIgnore($chunk);
            }

            return [$created, count($createdStudentIds)];
        });

        $message = $createdCount > 0
            ? 'Added the selected monthly fee for '.$createdStudentCount.' student'.($createdStudentCount === 1 ? '' : 's').' for '.date('F Y', strtotime($billingMonth)).'.'
            : 'No charges added. The selected fees are already recorded for those students and that month.';

        return redirect()->route('student-fees.index', ['month' => $data['billing_month']])->with('success', $message);
    }

    public function recordPayment(Request $request, StudentFeeCharge $charge)
    {
        $this->authorizeRole();
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/D'],
            'paid_at' => 'required|date_format:Y-m-d',
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'pos', 'other'])],
            'reference' => 'nullable|string|max:255',
        ]);
        $amountKobo = PayrollAmounts::kobo((string) $data['amount']);
        if ($amountKobo <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount greater than zero.']);
        }

        DB::transaction(function () use ($charge, $data, $amountKobo) {
            $lockedCharge = StudentFeeCharge::query()->lockForUpdate()->findOrFail($charge->id);
            $paidAmount = (int) $lockedCharge->payments()->sum('amount_kobo');
            $balance = $lockedCharge->amount_due_kobo - $paidAmount;
            if ($amountKobo > $balance) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot be greater than the outstanding balance.']);
            }

            FeePayment::create([
                'student_fee_charge_id' => $lockedCharge->id,
                'amount_kobo' => $amountKobo,
                'paid_at' => $data['paid_at'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?: null,
                'received_by' => backpack_user()->id,
            ]);
        });

        return redirect()->route('student-fees.index', ['month' => substr((string) $charge->getRawOriginal('billing_month'), 0, 7)])->with('success', 'Payment recorded.');
    }
}
