@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.list') => false,
  ];

  // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('content')
  {{-- Default box --}}
  <div class="row" bp-section="crud-operation-list">

    {{-- THE ACTUAL CONTENT --}}
    <div class="{{ $crud->getListContentClass() }}">

      <x-backpack::datatable :controller="$controller" :crud="$crud" :modifiesUrl="true" />

    </div>

  </div>

@endsection

@push('after_styles')
  <style>
    .student-fee-row--unpaid > td,
    .student-fee-row--part-paid > td { background-color: #ffe066 !important; background-image: none !important; }
    .student-fee-row--paid > td { background-color: #d1e7dd !important; background-image: none !important; }
    .student-fee-row--not-charged > td { background-color: #fff !important; background-image: none !important; }
  </style>
@endpush

@push('after_scripts')
  <script>
    (() => {
      const applyStudentFeeRowColors = () => {
        document.querySelectorAll('[data-student-fee-status]').forEach(status => {
          const row = status.closest('tr');
          if (!row) return;

          row.classList.remove('student-fee-row--unpaid', 'student-fee-row--part-paid', 'student-fee-row--paid', 'student-fee-row--not-charged');
          row.classList.add(`student-fee-row--${status.dataset.studentFeeStatus}`);
        });
      };

      if (window.jQuery) {
        window.jQuery(document).on('draw.dt', applyStudentFeeRowColors);
      }
      applyStudentFeeRowColors();
    })();
  </script>
@endpush
