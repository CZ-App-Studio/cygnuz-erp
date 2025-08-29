@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => '#'],
    ['name' => __('Sales'), 'url' => '#']
  ];
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Sales Pipeline'))

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
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
      overflow: visible;
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

    .kanban-card {
      cursor: move;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      border: 1px solid var(--bs-border-color);
      background: var(--bs-card-bg);
      margin-bottom: 0.75rem;
      position: relative;
    }

    .kanban-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border-color: var(--bs-primary);
      z-index: 10;
    }

    [data-bs-theme="dark"] .kanban-card:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      border-color: var(--bs-primary);
    }

    .kanban-card .dropdown-menu.show {
      position: absolute !important;
      z-index: 9999 !important;
      transform: translate3d(0px, 0px, 0px) !important;
    }

    .kanban-stage {
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

    .kanban-card .dropdown-toggle::after {
      display: none;
    }

    .kanban-card .dropdown .btn-icon {
      background: transparent;
      border: none;
      color: var(--bs-body-color);
    }

    .kanban-card .dropdown .btn-icon:hover {
      background-color: var(--bs-gray-200);
      border-radius: 0.375rem;
    }

    [data-bs-theme="dark"] .kanban-card .dropdown .btn-icon:hover {
      background-color: var(--bs-gray-700);
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

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/moment/moment.js'
  ])
  @vite(['resources/assets/vendor/libs/sortablejs/sortable.js'])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        kanbanAjax: @json(route('deals.kanbanAjax')),
        dataTableAjax: @json(route('deals.dataTableAjax')),
        store: @json(route('deals.store')),
        getDealTemplate: @json(route('deals.getDealAjax', ['deal' => ':id'])),
        updateTemplate: @json(route('deals.update', ['deal' => ':id'])),
        destroyTemplate: @json(route('deals.destroy', ['deal' => ':id'])),
        updateKanbanStageTemplate: @json(route('deals.updateKanbanStage', ['deal' => ':id'])),
        userSearch: @json(route('users.selectSearch')),
        companySearch: @json(route('companies.selectSearch')),
        contactSearch: @json(route('contacts.selectSearch'))
      },
      pipelines: @json($pipelines->mapWithKeys(function ($pipeline) {
                return [$pipeline->id => [
                    'name' => $pipeline->name,
                    'stages' => $pipeline->stages->mapWithKeys(function ($stage) {
                        return [$stage->id => ['name' => $stage->name, 'color' => $stage->color]];
                    })
                ]];
            })),
      allPipelinesForForm: @json($allPipelinesForForm ?? []),
      initialPipelineId: @json($defaultPipeline->id ?? null),
      labels: {
        selectPipeline: @json(__('Pipeline')),
        kanbanView: @json(__('Kanban View')),
        listView: @json(__('List View')),
        addNewDeal: @json(__('Add New Deal')),
        noSalesPipelines: @json(__('No sales pipelines configured. Please set up a pipeline in settings.')),
        selectedPipelineNotFound: @json(__('Selected pipeline not found or has no stages.')),
        thisPipelineHasNoStages: @json(__('This pipeline has no active stages to display.'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/deals-index.js'])
@endsection

@section('content')
  <x-breadcrumb
    :title="__('Sales Pipeline')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  {{-- Kanban View Container --}}
  <div id="kanban-view-container">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <h5 class="card-title mb-0">{{ __('Deal Pipeline') }}</h5>
            <div>
              <label for="pipeline-filter-select" class="form-label visually-hidden">{{ __('Pipeline') }}</label>
              <select id="pipeline-filter-select" class="form-select form-select-sm select2-pipeline-filter">
                @foreach($pipelines as $pipeline)
                  <option value="{{ $pipeline->id }}" {{ $defaultPipeline && $defaultPipeline->id == $pipeline->id ? 'selected' : '' }}>
                    {{ $pipeline->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="d-flex gap-2">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-primary active" id="btn-kanban-view-top" title="{{ __('Kanban View') }}">
                <i class="bx bx-layout"></i>
              </button>
              <button type="button" class="btn btn-outline-primary" id="btn-list-view-top" title="{{ __('List View') }}">
                <i class="bx bx-list-ul"></i>
              </button>
            </div>
            <button type="button" class="btn btn-primary" id="add-new-deal-btn-kanban">
              <i class="bx bx-plus"></i> <span class="d-none d-sm-inline-block">{{ __('Add New Deal') }}</span>
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="kanban-board d-flex gap-3 p-3" id="kanban-stages-wrapper">
          @if($defaultPipeline)
            @foreach($defaultPipeline->stages as $stage)
              @if(!$stage->is_won_stage && !$stage->is_lost_stage)
              <div class="kanban-column">
                <div class="kanban-header d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">
                    <span class="badge" style="background-color: {{ $stage->color ?? '#6c757d' }};">{{ $stage->name }}</span>
                  </h6>
                  <span class="text-muted small" id="stage-total-{{$stage->id}}">$0 (0)</span>
                </div>
                <div id="kanban-stage-{{ $stage->id }}" class="kanban-stage" data-stage-id="{{ $stage->id }}" data-pipeline-id="{{ $defaultPipeline->id }}">
                  <!-- Deals will be populated here -->
                </div>
              </div>
              @endif
            @endforeach
          @else
            <div class="col-12">
              <div class="alert alert-warning">{{ __('No sales pipelines configured. Please set up a pipeline in settings.') }}</div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- List View Container --}}
  <div id="datatable-view-container" class="d-none">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('All Deals') }}</h5>
          <div class="d-flex gap-2">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-primary" id="btn-kanban-view" title="{{ __('Kanban View') }}">
                <i class="bx bx-layout"></i>
              </button>
              <button type="button" class="btn btn-outline-primary active" id="btn-list-view" title="{{ __('List View') }}">
                <i class="bx bx-list-ul"></i>
              </button>
            </div>
            <button type="button" class="btn btn-primary" id="add-new-deal-btn">
              <i class="bx bx-plus"></i> <span class="d-none d-sm-inline-block">{{ __('Add New Deal') }}</span>
            </button>
          </div>
        </div>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table table-bordered datatables-deals" id="dealsTable">
          <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Value') }}</th>
            <th>{{ __('Stage') }}</th>
            <th>{{ __('Company') }}</th>
            <th>{{ __('Contact') }}</th>
            <th>{{ __('Assigned To') }}</th>
            <th>{{ __('Expected Close') }}</th>
            <th class="text-start">{{ __('Actions') }}</th>
          </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

  {{-- Include the Offcanvas Form Partial --}}
  @include('crmcore::deals._form')
@endsection
