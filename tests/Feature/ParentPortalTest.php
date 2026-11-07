<?php

namespace Tests\Feature;

use App\Models\AcademyFee;
use App\Models\AbsenceNotice;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\FeePayment;
use App\Models\PaymentReceipt;
use App\Models\Person;
use App\Models\PickupContact;
use App\Models\SacRole;
use App\Models\StudentFeeCharge;
use App\Models\StudentWalletTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $parentUser;
    private User $accountant;
    private Person $child;
    private Person $sibling;
    private Person $otherChild;
    private PaymentReceipt $receipt;
    private PaymentReceipt $otherReceipt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parentUser = $this->userForRole(Person::ROLE_PARENT);
        $this->accountant = $this->userForRole(Person::ROLE_ACCOUNTANT);
        $studentRole = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $this->child = Person::create(['sac_role_id' => $studentRole->id, 'first_name' => 'Lucy', 'last_name' => 'Child']);
        $this->sibling = Person::create(['sac_role_id' => $studentRole->id, 'first_name' => 'Daisy', 'last_name' => 'Child']);
        $this->otherChild = Person::create(['sac_role_id' => $studentRole->id, 'first_name' => 'Private', 'last_name' => 'Child']);
        $this->parentUser->person->children()->attach([$this->child->id, $this->sibling->id]);

        $fee = AcademyFee::create([
            'fee_name' => 'Monthly Tuition', 'age_or_level' => 'All levels', 'amount' => 50000,
            'schedule' => 'Monthly', 'is_published' => true, 'sort_order' => 1,
        ]);
        $this->receipt = $this->recordFee($this->child, $fee);
        $this->otherReceipt = $this->recordFee($this->otherChild, $fee);
    }

    private function userForRole(string $code): User
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        Person::create(['user_id' => $user->id, 'sac_role_id' => $role->id, 'first_name' => $code, 'last_name' => 'User']);

        return $user;
    }

    private function recordFee(Person $student, AcademyFee $fee): PaymentReceipt
    {
        $charge = StudentFeeCharge::create([
            'student_id' => $student->id, 'academy_fee_id' => $fee->id,
            'billing_month' => now()->startOfMonth()->toDateString(), 'fee_name' => $fee->fee_name,
            'amount_due_kobo' => 5000000, 'created_by' => $this->accountant->id,
        ]);
        $receipt = PaymentReceipt::create([
            'student_id' => $student->id, 'amount_kobo' => 1000000, 'paid_at' => now()->toDateString(),
            'payment_method' => 'cash', 'received_by' => $this->accountant->id,
        ]);
        FeePayment::create([
            'student_fee_charge_id' => $charge->id, 'payment_receipt_id' => $receipt->id,
            'amount_kobo' => 1000000, 'paid_at' => now()->toDateString(),
            'payment_method' => 'cash', 'received_by' => $this->accountant->id,
        ]);

        return $receipt;
    }

    public function test_dashboard_shows_only_linked_children_and_published_events(): void
    {
        Attendance::create([
            'child_id' => $this->child->id, 'attendance_date' => now()->toDateString(),
            'dropped_off_at' => now(), 'dropped_off_by_name' => 'Parent', 'dropped_off_recorded_by' => $this->accountant->id,
        ]);
        Event::create(['title' => 'School Event', 'starts_at' => now()->addDay(), 'is_published' => true]);
        Event::create(['title' => 'Private Draft Event', 'starts_at' => now()->addDay(), 'is_published' => false]);
        $walletReceipt = PaymentReceipt::create([
            'student_id' => $this->child->id, 'amount_kobo' => 2000000, 'paid_at' => now()->toDateString(),
            'payment_method' => 'cash', 'received_by' => $this->accountant->id,
        ]);
        StudentWalletTransaction::create([
            'student_id' => $this->child->id, 'type' => 'credit', 'amount_kobo' => 2000000,
            'payment_receipt_id' => $walletReceipt->id, 'created_by' => $this->accountant->id,
        ]);

        $this->actingAs($this->parentUser, config('backpack.base.guard'))->get(backpack_url('dashboard'))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('My children')->assertSee('Lucy Child')->assertSee('Daisy Child')
            ->assertSee('At school')->assertSee('40,000.00')->assertSee('20,000.00')
            ->assertSee('School Event')->assertDontSee('Private Draft Event')->assertDontSee('Private Child')
            ->assertDontSee('Job openings')->assertDontSee('Roles')->assertDontSee('My Documents');
    }

    public function test_parent_can_view_linked_child_and_download_their_documents(): void
    {
        $contact = PickupContact::create(['first_name' => 'Approved', 'last_name' => 'Guardian']);
        $this->child->pickupContacts()->attach($contact->id, [
            'slot' => 1, 'relationship' => 'Guardian', 'can_pick_up' => true, 'can_drop_off' => false,
        ]);
        $month = now()->format('Y-m');
        $this->actingAs($this->parentUser, config('backpack.base.guard'))
            ->get(route('parent.children.show', ['student' => $this->child->id, 'month' => $month]))
            ->assertOk()->assertSee('Lucy Child')->assertSee('Monthly Tuition')->assertSee('40,000.00')
            ->assertSee('Approved Guardian')->assertSee('Download invoice')->assertSee('Receipt')
            ->assertSee('data-label="Balance (NGN)"', false)->assertSee('data-label="Payments"', false);

        $invoice = $this->get(route('parent.children.invoice', ['student' => $this->child->id, 'month' => $month]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $invoice->getContent());
        $receipt = $this->get(route('parent.receipts.download', $this->receipt))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $receipt->getContent());
    }

    public function test_unrelated_children_and_documents_are_not_accessible(): void
    {
        $month = now()->format('Y-m');
        $this->actingAs($this->parentUser, config('backpack.base.guard'));
        $this->get(route('parent.children.show', $this->otherChild->id))->assertNotFound();
        $this->get(route('parent.children.photo', $this->otherChild->id))->assertNotFound();
        $this->get(route('parent.children.invoice', ['student' => $this->otherChild->id, 'month' => $month]))->assertNotFound();
        $this->get(route('parent.receipts.download', $this->otherReceipt))->assertNotFound();
        $this->get(route('student-fees.invoice', ['student' => $this->child->id, 'month' => $month]))->assertForbidden();
        $this->get(route('student-fees.receipts.download', $this->receipt))->assertForbidden();
    }

    public function test_child_photo_requires_a_current_guardian_link(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('people/child.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/6b8AAAAASUVORK5CYII='));
        $this->child->update(['image_path' => 'people/child.png']);
        $this->actingAs($this->parentUser, config('backpack.base.guard'));
        $this->get(route('parent.children.photo', $this->child->id))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->parentUser->person->children()->detach($this->child->id);
        $this->get(route('parent.children.photo', $this->child->id))->assertNotFound();
    }

    public function test_shared_guardian_and_removed_guardian_have_correct_access(): void
    {
        $secondParent = $this->userForRole(Person::ROLE_PARENT);
        $secondParent->person->children()->attach($this->child->id);
        $month = now()->format('Y-m');
        $this->actingAs($secondParent, config('backpack.base.guard'))
            ->get(route('parent.children.show', $this->child->id))->assertOk();

        $this->parentUser->person->children()->detach($this->child->id);
        $this->actingAs($this->parentUser, config('backpack.base.guard'));
        $this->get(route('parent.children.show', $this->child->id))->assertNotFound();
        $this->get(route('parent.children.invoice', ['student' => $this->child->id, 'month' => $month]))->assertNotFound();
        $this->get(route('parent.receipts.download', $this->receipt))->assertNotFound();
    }

    public function test_parent_account_is_read_only_and_personal(): void
    {
        $this->actingAs($this->parentUser, config('backpack.base.guard'))
            ->get(route('parent.account'))->assertOk()->assertSee($this->parentUser->email)
            ->assertDontSee($this->accountant->email);
        $this->get(route('person.my-account'))->assertForbidden();
        $this->actingAs($this->accountant, config('backpack.base.guard'))
            ->get(route('parent.account'))->assertForbidden();
    }

    public function test_parent_can_report_an_absence_and_administrators_can_see_it(): void
    {
        $date = now('Africa/Lagos')->addDay()->toDateString();
        $this->actingAs($this->parentUser, config('backpack.base.guard'))
            ->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => $date, 'reason' => 'Family appointment'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('absence_notices', [
            'child_id' => $this->child->id,
            'absence_date' => $date,
            'reported_by' => $this->parentUser->id,
            'reason' => 'Family appointment',
            'cancelled_at' => null,
        ]);
        $this->assertDatabaseCount('attendances', 0);
        $this->get(backpack_url('dashboard'))->assertOk()->assertSee('Reported absence')->assertSee('Family appointment');

        $admin = $this->userForRole(Person::ROLE_ADMIN);
        $this->actingAs($admin, config('backpack.base.guard'))
            ->get(backpack_url('attendance').'?date='.$date)
            ->assertOk()->assertSee('Reported absences')->assertSee('Lucy Child')->assertSee('Family appointment');
    }

    public function test_absence_reason_is_limited_and_escaped(): void
    {
        $date = now('Africa/Lagos')->addDay()->toDateString();
        $this->actingAs($this->parentUser, config('backpack.base.guard'));
        $this->post(route('parent.children.absences.store', $this->child->id), [
            'absence_date' => $date, 'reason' => str_repeat('x', 501),
        ])->assertSessionHasErrors('reason');
        $this->assertDatabaseCount('absence_notices', 0);

        $reason = '<script>alert(1)</script>';
        $this->post(route('parent.children.absences.store', $this->child->id), [
            'absence_date' => $date, 'reason' => $reason,
        ])->assertSessionHasNoErrors();
        $this->get(backpack_url('dashboard'))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee($reason, false);

        $admin = $this->userForRole(Person::ROLE_ADMIN);
        $this->actingAs($admin, config('backpack.base.guard'))
            ->get(backpack_url('attendance').'?date='.$date)->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee($reason, false);
    }

    public function test_absence_notice_requires_a_linked_child_and_valid_future_date(): void
    {
        $today = now('Africa/Lagos');
        $this->actingAs($this->parentUser, config('backpack.base.guard'));
        $this->post(route('parent.children.absences.store', $this->otherChild->id), ['absence_date' => $today->toDateString()])
            ->assertNotFound();
        $this->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => $today->copy()->subDay()->toDateString()])
            ->assertSessionHasErrors('absence_date');
        $this->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => 'not-a-date'])
            ->assertSessionHasErrors('absence_date');
        Attendance::create([
            'child_id' => $this->child->id, 'attendance_date' => $today->toDateString(),
            'dropped_off_at' => now(), 'dropped_off_by_name' => 'Parent', 'dropped_off_recorded_by' => $this->accountant->id,
        ]);
        $this->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => $today->toDateString()])
            ->assertSessionHasErrors('absence_date');
        $this->assertDatabaseCount('absence_notices', 0);
    }

    public function test_absence_notice_can_be_cancelled_and_reported_again(): void
    {
        $date = now('Africa/Lagos')->addDay()->toDateString();
        $this->actingAs($this->parentUser, config('backpack.base.guard'));
        $this->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => $date])
            ->assertSessionHasNoErrors();
        $notice = AbsenceNotice::query()->sole();
        $this->assertNull($notice->reason);
        $this->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => $date])
            ->assertSessionHasErrors('absence_date');
        $this->delete(route('parent.children.absences.cancel', [$this->otherChild->id, $notice->id]))->assertNotFound();
        $this->delete(route('parent.children.absences.cancel', [$this->child->id, $notice->id]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('absence_notices', ['id' => $notice->id, 'cancelled_by' => $this->parentUser->id]);
        $this->assertNotNull($notice->fresh()->cancelled_at);
        $this->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => $date])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('absence_notices', 2);
        $this->assertDatabaseHas('absence_notices', ['child_id' => $this->child->id, 'absence_date' => $date, 'cancelled_at' => null]);
    }

    public function test_attendance_takes_precedence_over_a_reported_absence(): void
    {
        $date = now('Africa/Lagos')->toDateString();
        $this->actingAs($this->parentUser, config('backpack.base.guard'))
            ->post(route('parent.children.absences.store', $this->child->id), ['absence_date' => $date])
            ->assertSessionHasNoErrors();
        Attendance::create([
            'child_id' => $this->child->id, 'attendance_date' => $date,
            'dropped_off_at' => now(), 'dropped_off_by_name' => 'Parent', 'dropped_off_recorded_by' => $this->accountant->id,
        ]);
        $this->get(backpack_url('dashboard'))->assertOk()->assertSee('At school');
        $admin = $this->userForRole(Person::ROLE_ADMIN);
        $this->actingAs($admin, config('backpack.base.guard'))
            ->get(backpack_url('attendance').'?date='.$date)
            ->assertOk()->assertSee('Lucy Child')->assertSee('Attendance recorded');
    }

    public function test_parent_without_children_sees_an_empty_state(): void
    {
        $parent = $this->userForRole(Person::ROLE_PARENT);
        $this->actingAs($parent, config('backpack.base.guard'))->get(backpack_url('dashboard'))
            ->assertOk()->assertSee('No children are linked to your account.')->assertDontSee('Lucy Child');
    }
}
