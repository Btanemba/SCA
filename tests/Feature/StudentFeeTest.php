<?php

namespace Tests\Feature;

use App\Models\AcademyFee;
use App\Models\AcademySetting;
use App\Models\FeePayment;
use App\Models\PaymentReceipt;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\StudentFeeCharge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class StudentFeeTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;
    private Person $student;
    private AcademyFee $fee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountant = $this->userForRole(Person::ROLE_ACCOUNTANT, 'Fee Accountant');
        $studentRole = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $this->student = Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'Lucy',
            'last_name' => 'Anemba',
        ]);
        $this->fee = AcademyFee::create([
            'fee_name' => 'Monthly Tuition',
            'age_or_level' => 'All levels',
            'amount' => 75000,
            'schedule' => 'Monthly',
            'is_published' => true,
            'sort_order' => 1,
        ]);
    }

    private function userForRole(string $code, string $name): User
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $user = User::factory()->create(['name' => $name, 'email_verified_at' => now()]);
        Person::create([
            'user_id' => $user->id,
            'sac_role_id' => $role->id,
            'first_name' => $name,
            'last_name' => 'Employee',
        ]);

        return $user;
    }

    private function login(User $user): static
    {
        return $this->actingAs($user, config('backpack.base.guard'));
    }

    private function createCharge(array $overrides = []): StudentFeeCharge
    {
        $this->login($this->accountant)
            ->post(route('student-fees.charges'), array_replace([
                'student_id' => $this->student->id,
                'academy_fee_id' => $this->fee->id,
                'billing_month' => '2026-11',
            ], $overrides))
            ->assertRedirect();

        return StudentFeeCharge::query()->latest('id')->firstOrFail();
    }

    public function test_student_fee_list_shows_student_photos_and_a_fallback(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('people/lucy.jpg', 'photo');
        $this->student->update(['image_path' => 'people/lucy.jpg']);
        Person::create([
            'sac_role_id' => $this->student->sac_role_id,
            'first_name' => 'Daisy',
            'last_name' => 'Anemba',
        ]);

        $this->login($this->accountant)->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('storage/people/lucy.jpg')
            ->assertSee('Photo of Lucy Anemba')
            ->assertSee('No photo available');
    }

    public function test_accountant_can_download_a_students_monthly_invoice(): void
    {
        $settings = new AcademySetting;
        $settings->id = 1;
        $settings->fill([
            'name' => 'Springcare Academy', 'address' => 'School address', 'phone' => '1234567890',
            'email' => 'school@example.com', 'ceo_name' => 'Director',
            'bank_name' => 'Payroll Bank', 'debit_account_name' => 'Payroll Account', 'debit_account_number' => '1111111111',
            'fee_bank_name' => 'Fees Bank', 'fee_account_name' => 'School Fees', 'fee_account_number' => '0123456789',
        ])->save();
        $charge = $this->createCharge(['billing_month' => '2026-12']);
        $meals = AcademyFee::create([
            'fee_name' => 'Feeding', 'age_or_level' => 'All levels', 'amount' => 10000,
            'schedule' => 'Monthly', 'is_published' => true, 'sort_order' => 2,
        ]);
        $this->createCharge(['academy_fee_id' => $meals->id, 'billing_month' => '2026-12']);
        $this->createCharge(['billing_month' => '2026-11']);
        $otherStudent = Person::create([
            'sac_role_id' => $this->student->sac_role_id,
            'first_name' => 'Other',
            'last_name' => 'Student',
        ]);
        $this->createCharge(['student_id' => $otherStudent->id, 'billing_month' => '2026-12']);
        $this->post(route('student-fees.payments', $charge), [
            'amount' => '25000.00',
            'paid_at' => '2026-11-07',
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $url = route('student-fees.invoice', ['student' => $this->student->id, 'month' => '2026-12']);
        $this->get(route('student-fees.index', ['month' => '2026-12']))
            ->assertOk()->assertSee($url)->assertSee('Download invoice');

        $invoiceData = [];
        View::composer('student-fees.invoice', function ($view) use (&$invoiceData) {
            $invoiceData = $view->getData();
        });
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertNotEmpty($invoiceData);
        $this->assertCount(2, $invoiceData['charges']);
        $this->assertSame(8500000, (int) $invoiceData['charges']->sum('amount_due_kobo'));
        $this->assertSame(2500000, (int) $invoiceData['charges']->sum(fn ($item) => $item->paid_amount_kobo));
        $this->assertSame(6000000, (int) $invoiceData['charges']->sum(fn ($item) => $item->balance_kobo));
        $rendered = view('student-fees.invoice', $invoiceData)->render();
        $this->assertStringContainsString('Monthly Tuition', $rendered);
        $this->assertStringContainsString('Feeding', $rendered);
        $this->assertStringContainsString('60,000.00', $rendered);
        $this->assertStringContainsString('Fees Bank', $rendered);
        $this->assertStringContainsString('School Fees', $rendered);
        $this->assertStringContainsString('0123456789', $rendered);
        $this->assertStringNotContainsString('1111111111', $rendered);

        $this->get(route('student-fees.invoice', ['student' => $this->student->id, 'month' => '2027-01']))
            ->assertNotFound();
        $staff = $this->userForRole(Person::ROLE_STAFF, 'Invoice Staff');
        $this->login($staff)->get($url)->assertForbidden();
    }

    public function test_fee_payment_details_require_a_complete_account_and_founder_access(): void
    {
        $founder = $this->userForRole(Person::ROLE_FOUNDER, 'Founder');
        $data = [
            'name' => 'Springcare Academy', 'address' => 'School address', 'phone' => '1234567890',
            'email' => 'school@example.com', 'ceo_name' => 'Director',
            'fee_bank_name' => 'Fees Bank', 'fee_account_name' => 'School Fees', 'fee_account_number' => '0123456789',
        ];
        $this->login($this->accountant)->put(route('academy.settings.update'), $data)->assertForbidden();
        $this->login($founder)->put(route('academy.settings.update'), array_replace($data, ['fee_account_number' => '123']))
            ->assertSessionHasErrors('fee_account_number');
        $this->put(route('academy.settings.update'), array_replace($data, ['fee_account_name' => '']))
            ->assertSessionHasErrors('fee_account_name');
        $this->put(route('academy.settings.update'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('academy_settings', [
            'id' => 1, 'fee_bank_name' => 'Fees Bank', 'fee_account_name' => 'School Fees',
            'fee_account_number' => '0123456789',
        ]);
    }

    public function test_invoice_requests_contact_when_fee_payment_details_are_missing(): void
    {
        $this->createCharge();
        $invoiceData = [];
        View::composer('student-fees.invoice', function ($view) use (&$invoiceData) {
            $invoiceData = $view->getData();
        });
        $this->get(route('student-fees.invoice', ['student' => $this->student->id, 'month' => '2026-11']))
            ->assertOk();
        $this->assertStringContainsString('Please contact the academy for payment details.', view('student-fees.invoice', $invoiceData)->render());
    }

    public function test_accountant_tracks_unpaid_partial_and_paid_monthly_fees(): void
    {
        $charge = $this->createCharge();
        $this->assertSame(7500000, $charge->amount_due_kobo);

        $studentRole = SacRole::where('code', Person::ROLE_STUDENT)->firstOrFail();
        Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'Benedict',
            'last_name' => 'Anemba',
        ]);

        $this->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('All Students - November 2026')
            ->assertSee('Lucy Anemba')
            ->assertSee('Benedict Anemba')
            ->assertSee('Not charged');

        $this->get(route('student-fees.index', ['month' => '2026-11', 'status' => 'unpaid']))
            ->assertOk()
            ->assertSee('Child Fees')
            ->assertSee('Lucy Anemba')
            ->assertSee('Monthly Tuition')
            ->assertSee('75,000.00')
            ->assertSee('Unpaid')
            ->assertSee('Child Fees');

        $this->post(route('student-fees.payments', $charge), [
            'amount' => '25000.00',
            'paid_at' => '2026-11-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-001',
        ])->assertRedirect();

        $this->get(route('student-fees.index', ['month' => '2026-11', 'status' => 'part_paid']))
            ->assertOk()
            ->assertSee('Part-paid')
            ->assertSee('BANK-001')
            ->assertSee('50,000.00');

        $this->post(route('student-fees.payments', $charge), [
            'amount' => '50000.00',
            'paid_at' => '2026-11-06',
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->get(route('student-fees.index', ['month' => '2026-11', 'status' => 'paid']))
            ->assertOk()
            ->assertSee('Paid')
            ->assertSee('0.00');

        $this->assertDatabaseCount('fee_payments', 2);
        $this->assertSame(0, $charge->fresh()->balance_kobo);
        $this->assertSame('paid', $charge->fresh()->payment_status);
    }

    public function test_accountant_can_add_a_configured_per_session_fee_to_a_student(): void
    {
        $fee = AcademyFee::create([
            'fee_name' => 'Late pickup',
            'age_or_level' => 'All levels',
            'amount' => 5000,
            'schedule' => 'Per session',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        $this->login($this->accountant)->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()->assertSee('Late pickup');
        $this->post(route('student-fees.charges'), [
            'student_id' => $this->student->id,
            'academy_fee_id' => $fee->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_fee_charges', [
            'student_id' => $this->student->id,
            'academy_fee_id' => $fee->id,
            'amount_due_kobo' => 500000,
        ]);
        $this->post(route('student-fees.charges'), [
            'student_id' => $this->student->id,
            'academy_fee_id' => $fee->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_fee_charges', 2);
        $this->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()->assertSee('Late pickup - All levels - Per session');

        $this->post(route('student-fees.charges.all'), [
            'billing_month' => '2026-11',
            'fees' => [$this->student->id => $fee->id],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_fee_charges', 3);

        $charge = StudentFeeCharge::query()->where('academy_fee_id', $fee->id)->firstOrFail();
        $this->post(route('student-fees.payments', $charge), [
            'amount' => '5000.00', 'paid_at' => '2026-11-07', 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, $charge->fresh()->balance_kobo);
    }

    public function test_one_time_and_yearly_fees_are_not_charged_twice_for_the_same_period(): void
    {
        $registration = AcademyFee::create([
            'fee_name' => 'Registration', 'age_or_level' => 'All', 'amount' => 10000,
            'schedule' => 'One Time', 'is_published' => true, 'sort_order' => 2,
        ]);
        $card = AcademyFee::create([
            'fee_name' => 'Access Card', 'age_or_level' => 'Parent/Guardian', 'amount' => 15000,
            'schedule' => '1-Year', 'is_published' => true, 'sort_order' => 3,
        ]);
        $this->login($this->accountant);
        foreach ([$registration, $card] as $fee) {
            $this->post(route('student-fees.charges'), [
                'student_id' => $this->student->id, 'academy_fee_id' => $fee->id, 'billing_month' => '2026-11',
            ])->assertSessionHasNoErrors();
            $this->post(route('student-fees.charges'), [
                'student_id' => $this->student->id, 'academy_fee_id' => $fee->id, 'billing_month' => '2026-12',
            ])->assertSessionHasErrors('academy_fee_id');
        }

        $this->get(route('student-fees.index', ['month' => '2026-12']))
            ->assertOk()->assertDontSee('<option value="'.$registration->id.'">', false)
            ->assertDontSee('<option value="'.$card->id.'">', false);
        $this->post(route('student-fees.charges.all'), [
            'billing_month' => '2026-12', 'fees' => [$this->student->id => $card->id],
        ])->assertSessionHas('success', 'No charges added. The selected fees are already recorded for the applicable period.');
        $this->post(route('student-fees.charges'), [
            'student_id' => $this->student->id, 'academy_fee_id' => $registration->id, 'billing_month' => '2027-01',
        ])->assertSessionHasErrors('academy_fee_id');
        $this->post(route('student-fees.charges'), [
            'student_id' => $this->student->id, 'academy_fee_id' => $card->id, 'billing_month' => '2027-01',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_fee_charges', 3);
    }

    public function test_duplicate_monthly_charge_is_rejected(): void
    {
        $charge = $this->createCharge();

        $this->post(route('student-fees.charges'), [
            'student_id' => $this->student->id,
            'academy_fee_id' => $this->fee->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasErrors('academy_fee_id');

        $this->assertDatabaseCount('student_fee_charges', 1);
        $this->assertDatabaseCount('fee_payments', 0);
    }

    public function test_200000_payment_for_50000_fee_leaves_150000_in_wallet(): void
    {
        $this->fee->update(['amount' => 50000]);
        $charge = $this->createCharge();

        $this->post(route('student-fees.payments', $charge), [
            'amount' => '200000.00',
            'paid_at' => '2026-11-07',
            'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(0, $charge->fresh()->balance_kobo);
        $this->assertDatabaseHas('payment_receipts', [
            'student_id' => $this->student->id,
            'amount_kobo' => 20000000,
        ]);
        $this->assertDatabaseHas('fee_payments', [
            'student_fee_charge_id' => $charge->id,
            'amount_kobo' => 5000000,
        ]);
        $this->assertDatabaseHas('student_wallet_transactions', [
            'student_id' => $this->student->id,
            'type' => 'credit',
            'amount_kobo' => 15000000,
        ]);
        $this->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('150,000.00')
            ->assertSee('Received: NGN 200,000.00; applied to fees: NGN 50,000.00');
    }

    public function test_fee_payment_receipt_can_be_downloaded_and_verified(): void
    {
        $charge = $this->createCharge();
        $this->post(route('student-fees.payments', $charge), [
            'amount' => '90000.00',
            'paid_at' => '2026-11-07',
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-RECEIPT-001',
        ])->assertSessionHasNoErrors();

        $receipt = PaymentReceipt::query()->sole();
        $this->assertNotEmpty($receipt->receipt_number);
        $this->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()->assertSee(route('student-fees.receipts.download', $receipt));
        $response = $this->get(route('student-fees.receipts.download', $receipt))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $staff = $this->userForRole(Person::ROLE_STAFF, 'Receipt Staff');
        $this->login($staff)->get(route('student-fees.receipts.download', $receipt))->assertForbidden();
        auth()->guard(config('backpack.base.guard'))->logout();
        $this->get(route('payment-receipts.verify', $receipt->receipt_number))
            ->assertOk()->assertSee($receipt->receipt_number)->assertSee('Lucy Anemba')
            ->assertSee('90,000.00')->assertSee('75,000.00')->assertSee('15,000.00');
        $alteredNumber = substr($receipt->receipt_number, 0, -1).($receipt->receipt_number[-1] === 'A' ? 'B' : 'A');
        $this->get(route('payment-receipts.verify', $alteredNumber))->assertNotFound();
        $this->get(route('payment-receipts.verify', 'SCA-INVALID'))->assertNotFound();
    }

    public function test_each_payment_has_a_unique_persistent_receipt_number(): void
    {
        $charge = $this->createCharge();
        foreach (['25000.00', '50000.00'] as $amount) {
            $this->post(route('student-fees.payments', $charge), [
                'amount' => $amount,
                'paid_at' => '2026-11-07',
                'payment_method' => 'cash',
            ])->assertSessionHasNoErrors();
        }

        $receipts = PaymentReceipt::query()->orderBy('id')->get();
        $this->assertCount(2, $receipts);
        $this->assertNotSame($receipts[0]->receipt_number, $receipts[1]->receipt_number);
        $this->assertSame($receipts[0]->receipt_number, $receipts[0]->fresh()->receipt_number);
    }

    public function test_overpayment_is_recorded_as_wallet_credit(): void
    {
        $charge = $this->createCharge();

        $this->post(route('student-fees.payments', $charge), [
            'amount' => '90000.00',
            'paid_at' => '2026-11-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-002',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $charge->fresh()->balance_kobo);
        $this->assertDatabaseHas('payment_receipts', [
            'student_id' => $this->student->id,
            'amount_kobo' => 9000000,
        ]);
        $this->assertDatabaseHas('student_wallet_transactions', [
            'student_id' => $this->student->id,
            'type' => 'credit',
            'amount_kobo' => 1500000,
        ]);
        $this->assertDatabaseHas('fee_payments', [
            'student_fee_charge_id' => $charge->id,
            'amount_kobo' => 7500000,
            'payment_method' => 'bank_transfer',
        ]);

        $this->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('15,000.00')
            ->assertSee('BANK-002');

        $this->post(route('student-fees.payments', $charge), [
            'amount' => '90000.00',
            'paid_at' => '2026-11-05',
            'payment_method' => 'bank_transfer',
        ])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertDatabaseCount('student_wallet_transactions', 1);
    }

    public function test_existing_payments_are_backfilled_as_receipts(): void
    {
        $migration = require database_path('migrations/2026_11_07_000000_create_student_wallet_tables.php');
        $migration->down();
        $charge = $this->createCharge();
        $payment = FeePayment::create([
            'student_fee_charge_id' => $charge->id,
            'amount_kobo' => 125000,
            'paid_at' => '2026-11-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'EXISTING-001',
            'received_by' => $this->accountant->id,
        ]);

        $migration->up();

        $numbersMigration = require database_path('migrations/2026_11_07_100000_add_receipt_numbers_to_payment_receipts.php');
        $numbersMigration->up();
        $receiptNumber = PaymentReceipt::query()->sole()->receipt_number;
        $this->assertNotEmpty($receiptNumber);
        $numbersMigration->up();
        $this->assertSame($receiptNumber, PaymentReceipt::query()->sole()->receipt_number);

        $this->assertDatabaseHas('payment_receipts', [
            'student_id' => $this->student->id,
            'amount_kobo' => 125000,
            'reference' => 'EXISTING-001',
        ]);
        $this->assertSame(PaymentReceipt::query()->sole()->id, $payment->fresh()->payment_receipt_id);
        $this->assertSame(7375000, $charge->fresh()->balance_kobo);
        $migration->up();
        $this->assertDatabaseCount('payment_receipts', 1);
    }

    public function test_wallet_credit_can_pay_another_fee_without_creating_income_twice(): void
    {
        $firstCharge = $this->createCharge();
        $this->post(route('student-fees.payments', $firstCharge), [
            'amount' => '90000.00',
            'paid_at' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $nextCharge = $this->createCharge(['billing_month' => '2026-12']);
        $this->get(route('student-fees.index', ['month' => '2026-12']))
            ->assertOk()
            ->assertSee('Apply wallet');

        $this->post(route('student-fees.wallet', $nextCharge), ['amount' => '10000.00'])
            ->assertSessionHasNoErrors();
        $this->assertSame(6500000, $nextCharge->fresh()->balance_kobo);
        $this->assertDatabaseHas('fee_payments', [
            'student_fee_charge_id' => $nextCharge->id,
            'amount_kobo' => 1000000,
            'payment_method' => 'wallet',
            'payment_receipt_id' => null,
        ]);
        $this->assertDatabaseHas('student_wallet_transactions', [
            'student_id' => $this->student->id,
            'type' => 'debit',
            'amount_kobo' => 1000000,
        ]);
        $this->assertDatabaseCount('payment_receipts', 1);
        $migration = require database_path('migrations/2026_11_07_000000_create_student_wallet_tables.php');
        $migration->up();
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->get(route('student-fees.index', ['month' => '2026-12']))
            ->assertOk()
            ->assertSee('5,000.00');
        $this->get(backpack_url('dashboard'))->assertOk()->assertSee('NGN 90,000.00');

        $this->post(route('student-fees.wallet', $nextCharge), ['amount' => '5000.01'])
            ->assertSessionHasErrors('amount');
        $this->post(route('student-fees.wallet', $nextCharge), ['amount' => '0'])
            ->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('student_wallet_transactions', 2);
        $this->assertSame(6500000, $nextCharge->fresh()->balance_kobo);
    }

    public function test_wallet_credit_cannot_be_applied_to_another_student_or_exceed_the_fee(): void
    {
        $firstCharge = $this->createCharge();
        $this->post(route('student-fees.payments', $firstCharge), [
            'amount' => '90000.00',
            'paid_at' => '2026-11-05',
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $otherStudent = Person::create([
            'sac_role_id' => $this->student->sac_role_id,
            'first_name' => 'Other',
            'last_name' => 'Student',
        ]);
        $otherCharge = $this->createCharge(['student_id' => $otherStudent->id]);
        $this->post(route('student-fees.wallet', $otherCharge), ['amount' => '1.00'])
            ->assertSessionHasErrors('amount');

        $smallCharge = $this->createCharge(['billing_month' => '2026-12']);
        $smallCharge->update(['amount_due_kobo' => 500000]);
        $this->post(route('student-fees.wallet', $smallCharge), ['amount' => '5000.01'])
            ->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('student_wallet_transactions', 1);
    }

    public function test_wallet_payment_is_limited_to_finance_roles(): void
    {
        $charge = $this->createCharge();
        $user = $this->userForRole(Person::ROLE_STAFF, 'Staff');
        $this->login($user)->post(route('student-fees.wallet', $charge), ['amount' => '1.00'])
            ->assertForbidden();
        $this->assertDatabaseCount('student_wallet_transactions', 0);
    }

    public function test_same_fee_name_is_not_offered_or_chargeable_twice_for_a_student_month(): void
    {
        $this->createCharge();
        $alternateTuition = AcademyFee::create([
            'fee_name' => ' Monthly Tuition ',
            'age_or_level' => 'Older group',
            'amount' => 70000,
            'schedule' => 'Monthly',
            'is_published' => true,
            'sort_order' => 2,
        ]);
        $meals = AcademyFee::create([
            'fee_name' => 'Monthly Meals',
            'age_or_level' => 'All levels',
            'amount' => 18000,
            'schedule' => 'Monthly',
            'is_published' => true,
            'sort_order' => 3,
        ]);

        $this->get(route('student-fees.index', ['month' => '2026-11']))
            ->assertOk()
            ->assertDontSee('<option value="'.$alternateTuition->id.'">', false)
            ->assertSee('<option value="'.$meals->id.'">Monthly Meals', false);

        $this->login($this->accountant)->post(route('student-fees.charges'), [
            'student_id' => $this->student->id,
            'academy_fee_id' => $alternateTuition->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasErrors('academy_fee_id');

        $this->assertDatabaseCount('student_fee_charges', 1);
    }

    public function test_student_people_list_shows_recorded_fee_status_for_current_month(): void
    {
        $admin = $this->userForRole(Person::ROLE_ADMIN, 'Administrator');
        $month = now()->startOfMonth()->toDateString();
        $unpaidCharge = StudentFeeCharge::create([
            'student_id' => $this->student->id,
            'academy_fee_id' => $this->fee->id,
            'billing_month' => $month,
            'fee_name' => $this->fee->fee_name,
            'amount_due_kobo' => 7500000,
            'created_by' => $this->accountant->id,
        ]);

        $studentRole = SacRole::where('code', Person::ROLE_STUDENT)->firstOrFail();
        $paidStudent = Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'Benedict',
            'last_name' => 'Anemba',
        ]);
        $paidCharge = StudentFeeCharge::create([
            'student_id' => $paidStudent->id,
            'academy_fee_id' => $this->fee->id,
            'billing_month' => $month,
            'fee_name' => $this->fee->fee_name,
            'amount_due_kobo' => 7500000,
            'created_by' => $this->accountant->id,
        ]);
        FeePayment::create([
            'student_fee_charge_id' => $paidCharge->id,
            'amount_kobo' => 7500000,
            'paid_at' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'received_by' => $this->accountant->id,
        ]);
        Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'Daisy',
            'last_name' => 'Anemba',
        ]);

        $this->actingAs($admin, config('backpack.base.guard'))
            ->get(backpack_url('person?role='.Person::ROLE_STUDENT))
            ->assertOk()
            ->assertSee('Fee status ('.now()->format('F Y').')')
            ->assertSee('Lucy')
            ->assertSee('Unpaid')
            ->assertSee('Benedict')
            ->assertSee('Paid')
            ->assertSee('Daisy')
            ->assertSee('Not charged');
    }

    public function test_dashboard_shows_fee_income_to_accountant_admin_and_founder(): void
    {
        $today = now();
        $previousMonth = $today->copy()->startOfMonth()->subMonth();
        foreach ([[$today, 125000], [$previousMonth, 275000]] as [$paidAt, $paymentAmount]) {
            $charge = StudentFeeCharge::create([
                'student_id' => $this->student->id,
                'academy_fee_id' => $this->fee->id,
                'billing_month' => $paidAt->copy()->startOfMonth()->toDateString(),
                'fee_name' => $this->fee->fee_name,
                'amount_due_kobo' => 7500000,
                'created_by' => $this->accountant->id,
            ]);
            $receipt = PaymentReceipt::create([
                'student_id' => $this->student->id,
                'amount_kobo' => $paymentAmount,
                'paid_at' => $paidAt->toDateString(),
                'payment_method' => 'bank_transfer',
                'received_by' => $this->accountant->id,
            ]);
            FeePayment::create([
                'student_fee_charge_id' => $charge->id,
                'payment_receipt_id' => $receipt->id,
                'amount_kobo' => $paymentAmount,
                'paid_at' => $paidAt->toDateString(),
                'payment_method' => 'bank_transfer',
                'received_by' => $this->accountant->id,
            ]);
        }

        $this->login($this->accountant)->get(backpack_url('dashboard'))
            ->assertOk()
            ->assertSee('Fee payments received this month')
            ->assertSee('NGN 1,250.00');

        foreach ([Person::ROLE_ADMIN, Person::ROLE_FOUNDER] as $role) {
            $user = $this->userForRole($role, $role);
            $this->login($user)->get(backpack_url('dashboard'))
                ->assertOk()
                ->assertSee('Current month finances')
                ->assertSee('Fee payments received this month')
                ->assertSee('NGN</span> 1,250.00', false);
        }
    }

    public function test_accountant_can_bulk_add_only_selected_fees_for_each_student(): void
    {
        $studentRole = SacRole::where('code', Person::ROLE_STUDENT)->firstOrFail();
        $secondStudent = Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'Benedict',
            'last_name' => 'Anemba',
        ]);
        $thirdStudent = Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'Daisy',
            'last_name' => 'Anemba',
        ]);
        $secondFee = AcademyFee::create([
            'fee_name' => 'Monthly Tuition - Older Group',
            'age_or_level' => 'All levels',
            'amount' => 70000,
            'schedule' => 'Monthly',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        $this->login($this->accountant)->post(route('student-fees.charges.all'), [
            'billing_month' => '2026-11',
            'fees' => [
                $this->student->id => $this->fee->id,
                $secondStudent->id => $secondFee->id,
            ],
        ])->assertRedirect(route('student-fees.index', ['month' => '2026-11']))
            ->assertSessionHas('success', 'Added the selected fee for 2 students for November 2026.');

        $this->assertDatabaseCount('student_fee_charges', 2);
        $this->assertDatabaseHas('student_fee_charges', [
            'student_id' => $this->student->id,
            'academy_fee_id' => $this->fee->id,
            'billing_month' => '2026-11-01',
        ]);
        $this->assertDatabaseHas('student_fee_charges', [
            'student_id' => $secondStudent->id,
            'academy_fee_id' => $secondFee->id,
            'billing_month' => '2026-11-01',
        ]);
        $this->assertDatabaseMissing('student_fee_charges', ['student_id' => $thirdStudent->id]);

        $this->post(route('student-fees.charges.all'), [
            'billing_month' => '2026-11',
            'fees' => [
                $this->student->id => $this->fee->id,
                $secondStudent->id => $secondFee->id,
            ],
        ])->assertSessionHas('success', 'No charges added. The selected fees are already recorded for the applicable period.');
        $this->assertDatabaseCount('student_fee_charges', 2);
    }

    public function test_child_fee_ledger_is_limited_to_finance_roles(): void
    {
        foreach ([Person::ROLE_STAFF, Person::ROLE_STUDENT] as $role) {
            $user = $this->userForRole($role, $role);
            $this->login($user)
                ->get(route('student-fees.index'))
                ->assertForbidden();
        }

        $this->login($this->accountant)->get(route('student-fees.index'))->assertOk();
    }

    public function test_charge_requires_a_student_and_priced_fee(): void
    {
        $unpricedFee = AcademyFee::create([
            'fee_name' => 'Unpriced Activity',
            'age_or_level' => 'All levels',
            'amount' => null,
            'schedule' => 'Per session',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        $this->login($this->accountant)->post(route('student-fees.charges'), [
            'student_id' => $this->accountant->person->id,
            'academy_fee_id' => $this->fee->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasErrors('student_id');

        $this->post(route('student-fees.charges'), [
            'student_id' => $this->student->id,
            'academy_fee_id' => $unpricedFee->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasErrors('academy_fee_id');

        $this->assertDatabaseCount('student_fee_charges', 0);
    }
}
