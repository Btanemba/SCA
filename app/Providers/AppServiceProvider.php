<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\PaymentReceipt;
use App\Models\Payroll;
use App\Models\Person;
use App\Services\ParentDashboard;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(backpack_view('dashboard'), function ($view): void {
            $person = backpack_user()?->person;
            if ($person?->sacRole?->code === Person::ROLE_PARENT) {
                $view->with(app(ParentDashboard::class)->data($person));

                return;
            }
            if (in_array($person?->sacRole?->code, Person::ROLES_MANAGE_ACCOUNTS, true)) {
                $view->with('peopleTotals', Person::query()
                    ->join('sac_role', 'persons.sac_role_id', '=', 'sac_role.id')
                    ->whereIn('sac_role.code', [Person::ROLE_STUDENT, Person::ROLE_PARENT, Person::ROLE_STAFF, Person::ROLE_ADMIN])
                    ->select('sac_role.code')
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy('sac_role.code')
                    ->pluck('total', 'code'));

                $month = now();
                $previousMonth = $month->copy()->startOfMonth()->subMonth();
                $view->with([
                    'previousMonth' => $previousMonth,
                    'currentMonthFeeIncomeKobo' => PaymentReceipt::query()
                        ->whereYear('paid_at', $month->year)
                        ->whereMonth('paid_at', $month->month)
                        ->sum('amount_kobo'),
                ]);

                if ($person->sacRole->code === Person::ROLE_FOUNDER) {
                    $view->with([
                        'previousMonthExpenseKobo' => Expense::query()
                            ->where('status', 'paid')
                            ->whereYear('paid_at', $previousMonth->year)
                            ->whereMonth('paid_at', $previousMonth->month)
                            ->sum('amount_kobo'),
                        'previousMonthPayrollKobo' => \App\Models\PayrollEntry::query()
                            ->whereHas('payroll', fn ($query) => $query
                                ->where('year', $previousMonth->year)
                                ->where('month', $previousMonth->month))
                            ->sum('net_kobo'),
                    ]);
                }

                return;
            }

            if ($person?->sacRole?->code !== Person::ROLE_ACCOUNTANT) {
                return;
            }

            $month = now();
            $view->with([
                'currentMonthFeeIncomeKobo' => PaymentReceipt::query()
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)
                    ->sum('amount_kobo'),
                'paidExpenseKobo' => Expense::query()
                    ->where('status', 'paid')
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)
                    ->sum('amount_kobo'),
                'approvedExpenseKobo' => Expense::query()
                    ->where('status', 'approved')
                    ->sum('amount_kobo'),
                'submittedExpenseCount' => Expense::query()
                    ->where('status', 'submitted')
                    ->count(),
                'draftExpenseCount' => Expense::query()
                    ->where('status', 'draft')
                    ->count(),
                'returnedExpenseCount' => Expense::query()
                    ->where('status', 'returned')
                    ->count(),
                'attentionExpenses' => Expense::query()
                    ->whereIn('status', ['draft', 'returned'])
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(),
                'currentPayroll' => Payroll::query()
                    ->withSum('entries', 'net_kobo')
                    ->where('year', $month->year)
                    ->where('month', $month->month)
                    ->first(),
            ]);
        });
    }
}
