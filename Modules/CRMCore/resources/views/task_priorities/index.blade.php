@extends('layouts.layoutMaster')

@section('title', __('Manage Task Priorities'))

@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
    ['name' => __('Settings'), 'url' => '#']
  ];
@endphp

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
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
        ajax: @json(route('settings.taskPriorities.ajax')),
        store: @json(route('settings.taskPriorities.store')),
        updateOrder: @json(route('settings.taskPriorities.updateOrder')),
        getPriorityTemplate: @json(route('settings.taskPriorities.getPriorityAjax', ['task_priority' => ':id'])),
        updateTemplate: @json(route('settings.taskPriorities.update', ['task_priority' => ':id'])),
        destroyTemplate: @json(route('settings.taskPriorities.destroy', ['task_priority' => ':id']))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__('Tasks using this priority might be affected or prevent deletion!')),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        deleting: @json(__('Deleting...')),
        deleted: @json(__('Deleted!')),
        saving: @json(__('Saving...')),
        savePriority: @json(__('Save Priority')),
        success: @json(__('Success!')),
        error: @json(__('Error!')),
        validationError: @json(__('Validation Error')),
        unexpectedError: @json(__('An unexpected error occurred.')),
        addPriority: @json(__('Add Task Priority')),
        editPriority: @json(__('Edit Task Priority')),
        priorityCreated: @json(__('Task Priority created successfully.')),
        priorityUpdated: @json(__('Task Priority updated successfully.')),
        orderUpdated: @json(__('Order Updated')),
        fetchError: @json(__('Could not fetch priority details.')),
        correctErrors: @json(__('Please correct the errors.'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/settings-task-priorities.js']) {{-- Create this JS file next --}}
@endsection

@section('content')
<x-breadcrumb
  :title="__('Task Priorities')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Task Priorities') }}</h5>
      <button type="button" class="btn btn-primary" id="add-new-task-priority-btn">
        <i class="bx bx-plus"></i> {{ __('Add New Priority') }}
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="alert alert-info">
      <i class="bx bx-info-circle me-2"></i>
      {{ __('Drag to reorder priorities. Higher priorities appear at the top.') }}
    </div>
    @if($taskPriorities->count() > 0)
      <ul id="task-priority-list" class="list-group">
        @foreach($taskPriorities as $priority)
          <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $priority->id }}">
            <div>
              <i class="bx bx-grid-vertical me-2" style="cursor: move;" title="{{ __('Drag to reorder') }}"></i>
              <span class="badge me-2" style="background-color: {{ $priority->color ?? '#6c757d' }}; color: #fff; min-width: 20px;">&nbsp;</span>
              <strong>{{ $priority->name }}</strong>
              @if($priority->is_default) <span class="badge bg-label-primary ms-2">{{ __('Default') }}</span> @endif
            </div>
            <div>
              <button class="btn btn-sm btn-icon me-1 edit-task-priority" data-id="{{ $priority->id }}" title="{{ __('Edit') }}"><i class="bx bx-pencil"></i></button>
              @if(!$priority->is_default || $taskPriorities->count() == 1)
                <button class="btn btn-sm btn-icon text-danger delete-task-priority" data-id="{{ $priority->id }}" title="{{ __('Delete') }}"><i class="bx bx-trash"></i></button>
              @endif
            </div>
          </li>
        @endforeach
      </ul>
    @else
      <div class="alert alert-info">{{ __('No task priorities configured yet. Add one to get started.') }}</div>
    @endif
  </div>
</div>

  @include('crmcore::task_priorities._form') {{-- Offcanvas form partial --}}
@endsection
