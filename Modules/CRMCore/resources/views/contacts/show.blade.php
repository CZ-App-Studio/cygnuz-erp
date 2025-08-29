@php
  use Illuminate\Support\Str;
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => '#'],
    ['name' => __('Contacts'), 'url' => route('contacts.index')]
  ];
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Contact Details') . ': ' . $contact->full_name)

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
  <style>
    /* Timeline styles */
    .timeline-item-transparent .timeline-point { background-color: transparent !important; border: 2px solid; }
    .timeline-point-success { border-color: var(--bs-success) !important; }
    .timeline-point-info { border-color: var(--bs-info) !important; }
    .timeline-point-danger { border-color: var(--bs-danger) !important; }
    .timeline-point-secondary { border-color: var(--bs-secondary) !important; }
  </style>
@endsection

@section('vendor-script')  @vite([
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/moment/moment.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')  <script>
    const pageData = {
      contactId: @json($contact->id),
      contactName: @json($contact->full_name),
      contactCompanyId: @json($contact->company_id),
      contactCompanyName: @json($contact->company?->name),
      urls: {
        // Deal Form URLs
        dealStore: @json(route('deals.store')),
        getDealTemplate: @json(route('deals.getDealAjax', ['deal' => '__DEAL_ID__'])),
        dealUpdateTemplate: @json(route('deals.update', ['deal' => '__DEAL_ID__'])),
        // Task Form URLs
        taskStore: @json(route('tasks.store')),
        getTaskTemplate: @json(route('tasks.getTaskAjax', ['task' => '__TASK_ID__'])),
        taskUpdateTemplate: @json(route('tasks.update', ['task' => '__TASK_ID__'])),
        // Select2 Search URLs
        userSearch: @json(route('users.selectSearch')),
        companySearch: @json(route('companies.selectSearch')),
        contactSearch: @json(route('contacts.selectSearch')),
        leadSearch: @json(route('leads.selectSearch')),
        dealSearch: @json(route('deals.selectSearch'))
      },
      taskStatuses: @json($taskStatuses ?? []),
      taskPriorities: @json($taskPriorities ?? []),
      allPipelinesForForm: @json($allPipelinesForForm ?? []),
      pipelinesWithStages: @json($pipelinesWithStages ?? []),
      initialPipelineId: @json($initialPipelineIdData ?? null),
      labels: {
        // Deal labels
        addNewDeal: @json(__('Add New Deal')),
        editDeal: @json(__('Edit Deal')),
        saveDeal: @json(__('Save Deal')),
        selectPipeline: @json(__('Select Pipeline')),
        selectStage: @json(__('Select Stage')),
        searchCompany: @json(__('Search Company...')),
        searchContact: @json(__('Search Contact...')),
        searchUser: @json(__('Search User...')),
        couldNotFetchDeal: @json(__('Could not fetch deal details.')),
        // Task labels
        addNewTask: @json(__('Add New Task')),
        editTask: @json(__('Edit Task')),
        saveTask: @json(__('Save Task')),
        selectStatus: @json(__('Select Status...')),
        selectPriority: @json(__('Select Priority...')),
        selectTypeFirst: @json(__('Select Type First...')),
        searchType: @json(__('Search {type}...')),
        couldNotFetchTask: @json(__('Could not fetch task details.')),
        // General labels
        success: @json(__('Success!')),
        error: @json(__('Error')),
        saving: @json(__('Saving...')),
        operationSuccessful: @json(__('Operation completed successfully.')),
        operationFailed: @json(__('Operation failed.')),
        validationError: @json(__('Validation Error')),
        correctErrors: @json(__('Please correct the errors.')),
        unexpectedError: @json(__('An unexpected error occurred.'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/contact-show-interactions.js'])
@endsection

@section('content')
  <x-breadcrumb
    :title="__('Contact Details') . ': ' . $contact->full_name"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">{{ __('Contact Details') }} }}: <span class="fw-bold">{{ $contact->full_name }}</span></h5>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-sm btn-outline-primary add-new-task-for-contact">
                <i class="bx bx-task"></i> {{ __('Add Task') }}              </button>
              <button type="button" class="btn btn-sm btn-primary add-new-deal-for-contact">
                <i class="bx bx-dollar"></i> {{ __('Add Deal') }}              </button>
              <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-sm btn-secondary">
                <i class="bx bx-edit"></i> {{ __('Edit Contact') }}              </a>
              <a href="{{ route('contacts.index') }} }}" class="btn btn-sm btn-outline-secondary">
                <i class="bx bx-arrow-back"></i> {{ __('Back to List') }}              </a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <h6>{{ __('Contact Information') }} }}</h6>
              <dl class="row">
                <dt class="col-sm-4">{{ __('Company') }} }}:</dt>
                <dd class="col-sm-8">
                  @if($contact->company)
                    <a href="{{ route('companies.show', $contact->company_id) }}">{{ $contact->company->name }}</a>
                  @else - @endif
                </dd>
                <dt class="col-sm-4">{{ __('Job Title') }}:</dt>
                <dd class="col-sm-8">{{ $contact->job_title ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Primary Email') }}:</dt>
                <dd class="col-sm-8">{{ $contact->email_primary ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Secondary Email') }}:</dt>
                <dd class="col-sm-8">{{ $contact->email_secondary ?? '-' }}</dd>
              </dl>
            </div>
            <div class="col-md-6">
              <h6>{{ __('Phone & Other') }}</h6>
              <dl class="row">
                <dt class="col-sm-4">{{ __('Primary Phone') }}:</dt>
                <dd class="col-sm-8">{{ $contact->phone_primary ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Mobile Phone') }}:</dt>
                <dd class="col-sm-8">{{ $contact->phone_mobile ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Assigned To') }}:</dt>
                <dd class="col-sm-8">{{ $contact->assignedToUser?->getFullNameAttribute() ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Lead Source') }}:</dt>
                <dd class="col-sm-8">{{ $contact->lead_source_name ?? '-' }}</dd>
              </dl>
            </div>
          </div>
          {{-- Address & Description --}}
          <div class="row mb-4">
            <div class="col-md-6">
              <h6>{{ __('Address') }}</h6>
              <address class="mb-0">
                {{ $contact->address_street ?? '' }}
                @if($contact->address_street && ($contact->address_city || $contact->address_state || $contact->address_postal_code || $contact->address_country))<br>@endif
                {{ $contact->address_city ? $contact->address_city . ',' : '' }} {{ $contact->address_state ?? '' }} {{ $contact->address_postal_code ?? '' }}
                @if(($contact->address_city || $contact->address_state || $contact->address_postal_code) && $contact->address_country)<br>@endif
                {{ $contact->address_country ?? '' }}
                @if(empty(trim($contact->address_street ?? '')) && empty(trim($contact->address_city ?? '')) && empty(trim($contact->address_country ?? '')))
                  -
                @endif
              </address>
            </div>
            <div class="col-md-6">
              @if($contact->description)
                <h6>{{ __('Description') }}</h6>
                <p>{{ $contact->description }}</p>
              @endif
            </div>
          </div>


          {{-- Tabbed Interface --}}
          <ul class="nav nav-tabs nav-fill" role="tablist">
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-deals-contact" aria-controls="navs-deals-contact" aria-selected="true">
                <i class="bx bx-briefcase-alt-2 me-1"></i> {{ __('Deals') }} <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-info ms-1">{{ $contact->deals->count() }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tasks-contact" aria-controls="navs-tasks-contact" aria-selected="false">
                <i class="bx bx-check-square me-1"></i> {{ __('Open Tasks') }} <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-warning ms-1">{{ $contact->tasks->count() }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-activity-contact" aria-controls="navs-activity-contact" aria-selected="false">
                <i class="bx bx-history me-1"></i> {{ __('Activity Log') }}
              </button>
            </li>
          </ul>

          <div class="tab-content pt-4">
            {{-- Deals Tab --}}
            <div class="tab-pane fade show active" id="navs-deals-contact" role="tabpanel">
              <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-primary add-new-deal-for-contact">{{ __('Add New Deal') }}</button>
              </div>
              @if($contact->deals->isNotEmpty())
                <div class="table-responsive text-nowrap">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>{{ __('Title') }}</th>
                        <th>{{ __('Value') }}</th>
                        <th>{{ __('Stage') }}</th>
                        <th>{{ __('Expected Close') }}</th>
                        <th>{{ __('Assigned To') }}</th>
                        <th>{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                    @foreach($contact->deals as $deal)
                      <tr>
                        <td><a href="{{ route('deals.show', $deal->id) }}">{{ $deal->title }}</a></td>
                        <td>${{ number_format($deal->value, 2) }}</td>
                        <td><span class="badge" style="background-color:{{ $deal->dealStage?->color ?? '#6c757d' }}; color:#fff;">{{ $deal->dealStage?->name ?? '-' }}</span></td>
                        <td>{{ $deal->expected_close_date ? $deal->expected_close_date->format('d M Y') : '-' }}</td>
                        <td>{{ $deal->assignedToUser?->getFullNameAttribute() ?? '-' }}</td>
                        <td>
                          <button class="btn btn-xs btn-icon item-edit edit-deal-from-related" data-deal-id="{{ $deal->id }}" title="{{ __('Edit Deal') }}">
                            <i class="bx bx-pencil"></i>
                          </button>
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <p>{{ __('No deals associated with this contact yet.') }}</p>
              @endif
            </div>

            <div class="tab-pane fade" id="navs-tasks-contact" role="tabpanel">
              <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-primary add-new-task-for-contact">{{ __('Add New Task') }}</button>
              </div>
              @if($contact->tasks->isNotEmpty())
                <div class="table-responsive text-nowrap">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>{{ __('Title') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th>{{ __('Due Date') }}</th>
                        <th>{{ __('Assigned To') }}</th>
                        <th>{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                    @foreach($contact->tasks as $task)
                      <tr>
                        <td>{{ $task->title }}</td>
                        <td><span class="badge" style="background-color:{{ $task->status?->color ?? '#6c757d' }}; color:#fff;">{{ $task->status?->name ?? '-' }}</span></td>
                        <td><span class="badge" style="background-color:{{ $task->priority?->color ?? '#6c757d' }}; color:#fff;">{{ $task->priority?->name ?? '-' }}</span></td>
                        <td>{{ $task->due_date ? $task->due_date->format('d M Y, H:i A') : '-' }}</td>
                        <td>{{ $task->assignedToUser?->getFullNameAttribute() ?? '-' }}</td>
                        <td>
                          <button class="btn btn-xs btn-icon item-edit edit-task-from-related" data-task-id="{{ $task->id }}" title="{{ __('Edit Task') }}">
                            <i class="bx bx-pencil"></i>
                          </button>
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <p>{{ __('No open tasks associated with this contact.') }}</p>
              @endif
            </div>

            <div class="tab-pane fade" id="navs-activity-contact" role="tabpanel">
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
                        @if ($activity->causer)
                          <p class="mb-1">{{ __('By') }}: {{ $activity->causer->getFullNameAttribute() ?? $activity->causer->email }}</p>
                        @endif
                        @include('_partials._activity_log_changes', ['activity' => $activity])
                      </div>
                    </li>
                  @endforeach
                </ul>
              @else
                <p>{{ __('No activity log available for this contact.') }}</p>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Include necessary offcanvas forms. Their JS logic will be managed by contact-show-interactions.js --}}
  @include('crmcore::deals._form')  @include('crmcore::tasks._form')
@endsection
