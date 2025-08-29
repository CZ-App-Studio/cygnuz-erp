@extends('layouts.layoutMaster')

@section('title', __('Manage Deal Pipelines'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/SortableJS/1.15.6/Sortable.min.css" />
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('settings.dealPipelines.datatable')),
        store: @json(route('settings.dealPipelines.store')),
        getPipelineTemplate: @json(route('settings.dealPipelines.getPipelineAjax', ['deal_pipeline' => ':id'])),
        updateTemplate: @json(route('settings.dealPipelines.update', ['deal_pipeline' => ':id'])),
        destroyTemplate: @json(route('settings.dealPipelines.destroy', ['deal_pipeline' => ':id'])),
        toggleStatusTemplate: @json(route('settings.dealPipelines.toggleStatus', ['deal_pipeline' => ':id'])),
        updateOrder: @json(route('settings.dealPipelines.updateOrder')),
        manageStagesUrl: @json(route('settings.dealStages.index', ['deal_pipeline' => ':id']))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__('Associated stages and deals might be affected or prevent deletion! This cannot be undone.')),
        deleteConfirm: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        deleting: @json(__('Deleting...')),
        deleted: @json(__('Deleted!')),
        success: @json(__('Success!')),
        error: @json(__('Error!')),
        validationError: @json(__('Validation Error')),
        orderUpdated: @json(__('Order Updated!')),
        saving: @json(__('Saving...')),
        unexpectedError: @json(__('An unexpected error occurred.'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/settings-deal-pipelines.js'])
@endsection

@section('content')
  {{-- Breadcrumb --}}
  @php
    $breadcrumbs = [
      ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
      ['name' => __('Settings'), 'url' => '#']
    ];
  @endphp

  <x-breadcrumb
    :title="__('Deal Pipelines')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('crm.dashboard.index')"
  />

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('All Deal Pipelines') }}</h5>
        <button type="button" class="btn btn-primary" id="add-new-pipeline-btn">
          <i class="bx bx-plus"></i> {{ __('Add New Pipeline') }}
        </button>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="table table-bordered" id="pipelineTable">
        <thead>
          <tr>
            <th>{{ __('Order') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Description') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  @include('crmcore::deal_pipelines._form')
@endsection
