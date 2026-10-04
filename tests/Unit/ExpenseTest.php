<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Services\PayrollAmounts;
use PHPUnit\Framework\TestCase;

class ExpenseTest extends TestCase
{
    public function test_only_drafts_and_returned_expenses_are_editable(): void
    {
        foreach (['draft', 'returned', 'submitted', 'approved', 'paid'] as $status) {
            $expense = new Expense(['status' => $status]);
            $this->assertSame(in_array($status, ['draft', 'returned'], true), $expense->editable());
            $this->assertSame(in_array($status, ['approved', 'paid'], true), $expense->approved());
        }
    }

    public function test_expense_amounts_use_exact_integer_kobo(): void
    {
        $this->assertSame(10000001, PayrollAmounts::kobo('100000.01'));
        $this->assertSame(99999999999, PayrollAmounts::kobo('999999999.99'));
    }
}
