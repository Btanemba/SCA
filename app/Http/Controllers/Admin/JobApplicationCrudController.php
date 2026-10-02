<?php

namespace App\Http\Controllers\Admin;

use App\Models\JobApplication;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class JobApplicationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(JobApplication::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/job-application');
        CRUD::setEntityNameStrings('application', 'applications');

        // Applications only ever arrive through the public careers form
        CRUD::denyAccess('create');
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

        $openingId = request()->query('job_opening');
        if (is_numeric($openingId)) {
            CRUD::addClause('where', 'job_opening_id', (int) $openingId);
        }

        CRUD::column('created_at')->type('datetime')->label('Received');
        CRUD::column('jobOpening.title')->label('Job opening');
        CRUD::column('first_name')->label('First name');
        CRUD::column('last_name')->label('Last name');
        CRUD::column('email');
        CRUD::column('phone');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'select_from_array',
            'options' => JobApplication::STATUSES,
        ]);
        CRUD::addColumn([
            'name' => 'resume_path',
            'label' => 'CV',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.job_application_resume',
            'orderable' => false,
            'searchable' => false,
            'visibleInExport' => false,
        ]);
         $this->crud->removeButton('preview');
        $this->crud->removeButton('update');
        $this->crud->removeButton('revisions');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');
    }

    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        CRUD::column('cover_letter')->label('Cover letter');
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation([
            'status' => ['required', 'in:'.implode(',', array_keys(JobApplication::STATUSES))],
        ]);

        CRUD::field('status')
            ->type('select_from_array')
            ->options(JobApplication::STATUSES)
            ->allows_null(false);
    }

    public function downloadResume(int $id): BinaryFileResponse
    {
        $application = JobApplication::findOrFail($id);
        $disk = Storage::disk('local');

        abort_if(! $application->resume_path || ! $disk->exists($application->resume_path), 404);

        $extension = pathinfo($application->resume_path, PATHINFO_EXTENSION);
        $filename = str($application->full_name.'-cv')->slug().'.'.$extension;

        return response()->download($disk->path($application->resume_path), $filename);
    }
}
