@extends('layouts.layoutMaster')

@section('title', __('Manage Lead Statuses'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
  {{-- Add SortableJS CDN if not already global --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/SortableJS/1.15.6/Sortable.min.css" />
  {{-- Add a simple color picker if needed for the form e.g. https://github.com/Simonwep/pickr --}}
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
  <script src="
https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js
"></script>
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        store: @json(route('settings.leadStatuses.store')),
        getLeadStatusTemplate: @json(route('settings.leadStatuses.getLeadStatusAjax', ['lead_status' => ':id'])),
        updateTemplate: @json(route('settings.leadStatuses.update', ['lead_status' => ':id'])),
        destroyTemplate: @json(route('settings.leadStatuses.destroy', ['lead_status' => ':id'])),
        updateOrder: @json(route('settings.leadStatuses.updateOrder'))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        cancelButton: @json(__('Cancel')),
        deleting: @json(__('Deleting...')),
        deleted: @json(__('Deleted!')),
        success: @json(__('Success!')),
        error: @json(__('Error')),
        validationError: @json(__('Validation Error')),
        correctErrors: @json(__('Please correct the errors.')),
        unexpectedError: @json(__('An unexpected error occurred.')),
        couldNotFetch: @json(__('Could not fetch status details.')),
        operationFailed: @json(__('Operation failed.')),
        couldNotDelete: @json(__('Could not delete status.')),
        orderUpdated: @json(__('Order Updated!')),
        couldNotUpdateOrder: @json(__('Could not update order.')),
        failedToSaveOrder: @json(__('Failed to save new order.')),
        saving: @json(__('Saving...')),
        addLeadStatus: @json(__('Add Lead Status')),
        editLeadStatus: @json(__('Edit Lead Status')),
        saveStatus: @json(__('Save Status'))
      },
      statuses: @json($leadStatuses)
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/settings-lead-statuses.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
    ['name' => __('Settings'), 'url' => '#']
  ];
@endphp

<x-breadcrumb
  :title="__('Lead Statuses')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('All Lead Statuses') }}</h5>
        <button type="button" class="btn btn-primary" id="add-new-status-btn">
          <i class="bx bx-plus"></i> {{ __('Add New Status') }}
        </button>
      </div>
      <small class="text-muted mt-2 d-block">{{ __('Drag to reorder statuses for Kanban view') }}</small>
    </div>
    <div class="card-body">
      <ul id="status-list" class="list-group">
        {{-- Statuses will be rendered here by JS or can be pre-rendered --}}
        @foreach($leadStatuses as $status)
          <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $status->id }}">
            <div>
              <i class="bx bx-grid-vertical me-1" style="cursor: move;"></i>
              <span class="badge me-2" style="background-color: {{ $status->color ?? '#6c757d' }}; color: #fff;">&nbsp;</span>
              <strong>{{ $status->name }}</strong>
              @if($status->is_default) <span class="badge bg-label-primary ms-2">{{ __('Default') }}</span> @endif
              @if($status->is_final) <span class="badge bg-label-info ms-2">{{ __('Final Stage') }}</span> @endif
            </div>
            <div>
              <button class="btn btn-sm btn-icon me-1 edit-lead-status" data-id="{{ $status->id }}" title="{{ __('Edit') }}"><i class="bx bx-pencil"></i></button>
              @if(!$status->is_default)
              <button class="btn btn-sm btn-icon text-danger delete-lead-status" data-id="{{ $status->id }}" title="{{ __('Delete') }}"><i class="bx bx-trash"></i></button>
              @endif
            </div>
          </li>
        @endforeach
      </ul>
      <small class="text-muted mt-2 d-block">{{ __('Note: Default status cannot be deleted. Final stages will not show on the main Kanban board for active leads.') }}</small>
    </div>
  </div>

  @include('crmcore::lead_statuses._form') {{-- Offcanvas form partial --}}
@endsection
