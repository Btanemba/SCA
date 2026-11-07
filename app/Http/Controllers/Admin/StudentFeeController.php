<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademyFee;
use App\Models\FeePayment;
use App\Models\PaymentReceipt;
use App\Models\Person;
use App\Models\StudentFeeCharge;
use App\Models\StudentWalletTransaction;
use App\Services\AcademyLetterhead;
use App\Services\PayrollAmounts;
use App\Services\ParentChildAccess;
use Barryvdh\DomPDF\Facade\Pdf;
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

    private function availableFees()
    {
        return AcademyFee::query()
            ->whereNotNull('amount');
    }

    private function feeAlreadyCharged(AcademyFee $fee, string $billingMonth, $charges): bool
    {
        $schedule = strtolower(trim((string) $fee->schedule));
        if (preg_match('/\bper[\s-]+(session|day|hour|visit|pickup|occurrence)\b/', $schedule)) {
            return false;
        }

        $oneTime = preg_match('/\bonce\b|\bone[\s-]*(time|off)\b/', $schedule);
        $yearly = preg_match('/year|annual/', $schedule);

        return $charges->contains(function ($charge) use ($fee, $billingMonth, $oneTime, $yearly) {
            if (strtolower(trim($charge->fee_name)) !== strtolower(trim($fee->fee_name))) {
                return false;
            }
            if ($oneTime) {
                return true;
            }
            $chargeMonth = $charge->billing_month->format('Y-m-d');

            return $yearly
                ? substr($chargeMonth, 0, 4) === substr($billingMonth, 0, 4)
                : $chargeMonth === $billingMonth;
        });
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
        $fees = $this->availableFees()->orderBy('sort_order')->orderBy('fee_name')->get();

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
            ->get(['id', 'student_id', 'first_name', 'last_name', 'image_path']);

        $allCharges = StudentFeeCharge::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->get(['student_id', 'fee_name', 'billing_month'])
            ->groupBy('student_id');

        $filteredStudents = collect();
        foreach ($students as $student) {
            $studentCharges = $monthlyCharges->get($student->id, collect());
            $amountDue = (int) $studentCharges->sum('amount_due_kobo');
            $amountPaid = (int) $studentCharges->sum(fn ($charge) => $charge->paid_amount_kobo);
            $status = $studentCharges->isEmpty()
                ? 'not_charged'
                : ($amountPaid >= $amountDue ? 'paid' : ($amountPaid > 0 ? 'part_paid' : 'unpaid'));

            $student->setRelation('monthCharges', $studentCharges);
            $studentChargeHistory = $allCharges->get($student->id, collect());
            $availableFees = $fees->filter(fn ($fee) => ! $this->feeAlreadyCharged($fee, $billingMonth, $studentChargeHistory))->values();
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

        $walletTransactions = StudentWalletTransaction::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->with(['receipt.payments', 'feePayment.charge'])
            ->orderByDesc('id')
            ->get()
            ->groupBy('student_id');
        $walletBalances = [];
        foreach ($students as $student) {
            $transactions = $walletTransactions->get($student->id, collect());
            $walletBalances[$student->id] = (int) $transactions->sum(fn ($transaction) =>
                $transaction->type === 'credit' ? $transaction->amount_kobo : -$transaction->amount_kobo
            );
            $student->setRelation('walletTransactions', $transactions);
            $student->setAttribute('wallet_balance_kobo', $walletBalances[$student->id]);
        }

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
            'walletBalances' => $walletBalances,
        ]);
    }

    public function invoice(Request $request, Person $student, AcademyLetterhead $letterhead, ParentChildAccess $access)
    {
        if (backpack_user()?->person?->sacRole?->code === Person::ROLE_PARENT) {
            $access->child($student->id);
        } else {
            $this->authorizeRole();
        }
        abort_unless($student->sacRole?->code === Person::ROLE_STUDENT, 404);
        $data = $request->validate(['month' => 'required|date_format:Y-m']);
        $charges = StudentFeeCharge::query()
            ->where('student_id', $student->id)
            ->whereDate('billing_month', $data['month'].'-01')
            ->withSum('payments', 'amount_kobo')
            ->orderBy('id')
            ->get();
        abort_if($charges->isEmpty(), 404);

        $wallet = StudentWalletTransaction::query()->where('student_id', $student->id);
        $walletBalance = (int) (clone $wallet)->where('type', 'credit')->sum('amount_kobo')
            - (int) (clone $wallet)->where('type', 'debit')->sum('amount_kobo');
        $reference = 'INV-'.str_replace('-', '', $data['month']).'-'.$student->id;

        return Pdf::loadView('student-fees.invoice', [
            'academy' => $letterhead->details(),
            'paymentDetails' => $letterhead->feePaymentDetails(),
            'student' => $student,
            'charges' => $charges,
            'month' => $data['month'],
            'reference' => $reference,
            'walletBalance' => $walletBalance,
            'generatedAt' => now(),
        ])->setPaper('a4')->setOption('isRemoteEnabled', false)
            ->download($reference.'.pdf')
            ->header('Cache-Control', 'no-store, private');
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

        $fee = $this->availableFees()->find($data['academy_fee_id']);
        if (! $fee) {
            throw ValidationException::withMessages(['academy_fee_id' => 'Choose a fee with an amount.']);
        }

        $billingMonth = $data['billing_month'].'-01';
        DB::transaction(function () use ($student, $fee, $billingMonth) {
            Person::query()->lockForUpdate()->findOrFail($student->id);
            $charges = StudentFeeCharge::query()->where('student_id', $student->id)->get(['fee_name', 'billing_month']);
            if ($this->feeAlreadyCharged($fee, $billingMonth, $charges)) {
                throw ValidationException::withMessages(['academy_fee_id' => 'This fee has already been recorded for the applicable period.']);
            }

            StudentFeeCharge::create([
                'student_id' => $student->id,
                'academy_fee_id' => $fee->id,
                'billing_month' => $billingMonth,
                'fee_name' => $fee->fee_name,
                'amount_due_kobo' => (int) $fee->amount * 100,
                'created_by' => backpack_user()->id,
            ]);
        });

        return redirect()->route('student-fees.index', ['month' => $data['billing_month']])->with('success', 'Fee charge recorded.');
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
        $fees = $this->availableFees()->whereIn('id', $feeIds)->get()->keyBy('id');
        if ($fees->count() !== count($feeIds)) {
            throw ValidationException::withMessages(['fees' => 'One or more selected fees are not configured with an amount.']);
        }

        $billingMonth = $data['billing_month'].'-01';
        [$createdCount, $createdStudentCount] = DB::transaction(function () use ($selections, $studentIds, $fees, $billingMonth) {
            Person::query()->whereIn('id', $studentIds)->orderBy('id')->lockForUpdate()->get(['id']);
            $existingCharges = StudentFeeCharge::query()
                ->whereIn('student_id', $studentIds)
                ->get(['student_id', 'fee_name', 'billing_month'])
                ->groupBy('student_id');

            $timestamp = now();
            $rows = [];
            $createdStudentIds = [];
            foreach ($selections as $studentId => $feeId) {
                $fee = $fees->get($feeId);
                if ($this->feeAlreadyCharged($fee, $billingMonth, $existingCharges->get($studentId, collect()))) {
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
            }

            $created = 0;
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('student_fee_charges')->insert($chunk);
                $created += count($chunk);
            }

            return [$created, count($createdStudentIds)];
        });

        $message = $createdCount > 0
            ? 'Added the selected fee for '.$createdStudentCount.' student'.($createdStudentCount === 1 ? '' : 's').' for '.date('F Y', strtotime($billingMonth)).'.'
            : 'No charges added. The selected fees are already recorded for the applicable period.';

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

        $creditedKobo = DB::transaction(function () use ($charge, $data, $amountKobo) {
            Person::query()->lockForUpdate()->findOrFail($charge->student_id);
            $lockedCharge = StudentFeeCharge::query()->lockForUpdate()->findOrFail($charge->id);
            $paidAmount = (int) $lockedCharge->payments()->sum('amount_kobo');
            $balance = $lockedCharge->amount_due_kobo - $paidAmount;
            if ($balance <= 0) {
                throw ValidationException::withMessages(['amount' => 'This fee has already been paid.']);
            }

            $receipt = PaymentReceipt::create([
                'student_id' => $lockedCharge->student_id,
                'amount_kobo' => $amountKobo,
                'paid_at' => $data['paid_at'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'received_by' => backpack_user()->id,
            ]);

            FeePayment::create([
                'student_fee_charge_id' => $lockedCharge->id,
                'payment_receipt_id' => $receipt->id,
                'amount_kobo' => min($amountKobo, $balance),
                'paid_at' => $data['paid_at'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'received_by' => backpack_user()->id,
            ]);

            if ($amountKobo > $balance) {
                StudentWalletTransaction::create([
                    'student_id' => $lockedCharge->student_id,
                    'type' => 'credit',
                    'amount_kobo' => $amountKobo - $balance,
                    'payment_receipt_id' => $receipt->id,
                    'created_by' => backpack_user()->id,
                ]);
            }

            return max(0, $amountKobo - $balance);
        });

        $message = $creditedKobo > 0
            ? 'Payment recorded. NGN '.number_format($creditedKobo / 100, 2).' added to the student wallet.'
            : 'Payment recorded.';

        return redirect()->route('student-fees.index', ['month' => substr((string) $charge->getRawOriginal('billing_month'), 0, 7)])->with('success', $message);
    }

    public function applyWallet(Request $request, StudentFeeCharge $charge)
    {
        $this->authorizeRole();
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/D'],
        ]);
        $amountKobo = PayrollAmounts::kobo((string) $data['amount']);
        if ($amountKobo <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount greater than zero.']);
        }

        DB::transaction(function () use ($charge, $amountKobo) {
            Person::query()->lockForUpdate()->findOrFail($charge->student_id);
            $lockedCharge = StudentFeeCharge::query()->lockForUpdate()->findOrFail($charge->id);
            $balance = $lockedCharge->amount_due_kobo - (int) $lockedCharge->payments()->sum('amount_kobo');
            $wallet = StudentWalletTransaction::query()->where('student_id', $lockedCharge->student_id);
            $walletBalance = (int) (clone $wallet)->where('type', 'credit')->sum('amount_kobo')
                - (int) (clone $wallet)->where('type', 'debit')->sum('amount_kobo');

            if ($amountKobo > $balance) {
                throw ValidationException::withMessages(['amount' => 'Amount cannot be greater than the outstanding fee balance.']);
            }
            if ($amountKobo > $walletBalance) {
                throw ValidationException::withMessages(['amount' => 'Amount cannot be greater than the wallet balance.']);
            }

            $payment = FeePayment::create([
                'student_fee_charge_id' => $lockedCharge->id,
                'amount_kobo' => $amountKobo,
                'paid_at' => now()->toDateString(),
                'payment_method' => 'wallet',
                'received_by' => backpack_user()->id,
            ]);
            StudentWalletTransaction::create([
                'student_id' => $lockedCharge->student_id,
                'type' => 'debit',
                'amount_kobo' => $amountKobo,
                'fee_payment_id' => $payment->id,
                'created_by' => backpack_user()->id,
            ]);
        });

        return redirect()->route('student-fees.index', ['month' => substr((string) $charge->getRawOriginal('billing_month'), 0, 7)])->with('success', 'Wallet credit applied.');
    }
}
