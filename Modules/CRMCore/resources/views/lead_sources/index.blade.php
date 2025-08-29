@extends('layouts.layoutMaster')

@section('title', __('Lead Sources'))

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        ajax: @json(route('settings.leadSources.ajax')),
        store: @json(route('settings.leadSources.store')),
        getLeadSourceTemplate: @json(route('settings.leadSources.getLeadSourceAjax', ['lead_source' => ':id'])),
        updateTemplate: @json(route('settings.leadSources.update', ['lead_source' => ':id'])),
        destroyTemplate: @json(route('settings.leadSources.destroy', ['lead_source' => ':id'])),
        toggleStatusTemplate: @json(route('settings.leadSources.toggleStatus', ['lead_source' => ':id']))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__('This cannot be undone!')),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        deleting: @json(__('Deleting...')),
        success: @json(__('Success!')),
        deleted: @json(__('Deleted!')),
        error: @json(__('Error!')),
        updated: @json(__('Updated!')),
        saving: @json(__('Saving...')),
        validationError: @json(__('Validation Error')),
        correctErrors: @json(__('Please correct the errors.')),
        unexpectedError: @json(__('An unexpected error occurred.')),
        addLeadSource: @json(__('Add Lead Source')),
        editLeadSource: @json(__('Edit Lead Source')),
        save: @json(__('Save')),
        searchSources: @json(__('Search Sources...')),
        fetchError: @json(__('Could not fetch source details.')),
        operationFailed: @json(__('Operation failed.')),
        deleteError: @json(__('Could not delete source.')),
        statusUpdateError: @json(__('Failed to update status.'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/settings-lead-sources.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
    ['name' => __('Settings'), 'url' => '#']
  ];
@endphp

<x-breadcrumb
  :title="__('Lead Sources')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('crm.dashboard.index')"
/>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('All Lead Sources') }}</h5>
            <button type="button" class="btn btn-primary" id="add-new-source-btn">
                <i class="bx bx-plus"></i> {{ __('Add New Source') }}
            </button>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table table-bordered datatables-lead-sources" id="leadSourcesTable">
            <thead>
            <tr>
                <th>{{ __('ID') }}</th>
                <th>{{ __('Name') }}</th>
                <th class="text-center">{{ __('Status') }}</th>
                <th class="text-center">{{ __('Actions') }}</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

@include('crmcore::lead_sources._form')
@endsection
