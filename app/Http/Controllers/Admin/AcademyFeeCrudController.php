<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AcademyFeeRequest;
use App\Models\AcademyFee;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AcademyFeeCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup(): void
    {
        CRUD::setModel(AcademyFee::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/academy-fee');
        CRUD::setEntityNameStrings('fee', 'fees');
    }

    protected function setupListOperation(): void
    {
         CRUD::addColumn([
            'name' => 'custom_actions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.custom_button',
            'orderable' => false,
            'searchable' => false,
            'visibleInExport' => false,
        ]);
        CRUD::orderBy('sort_order')->orderBy('id');
        CRUD::column('fee_name')->label('Fee');
        CRUD::column('age_or_level')->label('Age or level');
        CRUD::column('amount')->label('Amount (N)');
        CRUD::column('schedule');
        CRUD::column('is_published')->type('boolean')->label('Published');
        CRUD::column('sort_order')->label('Display order');
          $this->crud->removeButton('preview');
        $this->crud->removeButton('update');
        $this->crud->removeButton('revisions');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(AcademyFeeRequest::class);
        CRUD::field('fee_name')->type('text')->label('Fee name');
        CRUD::field('age_or_level')->type('text')->label('Age or level');
        CRUD::field('amount')
            ->type('number')
            ->attributes(['min' => 0, 'step' => 1])
            ->hint('Leave blank to display "Coming soon".');
        CRUD::field('schedule')->type('text')->hint('For example: Monthly or Per session.');
        CRUD::field('sort_order')->type('number')->attributes(['min' => 0, 'step' => 1]);
        CRUD::field('is_published')->type('switch')->label('Visible on the public fees page');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}