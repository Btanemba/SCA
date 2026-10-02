<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\JobOpeningRequest;
use App\Models\JobOpening;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class JobOpeningCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(JobOpening::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/job-opening');
        CRUD::setEntityNameStrings('job opening', 'job openings');
    }

    protected function setupListOperation(): void
    {
        CRUD::orderBy('created_at', 'desc');
         CRUD::addColumn([
            'name' => 'custom_actions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.custom_button',
            'orderable' => false,
            'searchable' => false,
            'visibleInExport' => false,
        ]);

        CRUD::column('title');
        CRUD::column('department');
        CRUD::column('employment_type')->label('Type');
        CRUD::column('location');
        CRUD::column('closes_at')->type('date')->label('Closes');
        CRUD::column('is_published')->type('boolean')->label('Published');

        CRUD::addColumn([
            'name' => 'applications_count',
            'label' => 'Applications',
            'type' => 'text',
            'orderable' => false,
            'searchable' => false,
        ]);

        CRUD::addClause('withCount', 'applications');
         $this->crud->removeButton('preview');
        $this->crud->removeButton('update');
        $this->crud->removeButton('revisions');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('title');
        CRUD::column('slug');
        CRUD::column('department');
        CRUD::column('employment_type')->label('Type');
        CRUD::column('location');
        CRUD::column('salary_range')->label('Salary range');
        CRUD::column('closes_at')->type('date')->label('Closes');
        CRUD::column('is_published')->type('boolean')->label('Published');
        CRUD::column('summary');
        CRUD::column('description');
        CRUD::column('requirements');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(JobOpeningRequest::class);

        CRUD::field('title')->type('text')->wrapper(['class' => 'form-group col-md-8']);
        CRUD::field('slug')
            ->type('text')
            ->hint('Leave blank to generate it from the title.')
            ->wrapper(['class' => 'form-group col-md-4']);

        CRUD::field('department')->type('text')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('employment_type')
            ->type('select_from_array')
            ->options(JobOpening::EMPLOYMENT_TYPES)
            ->allows_null(true)
            ->label('Employment type')
            ->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('location')->type('text')->wrapper(['class' => 'form-group col-md-4']);

        CRUD::field('salary_range')->type('text')->label('Salary range')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('closes_at')->type('date')->label('Closing date')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('is_published')->type('switch')->label('Published')->wrapper(['class' => 'form-group col-md-4']);

        CRUD::field('summary')
            ->type('textarea')
            ->hint('Short teaser shown on the jobs listing page.')
            ->attributes(['rows' => 3]);
        CRUD::field('description')->type('textarea')->attributes(['rows' => 10]);
        CRUD::field('requirements')->type('textarea')->attributes(['rows' => 8]);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
