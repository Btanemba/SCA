<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StaffMemberRequest;
use App\Models\Person;
use App\Models\StaffMember;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StaffMemberCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(StaffMember::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/staff-member');
        CRUD::setEntityNameStrings('staff member', 'staff members');
    }

    protected function setupListOperation(): void
    {
        CRUD::addClause('with', 'person');
        CRUD::orderBy('sort_order');
        CRUD::orderBy('person_id');
        CRUD::addColumn([
            'name' => 'custom_actions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.custom_button',
            'orderable' => false,
            'searchable' => false,
            'visibleInExport' => false,
        ]);
        CRUD::column('staff_name')->label('Staff member');
        CRUD::column('sort_order')->label('Display order');
        CRUD::column('is_published')->type('boolean')->label('Published');
        $this->crud->removeButton('preview');
        $this->crud->removeButton('update');
        $this->crud->removeButton('revisions');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('staff_name')->label('Staff member');
        CRUD::column('description');
        CRUD::column('sort_order')->label('Display order');
        CRUD::column('is_published')->type('boolean')->label('Published');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(StaffMemberRequest::class);
        CRUD::addField([
            'name' => 'person_id',
            'label' => 'Staff member',
            'type' => 'select_from_array',
            'options' => Person::query()
                ->whereHas('sacRole', fn ($query) => $query->whereIn('code', [Person::ROLE_STAFF, Person::ROLE_ADMIN]))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn (Person $person) => [$person->id => $person->full_name])
                ->all(),
            'allows_null' => false,
        ]);
        CRUD::field('description')->type('textarea')->attributes(['rows' => 6]);
        CRUD::field('sort_order')
            ->type('number')
            ->label('Display order')
            ->default(0)
            ->attributes(['min' => 0, 'max' => 65535, 'step' => 1]);
        CRUD::field('is_published')->type('switch')->label('Published')->default(false);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
