<?php

namespace Tests\Feature;

use App\Models\AcademyFee;
use App\Models\FeePayment;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\StudentFeeCharge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'amount' => '250.00',
            'paid_at' => '2026-11-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-001',
        ])->assertRedirect();

        $this->get(route('student-fees.index', ['month' => '2026-11', 'status' => 'part_paid']))
            ->assertOk()
            ->assertSee('Part-paid')
            ->assertSee('BANK-001')
            ->assertSee('500.00');

        $this->post(route('student-fees.payments', $charge), [
            'amount' => '500.00',
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

    public function test_duplicate_monthly_charge_and_overpayment_are_rejected(): void
    {
        $charge = $this->createCharge();

        $this->post(route('student-fees.charges'), [
            'student_id' => $this->student->id,
            'academy_fee_id' => $this->fee->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasErrors('academy_fee_id');

        $this->post(route('student-fees.payments', $charge), [
            'amount' => '750.01',
            'paid_at' => '2026-11-05',
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('student_fee_charges', 1);
        $this->assertDatabaseCount('fee_payments', 0);
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
            FeePayment::create([
                'student_fee_charge_id' => $charge->id,
                'amount_kobo' => $paymentAmount,
                'paid_at' => $paidAt->toDateString(),
                'payment_method' => 'bank_transfer',
                'received_by' => $this->accountant->id,
            ]);
        }

        $this->login($this->accountant)->get(backpack_url('dashboard'))
            ->assertOk()
            ->assertSee('Fee income this month')
            ->assertSee('NGN 1,250.00');

        foreach ([Person::ROLE_ADMIN, Person::ROLE_FOUNDER] as $role) {
            $user = $this->userForRole($role, $role);
            $this->login($user)->get(backpack_url('dashboard'))
                ->assertOk()
                ->assertSee('Current month finances')
                ->assertSee('Fee income this month')
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
            ->assertSessionHas('success', 'Added the selected monthly fee for 2 students for November 2026.');

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
        ])->assertSessionHas('success', 'No charges added. The selected fees are already recorded for those students and that month.');
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

    public function test_charge_requires_a_student_and_priced_monthly_fee(): void
    {
        $perSessionFee = AcademyFee::create([
            'fee_name' => 'Per Session Activity',
            'age_or_level' => 'All levels',
            'amount' => 5000,
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
            'academy_fee_id' => $perSessionFee->id,
            'billing_month' => '2026-11',
        ])->assertSessionHasErrors('academy_fee_id');

        $this->assertDatabaseCount('student_fee_charges', 0);
    }
}
