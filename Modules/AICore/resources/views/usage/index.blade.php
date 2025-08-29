@extends('layouts.layoutMaster')

@section('title', __('AI Usage Analytics'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/AICore/resources/assets/js/aicore-usage-analytics.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('AI Usage Analytics')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('Usage Analytics'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  {{-- Period Filter --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-md-8">
          <div class="row g-3">
            <div class="col-md-3">
              <select class="form-select" id="period-filter">
                <option value="7">{{ __('Last 7 Days') }}</option>
                <option value="30" selected>{{ __('Last 30 Days') }}</option>
                <option value="90">{{ __('Last 90 Days') }}</option>
                <option value="custom">{{ __('Custom Range') }}</option>
              </select>
            </div>
            <div class="col-md-3" id="date-range-container" style="display: none;">
              <div class="input-group">
                <input type="text" class="form-control" id="date-range" placeholder="{{ __('Select date range') }}">
                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select" id="provider-filter">
                <option value="">{{ __('All Providers') }}</option>
                @if(isset($providers))
                  @foreach($providers as $provider)
                    <option value="{{ $provider->id }}" {{ $providerId == $provider->id ? 'selected' : '' }}>
                      {{ $provider->name }}
                    </option>
                  @endforeach
                @endif
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-select" id="module-filter">
                <option value="">{{ __('All Modules') }}</option>
                @if(isset($modules))
                  @foreach($modules as $module)
                    <option value="{{ $module->module_name }}" {{ $moduleName == $module->module_name ? 'selected' : '' }}>
                      {{ $module->module_display_name }}
                    </option>
                  @endforeach
                @endif
              </select>
            </div>
          </div>
          <div class="row g-3 mt-2">
            <div class="col-md-3">
              <button class="btn btn-primary w-100" onclick="applyFilters()">
                <i class="bx bx-filter"></i> {{ __('Apply Filters') }}
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-4 text-end">
          <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-secondary" onclick="refreshData()">
              <i class="bx bx-refresh"></i> {{ __('Refresh') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Usage Summary Cards --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-message bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0" id="total-requests">{{ number_format($summary['total_requests'] ?? 0) }}</h5>
              <small>{{ __('Total Requests') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-chip bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0" id="total-tokens">{{ number_format($summary['total_tokens'] ?? 0) }}</h5>
              <small>{{ __('Total Tokens') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="bx bx-dollar bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0" id="total-cost">${{ number_format($summary['total_cost'] ?? 0, 2) }}</h5>
              <small>{{ __('Total Cost') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="bx bx-time bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0" id="avg-response-time">{{ number_format($summary['avg_response_time'] ?? 0) }}ms</h5>
              <small>{{ __('Avg Response Time') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Usage Trends Chart --}}
    <div class="col-lg-8 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">{{ __('Usage Trends') }}</h5>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              {{ __('Requests') }}
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item chart-metric" href="#" data-metric="requests">{{ __('Requests') }}</a></li>
              <li><a class="dropdown-item chart-metric" href="#" data-metric="tokens">{{ __('Tokens') }}</a></li>
              <li><a class="dropdown-item chart-metric" href="#" data-metric="cost">{{ __('Cost') }}</a></li>
              <li><a class="dropdown-item chart-metric" href="#" data-metric="response_time">{{ __('Response Time') }}</a></li>
            </ul>
          </div>
        </div>
        <div class="card-body">
          <div id="usage-trends-chart"></div>
        </div>
      </div>
    </div>

    {{-- Top Models --}}
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Top Models by Usage') }}</h5>
        </div>
        <div class="card-body">
          <div id="top-models-list">
            @if(isset($topModels) && count($topModels) > 0)
              @foreach($topModels as $model)
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <h6 class="mb-0">{{ $model['model_name'] }}</h6>
                  <small class="text-muted">{{ $model['provider_name'] }}</small>
                </div>
                <div class="text-end">
                  <div class="badge bg-label-primary">{{ number_format($model['total_requests']) }}</div>
                  <small class="d-block text-muted">${{ number_format($model['total_cost'], 2) }}</small>
                </div>
              </div>
              @endforeach
            @else
              <div class="text-center py-4">
                <i class="bx bx-data bx-lg text-muted"></i>
                <p class="text-muted mt-2">{{ __('No usage data available') }}</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Module Usage Breakdown --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Usage by Module') }}</h5>
        </div>
        <div class="card-body">
          <div id="module-usage-chart"></div>
        </div>
      </div>
    </div>

    {{-- Provider Cost Breakdown --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Cost by Provider') }}</h5>
        </div>
        <div class="card-body">
          <div id="provider-cost-chart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Detailed Usage Logs --}}
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="card-title mb-0">{{ __('Detailed Usage Logs') }}</h5>
      <div class="d-flex gap-2">
        <select class="form-select form-select-sm" id="logs-status-filter" style="width: auto;">
          <option value="">{{ __('All Status') }}</option>
          <option value="success">{{ __('Success') }}</option>
          <option value="error">{{ __('Error') }}</option>
          <option value="timeout">{{ __('Timeout') }}</option>
        </select>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover" id="usage-logs-table">
          <thead>
            <tr>
              <th>{{ __('Timestamp') }}</th>
              <th>{{ __('Module') }}</th>
              <th>{{ __('Operation') }}</th>
              <th>{{ __('Model') }}</th>
              <th>{{ __('Tokens') }}</th>
              <th>{{ __('Cost') }}</th>
              <th>{{ __('Response Time') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @if(isset($recentLogs) && count($recentLogs) > 0)
              @foreach($recentLogs as $log)
              <tr>
                <td>
                  <div>
                    <span class="fw-semibold">{{ $log['created_at']->format('M d, H:i') }}</span>
                    <small class="d-block text-muted">{{ $log['created_at']->diffForHumans() }}</small>
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-secondary">{{ $log['module_name'] }}</span>
                </td>
                <td>{{ $log['operation_type'] }}</td>
                <td>
                  <div>
                    <span class="fw-semibold">{{ $log['model_name'] }}</span>
                    <small class="d-block text-muted">{{ $log['provider_name'] }}</small>
                  </div>
                </td>
                <td>{{ number_format($log['total_tokens']) }}</td>
                <td>${{ number_format($log['cost'], 6) }}</td>
                <td>{{ $log['processing_time_ms'] ? $log['processing_time_ms'] . 'ms' : '-' }}</td>
                <td>
                  @switch($log['status'])
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
                      <span class="badge bg-label-secondary">{{ ucfirst($log['status']) }}</span>
                  @endswitch
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary view-log-details" 
                          data-log-id="{{ $log['id'] }}" 
                          data-bs-toggle="tooltip" 
                          title="{{ __('View Details') }}">
                    <i class="bx bx-show-alt"></i>
                  </button>
                </td>
              </tr>
              @endforeach
            @else
              <tr>
                <td colspan="9" class="text-center py-4">
                  <i class="bx bx-data bx-lg text-muted"></i>
                  <p class="text-muted mt-2">{{ __('No usage logs found for the selected period') }}</p>
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
window.pageData = {
  routes: {
    getData: "{{ route('aicore.usage.index') }}",
    export: "{{ route('aicore.usage.export') }}"
  },
  chartData: {
    usageTrends: @json($chartData['usage_trends'] ?? []),
    moduleUsage: @json($chartData['module_usage'] ?? []),
    providerCost: @json($chartData['provider_cost'] ?? [])
  },
  currentPeriod: {{ $period ?? 30 }},
  currentProvider: "{{ $providerId ?? '' }}"
};
</script>
@endsection