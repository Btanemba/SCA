<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PersonRequest;
use App\Models\ChildPickupContact;
use App\Models\Person;
use App\Models\PickupContact;
use App\Models\SacRole;
use App\Models\User;
use App\Notifications\CompleteRegistrationInvitation;
use Illuminate\Support\Facades\Storage;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PersonCrudController extends CrudController
{
    private const GUARDIAN_RELATIONSHIP_OPTIONS = [
        'Mother' => 'Mother',
        'Father' => 'Father',
        'Stepparent' => 'Stepparent',
        'Adoptive parent' => 'Adoptive parent',
        'Grandparent' => 'Grandparent',
        'Aunt' => 'Aunt',
        'Uncle' => 'Uncle',
        'Sibling' => 'Sibling',
        'Foster parent' => 'Foster parent',
        'Legal guardian' => 'Legal guardian',
        'Other' => 'Other',
    ];

    private const PICKUP_RELATIONSHIP_OPTIONS = [
        'Mother' => 'Mother',
        'Father' => 'Father',
        'Stepparent' => 'Stepparent',
        'Grandparent' => 'Grandparent',
        'Aunt' => 'Aunt',
        'Uncle' => 'Uncle',
        'Sibling' => 'Sibling',
        'Foster parent' => 'Foster parent',
        'Legal guardian' => 'Legal guardian',
        'Nanny' => 'Nanny',
        'Family friend' => 'Family friend',
        'Driver' => 'Driver',
        'Other' => 'Other',
    ];

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Person::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person');
        CRUD::setEntityNameStrings('person', 'people');
    }

    public function store()
    {
        $this->syncUserFromRequest();

        $response = $this->traitStore();
        $entry = $this->data['entry'] ?? null;
        $this->syncGuardianRelationshipsFromRequest($entry);
        $this->syncPickupContactsFromRequest($entry);

        return $response;
    }

    public function update()
    {
        $entry = CRUD::getEntry(request()->route('id'));
        request()->merge(['sac_role_id' => $entry->sac_role_id]);

        $children = request()->input('children', []);
        if (is_string($children)) {
            $children = json_decode($children, true) ?? [];
        }
        if (! is_array($children)) {
            $children = [];
        }
        request()->merge([
            'children' => array_values(array_unique(array_merge(
                $entry->children()->allRelatedIds()->all(),
                $children
            ))),
        ]);

        $this->syncUserFromRequest($entry);

        $response = $this->traitUpdate();
        $this->syncGuardianRelationshipsFromRequest($this->data['entry'] ?? $entry);
        $this->syncPickupContactsFromRequest($this->data['entry'] ?? $entry);

        return $response;
    }

    public function searchStudents()
    {
        $term = trim((string) request()->query('term', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $parentId = request()->query('parent_id');
        $parent = $parentId ? Person::findOrFail($parentId) : null;

        if ($parent && $parent->sacRole?->code !== Person::ROLE_PARENT) {
            abort(404);
        }

        $students = Person::query()
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->where(function ($query) use ($term) {
                $query->where('student_id', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            })
            ->when($parent, fn ($query) => $query->whereDoesntHave(
                'guardians',
                fn ($guardians) => $guardians->where('persons.id', $parent->getKey())
            ))
            ->withCount('guardians')
            ->orderBy('student_id')
            ->limit(15)
            ->get(['id', 'student_id', 'first_name', 'last_name'])
            ->map(fn (Person $student) => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'has_other_guardians' => $student->guardians_count > 0,
                'details_url' => route('person.students.details', $student->getKey()),
            ]);

        return response()->json($students);
    }

    public function showStudentDetails(int $student)
    {
        $student = Person::query()
            ->whereKey($student)
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->firstOrFail();

        return view('vendor.backpack.crud.fields.student_details', compact('student'));
    }

    public function myAccount()
    {
        $person = backpack_user()?->person;

        if (! $person || $person->sacRole?->code === Person::ROLE_SECURITY) {
            return redirect(backpack_url('dashboard'));
        }

        return redirect(backpack_url('person/'.$person->getKey().'/edit'));
    }

    public function sendRegistrationInvite(int $id)
    {
        abort_unless($this->canManageAccounts(), 403);

        $person = Person::with(['user', 'sacRole'])->findOrFail($id);

        if ($person->sacRole?->code === Person::ROLE_STUDENT) {
            return response()->json(['message' => 'Students do not get login accounts.'], 422);
        }

        $user = $person->user;

        if (! $user?->email) {
            return response()->json(['message' => 'Add an email address for this person first.'], 422);
        }

        $token = Password::createToken($user);
        $user->notify(new CompleteRegistrationInvitation($token));

        $person->forceFill(['invited_at' => now()])->saveQuietly();

        return response()->json([
            'message' => 'Invitation sent to '.$user->email.'.',
        ]);
    }

    protected function canManageAccounts(): bool
    {
        return in_array(
            backpack_user()?->person?->sacRole?->code,
            Person::ROLES_MANAGE_ACCOUNTS,
            true
        );
    }

    // Students never get a login account; everyone else does, keyed by email
    protected function syncUserFromRequest(?Person $entry = null): void    {
        // Once an account exists the email is permanent, so ignore any attempt to change it.
        if ($entry?->user?->email) {
            request()->merge([
                'email' => $entry->user->email,
                'user_id' => $entry->user->getKey(),
            ]);

            return;
        }

        $email = request('email');
        $role = SacRole::find(request('sac_role_id'));

        if (! $email || $role?->code === Person::ROLE_STUDENT) {
            return;
        }

        $user = $entry?->user;
        $existingUser = User::query()
            ->where('email', $email)
            ->when($user, fn ($query) => $query->where('id', '<>', $user->getKey()))
            ->first();

        $linkedPerson = $existingUser
            ? Person::query()
                ->where('user_id', $existingUser->getKey())
                ->when($entry, fn ($query) => $query->where('persons.id', '<>', $entry->getKey()))
                ->first()
            : null;

        if ($linkedPerson) {
            throw ValidationException::withMessages([
                'email' => 'This email is already assigned to another person.',
            ]);
        }

        if ($user) {
            // A changed address is unproven until they open a fresh invitation link.
            $user->forceFill([
                'email' => $email,
                'email_verified_at' => $user->email === $email ? $user->email_verified_at : null,
            ])->save();
        } elseif ($existingUser) {
            $user = $existingUser;
        } else {
            $user = User::create([
                'email' => $email,
                'name' => trim(request('first_name').' '.request('last_name')),
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        request()->merge(['user_id' => $user->id]);
    }

    protected function syncGuardianRelationshipsFromRequest(?Person $parent): void
    {
        $relationships = request()->input('guardian_relationships', []);

        if (! $parent || $parent->sacRole?->code !== Person::ROLE_PARENT || ! is_array($relationships)) {
            return;
        }

        $linkedStudentIds = $parent->children()->allRelatedIds();

        foreach ($relationships as $studentId => $details) {
            if (! is_array($details) || ! $linkedStudentIds->contains($studentId)) {
                continue;
            }

            $relationship = trim((string) ($details['relationship'] ?? ''));
            $parent->children()->updateExistingPivot($studentId, [
                'relationship' => $relationship !== '' ? $relationship : null,
                'is_primary_contact' => filter_var(
                    $details['is_primary_contact'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                ),
            ]);
        }
    }

    protected function syncPickupContactsFromRequest(?Person $child): void
    {
        if (! $child || $child->sacRole?->code !== Person::ROLE_STUDENT) {
            return;
        }

        $submittedContacts = request()->input('pickup_contacts', []);
        if (! is_array($submittedContacts)) {
            $submittedContacts = [];
        }

        $existingContacts = $child->pickupContactLinks()->with('contact')->get()->keyBy('slot');
        $savedSlots = [];

        foreach ($submittedContacts as $index => $details) {
            $slot = ((int) $index) + 1;
            if ($slot > 2 || ! is_array($details)) {
                continue;
            }

            $firstName = trim((string) ($details['first_name'] ?? ''));
            $lastName = trim((string) ($details['last_name'] ?? ''));
            if ($firstName === '' || $lastName === '') {
                continue;
            }

            $existingLink = $existingContacts->get($slot);
            $contact = $existingLink?->contact;
            $email = filled($details['email'] ?? null) ? trim($details['email']) : null;
            $phone = filled($details['phone'] ?? null) ? trim($details['phone']) : null;
            $registeredPerson = null;

            if ($email) {
                $registeredPerson = Person::query()
                    ->where('persons.id', '<>', $child->getKey())
                    ->whereHas('user', fn ($query) => $query->where('email', $email))
                    ->first();
            }

            if (! $registeredPerson && $phone) {
                $registeredPerson = Person::query()
                    ->where('persons.id', '<>', $child->getKey())
                    ->where('phone', $phone)
                    ->first();
            }

            if (! $contact) {
                $contactQuery = PickupContact::query();

                if ($registeredPerson) {
                    $contactQuery->where('person_id', $registeredPerson->getKey());
                } elseif ($email) {
                    $contactQuery->where('email', $email);
                } elseif ($phone) {
                    $contactQuery->where('phone', $phone);
                } else {
                    $contactQuery = null;
                }

                $contact = $contactQuery?->first();
            }

            $contact ??= new PickupContact();
            $imagePath = $contact->image_path;
            $image = request()->file("pickup_contacts.{$index}.image");

            if ($image) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $image->store('pickup-contacts', 'public');
            } elseif (filter_var($details['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = null;
            }

            $contact->fill([
                'person_id' => $registeredPerson?->getKey() ?? $contact->person_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'image_path' => $imagePath,
                'address' => filled($details['address'] ?? null) ? trim($details['address']) : null,
                'email' => $email,
                'phone' => $phone,
            ])->save();

            $child->pickupContactLinks()->updateOrCreate(
                ['slot' => $slot],
                [
                    'pickup_contact_id' => $contact->getKey(),
                    'relationship' => filled($details['relationship'] ?? null) ? trim($details['relationship']) : null,
                    'can_pick_up' => filter_var($details['can_pick_up'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'can_drop_off' => filter_var($details['can_drop_off'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'created_by' => $existingLink?->created_by ?? backpack_user()?->getKey(),
                    'updated_by' => backpack_user()?->getKey(),
                ]
            );
            $savedSlots[] = $slot;
        }

        if ($savedSlots === []) {
            return;
        }

        $child->pickupContactLinks()
            ->when($savedSlots, fn ($query) => $query->whereNotIn('slot', $savedSlots))
            ->when(! $savedSlots, fn ($query) => $query)
            ->get()
            ->each(function (ChildPickupContact $contact) {
                if ($contact->image_path) {
                    Storage::disk('public')->delete($contact->image_path);
                }
                $contact->delete();
            });
    }

    protected function setupListOperation(): void
    {
        $ownPersonId = backpack_user()?->person?->getKey();
        if ($ownPersonId) {
            CRUD::addClause('where', 'persons.id', '<>', $ownPersonId);
        }

        $roleCode = request()->query('role');

        // Accountants only ever see staff, whatever the query string says.
        if (backpack_user()?->person?->sacRole?->code === Person::ROLE_ACCOUNTANT) {
            $roleCode = Person::ROLE_STAFF;
        }

        if (is_string($roleCode) && SacRole::query()->where('code', $roleCode)->exists()) {
            CRUD::addClause('whereHas', 'sacRole', fn ($query) => $query->where('code', $roleCode));
        }

        CRUD::removeButton('create');
        CRUD::addButtonFromView(
            'top',
            'create',
            'person_create'
        );

         CRUD::addColumn([
            'name' => 'custom_actions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.custom_button',
            'orderable' => false,
            'searchable' => false,
            'visibleInExport' => false,
        ]);
        CRUD::column('student_id')->label('Student ID');
        CRUD::column('first_name')->label('First name');
        CRUD::column('last_name')->label('Last name');
        CRUD::addColumn([
            'name' => 'image_path',
            'label' => 'Image',
            'type' => 'image',
            'disk' => 'public',
            'height' => '40px',
            'width' => '40px',
        ]);
        CRUD::column('gender');
        CRUD::column('sacRole.name')->label('Role');
        CRUD::column('date_of_birth')->type('date')->label('Date of birth');


        $this->crud->removeButton('preview');
        $this->crud->removeButton('update');
        $this->crud->removeButton('revisions');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(PersonRequest::class);

        // getCurrentEntry() returns false (not null) during create.
        $entry = CRUD::getCurrentEntry() ?: null;
        $selectedRoleId = request()->input('sac_role_id') ?? ($entry ? $entry->sac_role_id : null);
        $selectedRole = $selectedRoleId ? SacRole::find($selectedRoleId) : null;

        if ($entry && $entry->user_id === backpack_user()?->id) {
            CRUD::addField([
                'name' => 'my_payslips_link',
                'type' => 'custom_html',
                'value' => '<a class="btn btn-outline-primary" href="'.e(route('payroll.mine')).'"><i class="la la-file-invoice-dollar"></i> My Payslips</a>',
                'tab' => 'General',
            ]);
        }

        if ($entry && $entry->student_id) {
            CRUD::addField([
                'name' => 'student_id',
                'label' => 'Student ID',
                'type' => 'text',
                'wrapper' => ['class' => 'form-group col-md-9'],
                'attributes' => [
                    'readonly' => 'readonly',
                    'style' => 'background-color: #e5e5e5;',
                ],
                'tab' => 'General',
            ]);
        }

        CRUD::field('first_name')
        ->type('text')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('last_name')
        ->type('text')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('gender')
        ->type('select_from_array')->options([
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
        ])->wrapper(['class' => 'form-group col-md-4'])
        ->allows_null(true)
        ->tab('General');

        CRUD::field('date_of_birth')
        ->type('date')
        ->label('Date of birth')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

          CRUD::addField([
            'name' => 'image_path',
            'label' => 'Photo',
            'type' => 'upload',
            'withFiles' => true,
            'disk' => 'public',
            'attributes' => ['accept' => 'image/*'],
            'wrapper' => ['class' => 'form-group col-md-3'],
            'tab' => 'General',
        ]);

        CRUD::addField([
            'name' => 'photo_display',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.fields.photo_display',
            'wrapper' => ['class' => 'form-group col-md-3'],
            'tab' => 'General',
        ]);

        if ($selectedRole?->code !== Person::ROLE_STUDENT) {
            $lockedEmail = $entry?->user?->email;

            CRUD::addField([
                'name' => 'email',
                'label' => 'Email',
                'type' => 'text',
                'hint' => $lockedEmail ? 'The sign-in email cannot be changed once the account exists.' : null,
                'attributes' => $lockedEmail ? [
                    'readonly' => 'readonly',
                    'style' => 'background-color: #e5e5e5;',
                ] : [],
                'wrapper' => ['class' => 'form-group col-md-6'],
                'tab' => 'General',
            ]);
        }

        if ($selectedRole?->code !== Person::ROLE_STUDENT) {
            CRUD::addField([
                'name' => 'alt_email',
               'label' => 'Alternative Email',
                'type' => 'text',
                'wrapper' => ['class' => 'form-group col-md-6'],
                'tab' => 'General',
            ]);
            CRUD::field('phone')
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('General');

            CRUD::field('alt_phone')
            ->type('text')
            ->label('Alternative Phone')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('General');

            CRUD::field('Occupation')
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('General');
        }

        if (in_array($selectedRole?->code, [Person::ROLE_STAFF, Person::ROLE_ADMIN, Person::ROLE_ACCOUNTANT, Person::ROLE_SECURITY], true)) {
            CRUD::field('bank_name')
                ->type('text')
                ->label('Bank name')
                ->wrapper(['class' => 'form-group col-md-4'])
                ->tab('Banking');

            CRUD::field('account_name')
                ->type('text')
                ->label('Account name')
                ->wrapper(['class' => 'form-group col-md-4'])
                ->tab('Banking');

            CRUD::addField([
                'name' => 'account_number',
                'label' => 'Account number',
                'type' => 'text',
                'attributes' => ['inputmode' => 'numeric', 'maxlength' => 10, 'pattern' => '[0-9]{10}'],
                'wrapper' => ['class' => 'form-group col-md-4'],
                'tab' => 'Banking',
            ]);
        }

        CRUD::field('address_line_1')
        ->type('text')
        ->label('Home Address')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('city')
        ->type('text')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('state')
        ->type('text')
        ->label('State / province')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('postal_code')
        ->type('text')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('country')
        ->type('text')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('nationality')
        ->type('text')
        ->wrapper(['class' => 'form-group col-md-4'])
        ->tab('General');

        CRUD::field('state_of_origin')
        ->type('text')
        ->label('State of origin')
        ->wrapper(['class' => 'form-group col-md-6'])
        ->tab('General');

        CRUD::field('lga')
        ->type('text')
        ->label('Local government area')
        ->wrapper(['class' => 'form-group col-md-6'])
        ->tab('General');

        CRUD::addField([
            'name' => 'sac_role_display',
            'label' => 'Role',
            'type' => 'text',
            'default' => $selectedRole?->name,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'attributes' => [
                'readonly' => 'readonly',
                'style' => 'background-color: #e5e5e5;',
            ],
            'tab' => 'Role',
        ]);

        CRUD::addField([
            'name' => 'sac_role_id',
            'type' => 'hidden',
            'default' => request()->query('sac_role_id'),
            'tab' => 'Role',
        ]);

        CRUD::addField([
            'name' => 'user_id',
            'type' => 'hidden',
            'tab' => 'Role',
        ]);

        if ($selectedRole?->code === Person::ROLE_PARENT) {
            CRUD::addField([
                'name' => 'children',
                'label' => 'Student(s)',
                'type' => 'view',
                'view' => 'vendor.backpack.crud.fields.student_picker',
                'entity' => 'children',
                'model' => Person::class,
                'attribute' => 'full_name',
                'pivot' => true,
                'search_url' => route('person.students.search'),
                'parent_id' => $entry instanceof Person ? $entry->getKey() : null,
                'relationship_options' => self::GUARDIAN_RELATIONSHIP_OPTIONS,
                'options' => (function ($query) {
                    return $query->whereHas('sacRole', fn ($q) => $q->where('code', Person::ROLE_STUDENT));
                }),
                'tab' => 'Family',
            ]);
        }

        if ($entry instanceof Person && $selectedRole?->code === Person::ROLE_STUDENT) {
            $entry->loadMissing('guardians.user');

            CRUD::addField([
                'name' => 'guardian_details',
                'type' => 'view',
                'view' => 'vendor.backpack.crud.fields.guardian_details',
                'wrapper' => ['class' => 'form-group col-md-12'],
                'tab' => 'Parents/Guardians',
            ]);

            $entry->loadMissing('pickupContactLinks.contact');
        }

        if ($selectedRole?->code === Person::ROLE_STUDENT) {
            CRUD::addField([
                'name' => 'pickup_contacts',
                'label' => 'Pickup and drop-off contacts',
                'type' => 'view',
                'view' => 'vendor.backpack.crud.fields.child_pickup_contacts',
                'relationship_options' => self::PICKUP_RELATIONSHIP_OPTIONS,
                'wrapper' => ['class' => 'form-group col-md-12'],
                'tab' => 'Pickup and drop-off',
            ]);
        }

        if ($entry instanceof Person && $selectedRole?->code !== Person::ROLE_STUDENT && $this->canManageAccounts()) {
            $entry->loadMissing('user');

            CRUD::addField([
                'name' => 'registration_invite',
                'type' => 'view',
                'view' => 'vendor.backpack.crud.fields.registration_invite',
                'person' => $entry,
                'invite_url' => route('person.registration.invite', $entry->getKey()),
                'wrapper' => ['class' => 'form-group col-md-12'],
                'tab' => 'Security',
            ]);
        }


         CRUD::addField([
            'name' => 'created_by_name',
            'label' => 'Created by',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-3'],
            'attributes' => [
                'readonly' => 'readonly',
                'style' => 'background-color: #e5e5e5;',
            ],
            'tab' => 'General',

        ]);

        CRUD::addField([
            'name'  => 'created_at',
            'label' => 'Created at',
            'type'  => 'text',
            'wrapper' => ['class' => 'form-group col-md-3'],
            'attributes' => [
                'readonly' => 'readonly',
                'style' => 'background-color: #e5e5e5;',
            ],
            'tab' => 'General',
        ]);

        CRUD::addField([
            'name' => 'updated_by_name',
            'label' => 'Updated by',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-3'],
            'attributes' => [
                'readonly' => 'readonly',
                'style' => 'background-color: #e5e5e5;',
            ],
            'tab' => 'General',
        ]);

        CRUD::addField([
            'name'  => 'updated_at',
            'label' => 'Updated at',
            'type'  => 'text',
            'wrapper' => ['class' => 'form-group col-md-3'],
            'attributes' => [
                'readonly' => 'readonly',
                'style' => 'background-color: #e5e5e5;',
            ],
            'tab' => 'General',
        ]);

    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
