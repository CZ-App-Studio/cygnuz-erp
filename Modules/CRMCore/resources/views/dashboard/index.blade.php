@extends('layouts.layoutMaster')

@section('title', __('CRM Dashboard'))

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/CRMCore/resources/assets/js/dashboard.js'])
@endsection

@section('content')
  @php
    $breadcrumbs = [
        ['name' => __('CRM'), 'url' => '#']
    ];
  @endphp

  <x-breadcrumb
    :title="__('Dashboard')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  {{-- Statistics Cards --}}
  <div class="row g-3 mb-4">
    {{-- Companies Card --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded">
                <i class="bx bx-buildings bx-sm"></i>
              </div>
            </div>
            <div class="text-end">
              <h3 class="mb-0">{{ number_format($statistics['total_companies']) }}</h3>
              <small class="text-muted">{{ __('Total Companies') }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center mt-3">
            <small class="text-success">
              <i class="bx bx-check-circle me-1"></i>
              {{ number_format($statistics['active_companies']) }} {{ __('Active') }}
            </small>
          </div>
        </div>
      </div>
    </div>

    {{-- Contacts Card --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded">
                <i class="bx bx-user bx-sm"></i>
              </div>
            </div>
            <div class="text-end">
              <h3 class="mb-0">{{ number_format($statistics['total_contacts']) }}</h3>
              <small class="text-muted">{{ __('Total Contacts') }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center mt-3">
            <small class="text-muted">
              <i class="bx bx-trending-up me-1"></i>
              {{ __('All contacts') }}
            </small>
          </div>
        </div>
      </div>
    </div>

    {{-- Deals Card --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded">
                <i class="bx bx-dollar bx-sm"></i>
              </div>
            </div>
            <div class="text-end">
              <h3 class="mb-0">{{ number_format($statistics['total_deals']) }}</h3>
              <small class="text-muted">{{ __('Total Deals') }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center mt-3">
            <small class="text-info">
              <i class="bx bx-refresh me-1"></i>
              {{ number_format($statistics['open_deals']) }} {{ __('Open') }}
            </small>
            <small class="text-success ms-3">
              <i class="bx bx-check me-1"></i>
              {{ number_format($statistics['won_deals']) }} {{ __('Won') }}
            </small>
          </div>
        </div>
      </div>
    </div>

    {{-- Tasks Card --}}
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <div class="avatar-initial bg-label-warning rounded">
                <i class="bx bx-task bx-sm"></i>
              </div>
            </div>
            <div class="text-end">
              <h3 class="mb-0">{{ number_format($statistics['total_tasks']) }}</h3>
              <small class="text-muted">{{ __('Total Tasks') }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center mt-3">
            <small class="text-warning">
              <i class="bx bx-time me-1"></i>
              {{ number_format($statistics['pending_tasks']) }} {{ __('Pending') }}
            </small>
            <small class="text-success ms-3">
              <i class="bx bx-check me-1"></i>
              {{ number_format($statistics['completed_tasks']) }} {{ __('Done') }}
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Revenue Cards --}}
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar avatar-sm">
              <div class="avatar-initial bg-label-success rounded">
                <i class="bx bx-dollar-circle"></i>
              </div>
            </div>
            <h6 class="mb-0 ms-2">{{ __('Total Revenue') }}</h6>
          </div>
          <h3 class="mb-0 text-success">${{ number_format($revenue['total_revenue'], 2) }}</h3>
          <small class="text-muted">{{ __('From won deals') }}</small>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar avatar-sm">
              <div class="avatar-initial bg-label-info rounded">
                <i class="bx bx-trending-up"></i>
              </div>
            </div>
            <h6 class="mb-0 ms-2">{{ __('Potential Revenue') }}</h6>
          </div>
          <h3 class="mb-0 text-info">${{ number_format($revenue['potential_revenue'], 2) }}</h3>
          <small class="text-muted">{{ __('From open deals') }}</small>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar avatar-sm">
              <div class="avatar-initial bg-label-primary rounded">
                <i class="bx bx-bar-chart-alt-2"></i>
              </div>
            </div>
            <h6 class="mb-0 ms-2">{{ __('Average Deal Value') }}</h6>
          </div>
          <h3 class="mb-0 text-primary">${{ number_format($revenue['average_deal_value'], 2) }}</h3>
          <small class="text-muted">{{ __('Per won deal') }}</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row --}}
  <div class="row g-3 mb-4">
    {{-- Deals Chart --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Deals Overview') }}</h5>
        </div>
        <div class="card-body">
          <div id="dealsChart"></div>
        </div>
      </div>
    </div>

    {{-- Pipeline Distribution --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Pipeline Distribution') }}</h5>
        </div>
        <div class="card-body">
          <div id="pipelineChart"></div>
          <div class="mt-3">
            @foreach($pipelines as $pipeline)
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-truncate">{{ $pipeline->name }}</span>
                <span class="badge bg-label-primary">{{ $pipeline->deals_count }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Additional Charts Row --}}
  <div class="row g-3 mb-4">
    {{-- Lead Sources --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Lead Sources') }}</h5>
        </div>
        <div class="card-body">
          <div id="leadSourcesChart"></div>
        </div>
      </div>
    </div>

    {{-- Task Status --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Task Status Distribution') }}</h5>
        </div>
        <div class="card-body">
          <div id="taskStatusChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Recent Activities & Upcoming Tasks --}}
  <div class="row g-3">
    {{-- Recent Companies --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Recent Companies') }}</h5>
          <a href="{{ route('companies.index') }}" class="btn btn-sm btn-label-primary">{{ __('View All') }}</a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>{{ __('Company') }}</th>
                  <th>{{ __('Assigned To') }}</th>
                  <th>{{ __('Created') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentCompanies as $company)
                  <tr>
                    <td>
                      <a href="{{ route('companies.show', $company) }}" class="text-primary">
                        {{ $company->name }}
                      </a>
                    </td>
                    <td>
                      @if($company->assignedToUser)
                        <x-datatable-user :user="$company->assignedToUser" />
                      @else
                        <span class="text-muted">{{ __('Unassigned') }}</span>
                      @endif
                    </td>
                    <td>{{ $company->created_at->diffForHumans() }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">{{ __('No companies found') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Upcoming Tasks --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Upcoming Tasks') }}</h5>
          <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-label-primary">{{ __('View All') }}</a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>{{ __('Task') }}</th>
                  <th>{{ __('Related To') }}</th>
                  <th>{{ __('Due Date') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($upcomingTasks as $task)
                  <tr>
                    <td>
                      <div>
                        <span class="fw-medium">{{ $task->title }}</span>
                        @if($task->priority && str_contains(strtolower($task->priority->name), 'high'))
                          <span class="badge bg-label-danger ms-2">{{ $task->priority->name }}</span>
                        @elseif($task->priority && str_contains(strtolower($task->priority->name), 'medium'))
                          <span class="badge bg-label-warning ms-2">{{ $task->priority->name }}</span>
                        @elseif($task->priority && str_contains(strtolower($task->priority->name), 'low'))
                          <span class="badge bg-label-info ms-2">{{ $task->priority->name }}</span>
                        @endif
                      </div>
                    </td>
                    <td>
                      @if($task->taskable)
                        @if($task->taskable instanceof \Modules\CRMCore\app\Models\Contact)
                          <a href="{{ route('contacts.show', $task->taskable) }}" class="text-primary">
                            {{ $task->taskable->full_name }}
                          </a>
                        @elseif($task->taskable instanceof \Modules\CRMCore\app\Models\Company)
                          <a href="{{ route('companies.show', $task->taskable) }}" class="text-primary">
                            {{ $task->taskable->name }}
                          </a>
                        @elseif($task->taskable instanceof \Modules\CRMCore\app\Models\Deal)
                          <a href="{{ route('deals.show', $task->taskable) }}" class="text-primary">
                            {{ $task->taskable->title }}
                          </a>
                        @else
                          <span class="text-muted">{{ class_basename($task->taskable) }}</span>
                        @endif
                      @else
                        <span class="text-muted">{{ __('No related entity') }}</span>
                      @endif
                    </td>
                    <td>
                      <span class="{{ $task->due_date->isPast() ? 'text-danger' : '' }}">
                        {{ $task->due_date->format('M d, Y') }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted">{{ __('No upcoming tasks') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const pageData = {
      urls: {
        dealsChart: @json(route('crm.dashboard.deals-chart')),
        leadsChart: @json(route('crm.dashboard.leads-chart')),
        tasksChart: @json(route('crm.dashboard.tasks-chart'))
      },
      pipelines: @json($pipelines),
      labels: {
        deals: @json(__('Deals')),
        won: @json(__('Won')),
        lost: @json(__('Lost')),
        revenue: @json(__('Revenue')),
        leads: @json(__('Leads')),
        tasks: @json(__('Tasks'))
      }
    };
  </script>
@endsection
