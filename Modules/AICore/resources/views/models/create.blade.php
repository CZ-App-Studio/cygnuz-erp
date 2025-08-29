@extends('layouts.layoutMaster')

@section('title', __('Create AI Model'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
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

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('Create AI Model')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('AI Models'), 'url' => route('aicore.models.index')],
      ['name' => __('Create'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  <div class="row">
    <div class="col-xl-8 col-lg-8 col-md-10">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Model Information') }}</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('aicore.models.store') }}" id="createModelForm">
            @csrf
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="provider_id" class="form-label">{{ __('Provider') }} <span class="text-danger">*</span></label>
                <select class="form-select select2 @error('provider_id') is-invalid @enderror" id="provider_id" name="provider_id" data-placeholder="{{ __('Select Provider') }}" required>
                  <option value=""></option>
                  @foreach($providers as $provider)
                    <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>
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
                <select class="form-select select2 @error('type') is-invalid @enderror" id="type" name="type" data-placeholder="{{ __('Select Type') }}" required>
                  <option value=""></option>
                  @foreach($modelTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
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
                       id="name" name="name" value="{{ old('name') }}" 
                       placeholder="{{ __('e.g., GPT-4 Turbo') }}" required maxlength="100">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3">
                <label for="model_identifier" class="form-label">{{ __('Model Identifier') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('model_identifier') is-invalid @enderror" 
                       id="model_identifier" name="model_identifier" value="{{ old('model_identifier') }}" 
                       placeholder="{{ __('e.g., gpt-4-1106-preview') }}" required maxlength="200">
                @error('model_identifier')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">{{ __('The exact identifier used by the provider\'s API') }}</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="max_tokens" class="form-label">{{ __('Max Tokens') }}</label>
                <input type="number" class="form-control @error('max_tokens') is-invalid @enderror" 
                       id="max_tokens" name="max_tokens" value="{{ old('max_tokens', 4096) }}" 
                       placeholder="{{ __('e.g., 4096') }}" min="1" max="32000">
                @error('max_tokens')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label for="cost_per_input_token" class="form-label">{{ __('Input Token Cost ($)') }}</label>
                <input type="number" class="form-control @error('cost_per_input_token') is-invalid @enderror" 
                       id="cost_per_input_token" name="cost_per_input_token" 
                       value="{{ old('cost_per_input_token') }}" 
                       placeholder="0.000001" step="0.000001" min="0">
                @error('cost_per_input_token')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-4 mb-3">
                <label for="cost_per_output_token" class="form-label">{{ __('Output Token Cost ($)') }}</label>
                <input type="number" class="form-control @error('cost_per_output_token') is-invalid @enderror" 
                       id="cost_per_output_token" name="cost_per_output_token" 
                       value="{{ old('cost_per_output_token') }}" 
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
                         {{ old('supports_streaming') ? 'checked' : '' }}>
                  <label class="form-check-label" for="supports_streaming">
                    {{ __('Supports Streaming') }}
                  </label>
                </div>
                <div class="form-text">{{ __('Whether this model supports real-time response streaming') }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                         {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">
                    {{ __('Active') }}
                  </label>
                </div>
                <div class="form-text">{{ __('Whether this model is available for use') }}</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="configuration" class="form-label">{{ __('Configuration (JSON)') }}</label>
              <textarea class="form-control @error('configuration') is-invalid @enderror" 
                        id="configuration" name="configuration" rows="4" 
                        placeholder='{"temperature": 0.7, "top_p": 1.0}'>{{ old('configuration') }}</textarea>
              @error('configuration')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">{{ __('Additional configuration parameters in JSON format (optional)') }}</div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('aicore.models.index') }}" class="btn btn-label-secondary">
                <i class="bx bx-arrow-back me-1"></i> {{ __('Cancel') }}
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Model') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Info Card --}}
    <div class="col-xl-4 col-lg-4 col-md-2">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Model Guidelines') }}</h5>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <h6 class="alert-heading">
              <i class="bx bx-info-circle me-1"></i> {{ __('Guidelines') }}
            </h6>
            <small>
              <strong>{{ __('Model Name:') }}</strong> {{ __('User-friendly display name') }}<br>
              <strong>{{ __('Identifier:') }}</strong> {{ __('Exact API model name') }}<br>
              <strong>{{ __('Type:') }}</strong> {{ __('Primary capability of the model') }}<br>
              <strong>{{ __('Tokens:') }}</strong> {{ __('Maximum context window') }}<br>
              <strong>{{ __('Costs:') }}</strong> {{ __('Usually very small decimals') }}
            </small>
          </div>
          
          <div class="mb-3">
            <h6>{{ __('Common Model Types:') }}</h6>
            <ul class="list-unstyled">
              <li><small><strong>{{ __('Text:') }}</strong> GPT, Claude, Llama</small></li>
              <li><small><strong>{{ __('Image:') }}</strong> DALL-E, Midjourney, Stable Diffusion</small></li>
              <li><small><strong>{{ __('Embedding:') }}</strong> {{ __('Text similarity models') }}</small></li>
              <li><small><strong>{{ __('Multimodal:') }}</strong> {{ __('Vision + text models') }}</small></li>
            </ul>
          </div>

          <div class="mb-3">
            <h6>{{ __('Examples:') }}</h6>
            <ul class="list-unstyled">
              <li><small><strong>GPT-4:</strong> gpt-4-1106-preview</small></li>
              <li><small><strong>Claude:</strong> claude-3-opus-20240229</small></li>
              <li><small><strong>DALL-E:</strong> dall-e-3</small></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection