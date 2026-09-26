<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PersonRequest;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Hash;
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
        $this->syncGuardianRelationshipsFromRequest($this->data['entry'] ?? null);

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

    // Students never get a login account; everyone else does, keyed by email
    protected function syncUserFromRequest(?Person $entry = null): void
    {
        $email = request('email');
        $role = SacRole::find(request('sac_role_id'));

        if (! $email || $role?->code === Person::ROLE_STUDENT) {
            return;
        }

        $user = $entry?->user;

        if ($user) {
            $user->update(['email' => $email]);
        } else {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => trim(request('first_name').' '.request('last_name')),
                    'password' => Hash::make(Str::random(32)),
                ]
            );
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

    protected function setupListOperation(): void
    {
        $roleCode = request()->query('role');
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

        $entry = CRUD::getCurrentEntry();
        $selectedRoleId = request()->input('sac_role_id') ?? ($entry ? $entry->sac_role_id : null);
        $selectedRole = $selectedRoleId ? SacRole::find($selectedRoleId) : null;

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
            CRUD::addField([
                'name' => 'email',
                'label' => 'Email',
                'type' => 'text',
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
