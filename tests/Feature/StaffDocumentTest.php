<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\SacRole;
use App\Models\StaffDocument;
use App\Models\User;
use App\Notifications\StaffDocumentPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $founder;

    private User $staff;

    private User $otherStaff;

    private User $student;

    private User $administrator;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->founder = $this->employee(Person::ROLE_FOUNDER, 'Founder');
        $this->staff = $this->employee(Person::ROLE_STAFF, 'Teacher');
        $this->otherStaff = $this->employee(Person::ROLE_STAFF, 'Other');
        $this->student = $this->employee(Person::ROLE_STUDENT, 'Student');
        $this->administrator = $this->employee(Person::ROLE_ADMIN, 'Administrator');
        $this->accountant = $this->employee(Person::ROLE_ACCOUNTANT, 'Accountant');
    }

    private function employee(string $code, string $name): User
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

    public function test_founder_can_create_publish_and_staff_can_download_a_branded_document(): void
    {
        $this->login($this->founder)->post(route('staff-documents.store'), [
            'person_id' => $this->staff->person->id,
            'title' => 'Employment Contract',
            'document_type' => 'contract',
            'body' => "Dear Teacher Employee,\nWe are pleased to offer you employment.",
            'requires_staff_signature' => '1',
        ])->assertRedirect();

        $document = StaffDocument::firstOrFail();
        $preview = $this->get(route('staff-documents.preview', $document));
        $preview->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $preview->getContent());

        $this->post(route('staff-documents.publish', $document))->assertRedirect();
        $this->assertNotNull($document->fresh()->published_at);
        Notification::assertSentTo($this->staff, StaffDocumentPublished::class);

        $this->login($this->staff)->get(route('staff-documents.mine'))
            ->assertOk()
            ->assertSee('Employment Contract');
        $response = $this->get(route('staff-documents.download', $document));
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_only_founder_can_manage_documents_and_staff_cannot_access_another_persons_or_unpublished_document(): void
    {
        $this->login($this->founder)->post(route('staff-documents.store'), [
            'person_id' => $this->staff->person->id,
            'title' => 'Query Letter',
            'document_type' => 'query',
            'body' => 'Please respond to the matter discussed.',
        ])->assertRedirect();
        $document = StaffDocument::firstOrFail();

        $this->login($this->staff)->get(route('staff-documents.download', $document))->assertForbidden();
        $this->post(route('staff-documents.publish', $document))->assertForbidden();
        $this->login($this->otherStaff)->get(route('staff-documents.download', $document))->assertForbidden();
        $this->login($this->staff)->get(route('staff-documents.mine'))
            ->assertOk()
            ->assertDontSee('Query Letter');
        $this->login($this->student)->get(route('staff-documents.mine'))->assertForbidden();
        $this->login($this->founder)->post(route('staff-documents.store'), [
            'person_id' => $this->student->person->id,
            'title' => 'Not allowed',
            'document_type' => 'other',
            'body' => 'Student recipients are excluded.',
        ])->assertSessionHasErrors('person_id');
        $this->login($this->otherStaff)->get(route('staff-documents.create'))->assertForbidden();
    }

    public function test_administrator_and_accountant_accounts_are_valid_recipients_but_students_and_founders_are_not(): void
    {
        $this->login($this->founder)->get(route('staff-documents.create'))
            ->assertOk()
            ->assertSee($this->administrator->email)
            ->assertSee($this->accountant->email)
            ->assertDontSee($this->student->email)
            ->assertDontSee($this->founder->email);

        $this->post(route('staff-documents.store'), [
            'person_id' => $this->founder->person->id,
            'title' => 'Founder document',
            'document_type' => 'other',
            'body' => 'Founders are not document recipients.',
        ])->assertSessionHasErrors('person_id');
    }
}
