@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => '#'],
    ['name' => __('Tasks'), 'url' => '']
  ];
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Tasks'))

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/moment/moment.js'
  ])
@endsection

@section('page-style')
  <style>
    .kanban-board {
      overflow-x: auto;
      min-height: 70vh;
    }

    .kanban-column {
      min-width: 300px;
      max-width: 350px;
      background: var(--bs-gray-100);
      border: 1px solid var(--bs-border-color);
      border-radius: 8px;
      padding: 1rem;
      margin-right: 1rem;
      flex-shrink: 0;
    }

    [data-bs-theme="dark"] .kanban-column {
      background: var(--bs-dark);
      border-color: var(--bs-border-color);
    }

    .kanban-header {
      border-bottom: 2px solid var(--bs-border-color);
      padding-bottom: 0.5rem;
      margin-bottom: 1rem;
    }

    .kanban-stage {
      min-height: 400px;
      padding-top: 0.5rem;
    }

    .kanban-task {
      cursor: move;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      border: 1px solid var(--bs-border-color);
      background: var(--bs-card-bg);
      margin-bottom: 0.75rem;
      position: relative;
    }

    .kanban-task:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border-color: var(--bs-primary);
      z-index: 10;
    }

    [data-bs-theme="dark"] .kanban-task:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      border-color: var(--bs-primary);
    }

    .kanban-task .dropdown-menu.show {
      position: absolute !important;
      z-index: 9999 !important;
      transform: translate3d(0px, 0px, 0px) !important;
    }

    .kanban-column {
      overflow: visible;
    }

    .kanban-tasks {
      position: relative;
      z-index: 1;
    }

    .sortable-chosen {
      opacity: 0.5;
    }

    .sortable-ghost {
      opacity: 0.3;
      background: var(--bs-secondary-bg);
      border: 2px dashed var(--bs-secondary);
    }

    .kanban-task .dropdown-toggle::after {
      display: none;
    }

    @media (max-width: 768px) {
      .kanban-board {
        flex-direction: column;
      }

      .kanban-column {
        min-width: 100%;
        margin-right: 0;
        margin-bottom: 1rem;
      }
    }
  </style>
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        dataTableAjax: @json(route('tasks.dataTableAjax')),
        kanbanAjax: @json(route('tasks.kanbanAjax')),
        store: @json(route('tasks.store')),
        getTaskTemplate: @json(route('tasks.getTaskAjax', ['task' => ':id'])),
        updateTemplate: @json(route('tasks.update', ['task' => ':id'])),
        destroyTemplate: @json(route('tasks.destroy', ['task' => ':id'])),
        updateKanbanStatusTemplate: @json(route('tasks.updateKanbanStatus', ['task' => ':id'])),
        userSearch: @json(route('users.selectSearch')),
        relatedTo: @json($relatedToUrls ?? [])
      },
      taskStatuses: @json($taskStatuses->mapWithKeys(function ($status) {
                return [$status->id => ['name' => $status->name, 'color' => $status->color, 'is_completed' => $status->is_completed_status]];
            }) ?? []),
      taskPriorities: @json($taskPriorities ?? []),
      labels: {
        addNewTask: @json(__('Add New Task')),
        editTask: @json(__('Edit Task')),
        saveTask: @json(__('Save Task')),
        saving: @json(__('Saving...')),
        success: @json(__('Success!')),
        error: @json(__('Error')),
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__("You won't be able to revert this!")),
        deleteButton: @json(__('Yes, delete it!')),
        cancelButton: @json(__('Cancel')),
        deleting: @json(__('Deleting...')),
        deleted: @json(__('Deleted!')),
        edit: @json(__('Edit')),
        viewDetails: @json(__('View Details')),
        delete: @json(__('Delete')),
        markCompleted: @json(__('Mark as Completed')),
        confirmComplete: @json(__('Mark as Completed?')),
        completeWarning: @json(__('Are you sure you want to mark this task as completed?')),
        yesComplete: @json(__('Yes, Complete it!')),
        completed: @json(__('Completed!')),
        taskCompleted: @json(__('Task has been marked as completed.')),
        errorCompletingTask: @json(__('Could not complete the task.')),
        assignedTo: @json(__('Assigned to')),
        unassigned: @json(__('Unassigned')),
        selectUser: @json(__('Select User')),
        searchPlaceholder: @json(__('Search Tasks...')),
        couldNotUpdateStatus: @json(__('Could not update status.')),
        unexpectedError: @json(__('An unexpected error occurred.')),
        couldNotFetchTask: @json(__('Could not fetch task details.')),
        pleaseCorrectErrors: @json(__('Please correct the errors below.')),
        couldNotDeleteTask: @json(__('Could not delete task.')),
        dueDate: @json(__('Due Date')),
        priority: @json(__('Priority')),
        relatedTo: @json(__('Related To')),
        overdue: @json(__('Overdue')),
        markComplete: @json(__('Mark Complete')),
        completed: @json(__('Completed')),
        allStatuses: @json(__('All Statuses')),
        allPriorities: @json(__('All Priorities')),
        anyUser: @json(__('Any User')),
        selectStatus: @json(__('Select Status')),
        selectPriority: @json(__('Select Priority')),
        selectType: @json(__('Select Type')),
        selectRecord: @json(__('Select Record')),
        selectTypeFirst: @json(__('Select Type First...')),
        searchAndSelect: @json(__('Search & Select'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/tasks-index.js'])
@endsection

@section('content')
  <x-breadcrumb
    :title="__('Tasks')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  {{-- Kanban View Container --}}
  <div id="kanban-view-container" class="d-none">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Task Board') }}</h5>
          <div class="d-flex gap-2">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-primary active" id="btn-kanban-view-top" title="{{ __('Kanban View') }}">
                <i class="bx bx-layout"></i>
              </button>
              <button type="button" class="btn btn-outline-primary" id="btn-list-view-top" title="{{ __('List View') }}">
                <i class="bx bx-list-ul"></i>
              </button>
            </div>
            <button type="button" class="btn btn-primary" id="add-new-task-btn-kanban">
              <i class="bx bx-plus"></i> {{ __('Add New Task') }}
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="kanban-board d-flex gap-3 p-3">
          @foreach($taskStatuses as $status)
            @if(!$status->is_completed_status)
              <div class="kanban-column">
                <div class="kanban-header d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">
                    <span class="badge" style="background-color: {{ $status->color ?? '#6c757d' }};">{{ $status->name }}</span>
                  </h6>
                  <span class="text-muted small task-count" data-status="{{ $status->id }}">0</span>
                </div>
                <div id="kanban-stage-{{ $status->id }}" class="kanban-stage" data-status-id="{{ $status->id }}">
                  <!-- Tasks will be populated here -->
                </div>
              </div>
            @endif
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- List View Container --}}
  <div id="datatable-view-container">
    {{-- Filters Card --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label for="filter_task_status_id" class="form-label">{{ __('Status') }}</label>
            <select id="filter_task_status_id" class="form-select select2-filter">
              <option value="">{{ __('All Statuses') }}</option>
              @foreach($taskStatuses as $status)
                <option value="{{ $status->id }}">{{ $status->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label for="filter_task_priority_id" class="form-label">{{ __('Priority') }}</label>
            <select id="filter_task_priority_id" class="form-select select2-filter">
              <option value="">{{ __('All Priorities') }}</option>
              @foreach($taskPriorities as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label for="filter_assigned_to_user_id" class="form-label">{{ __('Assigned To') }}</label>
            <select id="filter_assigned_to_user_id" class="form-select select2-users-filter"
                    data-placeholder="{{ __('Any User') }}"
                    data-ajax--url="{{ route('users.selectSearch') }}">
              <option value="">{{ __('Any User') }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="filter_due_date_range" class="form-label">{{ __('Due Date Range') }}</label>
            <input type="text" id="filter_due_date_range" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('All Tasks') }}</h5>
          <div class="d-flex gap-2">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-primary" id="btn-kanban-view" title="{{ __('Kanban View') }}">
                <i class="bx bx-layout"></i>
              </button>
              <button type="button" class="btn btn-outline-primary active" id="btn-list-view" title="{{ __('List View') }}">
                <i class="bx bx-list-ul"></i>
              </button>
            </div>
            <button type="button" class="btn btn-primary" id="add-new-task-btn">
              <i class="bx bx-plus"></i> {{ __('Add New Task') }}
            </button>
          </div>
        </div>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table table-bordered" id="tasksTable">
          <thead>
            <tr>
              <th>{{ __('ID') }}</th>
              <th>{{ __('Title') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Priority') }}</th>
              <th>{{ __('Related To') }}</th>
              <th>{{ __('Assigned To') }}</th>
              <th>{{ __('Due Date') }}</th>
              <th class="text-center">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @include('crmcore::tasks._form')
@endsection
