@extends('layouts.layoutMaster')

@section('title', __('AI Model Details') . ' - ' . $model->name)

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <x-breadcrumb 
      :title="__('Model Details')"
      :breadcrumbs="[
        ['name' => __('AI Core'), 'url' => route('aicore.dashboard')],
        ['name' => __('Models'), 'url' => route('aicore.models.index')],
        ['name' => $model->name, 'url' => null]
      ]" 
    />

  <!-- Model Details Card -->
  <div class="row">
    <div class="col-xl-8 col-lg-8 col-md-8">
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">{{ $model->name }}</h5>
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bx bx-cog me-1"></i> {{ __('Actions') }}
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('aicore.models.edit', $model) }}">
                  <i class="bx bx-edit me-2"></i> {{ __('Edit Model') }}
                </a></li>
                <li>
                  <button class="dropdown-item test-model-btn" data-model-id="{{ $model->id }}">
                    <i class="bx bx-check-circle me-2"></i> {{ __('Test Model') }}
                  </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="POST" action="{{ route('aicore.models.destroy', $model) }}" class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">
                      <i class="bx bx-trash me-2"></i> {{ __('Delete Model') }}
                    </button>
                  </form>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-borderless">
                <tr>
                  <td class="fw-semibold">{{ __('Provider') }}:</td>
                  <td>
                    <a href="{{ route('aicore.providers.show', $model->provider) }}" class="text-primary">
                      {{ $model->provider->name }}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Model Identifier') }}:</td>
                  <td><code>{{ $model->model_identifier }}</code></td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Type') }}:</td>
                  <td>
                    <span class="badge badge-center rounded-pill 
                      @switch($model->type)
                        @case('text') bg-label-primary @break
                        @case('image') bg-label-success @break
                        @case('embedding') bg-label-info @break
                        @case('multimodal') bg-label-warning @break
                        @default bg-label-secondary
                      @endswitch">
                      {{ ucfirst($model->type) }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Status') }}:</td>
                  <td>
                    <span class="badge badge-center rounded-pill {{ $model->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                      {{ $model->is_active ? __('Active') : __('Inactive') }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Max Tokens') }}:</td>
                  <td>{{ number_format($model->max_tokens) }}</td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <table class="table table-borderless">
                <tr>
                  <td class="fw-semibold">{{ __('Streaming Support') }}:</td>
                  <td>
                    <span class="badge badge-center rounded-pill {{ $model->supports_streaming ? 'bg-label-success' : 'bg-label-secondary' }}">
                      {{ $model->supports_streaming ? __('Yes') : __('No') }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Input Token Cost') }}:</td>
                  <td>${{ number_format($model->cost_per_input_token, 6) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Output Token Cost') }}:</td>
                  <td>${{ number_format($model->cost_per_output_token, 6) }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Created') }}:</td>
                  <td>{{ $model->created_at->format('M j, Y g:i A') }}</td>
                </tr>
                <tr>
                  <td class="fw-semibold">{{ __('Last Updated') }}:</td>
                  <td>{{ $model->updated_at->format('M j, Y g:i A') }}</td>
                </tr>
              </table>
            </div>
          </div>

          @if($model->configuration)
            <div class="mt-4">
              <h6>{{ __('Configuration') }}</h6>
              <div class="bg-light p-3 rounded">
                <pre class="mb-0">{{ json_encode($model->configuration, JSON_PRETTY_PRINT) }}</pre>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Statistics Card -->
    <div class="col-xl-4 col-lg-4 col-md-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Usage Statistics') }}</h5>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar avatar-sm me-3">
              <div class="avatar-initial bg-label-primary rounded">
                <i class="bx bx-chart"></i>
              </div>
            </div>
            <div>
              <p class="mb-0 small">{{ __('Total Requests (30 days)') }}</p>
              <h6 class="mb-0">0</h6>
            </div>
          </div>
          
          <div class="d-flex align-items-center mb-3">
            <div class="avatar avatar-sm me-3">
              <div class="avatar-initial bg-label-success rounded">
                <i class="bx bx-coin"></i>
              </div>
            </div>
            <div>
              <p class="mb-0 small">{{ __('Total Tokens') }}</p>
              <h6 class="mb-0">0</h6>
            </div>
          </div>
          
          <div class="d-flex align-items-center mb-3">
            <div class="avatar avatar-sm me-3">
              <div class="avatar-initial bg-label-info rounded">
                <i class="bx bx-dollar"></i>
              </div>
            </div>
            <div>
              <p class="mb-0 small">{{ __('Total Cost') }}</p>
              <h6 class="mb-0">$0.00</h6>
            </div>
          </div>

          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-3">
              <div class="avatar-initial bg-label-warning rounded">
                <i class="bx bx-time"></i>
              </div>
            </div>
            <div>
              <p class="mb-0 small">{{ __('Avg Response Time') }}</p>
              <h6 class="mb-0">0ms</h6>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions Card -->
      <div class="card mt-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Quick Actions') }}</h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="{{ route('aicore.models.edit', $model) }}" class="btn btn-primary">
              <i class="bx bx-edit me-1"></i> {{ __('Edit Model') }}
            </a>
            <a href="{{ route('aicore.models.index') }}" class="btn btn-outline-secondary">
              <i class="bx bx-arrow-back me-1"></i> {{ __('Back to Models') }}
            </a>
            @if($model->is_active)
              <button class="btn btn-outline-success" disabled>
                <i class="bx bx-check me-1"></i> {{ __('Model Active') }}
              </button>
            @else
              <button class="btn btn-outline-warning" disabled>
                <i class="bx bx-pause me-1"></i> {{ __('Model Inactive') }}
              </button>
            @endif
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
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  @vite(['Modules/AICore/resources/assets/js/aicore-model-details.js'])
@endsection