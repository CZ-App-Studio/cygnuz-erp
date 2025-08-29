@extends('layouts.layoutMaster')

@section('title', __('AI Models'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/select2/select2.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/AICore/resources/assets/js/aicore-models.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('AI Models')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('AI Models'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  {{-- Model Statistics --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="bx bx-check-circle bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $models->where('is_active', true)->count() }}</h5>
              <small>{{ __('Active Models') }}</small>
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
                <i class="bx bx-message bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $models->where('type', 'text')->count() }}</h5>
              <small>{{ __('Text Models') }}</small>
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
                <i class="bx bx-image bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $models->whereIn('type', ['image', 'multimodal'])->count() }}</h5>
              <small>{{ __('Vision Models') }}</small>
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
              <span class="avatar-initial rounded bg-label-primary">
                <i class="bx bx-play bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $models->where('supports_streaming', true)->count() }}</h5>
              <small>{{ __('Streaming Support') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filters Card --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">{{ __('Provider') }}</label>
          <select class="form-select select2" id="provider-filter" data-placeholder="{{ __('All Providers') }}">
            <option value="">{{ __('All Providers') }}</option>
            @foreach($providers as $provider)
              <option value="{{ $provider->id }}">{{ $provider->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('Type') }}</label>
          <select class="form-select select2" id="type-filter" data-placeholder="{{ __('All Types') }}">
            <option value="">{{ __('All Types') }}</option>
            <option value="text">{{ __('Text') }}</option>
            <option value="image">{{ __('Image') }}</option>
            <option value="embedding">{{ __('Embedding') }}</option>
            <option value="multimodal">{{ __('Multimodal') }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('Status') }}</label>
          <select class="form-select select2" id="status-filter" data-placeholder="{{ __('All Status') }}">
            <option value="">{{ __('All Status') }}</option>
            <option value="active">{{ __('Active') }}</option>
            <option value="inactive">{{ __('Inactive') }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">&nbsp;</label>
          <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
            <i class="bx bx-filter-alt"></i> {{ __('Clear Filters') }}
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Models Table --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('AI Models Management') }}</h5>
      <a href="{{ route('aicore.models.create') }}" class="btn btn-primary">
        <i class="bx bx-plus"></i> {{ __('Add Model') }}
      </a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover" id="models-table">
          <thead>
            <tr>
              <th>{{ __('Model') }}</th>
              <th>{{ __('Provider') }}</th>
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
            @foreach($models as $model)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-3">
                    <span class="avatar-initial rounded bg-label-{{ $model->type == 'text' ? 'primary' : ($model->type == 'image' ? 'warning' : 'info') }}">
                      @switch($model->type)
                        @case('text')
                          <i class="bx bx-message"></i>
                          @break
                        @case('image')
                          <i class="bx bx-image"></i>
                          @break
                        @case('embedding')
                          <i class="bx bx-vector"></i>
                          @break
                        @case('multimodal')
                          <i class="bx bx-layer"></i>
                          @break
                        @default
                          <i class="bx bx-chip"></i>
                      @endswitch
                    </span>
                  </div>
                  <div>
                    <h6 class="mb-0">{{ $model->name }}</h6>
                    <small class="text-muted">{{ $model->model_identifier }}</small>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <span class="badge bg-label-secondary me-2">{{ ucfirst($model->provider->type) }}</span>
                  <span>{{ $model->provider->name }}</span>
                </div>
              </td>
              <td>
                <span class="badge bg-label-{{ $model->type == 'text' ? 'primary' : ($model->type == 'image' ? 'warning' : 'info') }}">
                  {{ ucfirst($model->type) }}
                </span>
              </td>
              <td>{{ number_format($model->max_tokens) }}</td>
              <td>
                @if($model->cost_per_input_token)
                  ${{ number_format($model->cost_per_input_token, 8) }}
                @else
                  <span class="text-muted">{{ __('N/A') }}</span>
                @endif
              </td>
              <td>
                @if($model->cost_per_output_token)
                  ${{ number_format($model->cost_per_output_token, 8) }}
                @else
                  <span class="text-muted">{{ __('N/A') }}</span>
                @endif
              </td>
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
                      <i class="bx bx-show-alt me-1"></i> {{ __('View Details') }}
                    </a>
                    <a class="dropdown-item" href="{{ route('aicore.models.edit', $model) }}">
                      <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
                    </a>
                    <button class="dropdown-item test-model" data-model-id="{{ $model->id }}">
                      <i class="bx bx-play me-1"></i> {{ __('Test Model') }}
                    </button>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('aicore.models.destroy', $model) }}" method="POST" class="d-inline delete-form">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Model Test Modal --}}
<div class="modal fade" id="modelTestModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Test AI Model') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="model-test-form">
          <div class="mb-3">
            <label for="test-prompt" class="form-label">{{ __('Test Prompt') }}</label>
            <textarea class="form-control" id="test-prompt" rows="3" 
                      placeholder="{{ __('Enter a test prompt to send to the model...') }}"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="test-max-tokens" class="form-label">{{ __('Max Tokens') }}</label>
                <input type="number" class="form-control" id="test-max-tokens" value="100" min="1" max="4000">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="test-temperature" class="form-label">{{ __('Temperature') }}</label>
                <input type="number" class="form-control" id="test-temperature" value="0.7" min="0" max="2" step="0.1">
              </div>
            </div>
          </div>
        </form>
        <div id="test-results" class="mt-3 d-none">
          <h6>{{ __('Response:') }}</h6>
          <div class="border rounded p-3 bg-light">
            <pre id="test-response" class="mb-0"></pre>
          </div>
          <div class="row mt-2">
            <div class="col-md-4">
              <small class="text-muted">{{ __('Tokens Used:') }} <span id="tokens-used">-</span></small>
            </div>
            <div class="col-md-4">
              <small class="text-muted">{{ __('Cost:') }} $<span id="cost-used">-</span></small>
            </div>
            <div class="col-md-4">
              <small class="text-muted">{{ __('Response Time:') }} <span id="response-time">-</span>ms</small>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <button type="button" class="btn btn-primary" id="run-test-btn">
          <i class="bx bx-play"></i> {{ __('Run Test') }}
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
window.pageData = {
  routes: {
    testModel: "{{ route('api.ai.complete') }}",
    refresh: "{{ route('aicore.models.index') }}"
  },
  translations: {
    deleteTitle: "{{ __('Are you sure?') }}",
    deleteConfirm: "{{ __('This will permanently delete the AI model. This action cannot be undone.') }}",
    confirmButton: "{{ __('Yes, delete it!') }}",
    cancelButton: "{{ __('Cancel') }}",
    testingModel: "{{ __('Testing model...') }}",
    testSuccess: "{{ __('Test completed successfully') }}",
    testFailed: "{{ __('Test failed') }}"
  }
};
</script>
@endsection