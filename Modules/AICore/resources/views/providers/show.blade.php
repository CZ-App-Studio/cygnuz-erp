@extends('layouts.layoutMaster')

@section('title', __('Provider Details'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/AICore/resources/assets/js/aicore-provider-details.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="$provider->name"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('AI Providers'), 'url' => route('aicore.providers.index')],
      ['name' => $provider->name, 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  {{-- Provider Header --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-md-8">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-lg me-4">
              <span class="avatar-initial rounded bg-label-{{ $provider->type == 'openai' ? 'success' : ($provider->type == 'claude' ? 'info' : 'warning') }}">
                @switch($provider->type)
                  @case('openai')
                    <i class="bx bx-bot bx-lg"></i>
                    @break
                  @case('claude')
                    <i class="bx bx-brain bx-lg"></i>
                    @break
                  @case('gemini')
                    <i class="bx bx-diamond bx-lg"></i>
                    @break
                  @default
                    <i class="bx bx-server bx-lg"></i>
                @endswitch
              </span>
            </div>
            <div>
              <h4 class="mb-1">{{ $provider->name }}</h4>
              <div class="d-flex gap-2 mb-2">
                <span class="badge bg-label-{{ $provider->is_active ? 'success' : 'secondary' }}">
                  {{ $provider->is_active ? __('Active') : __('Inactive') }}
                </span>
                <span class="badge bg-label-info">{{ ucfirst($provider->type) }}</span>
                <span class="badge bg-label-primary">{{ __('Priority') }} {{ $provider->priority }}</span>
              </div>
              <p class="text-muted mb-0">
                {{ __('Added on :date', ['date' => $provider->created_at->format('M d, Y')]) }} • 
                {{ __('Last updated :date', ['date' => $provider->updated_at->diffForHumans()]) }}
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4 text-end">
          <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-primary" id="test-connection-btn" data-provider-id="{{ $provider->id }}">
              <i class="bx bx-wifi"></i> {{ __('Test Connection') }}
            </button>
            <a href="{{ route('aicore.providers.edit', $provider) }}" class="btn btn-primary">
              <i class="bx bx-edit-alt"></i> {{ __('Edit') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Configuration Details --}}
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Configuration') }}</h5>
        </div>
        <div class="card-body">
          <div class="info-container">
            <div class="info-item mb-3">
              <label class="form-label text-muted">{{ __('Endpoint URL') }}</label>
              <p class="mb-0">{{ $provider->endpoint_url ?: __('Default') }}</p>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">{{ __('Rate Limit') }}</label>
              <p class="mb-0">{{ $provider->max_requests_per_minute }} {{ __('requests/minute') }}</p>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">{{ __('Max Tokens') }}</label>
              <p class="mb-0">{{ number_format($provider->max_tokens_per_request) }} {{ __('tokens/request') }}</p>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">{{ __('Cost per Token') }}</label>
              <p class="mb-0">${{ number_format($provider->cost_per_token, 8) }}</p>
            </div>
            <div class="info-item">
              <label class="form-label text-muted">{{ __('API Key Status') }}</label>
              <p class="mb-0">
                @if($provider->api_key_encrypted)
                  <span class="badge bg-label-success">{{ __('Configured') }}</span>
                @else
                  <span class="badge bg-label-warning">{{ __('Not Set') }}</span>
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Usage Statistics --}}
    <div class="col-lg-8 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">{{ __('Usage Statistics') }}</h5>
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
          @if(isset($usageStats) && !empty($usageStats))
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="text-center">
                <h4 class="text-primary mb-0">{{ number_format($usageStats['total_requests'] ?? 0) }}</h4>
                <small class="text-muted">{{ __('Total Requests') }}</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <h4 class="text-success mb-0">{{ number_format($usageStats['total_tokens'] ?? 0) }}</h4>
                <small class="text-muted">{{ __('Total Tokens') }}</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <h4 class="text-warning mb-0">${{ number_format($usageStats['total_cost'] ?? 0, 2) }}</h4>
                <small class="text-muted">{{ __('Total Cost') }}</small>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <h4 class="text-info mb-0">{{ number_format($usageStats['avg_response_time'] ?? 0) }}ms</h4>
                <small class="text-muted">{{ __('Avg Response') }}</small>
              </div>
            </div>
          </div>
          <div id="usage-chart"></div>
          @else
          <div class="text-center py-5">
            <i class="bx bx-data bx-lg text-muted"></i>
            <p class="text-muted mt-2">{{ __('No usage data available for this provider yet') }}</p>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Models Table --}}
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="card-title mb-0">{{ __('AI Models') }} ({{ $provider->models->count() }})</h5>
      <a href="{{ route('aicore.models.create', ['provider' => $provider->id]) }}" class="btn btn-primary">
        <i class="bx bx-plus"></i> {{ __('Add Model') }}
      </a>
    </div>
    <div class="card-body">
      @if($provider->models->count() > 0)
      <div class="table-responsive">
        <table class="table table-hover" id="models-table">
          <thead>
            <tr>
              <th>{{ __('Model Name') }}</th>
              <th>{{ __('Identifier') }}</th>
              <th>{{ __('Type') }}</th>
              <th>{{ __('Max Tokens') }}</th>
              <th>{{ __('Input Cost') }}</th>
              <th>{{ __('Output Cost') }}</th>
              <th>{{ __('Streaming') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($provider->models as $model)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded bg-label-info">
                      <i class="bx bx-chip"></i>
                    </span>
                  </div>
                  <div>
                    <h6 class="mb-0">{{ $model->name }}</h6>
                    <small class="text-muted">ID: {{ $model->id }}</small>
                  </div>
                </div>
              </td>
              <td>
                <code class="text-muted">{{ $model->model_identifier }}</code>
              </td>
              <td>
                <span class="badge bg-label-secondary">{{ ucfirst($model->type) }}</span>
              </td>
              <td>{{ number_format($model->max_tokens) }}</td>
              <td>${{ number_format($model->cost_per_input_token, 8) }}</td>
              <td>${{ number_format($model->cost_per_output_token, 8) }}</td>
              <td>
                @if($model->supports_streaming)
                  <span class="badge bg-label-success">{{ __('Yes') }}</span>
                @else
                  <span class="badge bg-label-secondary">{{ __('No') }}</span>
                @endif
              </td>
              <td>
                @if($model->is_active)
                  <span class="badge bg-label-success">{{ __('Active') }}</span>
                @else
                  <span class="badge bg-label-secondary">{{ __('Inactive') }}</span>
                @endif
              </td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('aicore.models.show', $model) }}">
                      <i class="bx bx-show-alt me-1"></i> {{ __('View') }}
                    </a>
                    <a class="dropdown-item" href="{{ route('aicore.models.edit', $model) }}">
                      <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
                    </a>
                  </div>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="text-center py-5">
        <i class="bx bx-chip bx-lg text-muted"></i>
        <p class="text-muted mt-2">{{ __('No models configured for this provider') }}</p>
        <a href="{{ route('aicore.models.create', ['provider' => $provider->id]) }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Add First Model') }}
        </a>
      </div>
      @endif
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
window.pageData = {
  routes: {
    testConnection: "{{ route('aicore.providers.test', $provider) }}"
  },
  provider: {
    id: {{ $provider->id }},
    name: "{{ $provider->name }}",
    type: "{{ $provider->type }}"
  },
  usageStats: @json($usageStats ?? [])
};
</script>
@endsection