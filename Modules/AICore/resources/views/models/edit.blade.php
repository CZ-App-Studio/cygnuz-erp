@extends('layouts.layoutMaster')

@section('title', __('Edit AI Model') . ' - ' . $model->name)

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
  ])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <x-breadcrumb 
      :title="__('Edit Model')"
      :breadcrumbs="[
        ['name' => __('AI Core'), 'url' => route('aicore.dashboard')],
        ['name' => __('Models'), 'url' => route('aicore.models.index')],
        ['name' => __('Edit') . ': ' . $model->name, 'url' => null]
      ]" 
    />

  <div class="row">
    <div class="col-xl-8 col-lg-8 col-md-10">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Edit AI Model') }}</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('aicore.models.update', $model) }}">
            @csrf
            @method('PUT')
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="provider_id" class="form-label">{{ __('Provider') }} <span class="text-danger">*</span></label>
                <select class="form-select @error('provider_id') is-invalid @enderror" id="provider_id" name="provider_id" required>
                  <option value="">{{ __('Select Provider') }}</option>
                  @foreach($providers as $provider)
                    <option value="{{ $provider->id }}" {{ old('provider_id', $model->provider_id) == $provider->id ? 'selected' : '' }}>
                      {{ $provider->name }} ({{ ucfirst($provider->type) }})
                    </option>
                  @endforeach
                </select>
                @error('provider_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label for="type" class="form-label">{{ __('Model Type') }} <span class="text-danger">*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                  <option value="">{{ __('Select Type') }}</option>
                  @foreach($modelTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('type', $model->type) == $key ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                @error('type')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="name" class="form-label">{{ __('Model Name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name', $model->name) }}" 
                       placeholder="e.g., GPT-4 Turbo" required maxlength="100">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label for="model_identifier" class="form-label">{{ __('Model Identifier') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('model_identifier') is-invalid @enderror" 
                       id="model_identifier" name="model_identifier" value="{{ old('model_identifier', $model->model_identifier) }}" 
                       placeholder="e.g., gpt-4-1106-preview" required maxlength="200">
                @error('model_identifier')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">{{ __("The exact identifier used by the provider's API") }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="max_tokens" class="form-label">{{ __('Max Tokens') }}</label>
                <input type="number" class="form-control @error('max_tokens') is-invalid @enderror" 
                       id="max_tokens" name="max_tokens" value="{{ old('max_tokens', $model->max_tokens) }}" 
                       placeholder="e.g., 4096" min="1" max="32000">
                @error('max_tokens')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label for="cost_per_input_token" class="form-label">{{ __('Input Token Cost ($)') }}</label>
                <input type="number" class="form-control @error('cost_per_input_token') is-invalid @enderror" 
                       id="cost_per_input_token" name="cost_per_input_token" 
                       value="{{ old('cost_per_input_token', $model->cost_per_input_token) }}" 
                       placeholder="0.000001" step="0.000001" min="0">
                @error('cost_per_input_token')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label for="cost_per_output_token" class="form-label">{{ __('Output Token Cost ($)') }}</label>
                <input type="number" class="form-control @error('cost_per_output_token') is-invalid @enderror" 
                       id="cost_per_output_token" name="cost_per_output_token" 
                       value="{{ old('cost_per_output_token', $model->cost_per_output_token) }}" 
                       placeholder="0.000002" step="0.000001" min="0">
                @error('cost_per_output_token')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="supports_streaming" name="supports_streaming" value="1" 
                         {{ old('supports_streaming', $model->supports_streaming) ? 'checked' : '' }}>
                  <label class="form-check-label" for="supports_streaming">
                    {{ __('Supports Streaming') }}
                  </label>
                </div>
                <div class="form-text">{{ __('Whether this model supports real-time response streaming') }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                         {{ old('is_active', $model->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">
                    {{ __('Active') }}
                  </label>
                </div>
                <div class="form-text">{{ __('Whether this model is available for use') }}</div>
              </div>
            </div>

            @if($model->configuration)
              <div class="mb-3">
                <label for="configuration" class="form-label">{{ __('Configuration (JSON)') }}</label>
                <textarea class="form-control @error('configuration') is-invalid @enderror" 
                          id="configuration" name="configuration" rows="4" 
                          placeholder='{"temperature": 0.7, "top_p": 1.0}'>{{ old('configuration', json_encode($model->configuration, JSON_PRETTY_PRINT)) }}</textarea>
                @error('configuration')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">{{ __('Additional configuration parameters in JSON format') }}</div>
              </div>
            @endif

            <div class="d-flex justify-content-between">
              <a href="{{ route('aicore.models.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> {{ __('Back to Models') }}
              </a>
              <div>
                <a href="{{ route('aicore.models.show', $model) }}" class="btn btn-outline-info me-2">
                  <i class="bx bx-show me-1"></i> {{ __('View Details') }}
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save me-1"></i> {{ __('Update Model') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Info Card -->
    <div class="col-xl-4 col-lg-4 col-md-2">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Model Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <small class="text-muted">{{ __('Current Provider') }}</small>
            <div class="fw-semibold">{{ $model->provider->name }}</div>
          </div>
          
          <div class="mb-3">
            <small class="text-muted">{{ __('Current Status') }}</small>
            <div>
              <span class="badge badge-center rounded-pill {{ $model->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                {{ $model->is_active ? __('Active') : __('Inactive') }}
              </span>
            </div>
          </div>
          
          <div class="mb-3">
            <small class="text-muted">{{ __('Created') }}</small>
            <div class="fw-semibold">{{ $model->created_at->format('M j, Y') }}</div>
          </div>
          
          <div class="mb-3">
            <small class="text-muted">{{ __('Last Updated') }}</small>
            <div class="fw-semibold">{{ $model->updated_at->format('M j, Y') }}</div>
          </div>

          <hr>
          
          <div class="alert alert-info">
            <h6 class="alert-heading">
              <i class="bx bx-info-circle me-1"></i> {{ __('Tips') }}
            </h6>
            <small>
              • {{ __('Use exact model identifiers from the provider\'s documentation') }}<br>
              • {{ __('Token costs are typically very small decimal values') }}<br>
              • {{ __('Streaming is useful for real-time applications') }}
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>{{-- End container --}}
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
    'resources/assets/vendor/libs/select2/select2.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/AICore/resources/assets/js/aicore-model-form.js'])
  
  {{-- Include Gemini-specific assets if GeminiAIProvider module is enabled --}}
  @if(isset($geminiProviderEnabled) && $geminiProviderEnabled)
    @vite(['Modules/GeminiAIProvider/resources/assets/js/gemini-model-suggestions.js'])
  @endif
@endsection