<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SacRoleRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class SacRoleCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SacRoleCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\SacRole::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sac-role');
        CRUD::setEntityNameStrings('Springcare Academy Role', 'Springcare Academy Roles');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->orderBy('order');

          CRUD::addColumn([
            'name' => 'custom_actions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.custom_button',
            'orderable' => false,
            'searchable' => false,
            'visibleInExport' => false,
        ]);
        CRUD::setFromDb(); // set columns from db columns.

        foreach (['created_by' => 'Created by', 'updated_by' => 'Updated by'] as $column => $label) {
            CRUD::column($column)->label($label)->type('closure')
                ->function(fn ($entry) => $entry->{$column.'_name'})
                ->searchable(false)->orderable(false);
        }
        $this->crud->with(['creator.person', 'updater.person']);


        $this->crud->removeButton('preview');
        $this->crud->removeButton('update');
        $this->crud->removeButton('revisions');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');
        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SacRoleRequest::class);

        CRUD::field('name')->type('text');
        CRUD::field('code')->type('text');
        CRUD::field('order')->type('number');
        foreach ([
            'created_by_name' => 'Created by',
            'created_at' => 'Created at',
            'updated_by_name' => 'Updated by',
            'updated_at' => 'Updated at',
        ] as $name => $label) {
            CRUD::field($name)->label($label)->type('text')
                ->wrapper(['class' => 'form-group col-md-3'])
                ->attributes(['readonly' => 'readonly', 'style' => 'background-color: #e5e5e5;']);
        }
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupShowOperation(): void
    {
        $this->autoSetupShowOperation();

        foreach (['created_by' => 'Created by', 'updated_by' => 'Updated by'] as $column => $label) {
            CRUD::column($column)->label($label)->type('closure')
                ->function(fn ($entry) => $entry->{$column.'_name'});
        }
        $this->crud->with(['creator.person', 'updater.person']);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
