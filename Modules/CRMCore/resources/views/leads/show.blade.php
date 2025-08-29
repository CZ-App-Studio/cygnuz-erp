@php
  use Illuminate\Support\Str;
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('leads.index')],
    ['name' => __('Leads'), 'url' => route('leads.index')],
    ['name' => $lead->title, 'url' => null]
  ];
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Lead Details') . ': ' . $lead->title)

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
  <style>
    .timeline-item-transparent .timeline-point { background-color: transparent !important; border: 2px solid; }
    .timeline-point-success { border-color: var(--bs-success) !important; }
    .timeline-point-info { border-color: var(--bs-info) !important; }
    .timeline-point-danger { border-color: var(--bs-danger) !important; }
    .timeline-point-secondary { border-color: var(--bs-secondary) !important; }
  </style>
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/moment/moment.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    @php
    $leadData =[
            'contact_name' => $lead->contact_name,
            'contact_email' => $lead->contact_email,
            'contact_phone' => $lead->contact_phone,
            'company_name' => $lead->company_name,
            'value' => $lead->value,
            'contact_first_name' => trim(substr($lead->contact_name ?? '', 0, strpos($lead->contact_name ?? '', ' ') ?: strlen($lead->contact_name ?? ''))),
            'contact_last_name' => trim(substr($lead->contact_name ?? '', strpos($lead->contact_name ?? '', ' ') ?: strlen($lead->contact_name ?? '')))
        ];
    @endphp
    const pageData = {
      leadId: @json($lead->id),
      leadTitle: @json($lead->title),
      leadDataForConversion: @json($leadData),
      urls: {
        getLeadTemplate: @json(route('leads.getLeadAjax', ['lead' => '__LEAD_ID__'])),
        leadUpdateTemplate: @json(route('leads.update', ['lead' => '__LEAD_ID__'])),
        processConversion: @json(route('leads.processConversion', ['lead' => $lead->id])),
        taskStore: @json(route('tasks.store')),
        getTaskTemplate: @json(route('tasks.getTaskAjax', ['task' => '__TASK_ID__'])),
        taskUpdateTemplate: @json(route('tasks.update', ['task' => '__TASK_ID__'])),
        userSearch: @json(route('users.selectSearch')),
        companySearch: @json(route('companies.selectSearch')),
        contactSearch: @json(route('contacts.selectSearch')),
        leadSearch: @json(route('leads.selectSearch')),
        dealSearch: @json(route('deals.selectSearch'))
      },
      taskStatuses: @json($taskStatuses ?? []),
      taskPriorities: @json($taskPriorities ?? []),
      leadSourcesForForm: @json(\Modules\CRMCore\app\Models\LeadSource::where('is_active', true)->pluck('name', 'id') ?? []),
      allPipelinesForForm: @json(\Modules\CRMCore\app\Models\DealPipeline::orderBy('position')->pluck('name', 'id') ?? []),
      pipelinesWithStages: @json(\Modules\CRMCore\app\Models\DealPipeline::with(['stages' => function ($q) { $q->orderBy('position'); }])
                                    ->orderBy('position')->get()->mapWithKeys(function ($p) {
                                        return [$p->id => ['name' => $p->name, 'stages' => $p->stages->mapWithKeys(function ($s) {
                                            return [$s->id => ['name' => $s->name]];
                                        })]];
                                    }) ?? []),
      initialPipelineId: @json((\Modules\CRMCore\app\Models\DealPipeline::where('is_default', true)->first() ?? \Modules\CRMCore\app\Models\DealPipeline::orderBy('position')->first())->id ?? null),
      labels: {
        editLead: @json(__('Edit Lead')),
        saveChanges: @json(__('Save Changes')),
        selectSource: @json(__('Select Source...')),
        searchUser: @json(__('Search User...')),
        addNewTask: @json(__('Add New Task')),
        editTask: @json(__('Edit Task')),
        saveTask: @json(__('Save Task')),
        selectStatus: @json(__('Select Status...')),
        selectPriority: @json(__('Select Priority...')),
        selectTypeFirst: @json(__('Select Type First...')),
        search: @json(__('Search')),
        convertLead: @json(__('Convert Lead')),
        searchSelectCompany: @json(__('Search & Select Company...')),
        selectPipeline: @json(__('Select Pipeline')),
        selectStage: @json(__('Select Stage')),
        converting: @json(__('Converting...')),
        saving: @json(__('Saving...')),
        error: @json(__('Error')),
        success: @json(__('Success!')),
        converted: @json(__('Converted!')),
        validationError: @json(__('Validation Error')),
        pleaseCorrectErrors: @json(__('Please correct the errors.')),
        unexpectedError: @json(__('An unexpected error occurred.')),
        leadIdMissing: @json(__('Lead ID is missing. Cannot update.')),
        updateFailed: @json(__('Update failed.')),
        operationFailed: @json(__('Operation failed.')),
        conversionFailed: @json(__('Conversion failed.')),
        couldNotFetchLead: @json(__('Could not fetch lead details for editing.')),
        couldNotFetchTask: @json(__('Could not fetch task details for editing.')),
        leadConversionOffcanvasNotFound: @json(__('Lead conversion offcanvas not found.')),
        viewContact: @json(__('View Contact')),
        viewCompany: @json(__('View Company')),
        viewDeal: @json(__('View Deal')),
        okay: @json(__('Okay'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/lead-show-interactions.js'])
@endsection

@section('content')
  <x-breadcrumb
    :title="$lead->title"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
          <div class="d-flex align-items-center">
            @if(!$lead->converted_at)
              <button type="button" class="btn btn-success me-2 convert-lead-btn" data-lead-id="{{ $lead->id }}">
                <i class="bx bx-check-double me-1"></i> {{ __('Convert Lead') }}
              </button>
              <button type="button" class="btn btn-primary me-2 edit-lead-btn">
                <i class="bx bx-edit me-1"></i> {{ __('Edit Lead') }}
              </button>
            @endif
            <button type="button" class="btn btn-outline-primary me-2 add-new-task-for-lead">
              <i class="bx bx-list-plus me-1"></i> {{ __('Add Task') }}
            </button>
            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary">
              <i class="bx bx-arrow-back me-1"></i> {{ __('Back to Funnel') }}
            </a>
          </div>
        </div>
        <div class="card-body">
          @if($lead->converted_at)
            <div class="alert alert-success" role="alert">
              <h6 class="alert-heading mb-1">{{ __('Lead Converted') }}</h6>
              <p class="mb-0">
                {{ __('This lead was converted on') }} {{ $lead->converted_at->format('d M Y, H:i A') }}.
                @if($lead->convertedToContact)
                  <br>{{ __('View Contact') }}: <a href="{{ route('contacts.show', $lead->convertedToContact->id) }}" class="alert-link">{{ $lead->convertedToContact->full_name }}</a>
                @endif
                @if($lead->convertedToDeal)
                  <br>{{ __('View Deal') }}: <a href="{{ route('deals.show', $lead->convertedToDeal->id) }}" class="alert-link">{{ $lead->convertedToDeal->title }}</a>
                @endif
              </p>
            </div>
          @endif

          <div class="row mb-4">
            <div class="col-md-6">
              <h6>{{ __('Lead Information') }}</h6>
              <dl class="row">
                <dt class="col-sm-4">{{ __('Status') }}:</dt>
                <dd class="col-sm-8"><span class="badge" style="background-color:{{ $lead->leadStatus?->color ?? '#6c757d' }}; color:#fff;">{{ $lead->leadStatus?->name ?? '-' }}</span></dd>
                <dt class="col-sm-4">{{ __('Value') }}:</dt>
                <dd class="col-sm-8">${{ number_format($lead->value, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Source') }}:</dt>
                <dd class="col-sm-8">{{ $lead->leadSource?->name ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Assigned To') }}:</dt>
                <dd class="col-sm-8">{{ $lead->assignedToUser?->getFullNameAttribute() ?? '-' }}</dd>
              </dl>
            </div>
            <div class="col-md-6">
              <h6>{{ __('Contact Information') }}</h6>
              <dl class="row">
                <dt class="col-sm-4">{{ __('Contact Name') }}:</dt>
                <dd class="col-sm-8">{{ $lead->contact_name ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Company Name') }}:</dt>
                <dd class="col-sm-8">{{ $lead->company_name ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Email') }}:</dt>
                <dd class="col-sm-8">{{ $lead->contact_email ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Phone') }}:</dt>
                <dd class="col-sm-8">{{ $lead->contact_phone ?? '-' }}</dd>
              </dl>
            </div>
          </div>
          @if($lead->description)
            <hr class="my-2">
            <h6>{{ __('Description') }}</h6>
            <p>{{ $lead->description }}</p>
          @endif

          <ul class="nav nav-tabs nav-fill mt-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tasks-lead" aria-controls="navs-tasks-lead" aria-selected="true">
                <i class="bx bx-check-square me-1"></i> {{ __('Open Tasks') }} <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-warning ms-1">{{ $lead->tasks->count() }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-activity-lead" aria-controls="navs-activity-lead" aria-selected="false">
                <i class="bx bx-history me-1"></i> {{ __('Activity Log') }}
              </button>
            </li>
          </ul>

          <div class="tab-content pt-4">
            <div class="tab-pane fade show active" id="navs-tasks-lead" role="tabpanel">
              <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-primary add-new-task-for-lead">{{ __('Add New Task') }}</button>
              </div>
              @if($lead->tasks->isNotEmpty())
                <div class="table-responsive text-nowrap">
                  <table class="table table-sm">
                    <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Status') }}</th><th>{{ __('Priority') }}</th><th>{{ __('Due Date') }}</th><th>{{ __('Assigned To') }}</th><th>{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                    @foreach($lead->tasks as $task)
                      <tr>
                        <td>{{ $task->title }}</td>
                        <td><span class="badge" style="background-color:{{ $task->status?->color ?? '#6c757d' }}; color:#fff;">{{ $task->status?->name ?? '-' }}</span></td>
                        <td><span class="badge" style="background-color:{{ $task->priority?->color ?? '#6c757d' }}; color:#fff;">{{ $task->priority?->name ?? '-' }}</span></td>
                        <td>{{ $task->due_date ? $task->due_date->format('d M Y, H:i A') : '-' }}</td>
                        <td>{{ $task->assignedToUser?->getFullNameAttribute() ?? '-' }}</td>
                        <td><button class="btn btn-xs btn-icon item-edit edit-task-from-related" data-task-id="{{ $task->id }}" title="{{ __('Edit Task') }}"><i class="bx bx-pencil"></i></button></td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else <p>{{ __('No open tasks associated with this lead.') }}</p> @endif
            </div>

            <div class="tab-pane fade" id="navs-activity-lead" role="tabpanel">
              @if($activities && $activities->count() > 0)
                <ul class="timeline ms-2">
                  @foreach($activities as $activity)
                    <li class="timeline-item timeline-item-transparent">
                      <span class="timeline-point timeline-point-{{ ['created' => 'success', 'updated' => 'info', 'deleted' => 'danger'][$activity->event] ?? 'secondary' }}"></span>
                      <div class="timeline-event">
                        <div class="timeline-header mb-1">
                          <h6 class="mb-0">{{ __('Action') }}: {{ Str::title($activity->description ?: $activity->event) }}</h6>
                          <small class="text-muted">{{ $activity->created_at->format('d M Y, H:i A') }} ({{ $activity->created_at->diffForHumans() }})</small>
                        </div>
                        @if ($activity->causer) <p class="mb-1">{{ __('By') }}: {{ $activity->causer->getFullNameAttribute() ?? $activity->causer->email }}</p> @endif
                        @include('crmcore::partials._activity_log_changes', ['activity' => $activity])
                      </div>
                    </li>
                  @endforeach
                </ul>
              @else <p>{{ __('No activity log available for this lead.') }}</p> @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @include('crmcore::leads._form')
  @include('crmcore::tasks._form')
  @include('crmcore::leads._convert_lead_modal')
@endsection
