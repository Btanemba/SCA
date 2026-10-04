<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Person;
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
            if ($person?->sacRole?->code !== Person::ROLE_ACCOUNTANT) {
                return;
            }

            $month = now();
            $view->with([
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
