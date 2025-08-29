@php
  // Data passed from CompanyController@show:
  // $company
  // $activities
  // The following are now primarily for the pageData JS object,
  // as company-show-interactions.js will handle form element population.
  // $taskStatuses, $taskPriorities, $allPipelinesForForm, $pipelinesWithStages
use Illuminate\Support\Str;
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Company Details') . ': ' . $company->name)

@section('vendor-style')
  @vite([
      // DataTables for potential future use in tabs, or if you prefer for lists here
      // 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/select2/select2.scss',       // For offcanvas forms
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss',   // For offcanvas forms
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'// For notifications
  ])
  <style>
    /* Minor style for activity log if using the timeline from previous example */
    .timeline-item-transparent .timeline-point {
      background-color: transparent !important;
      border: 2px solid; /* Ensure border is visible */
    }
    .timeline-point-success { border-color: var(--bs-success) !important; }
    .timeline-point-info { border-color: var(--bs-info) !important; }
    .timeline-point-danger { border-color: var(--bs-danger) !important; }
    .timeline-point-secondary { border-color: var(--bs-secondary) !important; }
  </style>
@endsection

@section('vendor-script')
  @vite([
      // 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/moment/moment.js', // For Flatpickr
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  {{-- Load module JavaScript after vendor scripts --}}
  @vite(['Modules/CRMCore/resources/assets/js/companies/show.js'])

  <script>
    @php
      $companyIdForRoute = $company->id; // Current company ID

      // Define distinct placeholders for JS replacement
      $dealIdPlaceholder = '__DEAL_ID__';
      $taskIdPlaceholder = '__TASK_ID__';

      // URLs for Deal Form operations, generated safely
      $dealStoreUrl = route('deals.store');
      $getDealTemplateUrl = route('deals.getDealAjax', ['deal' => $dealIdPlaceholder]);
      $dealUpdateTemplateUrl = route('deals.update', ['deal' => $dealIdPlaceholder]);

      // URLs for Task Form operations, generated safely
      $taskStoreUrl = route('tasks.store');
      $getTaskTemplateUrl = route('tasks.getTaskAjax', ['task' => $taskIdPlaceholder]);
      $taskUpdateTemplateUrl = route('tasks.update', ['task' => $taskIdPlaceholder]);

      // Other URLs
      $contactCreateUrl = route('contacts.create');
      $userSearchUrl = route('users.selectSearch');
      $companySearchUrl = route('companies.selectSearch');
      $contactSearchUrl = route('contacts.selectSearch');
      $leadSearchUrl = route('leads.selectSearch');
      $dealSearchForTaskUrl = route('deals.selectSearch'); // For task "related to deal"

      // Data for static dropdowns
      $taskStatusesData = Modules\CRMCore\App\Models\TaskStatus::orderBy('position')->pluck('name', 'id')->all();
      $taskPrioritiesData = Modules\CRMCore\App\Models\TaskPriority::orderBy('level')->pluck('name', 'id')->all();
      $allPipelinesForFormData = Modules\CRMCore\App\Models\DealPipeline::orderBy('position')->pluck('name', 'id')->all();
      $pipelinesWithStagesData = Modules\CRMCore\App\Models\DealPipeline::with(['stages' => function ($query) {
          $query->orderBy('position');
      }])->orderBy('position')->get()->mapWithKeys(function ($pipeline) {
          return [$pipeline->id => [
              'name' => $pipeline->name,
              'stages' => $pipeline->stages->mapWithKeys(function ($stage) {
                  return [$stage->id => [
                      'name' => $stage->name,
                      'color' => $stage->color,
                      'is_won_stage' => $stage->is_won_stage,
                      'is_lost_stage' => $stage->is_lost_stage
                  ]];
              })
          ]];
      })->all();
      $initialPipelineIdData = (Modules\CRMCore\App\Models\DealPipeline::where('is_default', true)->first() ?? \App\Models\DealPipeline::orderBy('position')->first())->id ?? null;

    @endphp

    const pageData = {
      companyId: @json($companyIdForRoute),
      companyName: @json($company->name),
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        success: @json(__('Success!')),
        error: @json(__('Error')),
        validationError: @json(__('Validation Error')),
        pleaseCorrectErrors: @json(__('Please correct the errors.')),
        unexpectedError: @json(__('An unexpected error occurred.')),
        operationFailed: @json(__('Operation failed.')),
        saving: @json(__('Saving...')),
        loading: @json(__('Loading...')),
        couldNotFetch: @json(__('Could not fetch details.')),
        addNewDeal: @json(__('Add New Deal')),
        editDeal: @json(__('Edit Deal')),
        saveDeal: @json(__('Save Deal')),
        addNewTask: @json(__('Add New Task')),
        editTask: @json(__('Edit Task')),
        saveTask: @json(__('Save Task'))
      },
      urls: {
        contactCreateUrl: @json($contactCreateUrl),

        dealStore: @json($dealStoreUrl),
        getDealTemplate: @json($getDealTemplateUrl),    // Uses __DEAL_ID__
        dealUpdateTemplate: @json($dealUpdateTemplateUrl),// Uses __DEAL_ID__

        taskStore: @json($taskStoreUrl),
        getTaskTemplate: @json($getTaskTemplateUrl),    // Uses __TASK_ID__
        taskUpdateTemplate: @json($taskUpdateTemplateUrl),// Uses __TASK_ID__

        userSearch: @json($userSearchUrl),
        companySearch: @json($companySearchUrl),
        contactSearch: @json($contactSearchUrl),
        leadSearch: @json($leadSearchUrl),
        dealSearch: @json($dealSearchForTaskUrl) // Specifically for task's "related to deal"
      },
      taskStatuses: @json($taskStatusesData),
      taskPriorities: @json($taskPrioritiesData),
      allPipelinesForForm: @json($allPipelinesForFormData),
      pipelinesWithStages: @json($pipelinesWithStagesData),
      initialPipelineId: @json($initialPipelineIdData)
    };
  </script>
  {{-- forms-select2-ajax.js might not be needed if company-show.js handles all its Select2 inits --}}
@endsection

@section('content')
  @php
    $breadcrumbs = [
        ['name' => __('CRM'), 'url' => '#'],
        ['name' => __('Companies'), 'url' => route('companies.index')]
    ];
  @endphp

  {{-- Breadcrumb --}}
  <x-breadcrumb
    :title="$company->name"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
          <h5 class="mb-2 mb-sm-0 me-2">@lang('Company'): <span class="fw-bold">{{ $company->name }}</span></h5>
          <div class="d-flex align-items-center">
            {{-- "Add New" buttons trigger offcanvas forms managed by company-show-interactions.js --}}
            <button type="button" class="btn btn-sm btn-outline-primary me-2 add-new-task-for-company">
              <i class="bx bx-list-plus me-1"></i> @lang('Add Task')
            </button>
            <button type="button" class="btn btn-sm btn-primary me-2 add-new-deal-for-company">
              <i class="bx bx-dollar-circle me-1"></i> @lang('Add Deal')
            </button>
            <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-secondary me-2">
              <i class="bx bx-edit me-1"></i> @lang('Edit Company')
            </a>
            <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">
              <i class="bx bx-arrow-back me-1"></i> @lang('Back to List')
            </a>
          </div>
        </div>
        <div class="card-body">
          {{-- Main Company Details --}}
          <div class="row mb-4">
            <div class="col-md-6">
              <h6>@lang('Primary Information')</h6>
              <dl class="row">
                <dt class="col-sm-4">@lang('Website'):</dt>
                <dd class="col-sm-8">
                  @if($company->website)
                    <a href="{{ Str::startsWith($company->website, ['http://', 'https://']) ? $company->website : 'http://'.$company->website }}" target="_blank" rel="noopener noreferrer">{{ $company->website }}</a>
                  @else - @endif
                </dd>
                <dt class="col-sm-4">@lang('Office Phone'):</dt>
                <dd class="col-sm-8">{{ $company->phone_office ?? '-' }}</dd>
                <dt class="col-sm-4">@lang('Office Email'):</dt>
                <dd class="col-sm-8">{{ $company->email_office ?? '-' }}</dd>
                <dt class="col-sm-4">@lang('Industry'):</dt>
                <dd class="col-sm-8">{{ $company->industry ?? '-' }}</dd>
                <dt class="col-sm-4">@lang('Assigned To'):</dt>
                <dd class="col-sm-8">{{ $company->assignedToUser?->getFullNameAttribute() ?? '-' }}</dd>
              </dl>
            </div>
            <div class="col-md-6">
              <h6>@lang('Address')</h6>
              <address class="mb-0">
                {{ $company->address_street ?? '' }}
                @if($company->address_street && ($company->address_city || $company->address_state || $company->address_postal_code || $company->address_country))<br>@endif
                {{ $company->address_city ? $company->address_city . ',' : '' }} {{ $company->address_state ?? '' }} {{ $company->address_postal_code ?? '' }}
                @if(($company->address_city || $company->address_state || $company->address_postal_code) && $company->address_country)<br>@endif
                {{ $company->address_country ?? '' }}
                @if(empty(trim($company->address_street ?? '')) && empty(trim($company->address_city ?? '')) && empty(trim($company->address_country ?? '')))
                  -
                @endif
              </address>
              @if($company->description)
                <hr class="my-2">
                <h6>@lang('Description')</h6>
                <p>{{ $company->description }}</p>
              @endif
            </div>
          </div>

          {{-- Tabbed Interface for Related Information --}}
          <ul class="nav nav-tabs nav-fill" role="tablist">
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-contacts" aria-controls="navs-contacts" aria-selected="true">
                <i class="bx bx-user-pin me-1"></i> @lang('Contacts') <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-primary ms-1">{{ $company->contacts->count() }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-deals" aria-controls="navs-deals" aria-selected="false">
                <i class="bx bx-briefcase-alt-2 me-1"></i> @lang('Deals') <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-info ms-1">{{ $company->deals->count() }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tasks" aria-controls="navs-tasks" aria-selected="false">
                <i class="bx bx-check-square me-1"></i> @lang('Open Tasks') <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-warning ms-1">{{ $company->tasks->count() }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-activity" aria-controls="navs-activity" aria-selected="false">
                <i class="bx bx-history me-1"></i> @lang('Activity Log')
              </button>
            </li>
          </ul>

          <div class="tab-content pt-4">
            {{-- Contacts Tab --}}
            <div class="tab-pane fade show active" id="navs-contacts" role="tabpanel">
              <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-primary add-new-contact-for-company">@lang('Add New Contact')</button>
              </div>
              @if($company->contacts->isNotEmpty())
                <div class="table-responsive text-nowrap">
                  <table class="table table-sm">
                    <thead><tr><th>@lang('Name')</th><th>@lang('Email')</th><th>@lang('Phone')</th><th>@lang('Job Title')</th><th>@lang('Actions')</th></tr></thead>
                    <tbody>
                    @foreach($company->contacts as $contact)
                      <tr>
                        <td><a href="{{ route('contacts.show', $contact->id) }}">{{ $contact->getFullNameAttribute() }}</a></td>
                        <td>{{ $contact->email_primary ?? '-' }}</td>
                        <td>{{ $contact->phone_primary ?? '-' }}</td>
                        <td>{{ $contact->job_title ?? '-' }}</td>
                        <td><a href="{{ route('contacts.edit', ['contact' => $contact->id, 'from_company' => $company->id]) }}" class="btn btn-xs btn-icon item-edit" title="@lang('Edit Contact')"><i class="bx bx-pencil"></i></a></td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else <p>@lang('No contacts associated with this company yet.')</p> @endif
            </div>

            {{-- Deals Tab --}}
            <div class="tab-pane fade" id="navs-deals" role="tabpanel">
              <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-primary add-new-deal-for-company">@lang('Add New Deal')</button>
              </div>
              @if($company->deals->isNotEmpty())
                <div class="table-responsive text-nowrap">
                  <table class="table table-sm">
                    <thead><tr><th>@lang('Title')</th><th>@lang('Value')</th><th>@lang('Stage')</th><th>@lang('Expected Close')</th><th>@lang('Primary Contact')</th><th>@lang('Assigned To')</th><th>@lang('Actions')</th></tr></thead>
                    <tbody>
                    @foreach($company->deals as $deal)
                      <tr>
                        <td><a href="{{ route('deals.show', $deal->id) }}">{{ $deal->title }}</a></td>
                        <td>${{ number_format($deal->value, 2) }}</td>
                        <td><span class="badge" style="background-color:{{ $deal->dealStage?->color ?? '#6c757d' }}; color:#fff;">{{ $deal->dealStage?->name ?? '-' }}</span></td>
                        <td>{{ $deal->expected_close_date ? $deal->expected_close_date->format('d M Y') : '-' }}</td>
                        <td>{{ $deal->contact?->getFullNameAttribute() ?? '-' }}</td>
                        <td>{{ $deal->assignedToUser?->getFullNameAttribute() ?? '-' }}</td>
                        <td><button class="btn btn-xs btn-icon item-edit edit-deal-from-related" data-deal-id="{{ $deal->id }}" title="@lang('Edit Deal')"><i class="bx bx-pencil"></i></button></td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else <p>@lang('No deals associated with this company yet.')</p> @endif
            </div>

            {{-- Tasks Tab --}}
            <div class="tab-pane fade" id="navs-tasks" role="tabpanel">
              <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-primary add-new-task-for-company">@lang('Add New Task')</button>
              </div>
              @if($company->tasks->isNotEmpty())
                <div class="table-responsive text-nowrap">
                  <table class="table table-sm">
                    <thead><tr><th>@lang('Title')</th><th>@lang('Status')</th><th>@lang('Priority')</th><th>@lang('Due Date')</th><th>@lang('Assigned To')</th><th>@lang('Actions')</th></tr></thead>
                    <tbody>
                    @foreach($company->tasks as $task)
                      <tr>
                        <td>{{ $task->title }}</td>
                        <td><span class="badge" style="background-color:{{ $task->status?->color ?? '#6c757d' }}; color:#fff;">{{ $task->status?->name ?? '-' }}</span></td>
                        <td><span class="badge" style="background-color:{{ $task->priority?->color ?? '#6c757d' }}; color:#fff;">{{ $task->priority?->name ?? '-' }}</span></td>
                        <td>{{ $task->due_date ? $task->due_date->format('d M Y, H:i A') : '-' }}</td>
                        <td>{{ $task->assignedToUser?->getFullNameAttribute() ?? '-' }}</td>
                        <td><button class="btn btn-xs btn-icon item-edit edit-task-from-related" data-task-id="{{ $task->id }}" title="@lang('Edit Task')"><i class="bx bx-pencil"></i></button></td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else <p>@lang('No open tasks associated with this company.')</p> @endif
            </div>

            {{-- Activity Log Tab --}}
            <div class="tab-pane fade" id="navs-activity" role="tabpanel">
              @if($activities && $activities->count() > 0)
                <ul class="timeline ms-2">
                  @foreach($activities as $activity)
                    <li class="timeline-item timeline-item-transparent">
                      <span class="timeline-point timeline-point-{{ ['created' => 'success', 'updated' => 'info', 'deleted' => 'danger'][$activity->event] ?? 'secondary' }}"></span>
                      <div class="timeline-event">
                        <div class="timeline-header mb-1">
                          <h6 class="mb-0">@lang('Action'): {{ Str::title($activity->description ?: $activity->event) }}</h6>
                          <small class="text-muted">{{ $activity->created_at->format('d M Y, H:i A') }} ({{ $activity->created_at->diffForHumans() }})</small>
                        </div>
                        @if ($activity->causer) <p class="mb-1">@lang('By'): {{ $activity->causer->getFullNameAttribute() ?? $activity->causer->email }}</p> @endif
                        @include('_partials._activity_log_changes', ['activity' => $activity])
                      </div>
                    </li>
                  @endforeach
                </ul>
              @else <p>@lang('No activity log available for this company.')</p> @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Include necessary offcanvas forms. Ensure their IDs match what company-show-interactions.js expects. --}}
  @include('crmcore::deals._form')
  @include('crmcore::tasks._form')

@endsection
