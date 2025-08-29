@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => '#'],
    ['name' => __('Leads'), 'url' => '']
  ];
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Leads'))

@section('vendor-style')
  {{-- Vite for local assets --}}
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
      'resources/assets/vendor/libs/select2/select2.scss',
  ])
@endsection

@section('vendor-script')
  {{-- Vite for local assets --}}
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
      'resources/assets/vendor/libs/select2/select2.js',
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

    .kanban-lead {
      cursor: move;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      border: 1px solid var(--bs-border-color);
      background: var(--bs-card-bg);
      margin-bottom: 0.75rem;
    }

    .kanban-lead:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border-color: var(--bs-primary);
    }

    [data-bs-theme="dark"] .kanban-lead:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      border-color: var(--bs-primary);
    }

    .sortable-chosen {
      opacity: 0.5;
    }

    .sortable-ghost {
      opacity: 0.3;
      background: var(--bs-secondary-bg);
      border: 2px dashed var(--bs-secondary);
    }

    .kanban-lead .dropdown-toggle::after {
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
        dataTableAjax: @json(route('leads.dataTableAjax')),
        kanbanAjax: @json(route('leads.kanbanAjax')),
        store: @json(route('leads.store')),
        getLeadTemplate: @json(route('leads.getLeadAjax', ['lead' => ':id'])),
        updateTemplate: @json(route('leads.update', ['lead' => ':id'])),
        destroyTemplate: @json(route('leads.destroy', ['lead' => ':id'])),
        updateKanbanStageTemplate: @json(route('leads.updateKanbanStage', ['lead' => ':id'])),
        userSearch: @json(route('users.selectSearch'))
      },
      leadSources: @json($leadSources ?? []),
      leadStatuses: @json($leadStatuses->mapWithKeys(function ($status) {
                return [$status->id => ['name' => $status->name, 'color' => $status->color]];
            }) ?? []),
      labels: {
        addNewLead: @json(__('Add New Lead')),
        editLead: @json(__('Edit Lead')),
        saveLead: @json(__('Save Lead')),
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
        assignedTo: @json(__('Assigned to')),
        selectUser: @json(__('Select User')),
        searchPlaceholder: @json(__('Search Leads...')),
        couldNotUpdateStage: @json(__('Could not update stage.')),
        unexpectedError: @json(__('An unexpected error occurred.')),
        couldNotFetchLead: @json(__('Could not fetch lead details.')),
        pleaseCorrectErrors: @json(__('Please correct the errors below.')),
        couldNotDeleteLead: @json(__('Could not delete lead.'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/leads-list.js'])
@endsection

@section('content')
  <x-breadcrumb
    :title="__('Leads')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div id="kanban-view-container" class="d-none">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Lead Pipeline') }}</h5>
          <div class="d-flex gap-2">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-primary active" id="btn-kanban-view-top" title="{{ __('Kanban View') }}">
                <i class="bx bx-layout"></i>
              </button>
              <button type="button" class="btn btn-outline-primary" id="btn-list-view-top" title="{{ __('List View') }}">
                <i class="bx bx-list-ul"></i>
              </button>
            </div>
            <button type="button" class="btn btn-primary" id="add-new-lead-btn-kanban">
              <i class="bx bx-plus"></i> {{ __('Add New Lead') }}
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="kanban-board d-flex gap-3 p-3">
          @foreach($leadStatuses as $status)
            @if(!$status->is_final)
              <div class="kanban-column">
                <div class="kanban-header d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">
                    <span class="badge" style="background-color: {{ $status->color ?? '#6c757d' }};">{{ $status->name }}</span>
                  </h6>
                  <span class="text-muted small lead-count" data-status="{{ $status->id }}">0</span>
                </div>
                <div id="kanban-stage-{{ $status->id }}" class="kanban-stage" data-status-id="{{ $status->id }}">
                  <!-- Leads will be populated here -->
                </div>
              </div>
            @endif
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div id="datatable-view-container">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('All Leads') }}</h5>
          <div class="d-flex gap-2">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-primary" id="btn-kanban-view" title="{{ __('Kanban View') }}"><i class="bx bx-layout"></i></button>
              <button type="button" class="btn btn-outline-primary active" id="btn-list-view" title="{{ __('List View') }}"><i class="bx bx-list-ul"></i></button>
            </div>
            <button type="button" class="btn btn-primary" id="add-new-lead-btn">
              <i class="bx bx-plus"></i> {{ __('Add New Lead') }}
            </button>
          </div>
        </div>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table table-bordered" id="leadsTable">
          <thead>
            <tr>
              <th>{{ __('ID') }}</th>
              <th>{{ __('Title') }}</th>
              <th>{{ __('Contact') }}</th>
              <th>{{ __('Value') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Assigned To') }}</th>
              <th>{{ __('Created At') }}</th>
              <th class="text-center">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @include('crmcore::leads._form')
@endsection
