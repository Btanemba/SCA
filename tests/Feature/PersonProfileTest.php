<?php

namespace Tests\Feature;

use App\Http\Requests\PersonRequest;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonProfileTest extends TestCase
{
    use RefreshDatabase;

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a/s8AAAAASUVORK5CYII='
        ));
    }

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

    public function test_student_forms_cancel_to_the_student_list(): void
    {
        $admin = $this->employee(Person::ROLE_ADMIN);
        $studentRole = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $student = Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'OnlyStudent',
            'last_name' => 'Example',
            'gender' => 'female',
            'date_of_birth' => '2020-01-01',
        ]);
        $otherPerson = $this->employee(Person::ROLE_PARENT)->person;
        $otherPerson->update(['first_name' => 'NotAStudent']);
        $this->actingAs($admin, config('backpack.base.guard'));
        $listUrl = backpack_url('person?role='.Person::ROLE_STUDENT);

        foreach (['person/create?sac_role_id='.$studentRole->id, 'person/'.$student->id.'/edit'] as $path) {
            $this->get(backpack_url($path))
                ->assertOk()
                ->assertSee('href="'.$listUrl.'" class="btn btn-secondary text-decoration-none"', false);
        }

        $this->get($listUrl)->assertOk();
        $response = $this->postJson(backpack_url('person/search?role='.Person::ROLE_STUDENT), [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ])->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertSee('OnlyStudent')->assertDontSee('NotAStudent');
    }

    public function test_student_pickup_form_offers_linked_parent_details(): void
    {
        $admin = $this->employee(Person::ROLE_ADMIN);
        $parent = $this->employee(Person::ROLE_PARENT)->person;
        $parent->update([
            'first_name' => 'PickupParent',
            'last_name' => 'Example',
            'phone' => '08012345678',
            'address_line_1' => '12 Parent Street',
            'city' => 'Lagos',
            'state' => 'Lagos State',
            'postal_code' => '100001',
            'country' => 'Nigeria',
            'nationality' => 'Nigerian',
            'state_of_origin' => 'Ogun',
            'lga' => 'Abeokuta South',
        ]);
        $studentRole = SacRole::firstOrCreate(['code' => Person::ROLE_STUDENT], ['name' => 'Student', 'order' => 1]);
        $student = Person::create([
            'sac_role_id' => $studentRole->id,
            'first_name' => 'Child',
            'last_name' => 'Example',
            'gender' => 'female',
            'date_of_birth' => '2020-01-01',
        ]);
        $student->guardians()->attach($parent->id, ['relationship' => 'Mother']);
        $this->actingAs($admin, config('backpack.base.guard'));

        $this->get(backpack_url('person/'.$student->id.'/edit'))
            ->assertOk()
            ->assertSee('This parent will also pick up the child')
            ->assertSee('pickup-contact-1-use-parent', false)
            ->assertSee('pickup-contact-2-use-parent', false)
            ->assertSee('student-use-parent-details', false)
            ->assertSee('Use parent\'s details', false)
            ->assertSee('"address_line_1":"12 Parent Street"', false)
            ->assertSee('"city":"Lagos"', false)
            ->assertSee('"state":"Lagos State"', false)
            ->assertSee('"postal_code":"100001"', false)
            ->assertSee('"country":"Nigeria"', false)
            ->assertSee('"nationality":"Nigerian"', false)
            ->assertSee('"state_of_origin":"Ogun"', false)
            ->assertSee('"lga":"Abeokuta South"', false)
            ->assertSee('"first_name":"PickupParent"', false)
            ->assertSee('"email":"'.$parent->user->email.'"', false)
            ->assertSee('"phone":"08012345678"', false)
            ->assertSee('"address":"12 Parent Street"', false)
            ->assertSee('"relationship":"Mother"', false);

        $this->get(backpack_url('person/create?sac_role_id='.$studentRole->id))
            ->assertOk()
            ->assertSee('pickup-contact-1-use-parent', false)
            ->assertSee('student-use-parent-details', false)
            ->assertDontSee('"first_name":"PickupParent"', false);

        $this->get(backpack_url('person/'.$parent->id.'/edit'))
            ->assertOk()
            ->assertDontSee('student-use-parent-details', false);
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
        Storage::fake('public');
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
            'image_path' => $this->photo(),
            'pickup_contacts' => [[
                'first_name' => 'Pickup',
                'last_name' => 'Contact',
                'relationship' => 'Mother',
                'email' => 'pickup@example.com',
                'phone' => '08012345678',
                'address' => '1 Test Street',
                'can_pick_up' => true,
                'image' => $this->photo(),
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
            'image_path' => $this->photo(),
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
            'image' => $this->photo(),
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
            $request->files->set('pickup_contacts', array_map(
                fn ($contact) => isset($contact['image']) ? ['image' => $contact['image']] : [],
                $contacts
            ));
            $request->files->set('image_path', $this->photo());
            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);

            return $validator;
        };

        $this->assertTrue($validate([$completeContact])->passes());
        $this->assertTrue($validate([$completeContact, $blankContact])->passes());
        $this->assertTrue($validate([$blankContact, $completeContact])->passes());
        $this->assertTrue($validate([$completeContact, array_replace($blankContact, ['remove_image' => '1'])])->passes());

        $withoutPhoto = $completeContact;
        unset($withoutPhoto['image']);
        $missingPhoto = $validate([$withoutPhoto]);
        $this->assertTrue($missingPhoto->fails());
        $this->assertTrue($missingPhoto->errors()->has('pickup_contacts.0.image'));

        $partial = $validate([$completeContact, array_replace($blankContact, ['first_name' => 'Partial'])]);
        $this->assertTrue($partial->fails());
        $this->assertTrue($partial->errors()->has('pickup_contacts.1.last_name'));
        $this->assertTrue($partial->errors()->has('pickup_contacts.1.relationship'));
        $this->assertFalse($partial->errors()->has('pickup_contacts.0.first_name'));

        $empty = $validate([$blankContact, $blankContact]);
        $this->assertTrue($empty->fails());
        $this->assertTrue($empty->errors()->has('pickup_contacts'));
    }

    public function test_saved_photos_satisfy_requirements_until_removed(): void
    {
        $parent = $this->employee(Person::ROLE_PARENT)->person;
        $parent->update(['image_path' => 'parents/saved.png']);
        $child = $this->employee(Person::ROLE_STUDENT)->person;
        $contact = \App\Models\PickupContact::create([
            'first_name' => 'Pickup',
            'last_name' => 'Contact',
            'image_path' => 'pickup-contacts/saved.png',
        ]);
        $child->pickupContactLinks()->create([
            'slot' => 1,
            'pickup_contact_id' => $contact->id,
            'relationship' => 'Mother',
            'can_pick_up' => true,
            'can_drop_off' => true,
        ]);

        $validate = function (Person $person, array $input) {
            $request = new PersonRequest(array_merge(['sac_role_id' => $person->sac_role_id], $input));
            $route = new \Illuminate\Routing\Route('PUT', 'person/{id}', []);
            $route->bind(\Illuminate\Http\Request::create('/person/'.$person->id, 'PUT'));
            $request->setRouteResolver(fn () => $route);
            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);
            $validator->fails();

            return $validator->errors();
        };

        foreach ([$parent, $child] as $person) {
            $person->update(['image_path' => 'people/saved.png']);
            $this->assertFalse($validate($person, [])->has('image_path'));
            $this->assertTrue($validate($person, ['image_path' => null])->has('image_path'));
            $person->update(['image_path' => null]);
            $this->assertTrue($validate($person, [])->has('image_path'));
        }

        $details = [
            'first_name' => 'Pickup',
            'last_name' => 'Contact',
            'relationship' => 'Mother',
            'email' => 'pickup@example.com',
            'phone' => '08012345678',
            'address' => '1 Test Street',
        ];
        $this->assertFalse($validate($child, ['pickup_contacts' => [$details]])->has('pickup_contacts.0.image'));
        $this->assertTrue($validate($child, [
            'pickup_contacts' => [array_merge($details, ['remove_image' => '1'])],
        ])->has('pickup_contacts.0.image'));
        $contact->update(['image_path' => null]);
        $this->assertTrue($validate($child, ['pickup_contacts' => [$details]])->has('pickup_contacts.0.image'));
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
        if ($code === Person::ROLE_PARENT) {
            $request->files->set('image_path', $this->photo());
        }
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
