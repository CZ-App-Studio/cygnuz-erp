@php
  // Passed from DealStageController@index:
  // $deal_pipeline -> The parent DealPipeline model instance
  // $stages -> Collection of DealStage models for this pipeline, ordered by position
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Deal Stages'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/SortableJS/1.15.6/Sortable.min.css" />
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
  <script src="
https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js
"></script>
@endsection

@section('page-script')
  <script>
    // Pass all necessary data from PHP to our JS file
    const pageData = {
      pipeline: {
        id: @json($deal_pipeline->id),
        name: @json($deal_pipeline->name)
      },
      urls: {
        store: @json(route('settings.dealPipelineStages.store', ['deal_pipeline' => $deal_pipeline->id])),
        getStageTemplate: @json(route('settings.dealPipelineStages.getStageAjax', ['deal_pipeline' => $deal_pipeline->id, 'deal_stage' => '__DEAL_STAGE_ID__'])), // :id is placeholder
        updateTemplate: @json(route('settings.dealPipelineStages.update', ['deal_pipeline' => $deal_pipeline->id, 'deal_stage' => '__DEAL_STAGE_ID__'])),
        destroyTemplate: @json(route('settings.dealPipelineStages.destroy', ['deal_pipeline' => $deal_pipeline->id, 'deal_stage' => '__DEAL_STAGE_ID__'])),
        updateOrder: @json(route('settings.dealPipelineStages.updateOrder', ['deal_pipeline' => $deal_pipeline->id]))
      },
      stages: @json($stages->mapWithKeys(function ($stage) { // For JS to have initial data if needed
                return [$stage->id => $stage->toArray()];
            }))
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/settings-deal-stages.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Deal Stages')"
      :breadcrumbs="[
        ['name' => __('Settings'), 'url' => ''],
        ['name' => __('CRM'), 'url' => ''],
        ['name' => __('Deal Stages'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    {{-- Pipeline Info and Add Button --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h5 class="mb-1">@lang('Pipeline'): <span class="fw-bold">{{ $deal_pipeline->name }}</span></h5>
        @if($deal_pipeline->description)
          <p class="text-muted mb-0">{{ $deal_pipeline->description }}</p>
        @endif
      </div>
      <button type="button" class="btn btn-primary" id="add-new-stage-btn">
        <i class="bx bx-plus me-1"></i> <span class="d-none d-sm-inline-block">@lang('Add New Stage')</span>
      </button>
    </div>

    {{-- Deal Stages Card --}}
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">@lang('Drag to reorder stages')</h5>
      </div>
      <div class="card-body">
        @if($stages->count() > 0)
          <ul id="stage-list" class="list-group">
            @foreach($stages as $stage)
              <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $stage->id }}">
                <div>
                  <i class="bx bx-grid-vertical me-2" style="cursor: move;" title="@lang('Drag to reorder')"></i>
                  <span class="badge me-2" style="background-color: {{ $stage->color ?? '#6c757d' }}; color: #fff; min-width: 20px;">&nbsp;</span>
                  <strong>{{ $stage->name }}</strong>
                  @if($stage->is_default_for_pipeline) <span class="badge bg-label-primary ms-2">@lang('Default Stage')</span> @endif
                  @if($stage->is_won_stage) <span class="badge bg-label-success ms-2">@lang('Won Stage')</span> @endif
                  @if($stage->is_lost_stage) <span class="badge bg-label-danger ms-2">@lang('Lost Stage')</span> @endif
                </div>
                <div>
                  <button class="btn btn-sm btn-icon me-1 edit-deal-stage" data-id="{{ $stage->id }}" title="@lang('Edit')"><i class="bx bx-pencil"></i></button>
                  @if(!$stage->is_default_for_pipeline || $stages->count() == 1) {{-- Basic check, controller has more robust logic --}}
                  <button class="btn btn-sm btn-icon text-danger delete-deal-stage" data-id="{{ $stage->id }}" title="@lang('Delete')"><i class="bx bx-trash"></i></button>
                  @endif
                </div>
              </li>
            @endforeach
          </ul>
        @else
          <div class="alert alert-info">@lang('No stages configured for this pipeline yet. Add one to get started.')</div>
        @endif
        <small class="text-muted mt-2 d-block">
          @lang('Note: "Won" and "Lost" stages are considered final stages and might behave differently in the deal Kanban board.')<br>
          @lang('Ensure only one stage is marked as "Default", "Won", or "Lost" per pipeline for predictable behavior.')
        </small>
    </div>
  </div>

  @include('crmcore::deal_stages._form') {{-- Offcanvas form partial --}}
  </div>{{-- End container --}}
@endsection
