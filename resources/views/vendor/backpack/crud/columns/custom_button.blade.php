@php
    $id = $entry->getKey();
@endphp

@if($id)
    <a href="{{ url($crud->route . '/' . $id . '/edit') }}"
       class="btn btn-sm btn-primary">
        <i class="la la-pencil"></i>
    </a>

    @include('crud::buttons.delete', ['crudTableId' => request()->input('datatable_id', 'crudTable')])
@else
    <span>-</span>
@endif
