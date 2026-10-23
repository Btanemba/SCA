<?php

namespace Tests\Feature;

use App\Http\Requests\PersonRequest;
use App\Models\AcademySetting;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_blank_registration_pdf(): void
    {
        Storage::fake('local');
        AcademySetting::updateOrCreate(['id' => 1], [
            'name' => 'Springcare Test Academy',
            'address' => '1 School Road',
            'email' => 'admissions@example.com',
            'phone' => '08012345678',
        ]);

        $response = $this->get(route('admissions.registration-form'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertSame(3, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));

        $this->get(route('admissions.overview'))
            ->assertOk()
            ->assertSee(route('admissions.registration-form'), false)
            ->assertSee('Download Registration Form')
            ->assertSee('id="how-to-apply"', false);
    }

    public function test_print_template_has_letterhead_photo_spaces_and_authorizations(): void
    {
        $html = view('admissions.registration-form', [
            'academy' => [
                'name' => 'Springcare Test Academy',
                'address' => '1 School Road',
                'phone' => '08012345678',
                'email' => 'admissions@example.com',
                'website' => null,
                'logo' => 'data:image/png;base64,TEST',
            ],
            'sections' => config('registration'),
        ])->render();

        foreach (['Springcare Test Academy', '1 School Road', 'admissions@example.com',
            'data:image/png;base64,TEST', 'Parent / Guardian Details', 'Student Details',
            'Authorized Pickup / Drop-off Contacts', 'Can pick up', 'Can drop off',
            'passport photograph', 'Office Use Only', 'Parent / guardian signature'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('signature_path', $html);
        $this->assertSame(1, substr_count($html, '<section class="sheet">'));
        $this->assertSame(2, substr_count($html, '<section class="sheet sheet-new-page">'));
    }

    public function test_shared_fields_appear_in_online_and_paper_forms(): void
    {
        config([
            'registration.student.fields.registration_note' => ['label' => 'Additional registration note', 'required' => true],
            'registration.pickup.fields.contact_note' => ['label' => 'Additional contact note'],
        ]);
        $role = SacRole::firstOrCreate(['code' => Person::ROLE_ADMIN], ['name' => 'Admin', 'order' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        Person::create([
            'user_id' => $user->id,
            'sac_role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => 'Admin',
        ]);
        $studentRole = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $this->actingAs($user, config('backpack.base.guard'));

        $this->get(backpack_url('person/create?sac_role_id='.$studentRole->id))
            ->assertOk()
            ->assertSee('Additional registration note')
            ->assertSee('Additional contact note');
        foreach ([Person::ROLE_PARENT, Person::ROLE_STUDENT, Person::ROLE_STAFF, Person::ROLE_ADMIN, Person::ROLE_ACCOUNTANT, Person::ROLE_SECURITY, Person::ROLE_FOUNDER, null] as $code) {
            if ($code !== null) {
                SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
            }
            $response = $this->get(backpack_url('person').($code !== null ? '?role='.$code : ''))->assertOk();
            if (in_array($code, [Person::ROLE_PARENT, Person::ROLE_STUDENT], true)) {
                $response->assertSee('Print Registration Form');
            } else {
                $response->assertDontSee('Print Registration Form');
            }
        }

        $request = new PersonRequest(['sac_role_id' => $studentRole->id]);
        $this->assertContains('required', $request->rules()['registration_note']);
        $this->assertArrayHasKey('pickup_contacts.*.contact_note', $request->rules());

        $viewData = [
            'academy' => ['name' => 'Test Academy', 'address' => null, 'phone' => null, 'email' => null, 'website' => null, 'logo' => null],
            'sections' => config('registration'),
        ];
        $html = view('admissions.registration-form', $viewData)->render();
        $this->assertStringContainsString('Additional registration note', $html);
        $this->assertStringContainsString('Additional contact note', $html);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admissions.registration-form', $viewData);
        $this->assertStringStartsWith('%PDF-', $pdf->setPaper('a4')->output());
    }
}
