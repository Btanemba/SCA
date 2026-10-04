<?php

namespace Tests\Feature;

use App\Models\AcademySetting;
use App\Models\Payroll;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use App\Services\PayrollDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private User $ceo;
    private User $admin;
    private User $security;
    private User $staff;
    private User $otherStaff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->accountant = $this->employee(Person::ROLE_ACCOUNTANT, 'Accountant');
        $this->ceo = $this->employee(Person::ROLE_FOUNDER, 'Chief');
        $this->admin = $this->employee(Person::ROLE_ADMIN, 'Administrator');
        $this->security = $this->employee(Person::ROLE_SECURITY, 'Security');
        $this->staff = $this->employee(Person::ROLE_STAFF, 'Teacher');
        $this->otherStaff = $this->employee(Person::ROLE_STAFF, 'Other');
    }

    private function employee(string $code, string $name): User
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $user = User::factory()->create(['name' => $name, 'email_verified_at' => now()]);
        Person::create([
            'user_id' => $user->id, 'sac_role_id' => $role->id, 'first_name' => $name, 'last_name' => 'Employee',
            'bank_name' => 'Staff Bank', 'account_name' => $name.' Employee', 'account_number' => '0012345678',
        ]);

        return $user;
    }

    private function login(User $user): static
    {
        return $this->actingAs($user, config('backpack.base.guard'));
    }

    private function settings(): AcademySetting
    {
        $logo = UploadedFile::fake()->image('logo.jpg', 100, 100)->store('academy', 'local');
        $signature = UploadedFile::fake()->image('signature.jpg', 200, 50)->store('academy', 'local');
        $settings = new AcademySetting;
        $settings->id = 1;
        $settings->fill([
            'name' => 'Springcare Academy', 'address' => 'School Road, Nigeria',
            'phone' => '08000000000', 'email' => 'academy@example.com', 'website' => null,
            'ceo_name' => 'Chief Executive', 'logo_path' => $logo, 'signature_path' => $signature,
            'bank_name' => 'Test Bank', 'bank_email' => 'bank@example.com', 'bank_address' => 'Bank Road',
            'debit_account_name' => 'Springcare Academy', 'debit_account_number' => '0123456789',
        ])->save();

        return $settings;
    }

    private function draft(): Payroll
    {
        return Payroll::create(['year' => 2026, 'month' => 10, 'created_by' => $this->accountant->id]);
    }

    private function salaryData(): array
    {
        return [
            'person_id' => $this->staff->person->id,
            'bank_name' => 'Staff Bank', 'account_name' => 'Teacher Employee', 'account_number' => '0012345678',
            'basic' => '100000.01',
            'allowances' => [['label' => 'Transport', 'amount' => '5000.09']],
            'deductions' => [['label' => 'PAYE', 'amount' => '1000.10']],
        ];
    }

    private function populatedDraft(): Payroll
    {
        $payroll = $this->draft();
        $this->login($this->accountant)->post(route('payroll.entries.store', $payroll), $this->salaryData())->assertRedirect();

        return $payroll->fresh();
    }

    private function approvedPayroll(): Payroll
    {
        $this->settings();
        $payroll = $this->populatedDraft();
        $this->login($this->accountant)->post(route('payroll.action', [$payroll, 'submit']))->assertRedirect();
        $this->login($this->ceo)->post(route('payroll.action', [$payroll, 'approve']))->assertRedirect();

        return $payroll->fresh();
    }

    public function test_accountant_creates_unique_monthly_payroll_and_staff_cannot_manage_it(): void
    {
        $this->login($this->accountant)->post(route('payroll.store'), ['year' => 2026, 'month' => 10])->assertRedirect();
        $this->post(route('payroll.store'), ['year' => 2026, 'month' => 10])->assertSessionHasErrors('month');
        $this->assertDatabaseCount('payrolls', 1);
        $this->login($this->staff)->get(route('payroll.index'))->assertForbidden();
        $this->post(route('payroll.store'), ['year' => 2026, 'month' => 11])->assertForbidden();
        $this->login($this->ceo)->post(route('payroll.store'), ['year' => 2026, 'month' => 11])->assertForbidden();
    }

    public function test_empty_payroll_cannot_be_submitted(): void
    {
        $payroll = $this->draft();
        $this->login($this->accountant)->post(route('payroll.action', [$payroll, 'submit']))->assertStatus(409);
    }

    public function test_payroll_entry_picker_only_shows_eligible_unassigned_people(): void
    {
        $payroll = $this->draft();
        $this->login($this->accountant)->post(route('payroll.entries.store', $payroll), $this->salaryData())->assertRedirect();

        $this->get(route('payroll.entries.create', $payroll))
            ->assertOk()
            ->assertDontSee('value="'.$this->staff->person->id.'"', false)
            ->assertSee('Other Employee [Staff]')
            ->assertSee('value="'.$this->otherStaff->person->id.'"', false)
            ->assertSee('value="'.$this->accountant->person->id.'"', false)
            ->assertSee('Accountant Employee [Accountant]')
            ->assertSee('value="'.$this->admin->person->id.'"', false)
            ->assertSee('value="'.$this->security->person->id.'"', false)
            ->assertDontSee('value="'.$this->ceo->person->id.'"', false);

        $entry = $payroll->entries()->firstOrFail();
        $this->get(route('payroll.entries.edit', [$payroll, $entry]))
            ->assertOk()
            ->assertSee('value="'.$this->staff->person->id.'"', false)
            ->assertSee('Teacher Employee [Staff]');

        $this->post(route('payroll.entries.store', $payroll), array_replace(
            $this->salaryData(),
            ['person_id' => $this->ceo->person->id]
        ))->assertStatus(422);
    }

    public function test_payroll_entry_uses_readonly_person_bank_details(): void
    {
        $payroll = $this->draft();
        $this->login($this->accountant)->post(route('payroll.entries.store', $payroll), array_replace(
            $this->salaryData(),
            ['bank_name' => 'Tampered Bank', 'account_name' => 'Tampered Name', 'account_number' => '9999999999']
        ))->assertRedirect();

        $entry = $payroll->entries()->firstOrFail();
        $this->assertSame('Staff Bank', $entry->bank_name);
        $this->assertSame('Teacher Employee', $entry->account_name);
        $this->assertSame('0012345678', $entry->account_number);

        $this->get(route('payroll.entries.edit', [$payroll, $entry]))
            ->assertOk()
            ->assertSee('data-bank-name="Staff Bank"', false)
            ->assertSee('data-account-name="Teacher Employee"', false)
            ->assertSee('id="bank_name" value="Staff Bank" readonly', false)
            ->assertDontSee('name="bank_name"', false);
    }

    public function test_submission_locks_entries_and_return_allows_correction(): void
    {
        $payroll = $this->populatedDraft();
        $entry = $payroll->entries()->first();
        $this->assertSame(10400000, (int) $entry->net_kobo);
        $this->post(route('payroll.action', [$payroll, 'submit']))->assertRedirect();
        $this->put(route('payroll.entries.update', [$payroll, $entry]), $this->salaryData())->assertStatus(409);
        $this->delete(route('payroll.entries.destroy', [$payroll, $entry]))->assertStatus(409);
        $this->post(route('payroll.action', [$payroll, 'approve']))->assertForbidden();
        $this->login($this->ceo)->post(route('payroll.action', [$payroll, 'return']))->assertSessionHasErrors('reason');
        $this->post(route('payroll.action', [$payroll, 'return']), ['reason' => 'Correct PAYE'])->assertRedirect();
        $this->login($this->accountant)->put(route('payroll.entries.update', [$payroll, $entry]), array_replace($this->salaryData(), ['basic' => '110000.01']))->assertRedirect();
        $this->get(route('payroll.show', $payroll))->assertOk()->assertSee('Response to Return')->assertSee('Respond &amp; Resubmit', false);
        $this->post(route('payroll.action', [$payroll, 'submit']))->assertSessionHasErrors('response');
        $this->post(route('payroll.action', [$payroll, 'submit']), ['response' => str_repeat('x', 2001)])->assertSessionHasErrors('response');
        $this->assertSame('returned', $payroll->fresh()->status);
        $this->assertSame('Correct PAYE', $payroll->fresh()->return_reason);
        $this->assertSame(1, $payroll->events()->where('action', 'submit')->count());
        $this->login($this->ceo)->post(route('payroll.action', [$payroll, 'submit']), ['response' => 'PAYE corrected'])->assertForbidden();
        $this->login($this->accountant)->post(route('payroll.action', [$payroll, 'submit']), ['response' => 'PAYE corrected'])->assertRedirect();
        $this->assertSame('submitted', $payroll->fresh()->status);
        $this->assertNull($payroll->fresh()->return_reason);
        $this->assertSame(['response' => 'PAYE corrected', 'return_reason' => 'Correct PAYE'], $payroll->events()->where('action', 'submit')->latest('id')->first()->details);
        $this->login($this->ceo)->get(route('payroll.show', $payroll))->assertOk()->assertSee('Accountant Response')->assertSee('PAYE corrected')->assertSee('Correct PAYE')->assertDontSee('name="response"', false);
        $this->post(route('payroll.action', [$payroll, 'return']), ['reason' => 'Explain allowance'])->assertRedirect();
        $this->login($this->accountant)->get(route('payroll.show', $payroll))->assertOk()->assertSee('Response to Return')->assertDontSee('Accountant Response');
        $this->post(route('payroll.action', [$payroll, 'submit']), ['response' => 'Includes <b>transport</b> allowance'])->assertRedirect();
        $this->login($this->ceo)->get(route('payroll.show', $payroll))->assertOk()->assertSee('Includes &lt;b&gt;transport&lt;/b&gt; allowance', false)->assertDontSee('Includes <b>transport</b> allowance', false);
        $this->assertSame('PAYE corrected', $payroll->events()->where('action', 'submit')->orderBy('id')->get()[1]->details['response']);
        $this->assertSame(['response' => 'Includes <b>transport</b> allowance', 'return_reason' => 'Explain allowance'], $payroll->events()->where('action', 'submit')->latest('id')->first()->details);
    }

    public function test_approval_requires_complete_letterhead_and_signature(): void
    {
        $payroll = $this->populatedDraft();
        $this->post(route('payroll.action', [$payroll, 'submit']))->assertRedirect();
        $this->login($this->ceo)->post(route('payroll.action', [$payroll, 'approve']))->assertSessionHasErrors('settings');
        $this->assertSame('submitted', $payroll->fresh()->status);
    }

    public function test_approval_is_immutable_and_preserves_branding_snapshot(): void
    {
        $payroll = $this->approvedPayroll();
        $entry = $payroll->entries()->first();
        $this->assertSame($this->ceo->id, $payroll->approved_by);
        AcademySetting::find(1)->update(['name' => 'New Name', 'bank_email' => 'new-bank@example.com']);
        $this->assertSame('Springcare Academy', $payroll->fresh()->approval_snapshot['name']);
        $this->assertSame('bank@example.com', $payroll->fresh()->approval_snapshot['bank_email']);
        $snapshot = $payroll->approval_snapshot;
        $this->assertArrayNotHasKey('logo', $snapshot);
        $this->assertArrayNotHasKey('signature', $snapshot);
        foreach (['logo', 'signature'] as $asset) {
            $originalPath = AcademySetting::find(1)->getAttribute($asset.'_path');
            $contents = Storage::disk('local')->get($originalPath);
            $this->assertStringStartsWith('payroll/approval-assets/', $snapshot[$asset.'_path']);
            $this->assertSame($contents, Storage::disk('local')->get($snapshot[$asset.'_path']));
            Storage::disk('local')->put($originalPath, 'replacement image');
            $this->assertSame($contents, Storage::disk('local')->get($snapshot[$asset.'_path']));
        }
        $this->assertStringStartsWith('%PDF-', app(PayrollDocuments::class)->bankLetter($payroll));
        $this->post(route('payroll.action', [$payroll, 'return']), ['reason' => 'Change'])->assertStatus(409);
        $this->login($this->accountant)->put(route('payroll.entries.update', [$payroll, $entry]), $this->salaryData())->assertStatus(409);
        $this->delete(route('payroll.destroy', $payroll))->assertStatus(409);
    }

    public function test_large_approval_images_are_not_stored_in_database_snapshot(): void
    {
        $settings = $this->settings();
        foreach (['logo', 'signature'] as $asset) {
            $path = $settings->getAttribute($asset.'_path');
            Storage::disk('local')->put($path, Storage::disk('local')->get($path).str_repeat('padding', 150000));
        }
        $snapshot = app(PayrollDocuments::class)->snapshot();
        $this->assertLessThan(4096, strlen(json_encode($snapshot, JSON_THROW_ON_ERROR)));
        foreach (['logo', 'signature'] as $asset) {
            $this->assertSame(
                Storage::disk('local')->get($settings->getAttribute($asset.'_path')),
                Storage::disk('local')->get($snapshot[$asset.'_path'])
            );
        }
    }

    public function test_legacy_inline_approval_images_still_render(): void
    {
        $payroll = $this->approvedPayroll();
        $snapshot = $payroll->approval_snapshot;
        foreach (['logo', 'signature'] as $asset) {
            $contents = Storage::disk('local')->get($snapshot[$asset.'_path']);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            $snapshot[$asset] = 'data:'.$mime.';base64,'.base64_encode($contents);
            unset($snapshot[$asset.'_path']);
        }
        $payroll->approval_snapshot = $snapshot;
        $documents = app(PayrollDocuments::class);
        $this->assertStringStartsWith('%PDF-', $documents->bankLetter($payroll));
        $entry = $payroll->entries()->first();
        $entry->setRelation('payroll', $payroll);
        $this->assertStringStartsWith('%PDF-', $documents->payslip($entry));
    }

    public function test_staff_only_see_their_own_approved_payslips(): void
    {
        $payroll = $this->populatedDraft();
        $entry = $payroll->entries()->first();
        $this->login($this->staff)->get(route('payroll.mine'))
            ->assertOk()
            ->assertSee('No approved payslips yet.')
            ->assertDontSee('href="'.route('payroll.mine').'"', false);
        $this->get(route('payroll.payslip', $entry))->assertForbidden();
        $this->settings();
        $this->login($this->accountant)->post(route('payroll.action', [$payroll, 'submit']))->assertRedirect();
        $this->login($this->ceo)->post(route('payroll.action', [$payroll, 'approve']))->assertRedirect();
        $this->login($this->staff)->get(route('payroll.mine'))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertSee('Approved - payment pending')
            ->assertSee('href="'.route('payroll.mine').'"', false);
        $response = $this->get(route('payroll.payslip', $entry));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->login($this->otherStaff)->get(route('payroll.payslip', $entry))->assertForbidden();
        $this->get(route('payroll.mine'))->assertDontSee('October 2026');
        $this->get(route('payroll.bank', $payroll))->assertForbidden();
        $this->login($this->ceo)->get(route('payroll.mine'))
            ->assertOk()
            ->assertDontSee('href="'.route('payroll.mine').'"', false);
    }

    public function test_bank_pdf_and_email_use_approved_recipient_without_marking_paid(): void
    {
        $payroll = $this->approvedPayroll();
        $this->login($this->accountant);
        $response = $this->get(route('payroll.bank', $payroll));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        config(['mail.default' => 'smtp']);
        Mail::fake();
        $this->post(route('payroll.email', $payroll), ['confirm_recipient' => 'wrong@example.com'])->assertSessionHasErrors('confirm_recipient');
        $this->post(route('payroll.email', $payroll), ['confirm_recipient' => 'bank@example.com'])->assertRedirect()->assertSessionHasNoErrors();
        Mail::assertSentCount(1);
        Mail::assertSent(\App\Mail\PayrollBankInstruction::class, function ($mail) use ($payroll) {
            $this->assertTrue($mail->hasTo('bank@example.com'));
            $mail->render();
            $this->assertCount(1, $mail->rawAttachments);
            $attachment = $mail->rawAttachments[0];
            $this->assertStringStartsWith('%PDF-', $attachment['data']);
            $this->assertSame('payroll-'.$payroll->year.'-'.$payroll->month.'.pdf', $attachment['name']);
            $this->assertSame('application/pdf', $attachment['options']['mime']);

            return true;
        });
        $this->assertSame('approved', $payroll->fresh()->status);
        $this->assertDatabaseHas('payroll_events', ['payroll_id' => $payroll->id, 'action' => 'bank_email_sent']);
    }

    public function test_payment_requires_acc_and_a_valid_confirmation(): void
    {
        $payroll = $this->approvedPayroll();
        $this->post(route('payroll.action', [$payroll, 'paid']), ['payment_reference' => 'BANK-123', 'payment_date' => now()->format('Y-m-d')])->assertForbidden();
        $this->login($this->accountant)->post(route('payroll.action', [$payroll, 'paid']))->assertSessionHasErrors(['payment_reference', 'payment_date']);
        $this->post(route('payroll.action', [$payroll, 'paid']), ['payment_reference' => 'BANK-123', 'payment_date' => now()->format('Y-m-d')])->assertRedirect();
        $this->assertSame('paid', $payroll->fresh()->status);
        $this->post(route('payroll.action', [$payroll, 'paid']), ['payment_reference' => 'BANK-456', 'payment_date' => now()->format('Y-m-d')])->assertStatus(409);
    }

    public function test_settings_uploads_are_private_and_only_ceo_controls_signature(): void
    {
        $data = [
            'name' => 'Springcare Academy', 'address' => 'School Road', 'phone' => '08000000000', 'email' => 'academy@example.com',
            'ceo_name' => 'Chief Executive', 'logo' => UploadedFile::fake()->image('logo.png'), 'signature' => UploadedFile::fake()->image('signature.png'),
        ];
        $this->login($this->accountant)->put(route('academy.settings.update'), $data)->assertForbidden();
        $this->login($this->ceo)->put(route('academy.settings.update'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('academy_settings', 1);
        Storage::disk('local')->assertExists(AcademySetting::find(1)->signature_path);
        $this->get(route('academy.settings.asset', 'signature'))->assertOk();
        $admin = $this->employee(Person::ROLE_ADMIN, 'Admin');
        $this->login($admin)->put(route('academy.settings.update'), $data)->assertForbidden();
        $this->get(route('academy.settings.asset', 'signature'))->assertForbidden();
        $this->login($this->staff)->get(route('academy.settings.asset', 'logo'))->assertForbidden();
    }

    public function test_settings_and_salary_forms_render_for_their_owners(): void
    {
        $this->login($this->ceo)->get(route('academy.settings'))->assertOk()->assertSee('CEO signature');
        $payroll = $this->populatedDraft();
        $this->get(route('payroll.index'))->assertOk();
        $this->get(route('payroll.show', $payroll))->assertOk()->assertSee('Teacher Employee');
        $this->get(route('payroll.entries.edit', [$payroll, $payroll->entries()->first()]))->assertOk()->assertSee('PAYE');
    }

    public function test_payroll_index_can_search_by_reference_and_period(): void
    {
        $october = $this->draft();
        $september = Payroll::create(['year' => 2026, 'month' => 9, 'created_by' => $this->accountant->id]);

        $this->login($this->accountant)
            ->get(route('payroll.index', ['search' => sprintf('SCA/PAY/2026/10/%06d', $october->id)]))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertDontSee('September 2026');

        $this->get(route('payroll.index', ['search' => 'September']))
            ->assertOk()
            ->assertSee('September 2026')
            ->assertDontSee('October 2026');

        $this->get(route('payroll.index', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertDontSee('September 2026');
    }
}
