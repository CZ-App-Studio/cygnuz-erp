@php
  use Illuminate\Support\Str;
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => '#'],
    ['name' => __('Sales'), 'url' => '#'],
    ['name' => __('Deals'), 'url' => route('deals.index')]
  ];
@endphp
@extends('layouts.layoutMaster')

@section('title', __('Deal Details') . ': ' . $deal->title)

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
    const pageData = {
      dealId: @json($deal->id),
      dealTitle: @json($deal->title),
      urls: {
        getDealTemplate: @json(route('deals.getDealAjax', ['deal' => '__DEAL_ID__'])),
        dealUpdateTemplate: @json(route('deals.update', ['deal' => '__DEAL_ID__'])),
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
      allPipelinesForForm: @json($allPipelinesForForm ?? []),
      pipelinesWithStages: @json($pipelinesWithStages ?? []),
      labels: {
        editDeal: @json(__('Edit Deal')),
        addTask: @json(__('Add Task')),
        backToFunnel: @json(__('Back to Funnel')),
        areYouSure: @json(__('Are you sure?')),
        success: @json(__('Success!')),
        error: @json(__('Error')),
        editTask: @json(__('Edit Task')),
        noOpenTasks: @json(__('No open tasks associated with this deal.'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/deal-show-interactions.js'])
@endsection

@section('content')
  <x-breadcrumb
    :title="$deal->title"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
          <h5 class="mb-2 mb-sm-0 me-2">{{ __('Deal Details') }}</h5>
          <div class="d-flex align-items-center">
            <button type="button" class="btn btn-primary me-2 edit-deal-btn">
              <i class="bx bx-edit"></i> {{ __('Edit Deal') }}
            </button>
            <button type="button" class="btn btn-outline-primary me-2 add-new-task-for-deal">
              <i class="bx bx-list-plus"></i> {{ __('Add Task') }}
            </button>
            <a href="{{ route('deals.index') }}" class="btn btn-sm btn-outline-secondary">
              <i class="bx bx-arrow-back"></i> {{ __('Back to Funnel') }}
            </a>
          </div>
        </div>
        <div class="card-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <h6>{{ __('Deal Information') }}</h6>
              <dl class="row">
                <dt class="col-sm-4">{{ __('Value') }}:</dt>
                <dd class="col-sm-8">{{ \App\Helpers\FormattingHelper::formatCurrency($deal->value) }}</dd>
                <dt class="col-sm-4">{{ __('Pipeline') }}:</dt>
                <dd class="col-sm-8">{{ $deal->pipeline?->name ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Stage') }}:</dt>
                <dd class="col-sm-8">
                  <span class="badge" style="background-color:{{ $deal->dealStage?->color ?? '#6c757d' }}; color:#fff;">
                    {{ $deal->dealStage?->name ?? '-' }}
                  </span>
                </dd>
                <dt class="col-sm-4">{{ __('Probability') }}:</dt>
                <dd class="col-sm-8">{{ $deal->probability ?? '-' }}%</dd>
                <dt class="col-sm-4">{{ __('Expected Close') }}:</dt>
                <dd class="col-sm-8">{{ $deal->expected_close_date ? $deal->expected_close_date->format('d M Y') : '-' }}</dd>
              </dl>
            </div>
            <div class="col-md-6">
              <h6>{{ __('Associated Parties') }}</h6>
              <dl class="row">
                <dt class="col-sm-4">{{ __('Primary Contact') }}:</dt>
                <dd class="col-sm-8">
                  @if($deal->contact)
                    <a href="{{ route('contacts.show', $deal->contact_id) }}">{{ $deal->contact->full_name }}</a>
                  @else - @endif
                </dd>
                <dt class="col-sm-4">{{ __('Company') }}:</dt>
                <dd class="col-sm-8">
                  @if($deal->company)
                    <a href="{{ route('companies.show', $deal->company_id) }}">{{ $deal->company->name }}</a>
                  @else - @endif
                </dd>
                <dt class="col-sm-4">{{ __('Assigned To') }}:</dt>
                <dd class="col-sm-8">{{ $deal->assignedToUser?->getFullNameAttribute() ?? '-' }}</dd>
              </dl>
              @if($deal->dealStage && $deal->dealStage->is_lost_stage && $deal->lost_reason)
                <hr class="my-2">
                <h6>{{ __('Lost Reason') }}</h6>
                <p class="text-danger">{{ $deal->lost_reason }}</p>
              @endif
            </div>
          </div>
          @if($deal->description)
            <hr class="my-2">
            <h6>{{ __('Description') }}</h6>
            <p>{{ $deal->description }}</p>
          @endif

          <ul class="nav nav-tabs nav-fill mt-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tasks-deal" aria-controls="navs-tasks-deal" aria-selected="true">
                <i class="bx bx-check-square me-1"></i> {{ __('Open Tasks') }} <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-warning ms-1">{{ $deal->tasks->count() }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-activity-deal" aria-controls="navs-activity-deal" aria-selected="false">
                <i class="bx bx-history me-1"></i> {{ __('Activity Log') }}
              </button>
            </li>
          </ul>

          <div class="tab-content pt-4">
            <div class="tab-pane fade show active" id="navs-tasks-deal" role="tabpanel">
              <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm btn-primary add-new-task-for-deal">{{ __('Add New Task') }}</button>
              </div>
              @if($deal->tasks->isNotEmpty())
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
                    @foreach($deal->tasks as $task)
                      <tr>
                        <td>{{ $task->title }}</td>
                        <td><span class="badge" style="background-color:{{ $task->status?->color ?? '#6c757d' }}; color:#fff;">{{ $task->status?->name ?? '-' }}</span></td>
                        <td><span class="badge" style="background-color:{{ $task->priority?->color ?? '#6c757d' }}; color:#fff;">{{ $task->priority?->name ?? '-' }}</span></td>
                        <td>{{ $task->due_date ? $task->due_date->format('d M Y, H:i A') : '-' }}</td>
                        <td><x-datatable-user :user="$task->assignedToUser" /></td>
                        <td>
                          <x-datatable-actions :actions="[
                            ['label' => __('Edit'), 'icon' => 'bx bx-edit', 'class' => 'edit-task-from-related', 'attributes' => 'data-task-id=' . $task->id]
                          ]" :id="$task->id" />
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <p>{{ __('No open tasks associated with this deal.') }}</p>
              @endif
            </div>

            <div class="tab-pane fade" id="navs-activity-deal" role="tabpanel">
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
                        @include('crmcore::partials._activity_log_changes', ['activity' => $activity])
                      </div>
                    </li>
                  @endforeach
                </ul>
              @else
                <p>{{ __('No activity log available for this deal.') }}</p>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @include('crmcore::deals._form')
  @include('crmcore::tasks._form')

@endsection
