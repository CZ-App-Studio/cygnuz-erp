@extends('layouts.layoutMaster')

@section('title', __('Add AI Provider'))

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
    :title="__('Add AI Provider')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('AI Providers'), 'url' => route('aicore.providers.index')],
      ['name' => __('Add Provider'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  <div class="row">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Provider Information') }}</h5>
        </div>
        <div class="card-body">
          <form action="{{ route('aicore.providers.store') }}" method="POST" id="provider-form">
            @csrf
            
            {{-- Basic Information --}}
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="name" class="form-label">{{ __('Provider Name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" 
                       placeholder="{{ __('Enter provider name') }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label for="type" class="form-label">{{ __('Provider Type') }} <span class="text-danger">*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                  <option value="">{{ __('Select provider type') }}</option>
                  @foreach($providerTypes as $value => $label)
                    <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
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
                <label for="api_key" class="form-label">{{ __('API Key') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" class="form-control @error('api_key') is-invalid @enderror" 
                         id="api_key" name="api_key" value="{{ old('api_key') }}"
                         placeholder="{{ __('Enter API key') }}">
                  <button class="btn btn-outline-secondary" type="button" id="toggle-api-key">
                    <i class="bx bx-show"></i>
                  </button>
                </div>
                @error('api_key')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">{{ __('API key will be encrypted and stored securely') }}</small>
              </div>
              <div class="col-md-6">
                <label for="endpoint_url" class="form-label">{{ __('Endpoint URL') }}</label>
                <input type="url" class="form-control @error('endpoint_url') is-invalid @enderror" 
                       id="endpoint_url" name="endpoint_url" value="{{ old('endpoint_url') }}"
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
                       value="{{ old('max_requests_per_minute', 60) }}" min="1" max="1000">
                @error('max_requests_per_minute')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="max_tokens_per_request" class="form-label">{{ __('Max Tokens/Request') }}</label>
                <input type="number" class="form-control @error('max_tokens_per_request') is-invalid @enderror" 
                       id="max_tokens_per_request" name="max_tokens_per_request" 
                       value="{{ old('max_tokens_per_request', 4000) }}" min="1" max="32000">
                @error('max_tokens_per_request')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="cost_per_token" class="form-label">{{ __('Cost per Token ($)') }}</label>
                <input type="number" class="form-control @error('cost_per_token') is-invalid @enderror" 
                       id="cost_per_token" name="cost_per_token" 
                       value="{{ old('cost_per_token', 0.000015) }}" min="0" step="0.000001">
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
                    <option value="{{ $i }}" {{ old('priority', 1) == $i ? 'selected' : '' }}>
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
                         {{ old('is_active', true) ? 'checked' : '' }}>
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
                <button type="button" class="btn btn-outline-primary" id="test-connection-btn">
                  <i class="bx bx-wifi"></i> {{ __('Test Connection') }}
                </button>
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save"></i> {{ __('Create Provider') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Help Panel --}}
    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <h6 class="card-title mb-0">{{ __('Provider Setup Guide') }}</h6>
        </div>
        <div class="card-body">
          <div id="provider-help-content">
            <div class="text-center text-muted">
              <i class="bx bx-info-circle bx-lg"></i>
              <p class="mt-2">{{ __('Select a provider type to see setup instructions') }}</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Quick Stats --}}
      <div class="card mt-3">
        <div class="card-header">
          <h6 class="card-title mb-0">{{ __('Provider Types') }}</h6>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted">OpenAI</span>
            <span class="badge bg-label-success">{{ __('Most Popular') }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted">Claude</span>
            <span class="badge bg-label-info">{{ __('Enterprise') }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted">Gemini</span>
            <span class="badge bg-label-warning">{{ __('Cost Effective') }}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted">Custom</span>
            <span class="badge bg-label-secondary">{{ __('Advanced') }}</span>
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
    testConnection: "{{ route('aicore.providers.test', ':id') }}".replace(':id', 'test')
  },
  providerHelp: {
    openai: {
      title: "OpenAI Setup",
      content: "1. Get API key from OpenAI platform<br>2. Default endpoint: https://api.openai.com/v1<br>3. Rate limit: 60 requests/minute<br>4. Supports GPT-4, GPT-3.5, Embeddings"
    },
    claude: {
      title: "Claude (Anthropic) Setup", 
      content: "1. Get API key from Anthropic Console<br>2. Default endpoint: https://api.anthropic.com/v1<br>3. Rate limit: 50 requests/minute<br>4. Supports Claude 3 Sonnet, Haiku"
    },
    gemini: {
      title: "Google Gemini Setup",
      content: "1. Get API key from Google AI Studio<br>2. Default endpoint: https://generativelanguage.googleapis.com/v1<br>3. Rate limit: 60 requests/minute<br>4. Supports Gemini Pro, Pro Vision"
    },
    local: {
      title: "Local Model Setup",
      content: "1. No API key required<br>2. Configure local endpoint URL<br>3. Set appropriate rate limits<br>4. Ensure model server is running"
    },
    custom: {
      title: "Custom Provider Setup",
      content: "1. Configure custom endpoint URL<br>2. Set API key if required<br>3. Configure rate limits as needed<br>4. Test connection before saving"
    }
  }
};
</script>
@endsection