<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EventCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Event::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/event');
        CRUD::setEntityNameStrings('event', 'events');
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
        CRUD::orderBy('starts_at', 'desc');
        CRUD::column('title');
        CRUD::column('starts_at')->type('datetime')->label('Starts at');
        CRUD::column('ends_at')->type('datetime')->label('Ends at');
        CRUD::column('location');
        // CRUD::column('images')->type('upload_multiple')->label('Photos')->disk('public');
        CRUD::column('is_published')->type('boolean')->label('Published');

        $this->crud->removeButton('preview');
        $this->crud->removeButton('update');
        $this->crud->removeButton('revisions');
        $this->crud->removeButton('delete');
        $this->crud->removeButton('show');
    }


    protected function setupShowOperation(): void
    {
        CRUD::column('title');
        CRUD::column('starts_at')->type('datetime')->label('Starts at');
        CRUD::column('ends_at')->type('datetime')->label('Ends at');
        CRUD::column('location');
        CRUD::column('description');
        CRUD::column('images')->type('upload_multiple')->label('Photos')->disk('public');
        CRUD::column('is_published')->type('boolean')->label('Published');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(EventRequest::class);

        CRUD::field('title')->type('text')->wrapper(['class' => 'form-group col-md-8']);
        CRUD::field('location')->type('text')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::field('starts_at')
            ->type('datetime')
            ->label('Starts at')
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('ends_at')
            ->type('datetime')
            ->label('Ends at')
            ->hint('Optional. Leave blank for a single-time event.')
            ->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('description')->type('textarea')->attributes(['rows' => 5]);
        CRUD::addField([
            'name' => 'images',
            'label' => 'Event photos',
            'type' => 'upload_multiple',
            'withFiles' => true,
            'disk' => 'public',
            'prefix' => 'events',
            'attributes' => ['accept' => 'image/jpeg,image/png,image/webp'],
            'hint' => 'Upload up to 8 photos. You can remove individual photos when editing.',
        ]);
        CRUD::field('is_published')->type('switch')->label('Published')->default(false);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
