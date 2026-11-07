<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Person;
use App\Models\StudentWalletTransaction;

class ParentDashboard
{
    public function data(Person $parent): array
    {
        $currentMonth = now()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        $today = now('Africa/Lagos')->toDateString();
        $children = $parent->children()
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->with([
                'attendances' => fn ($query) => $query->whereDate('attendance_date', $today),
                'absenceNotices' => fn ($query) => $query->whereNull('cancelled_at')->where('absence_date', '>=', $today)->orderBy('absence_date'),
                'studentFeeCharges' => fn ($query) => $query
                    ->whereIn('billing_month', [$currentMonth->toDateString(), $nextMonth->toDateString()])
                    ->withSum('payments', 'amount_kobo'),
            ])
            ->orderBy('persons.first_name')->orderBy('persons.last_name')
            ->get();

        $wallets = StudentWalletTransaction::query()
            ->whereIn('student_id', $children->pluck('id'))
            ->selectRaw("student_id, SUM(CASE WHEN type = 'credit' THEN amount_kobo ELSE 0 END) as credit_kobo, SUM(CASE WHEN type = 'debit' THEN amount_kobo ELSE 0 END) as debit_kobo")
            ->groupBy('student_id')->get()->keyBy('student_id');

        foreach ($children as $child) {
            $currentCharges = $child->studentFeeCharges->filter(fn ($charge) => $charge->billing_month->format('Y-m') === $currentMonth->format('Y-m'));
            $nextCharges = $child->studentFeeCharges->filter(fn ($charge) => $charge->billing_month->format('Y-m') === $nextMonth->format('Y-m'));
            $child->setAttribute('current_fee_balance_kobo', (int) $currentCharges->sum(fn ($charge) => $charge->balance_kobo));
            $child->setAttribute('next_fee_balance_kobo', (int) $nextCharges->sum(fn ($charge) => $charge->balance_kobo));
            $child->setAttribute('has_current_fees', $currentCharges->isNotEmpty());
            $child->setAttribute('has_next_fees', $nextCharges->isNotEmpty());
            $wallet = $wallets->get($child->id);
            $child->setAttribute('wallet_balance_kobo', (int) ($wallet?->credit_kobo ?? 0) - (int) ($wallet?->debit_kobo ?? 0));
        }

        return [
            'parentChildren' => $children,
            'parentCurrentMonth' => $currentMonth,
            'parentNextMonth' => $nextMonth,
            'parentToday' => $today,
            'parentEvents' => Event::query()->where('is_published', true)
                ->where('starts_at', '>=', now())->orderBy('starts_at')->limit(4)->get(),
        ];
    }
}
