<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ManagementMemberRequest;
use App\Models\ManagementMember;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ManagementMemberCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(ManagementMember::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/management-member');
        CRUD::setEntityNameStrings('management member', 'management members');
    }

    protected function setupListOperation(): void
    {
        CRUD::orderBy('sort_order');
        CRUD::orderBy('last_name');
        CRUD::addColumn([
            'name' => 'custom_actions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.custom_button',
            'orderable' => false,
            'searchable' => false,
            'visibleInExport' => false,
        ]);

        CRUD::addColumn([
            'name' => 'image_path',
            'label' => 'Photo',
            'type' => 'image',
            'disk' => 'public',
            'height' => '48px',
            'width' => '48px',
        ]);
        CRUD::column('first_name')->label('First name');
        CRUD::column('last_name')->label('Last name');
        CRUD::column('title');
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
        CRUD::column('first_name')->label('First name');
        CRUD::column('last_name')->label('Last name');
        CRUD::column('title');
        CRUD::column('description');
        CRUD::addColumn([
            'name' => 'image_path',
            'label' => 'Photo',
            'type' => 'image',
            'disk' => 'public',
        ]);
        CRUD::column('sort_order')->label('Display order');
        CRUD::column('is_published')->type('boolean')->label('Published');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ManagementMemberRequest::class);

        CRUD::field('first_name')->type('text')->label('First name')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('last_name')->type('text')->label('Last name')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('title')->type('text')->wrapper(['class' => 'form-group col-md-12']);
        CRUD::field('description')->type('textarea')->attributes(['rows' => 6]);
        CRUD::addField([
            'name' => 'image_path',
            'label' => 'Photo',
            'type' => 'upload',
            'withFiles' => true,
            'disk' => 'public',
            'attributes' => ['accept' => 'image/jpeg,image/png,image/webp'],
        ]);
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
