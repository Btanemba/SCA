<?php

namespace Tests\Feature;

use App\Models\AcademySetting;
use App\Models\Expense;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private User $ceo;
    private User $staff;
    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake('local');
        $this->accountant = $this->employee(Person::ROLE_ACCOUNTANT, 'Accountant');
        $this->ceo = $this->employee(Person::ROLE_FOUNDER, 'Chief');
        $this->staff = $this->employee(Person::ROLE_STAFF, 'Teacher');
    }

    private function employee(string $code, string $name): User
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $user = User::factory()->create(['name' => $name, 'email_verified_at' => now()]);
        Person::create(['user_id' => $user->id, 'sac_role_id' => $role->id, 'first_name' => $name, 'last_name' => 'Employee']);

        return $user;
    }

    private function login(User $user): static
    {
        return $this->actingAs($user, config('backpack.base.guard'));
    }

    private function data(): array
    {
        return [
            'title' => 'Classroom supplies', 'category' => 'Supplies', 'description' => 'Exercise books and pencils',
            'expense_date' => now()->format('Y-m-d'), 'payee' => 'School Supplier', 'amount' => '100000.01',
            'bank_name' => 'Supplier Bank', 'account_name' => 'School Supplier Ltd', 'account_number' => '0012345678',
        ];
    }

    private function draft(array $overrides = []): Expense
    {
        $this->login($this->accountant)->post(route('expenses.store'), array_replace($this->data(), $overrides))->assertRedirect();

        return Expense::latest('id')->firstOrFail();
    }

    private function settings(): AcademySetting
    {
        $settings = new AcademySetting;
        $settings->id = 1;
        $settings->fill([
            'name' => 'Springcare Academy', 'address' => 'School Road, Nigeria', 'phone' => '08000000000',
            'email' => 'academy@example.com', 'ceo_name' => 'Chief Executive',
            'logo_path' => UploadedFile::fake()->image('logo.jpg')->store('academy', 'local'),
            'signature_path' => UploadedFile::fake()->image('signature.jpg')->store('academy', 'local'),
            'bank_name' => 'School Bank', 'bank_address' => 'Bank Road', 'debit_account_name' => 'Springcare Academy',
            'debit_account_number' => '0123456789',
        ])->save();

        return $settings;
    }

    private function approvedExpense(): Expense
    {
        $this->settings();
        $expense = $this->draft();
        $this->post(route('expenses.action', [$expense, 'submit']))->assertRedirect();
        $this->login($this->ceo)->post(route('expenses.action', [$expense, 'approve']))->assertRedirect();

        return $expense->fresh();
    }

    public function test_accountant_creates_exact_amount_with_optional_private_attachments(): void
    {
        $expense = $this->draft([
            'invoice' => UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf'),
            'receipt' => UploadedFile::fake()->image('receipt.png'),
            'status' => 'paid', 'approved_by' => $this->accountant->id,
        ]);
        $this->assertSame(10000001, $expense->amount_kobo);
        $this->assertSame('draft', $expense->status);
        $this->assertNull($expense->approved_by);
        $this->disk->assertExists([$expense->invoice_path, $expense->receipt_path]);
        $this->assertStringStartsWith('SCA/EXP/', $expense->reference);
        $this->get(route('expenses.attachment', [$expense, 'invoice']))->assertDownload();
        $this->get(route('expenses.attachment', [$expense, 'receipt']))->assertDownload();
        $this->get(route('expenses.attachment', [$expense, 'signature']))->assertNotFound();
        $this->assertSame(['created', 'invoice_downloaded', 'receipt_downloaded'], $expense->events()->pluck('action')->all());
    }

    public function test_only_accountant_creates_and_only_finance_roles_can_access_expenses(): void
    {
        $expense = $this->draft();
        foreach ([Person::ROLE_ADMIN, Person::ROLE_SECURITY, Person::ROLE_STAFF] as $role) {
            $user = $role === Person::ROLE_STAFF ? $this->staff : $this->employee($role, $role);
            $this->login($user)->get(route('expenses.index'))->assertForbidden();
            $this->get(route('expenses.show', $expense))->assertForbidden();
            $this->post(route('expenses.store'), $this->data())->assertForbidden();
            $this->get(route('expenses.attachment', [$expense, 'invoice']))->assertForbidden();
            $this->get(route('expenses.bank', $expense))->assertForbidden();
        }
        $this->login($this->ceo)->post(route('expenses.store'), $this->data())->assertForbidden();
        $this->get(route('expenses.create'))->assertForbidden();
        $this->put(route('expenses.update', $expense), $this->data())->assertForbidden();
    }

    public function test_sidebar_logout_ends_the_session_and_redirects_to_the_school_homepage(): void
    {
        $this->login($this->accountant)
            ->get(route('backpack.logout.home'))
            ->assertRedirect(url('/'));
        $this->assertGuest(config('backpack.base.guard'));
    }

    public function test_expense_pages_and_reference_filters_render(): void
    {
        $expense = $this->draft();
        $other = $this->draft(['title' => 'Bus repair', 'category' => 'Maintenance']);
        $this->get(route('expenses.index'))->assertOk()->assertSee('Classroom supplies')->assertSee('Bus repair')->assertSee('Draft - Not Submitted');
        $this->get(route('expenses.create'))->assertOk();
        $this->get(route('expenses.edit', $expense))->assertOk()->assertSee('100000.01');
        $this->get(route('expenses.show', $expense))->assertOk()->assertSee('Submit for Approval')->assertSee('Draft - Not Submitted')->assertDontSee('Bank Payment Instruction');
        $this->get(route('expenses.index', ['search' => $expense->reference]))->assertOk()->assertSee($expense->title)->assertDontSee($other->title);
        $this->get(route('expenses.index', ['status' => 'submitted']))->assertOk()->assertDontSee($expense->title);
        $this->login($this->ceo)->get(route('expenses.show', $expense))->assertOk()->assertDontSee('Edit Expense');
        foreach ([
            'draft' => ['Draft - Not Submitted', '#374151', '#e5e7eb'],
            'returned' => ['Returned - Needs Changes', '#8a2c0d', '#fff0d6'],
            'submitted' => ['Awaiting Approval', '#075985', '#e0f2fe'],
            'approved' => ['Approved - Awaiting Payment', '#166534', '#dcfce7'],
            'paid' => ['Paid', '#115e59', '#ccfbf1'],
        ] as $status => [$label, $foreground, $background]) {
            $this->view('expenses.status', ['status' => $status])
                ->assertSee($label)
                ->assertSee('color: '.$foreground, false)
                ->assertSee('background-color: '.$background, false)
                ->assertDontSee('class="badge', false);
        }
    }

    public function test_accountant_dashboard_shows_finance_summary(): void
    {
        $expense = $this->draft();
        $expense->update(['status' => 'paid', 'paid_at' => now()->toDateString()]);

        $this->get(backpack_url('dashboard'))
            ->assertOk()
            ->assertSee('Finance overview')
            ->assertSee('Paid expenses this month')
            ->assertSee('NGN 100,000.01')
            ->assertSee('Expense follow-up');
    }

    public function test_paid_expense_list_can_be_filtered_by_payment_month(): void
    {
        $thisMonth = $this->draft(['title' => 'Paid this month']);
        $thisMonth->update(['status' => 'paid', 'paid_at' => now()->toDateString()]);
        $lastMonth = $this->draft(['title' => 'Paid last month']);
        $lastMonth->update(['status' => 'paid', 'paid_at' => now()->subMonth()->toDateString()]);

        $this->get(route('expenses.index', ['status' => 'paid', 'paid_month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Paid this month')
            ->assertDontSee('Paid last month');

        $this->get(route('expenses.index', ['paid_month' => '10-2026']))
            ->assertSessionHasErrors('paid_month');
    }

    public function test_invalid_amounts_and_documents_are_rejected(): void
    {
        $this->login($this->accountant);
        foreach (['0', '-1', '1.001', '1000000000', '1e2'] as $amount) {
            $this->post(route('expenses.store'), array_replace($this->data(), ['amount' => $amount]))->assertSessionHasErrors('amount');
        }
        $this->post(route('expenses.store'), array_replace($this->data(), ['account_number' => '123']))->assertSessionHasErrors('account_number');
        $this->post(route('expenses.store'), array_replace($this->data(), ['invoice' => UploadedFile::fake()->create('bad.html', 1, 'text/html')]))->assertSessionHasErrors('invoice');
        $this->post(route('expenses.store'), array_replace($this->data(), ['receipt' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')]))->assertSessionHasErrors('receipt');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_draft_without_bank_details_can_be_saved_but_not_submitted(): void
    {
        $expense = $this->draft(['bank_name' => null, 'account_name' => null, 'account_number' => null]);
        $this->post(route('expenses.action', [$expense, 'submit']))->assertSessionHasErrors('bank_name');
        $this->assertSame('draft', $expense->fresh()->status);
        $this->get(route('expenses.attachment', [$expense, 'invoice']))->assertNotFound();
    }

    public function test_submitted_expense_is_locked_until_returned_and_resubmitted(): void
    {
        $expense = $this->draft();
        $this->post(route('expenses.action', [$expense, 'submit']))->assertRedirect();
        $this->put(route('expenses.update', $expense), $this->data())->assertStatus(409);
        $this->get(route('expenses.edit', $expense))->assertStatus(409);
        $this->delete(route('expenses.destroy', $expense))->assertStatus(409);
        $this->post(route('expenses.action', [$expense, 'submit']))->assertStatus(409);
        $this->post(route('expenses.action', [$expense, 'approve']))->assertForbidden();
        $this->post(route('expenses.receipt', $expense), ['receipt' => UploadedFile::fake()->image('receipt.png')])->assertStatus(409);
        $this->assertEmpty(Storage::disk('local')->allFiles('expenses'));
        $this->login($this->ceo)->get(route('expenses.show', $expense))->assertOk()->assertSee('Approve Expense');
        $this->post(route('expenses.action', [$expense, 'return']))->assertSessionHasErrors('reason');
        $this->post(route('expenses.action', [$expense, 'return']), ['reason' => 'Correct quantity'])->assertRedirect();
        $this->login($this->accountant)->put(route('expenses.update', $expense), array_replace($this->data(), ['amount' => '120000.02']))->assertRedirect();
        $this->get(route('expenses.show', $expense))->assertOk()->assertSee('Response to Return')->assertSee('Respond &amp; Resubmit', false);
        $this->delete(route('expenses.destroy', $expense))->assertStatus(409);
        $this->post(route('expenses.action', [$expense, 'submit']))->assertSessionHasErrors('response');
        $this->post(route('expenses.action', [$expense, 'submit']), ['response' => str_repeat('x', 2001)])->assertSessionHasErrors('response');
        $this->assertSame('returned', $expense->fresh()->status);
        $this->assertSame('Correct quantity', $expense->fresh()->return_reason);
        $this->assertSame(1, $expense->events()->where('action', 'submit')->count());
        $this->login($this->ceo)->post(route('expenses.action', [$expense, 'submit']), ['response' => 'Corrected quantity'])->assertForbidden();
        $this->login($this->accountant)->post(route('expenses.action', [$expense, 'submit']), ['response' => 'Corrected quantity'])->assertRedirect();
        $this->assertNull($expense->fresh()->return_reason);
        $this->assertSame(12000002, $expense->fresh()->amount_kobo);
        $this->assertSame(['response' => 'Corrected quantity', 'return_reason' => 'Correct quantity'], $expense->events()->where('action', 'submit')->latest('id')->first()->details);
        $this->login($this->ceo)->get(route('expenses.show', $expense))->assertOk()->assertSee('Accountant Response')->assertSee('Corrected quantity')->assertSee('Correct quantity')->assertDontSee('name="response"', false);
        $this->post(route('expenses.action', [$expense, 'return']), ['reason' => 'Explain delivery cost'])->assertRedirect();
        $this->login($this->accountant)->get(route('expenses.show', $expense))->assertOk()->assertSee('Response to Return')->assertDontSee('Accountant Response');
        $this->post(route('expenses.action', [$expense, 'submit']), ['response' => 'Includes transport <b>charges</b>'])->assertRedirect();
        $this->login($this->ceo)->get(route('expenses.show', $expense))->assertOk()->assertSee('Includes transport &lt;b&gt;charges&lt;/b&gt;', false)->assertDontSee('Includes transport <b>charges</b>', false);
        $this->assertSame('Corrected quantity', $expense->events()->where('action', 'submit')->orderBy('id')->get()[1]->details['response']);
        $this->assertSame(['response' => 'Includes transport <b>charges</b>', 'return_reason' => 'Explain delivery cost'], $expense->events()->where('action', 'submit')->latest('id')->first()->details);
    }

    public function test_approval_requires_settings_and_cannot_be_self_approved(): void
    {
        $expense = $this->draft();
        $this->login($this->ceo)->post(route('expenses.action', [$expense, 'approve']))->assertStatus(409);
        $this->login($this->accountant)->post(route('expenses.action', [$expense, 'submit']))->assertRedirect();
        $this->login($this->ceo)->post(route('expenses.action', [$expense, 'approve']))->assertSessionHasErrors('settings');
        $this->assertSame('submitted', $expense->fresh()->status);
        $expense->update(['created_by' => $this->ceo->id]);
        $this->post(route('expenses.action', [$expense, 'approve']))->assertForbidden();
    }

    public function test_approval_block_places_signature_and_name_below_ceo_title(): void
    {
        $document = new Expense(['id' => 1, 'created_at' => now(), 'approved_at' => now()]);
        $html = view('payroll.documents.approval', [
            'payroll' => $document,
            'academy' => ['signature' => 'signature.png', 'ceo_name' => 'Chief Executive'],
        ])->render();

        $this->assertMatchesRegularExpression('/<div>Chief Executive Officer<\/div>\s*<div><img class="signature"[^>]+><\/div>\s*<div><strong>Chief Executive<\/strong><\/div>/', $html);
    }

    public function test_bank_pdf_is_only_available_after_approval_and_download_does_not_mark_paid(): void
    {
        $expense = $this->draft();
        $this->get(route('expenses.bank', $expense))->assertForbidden();
        $this->post(route('expenses.action', [$expense, 'submit']))->assertRedirect();
        $this->get(route('expenses.bank', $expense))->assertForbidden();
        $settings = $this->settings();
        $this->login($this->ceo)->post(route('expenses.action', [$expense, 'approve']))->assertRedirect();
        $snapshot = $expense->fresh()->approval_snapshot;
        $settings->update(['name' => 'Changed School', 'debit_account_number' => '9999999999']);
        Storage::disk('local')->delete($settings->logo_path);
        Storage::disk('local')->delete($settings->signature_path);
        $response = $this->get(route('expenses.bank', $expense))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
        $this->get(route('expenses.bank', [$expense, 'inline' => 1]))->assertOk()->assertHeader('Content-Disposition', 'inline; filename="expense-'.$expense->id.'-bank-instruction.pdf"');
        $this->assertSame('approved', $expense->fresh()->status);
        $this->assertNull($expense->fresh()->paid_at);
        $this->assertSame($this->ceo->id, $expense->fresh()->approved_by);
        $this->assertSame($snapshot, $expense->fresh()->approval_snapshot);
        $html = view('expenses.bank', ['expense' => $expense->fresh(), 'academy' => $snapshot + ['logo' => '', 'signature' => '']])->render();
        $this->assertStringContainsString('Springcare Academy', $html);
        $this->assertStringContainsString('100,000.01', $html);
        $this->assertStringContainsString('0012345678', $html);
        $this->assertStringNotContainsString('Changed School', $html);
        $this->get(route('expenses.show', $expense))->assertOk()->assertSee('Bank Payment Instruction');
    }

    public function test_receipts_can_be_uploaded_after_approval_without_changing_approved_details(): void
    {
        $expense = $this->approvedExpense();
        $snapshot = $expense->approval_snapshot;
        $this->post(route('expenses.receipt', $expense), ['receipt' => UploadedFile::fake()->image('receipt.jpg')])->assertForbidden();
        $this->login($this->accountant)->post(route('expenses.receipt', $expense), ['receipt' => UploadedFile::fake()->image('receipt.jpg')])->assertRedirect();
        $original = $expense->fresh()->receipt_path;
        $this->disk->assertExists($original);
        $this->put(route('expenses.update', $expense), array_replace($this->data(), ['amount' => '1']))->assertStatus(409);
        $this->delete(route('expenses.destroy', $expense))->assertStatus(409);
        $this->post(route('expenses.action', [$expense, 'paid']), ['payment_reference' => 'BANK-123', 'payment_date' => now()->format('Y-m-d')])->assertRedirect();
        $this->post(route('expenses.receipt', $expense), ['receipt' => UploadedFile::fake()->image('final-receipt.png')])->assertRedirect();
        $this->disk->assertMissing($original);
        $this->disk->assertExists($expense->fresh()->receipt_path);
        $this->assertSame(10000001, $expense->fresh()->amount_kobo);
        $this->assertSame($snapshot, $expense->fresh()->approval_snapshot);
        $this->assertSame('paid', $expense->fresh()->status);
        $this->get(route('expenses.attachment', [$expense, 'receipt']))->assertDownload();
        $this->assertSame(['created', 'submit', 'approve', 'receipt_uploaded', 'paid', 'receipt_replaced', 'receipt_downloaded'], $expense->events()->pluck('action')->all());
    }

    public function test_payment_requires_approval_valid_date_and_accountant_role(): void
    {
        $expense = $this->draft();
        $data = ['payment_reference' => 'BANK-123', 'payment_date' => now()->format('Y-m-d')];
        $this->post(route('expenses.action', [$expense, 'paid']), $data)->assertStatus(409);
        $expense = $this->approvedExpense();
        $this->post(route('expenses.action', [$expense, 'paid']), $data)->assertForbidden();
        $this->login($this->accountant)->post(route('expenses.action', [$expense, 'paid']))->assertSessionHasErrors(['payment_reference', 'payment_date']);
        $this->post(route('expenses.action', [$expense, 'paid']), array_replace($data, ['payment_date' => now()->subDay()->format('Y-m-d')]))->assertStatus(422);
        $this->post(route('expenses.action', [$expense, 'paid']), array_replace($data, ['payment_date' => now()->addDay()->format('Y-m-d')]))->assertSessionHasErrors('payment_date');
        $this->assertSame('approved', $expense->fresh()->status);
    }

    public function test_draft_documents_can_be_replaced_removed_and_deleted(): void
    {
        $expense = $this->draft(['invoice' => UploadedFile::fake()->image('invoice.png'), 'receipt' => UploadedFile::fake()->image('receipt.png')]);
        $oldInvoice = $expense->invoice_path;
        $oldReceipt = $expense->receipt_path;
        $this->put(route('expenses.update', $expense), array_replace($this->data(), ['invoice' => UploadedFile::fake()->image('new-invoice.png'), 'remove_receipt' => 1]))->assertRedirect();
        $this->disk->assertMissing([$oldInvoice, $oldReceipt]);
        $newInvoice = $expense->fresh()->invoice_path;
        $this->disk->assertExists($newInvoice);
        $this->assertNull($expense->fresh()->receipt_path);
        $this->delete(route('expenses.destroy', $expense))->assertRedirect();
        $this->disk->assertMissing($newInvoice);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertDatabaseCount('expense_events', 0);
    }
}
