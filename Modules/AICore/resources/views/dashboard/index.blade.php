@extends('layouts.layoutMaster')

@section('title', __('AI Dashboard'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/AICore/resources/assets/js/aicore-dashboard.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('AI Dashboard')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('Dashboard'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  {{-- Overview Statistics --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-server bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $overviewStats['total_providers'] }}</h5>
              <small>{{ __('AI Providers') }}</small>
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
                <i class="bx bx-chip bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $overviewStats['total_models'] }}</h5>
              <small>{{ __('AI Models') }}</small>
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
                <i class="bx bx-bar-chart bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ number_format($overviewStats['today']['total_requests'] ?? 0) }}</h5>
              <small>{{ __('Requests Today') }}</small>
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
              <h5 class="mb-0">${{ number_format($overviewStats['month']['total_cost'] ?? 0, 2) }}</h5>
              <small>{{ __('Cost This Month') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Provider Status --}}
    <div class="col-md-6 col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">{{ __('Provider Status') }}</h5>
          <button class="btn btn-sm btn-outline-primary" onclick="refreshProviderStatus()">
            <i class="bx bx-refresh"></i>
          </button>
        </div>
        <div class="card-body">
          <div id="provider-status-list">
            @foreach($providerStatus as $provider)
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="d-flex align-items-center">
                <span class="badge badge-dot bg-{{ $provider['is_connected'] ? 'success' : 'danger' }} me-2"></span>
                <div>
                  <h6 class="mb-0">{{ $provider['name'] }}</h6>
                  <small class="text-muted">{{ ucfirst($provider['type']) }}</small>
                </div>
              </div>
              <div class="text-end">
                @if($provider['is_connected'])
                  <small class="text-success">{{ $provider['response_time'] }}ms</small>
                @else
                  <small class="text-danger">{{ __('Offline') }}</small>
                @endif
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    {{-- Usage Trends Chart --}}
    <div class="col-md-6 col-lg-8 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">{{ __('AI Usage Trends') }}</h5>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              {{ __('Last 30 Days') }}
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#" data-period="7">{{ __('Last 7 Days') }}</a></li>
              <li><a class="dropdown-item" href="#" data-period="30">{{ __('Last 30 Days') }}</a></li>
              <li><a class="dropdown-item" href="#" data-period="90">{{ __('Last 90 Days') }}</a></li>
            </ul>
          </div>
        </div>
        <div class="card-body">
          <div id="usage-trends-chart"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Top Models --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Top AI Models') }}</h5>
        </div>
        <div class="card-body">
          @if(count($topModels) > 0)
            @foreach($topModels as $model)
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <h6 class="mb-0">{{ $model['model_name'] }}</h6>
                <small class="text-muted">{{ $model['provider_name'] }}</small>
              </div>
              <div class="text-end">
                <div class="badge bg-label-primary">{{ number_format($model['total_requests']) }} {{ __('requests') }}</div>
                <small class="d-block text-muted">${{ number_format($model['total_cost'], 2) }}</small>
              </div>
            </div>
            @endforeach
          @else
            <div class="text-center py-4">
              <i class="bx bx-data bx-lg text-muted"></i>
              <p class="text-muted mt-2">{{ __('No usage data available yet') }}</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Recent Activity --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">{{ __('Recent AI Activity') }}</h5>
          <a href="{{ route('aicore.usage.index') }}" class="btn btn-sm btn-outline-primary">
            {{ __('View All') }}
          </a>
        </div>
        <div class="card-body">
          @if(count($recentUsage) > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <tbody>
                  @foreach(array_slice($recentUsage, 0, 10) as $activity)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <span class="badge badge-dot bg-{{ $activity['status'] == 'success' ? 'success' : 'danger' }} me-2"></span>
                        <div>
                          <h6 class="mb-0 text-truncate" style="max-width: 150px;">{{ $activity['module_name'] }}</h6>
                          <small class="text-muted">{{ $activity['operation_type'] }}</small>
                        </div>
                      </div>
                    </td>
                    <td class="text-end">
                      <small class="text-muted">{{ $activity['created_at']->diffForHumans() }}</small>
                      <div class="small text-muted">{{ number_format($activity['total_tokens']) }} tokens</div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4">
              <i class="bx bx-time bx-lg text-muted"></i>
              <p class="text-muted mt-2">{{ __('No recent activity') }}</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
window.pageData = {
  routes: {
    usage: "{{ route('aicore.usage.index') }}",
    providerStatus: "{{ route('aicore.dashboard') }}",
    getData: "{{ route('aicore.dashboard') }}"
  },
  chartData: {
    costTrends: @json($costTrends ?? []),
    topModels: @json($topModels ?? [])
  }
};
</script>
@endsection