@extends('layouts.layoutMaster')

@section('title', __('AI Usage Log Details'))

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

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('Usage Log Details')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('Usage Analytics'), 'url' => route('aicore.usage.index')],
      ['name' => __('Log Details'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  <div class="row">
    {{-- Main Information Card --}}
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">{{ __('Log Information') }}</h5>
          <div>
            @switch($log->status)
              @case('success')
                <span class="badge bg-label-success">{{ __('Success') }}</span>
                @break
              @case('error')
                <span class="badge bg-label-danger">{{ __('Error') }}</span>
                @break
              @case('timeout')
                <span class="badge bg-label-warning">{{ __('Timeout') }}</span>
                @break
              @default
                <span class="badge bg-label-secondary">{{ ucfirst($log->status) }}</span>
            @endswitch
          </div>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('Log ID') }}</h6>
              <p class="mb-0">#{{ $log->id }}</p>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('Timestamp') }}</h6>
              <p class="mb-0">{{ $log->created_at->format('M d, Y H:i:s') }}</p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('Module') }}</h6>
              <p class="mb-0"><span class="badge bg-label-primary">{{ $log->module_name }}</span></p>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('Operation Type') }}</h6>
              <p class="mb-0">{{ $log->operation_type }}</p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('AI Model') }}</h6>
              <p class="mb-0">{{ $log->model->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('Provider') }}</h6>
              <p class="mb-0">{{ $log->model->provider->name ?? 'N/A' }}</p>
            </div>
          </div>

          @if($log->user)
          <div class="row mb-3">
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('User') }}</h6>
              <p class="mb-0">{{ $log->user->name }}</p>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted mb-1">{{ __('User Email') }}</h6>
              <p class="mb-0">{{ $log->user->email }}</p>
            </div>
          </div>
          @endif

          @if($log->error_message)
          <div class="row mb-3">
            <div class="col-12">
              <h6 class="text-muted mb-1">{{ __('Error Message') }}</h6>
              <div class="alert alert-danger mb-0">
                {{ $log->error_message }}
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>

      {{-- Performance Metrics Card --}}
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Performance Metrics') }}</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="d-flex align-items-center">
                <div class="avatar me-3">
                  <span class="avatar-initial rounded bg-label-info">
                    <i class="bx bx-time"></i>
                  </span>
                </div>
                <div>
                  <h6 class="mb-1">{{ __('Processing Time') }}</h6>
                  <p class="mb-0 text-muted">{{ $log->processing_time_ms ? number_format($log->processing_time_ms) . ' ms' : 'N/A' }}</p>
                </div>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <div class="d-flex align-items-center">
                <div class="avatar me-3">
                  <span class="avatar-initial rounded bg-label-success">
                    <i class="bx bx-trending-up"></i>
                  </span>
                </div>
                <div>
                  <h6 class="mb-1">{{ __('Tokens/Second') }}</h6>
                  <p class="mb-0 text-muted">{{ number_format($metrics['tokens_per_second'], 2) }}</p>
                </div>
              </div>
            </div>
          </div>

          @if($comparisonStats)
          <hr class="my-3">
          <h6 class="mb-3">{{ __('Comparison with Similar Operations (7 days)') }}</h6>
          <div class="row">
            <div class="col-md-4">
              <small class="text-muted d-block">{{ __('Average Processing Time') }}</small>
              <strong>{{ number_format($comparisonStats->avg_processing_time) }} ms</strong>
            </div>
            <div class="col-md-4">
              <small class="text-muted d-block">{{ __('Min Processing Time') }}</small>
              <strong>{{ number_format($comparisonStats->min_processing_time) }} ms</strong>
            </div>
            <div class="col-md-4">
              <small class="text-muted d-block">{{ __('Max Processing Time') }}</small>
              <strong>{{ number_format($comparisonStats->max_processing_time) }} ms</strong>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="col-lg-4">
      {{-- Token Usage Card --}}
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Token Usage') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>{{ __('Prompt Tokens') }}</span>
              <strong>{{ number_format($log->prompt_tokens) }}</strong>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-primary" role="progressbar" 
                   style="width: {{ $metrics['prompt_percentage'] }}%" 
                   aria-valuenow="{{ $metrics['prompt_percentage'] }}" 
                   aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>{{ __('Completion Tokens') }}</span>
              <strong>{{ number_format($log->completion_tokens) }}</strong>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-success" role="progressbar" 
                   style="width: {{ $metrics['completion_percentage'] }}%" 
                   aria-valuenow="{{ $metrics['completion_percentage'] }}" 
                   aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{ __('Total Tokens') }}</h6>
            <h5 class="mb-0 text-primary">{{ number_format($log->total_tokens) }}</h5>
          </div>
        </div>
      </div>

      {{-- Cost Analysis Card --}}
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Cost Analysis') }}</h5>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span>{{ __('Request Cost') }}</span>
            <h4 class="mb-0 text-warning">${{ number_format($log->cost, 6) }}</h4>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <span>{{ __('Cost per Token') }}</span>
            <strong>${{ number_format($metrics['cost_per_token'], 8) }}</strong>
          </div>

          @if($comparisonStats)
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <span>{{ __('7-Day Avg Cost') }}</span>
            <strong>${{ number_format($comparisonStats->avg_cost, 6) }}</strong>
          </div>
          @endif
        </div>
      </div>

      {{-- Request Details Card --}}
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Request Details') }}</h5>
        </div>
        <div class="card-body">
          @if($log->company_id)
          <div class="mb-2">
            <small class="text-muted d-block">{{ __('Company ID') }}</small>
            <strong>{{ $log->company_id }}</strong>
          </div>
          @endif

          @if($log->request_hash)
          <div class="mb-2">
            <small class="text-muted d-block">{{ __('Request Hash') }}</small>
            <code style="font-size: 11px;">{{ $log->request_hash }}</code>
          </div>
          @endif

          <div class="mb-2">
            <small class="text-muted d-block">{{ __('Created') }}</small>
            <strong>{{ $log->created_at->diffForHumans() }}</strong>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Related Logs --}}
  @if($relatedLogs && $relatedLogs->count() > 0)
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Related Recent Logs') }}</h5>
      <small class="text-muted">{{ __('Similar operations in the last 24 hours') }}</small>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>{{ __('Time') }}</th>
              <th>{{ __('Model') }}</th>
              <th>{{ __('Tokens') }}</th>
              <th>{{ __('Cost') }}</th>
              <th>{{ __('Response Time') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($relatedLogs as $relatedLog)
            <tr>
              <td>{{ $relatedLog->created_at->format('H:i:s') }}</td>
              <td>{{ $relatedLog->model->name ?? 'N/A' }}</td>
              <td>{{ number_format($relatedLog->total_tokens) }}</td>
              <td>${{ number_format($relatedLog->cost, 6) }}</td>
              <td>{{ $relatedLog->processing_time_ms ? $relatedLog->processing_time_ms . 'ms' : '-' }}</td>
              <td>
                @switch($relatedLog->status)
                  @case('success')
                    <span class="badge bg-label-success">{{ __('Success') }}</span>
                    @break
                  @case('error')
                    <span class="badge bg-label-danger">{{ __('Error') }}</span>
                    @break
                  @case('timeout')
                    <span class="badge bg-label-warning">{{ __('Timeout') }}</span>
                    @break
                  @default
                    <span class="badge bg-label-secondary">{{ ucfirst($relatedLog->status) }}</span>
                @endswitch
              </td>
              <td>
                <a href="{{ route('aicore.usage.show', $relatedLog->id) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-show-alt"></i>
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection