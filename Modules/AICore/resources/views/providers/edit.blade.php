@extends('layouts.layoutMaster')

@section('title', __('Edit AI Provider'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/select2/select2.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/AICore/resources/assets/js/aicore-provider-form.js'])
  
  {{-- Include Gemini-specific assets if GeminiAIProvider module is enabled --}}
  @if(isset($geminiProviderEnabled) && $geminiProviderEnabled)
    <script>
      // Inject Gemini configuration when module is enabled
      window.GeminiProviderDefaults = {
        endpoint: 'https://generativelanguage.googleapis.com/v1beta',
        rateLimit: 60,
        tokenLimit: 32768,
        cost: 0.00001875
      };
    </script>
  @endif
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('Edit AI Provider')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('AI Providers'), 'url' => route('aicore.providers.index')],
      ['name' => $provider->name, 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  <div class="row">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">{{ __('Edit Provider: :name', ['name' => $provider->name]) }}</h5>
          <div class="d-flex gap-2">
            <span class="badge bg-label-{{ $provider->is_active ? 'success' : 'secondary' }}">
              {{ $provider->is_active ? __('Active') : __('Inactive') }}
            </span>
            <span class="badge bg-label-info">{{ ucfirst($provider->type) }}</span>
          </div>
        </div>
        <div class="card-body">
          <form action="{{ route('aicore.providers.update', $provider) }}" method="POST" id="provider-form">
            @csrf
            @method('PUT')
            
            {{-- Basic Information --}}
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="name" class="form-label">{{ __('Provider Name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name', $provider->name) }}" 
                       placeholder="{{ __('Enter provider name') }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label for="type" class="form-label">{{ __('Provider Type') }} <span class="text-danger">*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                  @foreach($providerTypes as $value => $label)
                    <option value="{{ $value }}" {{ old('type', $provider->type) == $value ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                @error('type')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            {{-- API Configuration --}}
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="api_key" class="form-label">{{ __('API Key') }}</label>
                <div class="input-group">
                  <input type="password" class="form-control @error('api_key') is-invalid @enderror" 
                         id="api_key" name="api_key" value="{{ old('api_key') }}"
                         placeholder="{{ __('Enter new API key to update') }}">
                  <button class="btn btn-outline-secondary" type="button" id="toggle-api-key">
                    <i class="bx bx-show"></i>
                  </button>
                </div>
                @error('api_key')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                  {{ __('Leave empty to keep current API key. Current key is encrypted and hidden for security.') }}
                </small>
              </div>
              <div class="col-md-6">
                <label for="endpoint_url" class="form-label">{{ __('Endpoint URL') }}</label>
                <input type="url" class="form-control @error('endpoint_url') is-invalid @enderror" 
                       id="endpoint_url" name="endpoint_url" value="{{ old('endpoint_url', $provider->endpoint_url) }}"
                       placeholder="{{ __('Enter custom endpoint URL (optional)') }}">
                @error('endpoint_url')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            {{-- Rate Limiting --}}
            <div class="row mb-3">
              <div class="col-md-4">
                <label for="max_requests_per_minute" class="form-label">{{ __('Max Requests/Minute') }}</label>
                <input type="number" class="form-control @error('max_requests_per_minute') is-invalid @enderror" 
                       id="max_requests_per_minute" name="max_requests_per_minute" 
                       value="{{ old('max_requests_per_minute', $provider->max_requests_per_minute) }}" 
                       min="1" max="1000">
                @error('max_requests_per_minute')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="max_tokens_per_request" class="form-label">{{ __('Max Tokens/Request') }}</label>
                <input type="number" class="form-control @error('max_tokens_per_request') is-invalid @enderror" 
                       id="max_tokens_per_request" name="max_tokens_per_request" 
                       value="{{ old('max_tokens_per_request', $provider->max_tokens_per_request) }}" 
                       min="1" max="32000">
                @error('max_tokens_per_request')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="cost_per_token" class="form-label">{{ __('Cost per Token ($)') }}</label>
                <input type="number" class="form-control @error('cost_per_token') is-invalid @enderror" 
                       id="cost_per_token" name="cost_per_token" 
                       value="{{ old('cost_per_token', $provider->cost_per_token) }}" 
                       min="0" step="0.000001">
                @error('cost_per_token')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            {{-- Settings --}}
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="priority" class="form-label">{{ __('Priority') }}</label>
                <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority">
                  @for($i = 1; $i <= 10; $i++)
                    <option value="{{ $i }}" {{ old('priority', $provider->priority) == $i ? 'selected' : '' }}>
                      {{ $i }} {{ $i == 1 ? '(Highest)' : ($i == 10 ? '(Lowest)' : '') }}
                    </option>
                  @endfor
                </select>
                @error('priority')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                         {{ old('is_active', $provider->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">
                    {{ __('Enable this provider') }}
                  </label>
                </div>
              </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="d-flex justify-content-between">
              <a href="{{ route('aicore.providers.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back"></i> {{ __('Back to Providers') }}
              </a>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" id="test-connection-btn" 
                        data-provider-id="{{ $provider->id }}">
                  <i class="bx bx-wifi"></i> {{ __('Test Connection') }}
                </button>
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save"></i> {{ __('Update Provider') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Provider Stats --}}
    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <h6 class="card-title mb-0">{{ __('Provider Statistics') }}</h6>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted">{{ __('Models') }}</span>
            <span class="badge bg-label-info">{{ $provider->models->count() }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted">{{ __('Active Models') }}</span>
            <span class="badge bg-label-success">{{ $provider->models->where('is_active', true)->count() }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted">{{ __('Created') }}</span>
            <small class="text-muted">{{ $provider->created_at->format('M d, Y') }}</small>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted">{{ __('Last Updated') }}</span>
            <small class="text-muted">{{ $provider->updated_at->format('M d, Y') }}</small>
          </div>
        </div>
      </div>

      {{-- Models Quick View --}}
      @if($provider->models->count() > 0)
      <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h6 class="card-title mb-0">{{ __('Associated Models') }}</h6>
          <a href="{{ route('aicore.models.index', ['provider' => $provider->id]) }}" class="btn btn-sm btn-outline-primary">
            {{ __('Manage') }}
          </a>
        </div>
        <div class="card-body">
          @foreach($provider->models->take(5) as $model)
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
              <h6 class="mb-0">{{ $model->name }}</h6>
              <small class="text-muted">{{ ucfirst($model->type) }}</small>
            </div>
            <span class="badge bg-label-{{ $model->is_active ? 'success' : 'secondary' }}">
              {{ $model->is_active ? __('Active') : __('Inactive') }}
            </span>
          </div>
          @endforeach
          @if($provider->models->count() > 5)
          <div class="text-center mt-2">
            <small class="text-muted">{{ __('and :count more models', ['count' => $provider->models->count() - 5]) }}</small>
          </div>
          @endif
        </div>
      </div>
      @endif

      {{-- Quick Actions --}}
      <div class="card mt-3">
        <div class="card-header">
          <h6 class="card-title mb-0">{{ __('Quick Actions') }}</h6>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="{{ route('aicore.providers.show', $provider) }}" class="btn btn-outline-primary btn-sm">
              <i class="bx bx-show-alt me-1"></i> {{ __('View Details') }}
            </a>
            <a href="{{ route('aicore.models.create', ['provider' => $provider->id]) }}" class="btn btn-outline-success btn-sm">
              <i class="bx bx-plus me-1"></i> {{ __('Add Model') }}
            </a>
            <button class="btn btn-outline-info btn-sm" onclick="duplicateProvider({{ $provider->id }})">
              <i class="bx bx-copy me-1"></i> {{ __('Duplicate Provider') }}
            </button>
          </div>
        </div>
      </div>
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
    type: "{{ $provider->type }}"
  }
};
</script>
@endsection