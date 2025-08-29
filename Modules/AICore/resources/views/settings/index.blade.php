@extends('layouts.layoutMaster')

@section('title', __('AI Core Settings'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('AI Core Settings')"
    :breadcrumbs="[
      ['name' => __('Settings'), 'url' => route('settings.index')],
      ['name' => __('AI Core'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  <div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3" role="tablist">
      <li class="nav-item">
        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#general" aria-controls="general">
          <i class="bx bx-cog me-1"></i> {{ __('General') }}
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#cost-controls" aria-controls="cost-controls">
          <i class="bx bx-dollar me-1"></i> {{ __('Cost Controls') }}
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#rate-limiting" aria-controls="rate-limiting">
          <i class="bx bx-time me-1"></i> {{ __('Rate Limiting') }}
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#security" aria-controls="security">
          <i class="bx bx-shield me-1"></i> {{ __('Security') }}
        </button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#cache" aria-controls="cache">
          <i class="bx bx-data me-1"></i> {{ __('Cache') }}
        </button>
      </li>
    </ul>

    <form id="ai-settings-form" method="POST" action="{{ route('aicore.settings.update') }}">
      @csrf
      @method('PUT')
      
      <div class="tab-content">
        {{-- General Tab --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ __('General Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="ai_enabled" name="ai_enabled" value="1" 
                           {{ setting('aicore.ai_enabled', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="ai_enabled">
                      {{ __('Enable AI Features') }}
                    </label>
                    <small class="d-block text-muted">{{ __('Enable or disable AI functionality system-wide') }}</small>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="default_temperature" class="form-label">{{ __('Default Temperature') }}</label>
                  <input type="range" class="form-range" id="default_temperature" name="default_temperature" 
                         min="0" max="2" step="0.1" value="{{ setting('aicore.default_temperature', 0.7) }}">
                  <div class="d-flex justify-content-between">
                    <small>0 ({{ __('Deterministic') }})</small>
                    <small id="temperature-value">{{ setting('aicore.default_temperature', 0.7) }}</small>
                    <small>2 ({{ __('Creative') }})</small>
                  </div>
                  <small class="text-muted">{{ __('Default creativity level for AI responses') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="default_max_tokens" class="form-label">{{ __('Default Max Tokens') }}</label>
                  <input type="number" class="form-control" id="default_max_tokens" name="default_max_tokens" 
                         value="{{ setting('aicore.default_max_tokens', 1000) }}" min="1" max="32000">
                  <small class="text-muted">{{ __('Default maximum tokens for AI responses') }}</small>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="request_timeout" class="form-label">{{ __('Request Timeout (seconds)') }}</label>
                  <input type="number" class="form-control" id="request_timeout" name="request_timeout" 
                         value="{{ setting('aicore.request_timeout', 30) }}" min="5" max="300">
                  <small class="text-muted">{{ __('Maximum time to wait for AI API responses') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="daily_token_limit" class="form-label">{{ __('Daily Token Limit') }}</label>
                  <input type="number" class="form-control" id="daily_token_limit" name="daily_token_limit" 
                         value="{{ setting('aicore.daily_token_limit', 100000) }}" min="0" max="10000000">
                  <small class="text-muted">{{ __('Maximum tokens per day per company (0 = unlimited)') }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Cost Controls Tab --}}
        <div class="tab-pane fade" id="cost-controls" role="tabpanel">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ __('Cost Control Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="monthly_budget" class="form-label">{{ __('Monthly Budget (USD)') }}</label>
                  <input type="number" class="form-control" id="monthly_budget" name="monthly_budget" 
                         value="{{ setting('aicore.monthly_budget', 100) }}" min="0" step="0.01">
                  <small class="text-muted">{{ __('Maximum monthly spending on AI services (0 = unlimited)') }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Rate Limiting Tab --}}
        <div class="tab-pane fade" id="rate-limiting" role="tabpanel">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ __('Rate Limiting Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="rate_limit_enabled" name="rate_limit_enabled" value="1"
                           {{ setting('aicore.rate_limit_enabled', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="rate_limit_enabled">
                      {{ __('Enable Rate Limiting') }}
                    </label>
                    <small class="d-block text-muted">{{ __('Enable rate limiting for AI requests') }}</small>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="global_rate_limit" class="form-label">{{ __('Global Rate Limit') }}</label>
                  <input type="number" class="form-control" id="global_rate_limit" name="global_rate_limit" 
                         value="{{ setting('aicore.global_rate_limit', 60) }}" min="1" max="1000">
                  <small class="text-muted">{{ __('Maximum requests per minute system-wide') }}</small>
                </div>

                <div class="col-md-6">
                  <label for="user_rate_limit" class="form-label">{{ __('User Rate Limit') }}</label>
                  <input type="number" class="form-control" id="user_rate_limit" name="user_rate_limit" 
                         value="{{ setting('aicore.user_rate_limit', 20) }}" min="1" max="100">
                  <small class="text-muted">{{ __('Maximum requests per minute per user') }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Security Tab --}}
        <div class="tab-pane fade" id="security" role="tabpanel">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ __('Security Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="log_requests" name="log_requests" value="1"
                           {{ setting('aicore.log_requests', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="log_requests">
                      {{ __('Log AI Requests') }}
                    </label>
                    <small class="d-block text-muted">{{ __('Log all AI requests for monitoring and debugging') }}</small>
                  </div>
                </div>

                <div class="col-md-6">
                  <label for="data_retention_days" class="form-label">{{ __('Data Retention (days)') }}</label>
                  <input type="number" class="form-control" id="data_retention_days" name="data_retention_days" 
                         value="{{ setting('aicore.data_retention_days', 90) }}" min="0" max="365">
                  <small class="text-muted">{{ __('How long to keep AI request logs (0 = forever)') }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Cache Tab --}}
        <div class="tab-pane fade" id="cache" role="tabpanel">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">{{ __('Cache Settings') }}</h5>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="cache_enabled" name="cache_enabled" value="1"
                           {{ setting('aicore.cache_enabled', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="cache_enabled">
                      {{ __('Enable Response Caching') }}
                    </label>
                    <small class="d-block text-muted">{{ __('Cache AI responses for similar requests') }}</small>
                  </div>
                </div>

                <div class="col-md-6">
                  <label for="cache_ttl" class="form-label">{{ __('Cache TTL (seconds)') }}</label>
                  <input type="number" class="form-control" id="cache_ttl" name="cache_ttl" 
                         value="{{ setting('aicore.cache_ttl', 3600) }}" min="60" max="86400">
                  <small class="text-muted">{{ __('How long to cache AI responses in seconds') }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-primary">
          <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
        <button type="button" class="btn btn-label-secondary" onclick="window.location.reload()">
          <i class="bx bx-reset me-1"></i> {{ __('Reset') }}
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Update temperature value display
  const temperatureSlider = document.getElementById('default_temperature');
  const temperatureValue = document.getElementById('temperature-value');
  
  if (temperatureSlider && temperatureValue) {
    temperatureSlider.addEventListener('input', function() {
      temperatureValue.textContent = this.value;
    });
  }

  // Form submission
  const form = document.getElementById('ai-settings-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      
      // Convert checkboxes to proper boolean values
      const checkboxes = form.querySelectorAll('input[type="checkbox"]');
      checkboxes.forEach(checkbox => {
        formData.set(checkbox.name, checkbox.checked ? '1' : '0');
      });

      fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: data.message || 'Settings updated successfully',
            timer: 1500,
            showConfirmButton: false
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: data.message || 'Failed to update settings'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: 'An error occurred while updating settings'
        });
      });
    });
  }
});
</script>
@endsection