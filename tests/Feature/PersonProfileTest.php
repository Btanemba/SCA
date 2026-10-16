<?php

namespace Tests\Feature;

use App\Http\Requests\PersonRequest;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonProfileTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $code): User
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        Person::create([
            'user_id' => $user->id,
            'sac_role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Employee',
            'account_number' => '0012345678',
        ]);

        return $user;
    }

    public static function profileRoles(): array
    {
        return [[Person::ROLE_ACCOUNTANT], [Person::ROLE_ADMIN]];
    }

    #[DataProvider('profileRoles')]
    public function test_own_profile_returns_to_dashboard(string $code): void
    {
        $user = $this->employee($code);
        $person = $user->person;
        $this->actingAs($user, config('backpack.base.guard'));

        $response = $this->get(backpack_url('person/'.$person->id.'/edit'));
        $response->assertOk();
        $response->assertSee('href="'.backpack_url('dashboard').'" class="btn btn-secondary text-decoration-none"', false);

        $this->put(backpack_url('person/'.$person->id), [
            'id' => $person->id,
            'first_name' => 'Updated',
            'last_name' => $person->last_name,
            'gender' => $person->gender,
            'date_of_birth' => '1990-01-01',
            'bank_name' => $person->bank_name,
            'account_name' => $person->account_name,
            'account_number' => $person->account_number,
            '_save_action' => 'save_and_back',
            '_http_referrer' => backpack_url('person'),
        ])->assertRedirect(backpack_url('dashboard'));

        $this->assertSame('Updated', $person->fresh()->first_name);
    }

    public function test_accountant_cannot_add_people(): void
    {
        $user = $this->employee(Person::ROLE_ACCOUNTANT);
        $this->actingAs($user, config('backpack.base.guard'));

        $this->get(backpack_url('person?role='.Person::ROLE_STAFF))
            ->assertOk()
            ->assertDontSee('person-create-role-dropdown', false);
        $this->get(backpack_url('person/create'))->assertForbidden();
        $this->post(backpack_url('person'), [
            'first_name' => 'Forbidden',
            'last_name' => 'Person',
            'email' => 'forbidden@example.com',
            'sac_role_id' => $user->person->sac_role_id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'forbidden@example.com']);
        $this->assertDatabaseCount('persons', 1);
    }

    public function test_student_picker_creates_a_student_and_parent_save_links_them(): void
    {
        $admin = $this->employee(Person::ROLE_ADMIN);
        $studentRole = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $parentRole = SacRole::firstOrCreate(['code' => Person::ROLE_PARENT], ['name' => 'Parent', 'order' => 1]);
        $this->actingAs($admin, config('backpack.base.guard'));

        $this->get(backpack_url('person/create?sac_role_id='.$parentRole->id))
            ->assertOk()
            ->assertSee('student-picker-toggle', false);

        $response = $this->postJson(backpack_url('person'), [
            '_student_picker' => true,
            'sac_role_id' => $studentRole->id,
            'first_name' => 'New',
            'last_name' => 'Student',
            'gender' => 'female',
            'date_of_birth' => '2020-01-01',
            'pickup_contacts' => [[
                'first_name' => 'Pickup',
                'last_name' => 'Contact',
                'relationship' => 'Mother',
                'email' => 'pickup@example.com',
                'phone' => '08012345678',
                'address' => '1 Test Street',
                'can_pick_up' => true,
            ]],
        ])->assertCreated()->assertJsonStructure(['id', 'student_id', 'first_name', 'last_name', 'details_url']);

        $student = Person::findOrFail($response->json('id'));
        $this->assertSame($studentRole->id, $student->sac_role_id);
        $this->assertNotEmpty($student->student_id);
        $this->assertNull($student->user_id);
        $this->assertCount(1, $student->pickupContactLinks);
        $this->assertCount(0, $student->guardians);
        $this->assertDatabaseHas('child_pickup_contacts', [
            'child_id' => $student->id,
            'relationship' => 'Mother',
        ]);
        $this->get(backpack_url('person/'.$student->id.'/edit'))
            ->assertOk()
            ->assertSee('<option value="Mother" selected>Mother</option>', false);

        $this->post(backpack_url('person'), [
            'sac_role_id' => $parentRole->id,
            'first_name' => 'New',
            'last_name' => 'Parent',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'email' => 'parent@example.com',
            'phone' => '08012345678',
            'Occupation' => 'Teacher',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'nationality' => 'Nigerian',
            'children' => json_encode([$student->id]),
            'guardian_relationships' => [$student->id => ['relationship' => 'Mother', 'is_primary_contact' => true]],
        ])->assertRedirect();

        $parent = Person::where('first_name', 'New')->where('last_name', 'Parent')->firstOrFail();
        $this->assertDatabaseHas('person_guardians', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relationship' => 'Mother',
            'is_primary_contact' => true,
        ]);
    }

    public function test_student_picker_preserves_student_validation_and_create_permissions(): void
    {
        $studentRole = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $admin = $this->employee(Person::ROLE_ADMIN);
        $this->actingAs($admin, config('backpack.base.guard'));

        $this->postJson(backpack_url('person'), [
            '_student_picker' => true,
            'sac_role_id' => $studentRole->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['first_name', 'last_name', 'gender', 'date_of_birth', 'pickup_contacts']);
        $this->assertDatabaseCount('persons', 1);

        $accountant = $this->employee(Person::ROLE_ACCOUNTANT);
        $this->actingAs($accountant, config('backpack.base.guard'));
        $this->postJson(backpack_url('person'), [
            '_student_picker' => true,
            'sac_role_id' => $studentRole->id,
        ])->assertForbidden();
        $this->assertDatabaseCount('persons', 2);
    }

    public function test_pickup_contacts_require_one_complete_contact_not_two(): void
    {
        $role = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $completeContact = [
            'first_name' => 'Pickup',
            'last_name' => 'Contact',
            'relationship' => 'Mother',
            'email' => 'pickup@example.com',
            'phone' => '08012345678',
            'address' => '1 Test Street',
        ];
        $blankContact = [
            'first_name' => null,
            'last_name' => null,
            'relationship' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'remove_image' => '0',
            'can_pick_up' => '1',
            'can_drop_off' => '1',
        ];
        $validate = function (array $contacts) use ($role) {
            $request = new PersonRequest([
                'sac_role_id' => $role->id,
                'first_name' => 'Test',
                'last_name' => 'Student',
                'gender' => 'female',
                'date_of_birth' => '2020-01-01',
                'pickup_contacts' => $contacts,
            ]);
            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);

            return $validator;
        };

        $this->assertTrue($validate([$completeContact])->passes());
        $this->assertTrue($validate([$completeContact, $blankContact])->passes());
        $this->assertTrue($validate([$blankContact, $completeContact])->passes());
        $this->assertTrue($validate([$completeContact, array_replace($blankContact, ['remove_image' => '1'])])->passes());

        $partial = $validate([$completeContact, array_replace($blankContact, ['first_name' => 'Partial'])]);
        $this->assertTrue($partial->fails());
        $this->assertTrue($partial->errors()->has('pickup_contacts.1.last_name'));
        $this->assertTrue($partial->errors()->has('pickup_contacts.1.relationship'));
        $this->assertFalse($partial->errors()->has('pickup_contacts.0.first_name'));

        $empty = $validate([$blankContact, $blankContact]);
        $this->assertTrue($empty->fails());
        $this->assertTrue($empty->errors()->has('pickup_contacts'));
    }

    public static function rolesRequiringContactDetails(): array
    {
        return [[Person::ROLE_PARENT], [Person::ROLE_STAFF]];
    }

    #[DataProvider('rolesRequiringContactDetails')]
    public function test_parents_and_staff_require_contact_and_personal_details(string $code): void
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $request = new PersonRequest([
            'sac_role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => 'Person',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Person',
            'account_number' => '0012345678',
        ]);
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);
        $this->assertTrue($validator->fails());

        foreach (['email', 'phone', 'Occupation', 'city', 'state', 'nationality'] as $field) {
            $this->assertTrue($validator->errors()->has($field), $field.' must be required for '.$code);
        }
        $this->assertFalse($validator->errors()->has('alt_email'));
        $this->assertFalse($validator->errors()->has('alt_phone'));

        $request->merge([
            'email' => 'person@example.com',
            'phone' => '08012345678',
            'Occupation' => 'Teacher',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'nationality' => 'Nigerian',
        ]);
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);
        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    #[DataProvider('rolesRequiringContactDetails')]
    public function test_parent_and_staff_forms_mark_contact_and_personal_details_required(string $code): void
    {
        $admin = $this->employee(Person::ROLE_ADMIN);
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $person = Person::create([
            'sac_role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => 'Person',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
        ]);
        $this->actingAs($admin, config('backpack.base.guard'));

        foreach (['person/create?sac_role_id='.$role->id, 'person/'.$person->id.'/edit'] as $path) {
            $response = $this->get(backpack_url($path))->assertOk();
            foreach (['email', 'phone', 'Occupation', 'city', 'state', 'nationality'] as $field) {
                $this->assertMatchesRegularExpression(
                    '/<input\b(?=[^>]*\bname="'.preg_quote($field, '/').'")(?=[^>]*\brequired(?:\s|=|>))[^>]*>/i',
                    $response->getContent()
                );
            }
        }
    }

    public function test_other_roles_do_not_require_parent_and_staff_details(): void
    {
        foreach ([Person::ROLE_STUDENT, Person::ROLE_ADMIN, Person::ROLE_ACCOUNTANT, Person::ROLE_SECURITY, Person::ROLE_FOUNDER] as $code) {
            $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
            $request = new PersonRequest(['sac_role_id' => $role->id]);
            $validator = Validator::make($request->all(), $request->rules());
            $validator->fails();

            foreach (['email', 'phone', 'Occupation', 'city', 'state', 'nationality'] as $field) {
                $this->assertFalse($validator->errors()->has($field), $field.' must remain optional for '.$code);
            }
        }
    }

    public function test_other_profile_keeps_people_return_url_and_admin_can_add_people(): void
    {
        $admin = $this->employee(Person::ROLE_ADMIN);
        $staff = $this->employee(Person::ROLE_STAFF);
        $this->actingAs($admin, config('backpack.base.guard'));

        $this->get(backpack_url('person/'.$staff->person->id.'/edit'))
            ->assertOk()
            ->assertSee('href="'.backpack_url('person').'" class="btn btn-secondary text-decoration-none"', false);
        $this->get(backpack_url('person'))
            ->assertOk()
            ->assertSee('person-create-role-dropdown', false);
        $this->get(backpack_url('person/create'))->assertOk();
    }
}
