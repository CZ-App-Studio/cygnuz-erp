@extends('layouts.layoutMaster')

@section('title', __('Task Statuses'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/SortableJS/1.15.6/Sortable.min.css" />
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        store: @json(route('settings.taskStatuses.store')),
        getTaskStatusTemplate: @json(route('settings.taskStatuses.getTaskStatusAjax', ['task_status' => ':id'])),
        updateTemplate: @json(route('settings.taskStatuses.update', ['task_status' => ':id'])),
        destroyTemplate: @json(route('settings.taskStatuses.destroy', ['task_status' => ':id'])),
        updateOrder: @json(route('settings.taskStatuses.updateOrder'))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__('Tasks using this status might be affected or prevent deletion!')),
        confirmButtonText: @json(__('Yes, delete it!')),
        cancelButtonText: @json(__('Cancel')),
        deleting: @json(__('Deleting...')),
        deleted: @json(__('Deleted!')),
        error: @json(__('Error!')),
        success: @json(__('Success!')),
        saving: @json(__('Saving...')),
        orderUpdated: @json(__('Order Updated!')),
        addNewStatus: @json(__('Add New Status')),
        editStatus: @json(__('Edit Task Status')),
        saveStatus: @json(__('Save Status'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/settings-task-statuses.js'])
@endsection

@section('content')
  @php
    $breadcrumbs = [
      ['name' => __('Settings'), 'url' => '#'],
      ['name' => __('CRM Settings'), 'url' => '#']
    ];
  @endphp

  <x-breadcrumb
    :title="__('Task Statuses')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('All Task Statuses') }}</h5>
        <button type="button" class="btn btn-primary" id="add-new-task-status-btn">
          <i class="bx bx-plus"></i> {{ __('Add New Status') }}
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="alert alert-info">
        <i class="bx bx-info-circle me-2"></i>
        {{ __('Drag to reorder statuses') }}
      </div>
      @if($taskStatuses->count() > 0)
        <ul id="task-status-list" class="list-group">
          @foreach($taskStatuses as $status)
            <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $status->id }}">
              <div>
                <i class="bx bx-grid-vertical me-2" style="cursor: move;" title="@lang('Drag to reorder')"></i>
                <span class="badge me-2" style="background-color: {{ $status->color ?? '#6c757d' }}; color: #fff; min-width: 20px;">&nbsp;</span>
                <strong>{{ $status->name }}</strong>
                @if($status->is_default) <span class="badge bg-label-primary ms-2">@lang('Default')</span> @endif
                @if($status->is_completed_status) <span class="badge bg-label-success ms-2">@lang('Is Completed')</span> @endif
              </div>
              <div>
                <button class="btn btn-sm btn-icon me-1 edit-task-status" data-id="{{ $status->id }}" title="@lang('Edit')"><i class="bx bx-pencil"></i></button>
                @if(!$status->is_default || $taskStatuses->count() == 1)
                  <button class="btn btn-sm btn-icon text-danger delete-task-status" data-id="{{ $status->id }}" title="@lang('Delete')"><i class="bx bx-trash"></i></button>
                @endif
              </div>
            </li>
          @endforeach
        </ul>
      @else
        <div class="alert alert-info">@lang('No task statuses configured yet. Add one to get started.')</div>
      @endif
      <div class="alert alert-secondary mt-3">
        <i class="bx bx-info-circle me-2"></i>
        <small>{{ __('Note: "Is Completed" statuses signify that a task is finished.') }}</small>
      </div>
    </div>
  </div>

  @include('crmcore::task_statuses._form') {{-- Offcanvas form partial --}}
@endsection
