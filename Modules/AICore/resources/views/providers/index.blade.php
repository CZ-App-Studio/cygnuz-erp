@extends('layouts.layoutMaster')

@section('title', __('AI Providers'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    // Pass data needed by the aicore-providers.js file
    const pageData = {
      urls: {
        create: @json(route('aicore.providers.create')),
        testConnection: @json(route('aicore.providers.test', ['provider' => ':id'])),
        refresh: @json(route('aicore.providers.index'))
      },
      translations: {
        deleteConfirm: @json(__('Are you sure you want to delete this provider?')),
        testingConnection: @json(__('Testing connection...')),
        connectionSuccess: @json(__('Connection successful')),
        connectionFailed: @json(__('Connection failed'))
      }
    };
  </script>
  @vite(['Modules/AICore/resources/assets/js/aicore-providers.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb Component --}}
  <x-breadcrumb
    :title="__('AI Providers')"
    :breadcrumbs="[
      ['name' => __('Artificial Intelligence'), 'url' => ''],
      ['name' => __('AI Providers'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
  />

  {{-- @if(count($availableAddons) > 0)
    <!-- Available Provider Addons Alert -->
    <div class="alert alert-info">
      <h6 class="alert-heading">
        <i class="bx bx-info-circle me-1"></i> More AI Providers Available
      </h6>
      <p class="mb-2">Expand your AI capabilities with additional provider addons. Currently showing <strong>{{ $providers->count() }}</strong> enabled providers.</p>
      <div class="row">
        @foreach(array_slice($availableAddons, 0, 3) as $addonName => $addon)
          <div class="col-md-4 mb-2">
            <strong>{{ $addon['name'] }}</strong> - {{ $addon['price'] }}<br>
            <small class="text-muted">{{ $addon['description'] }}</small>
          </div>
        @endforeach
      </div>
      <div class="mt-2">
        <a href="#available-addons" class="btn btn-sm btn-primary">View All {{ count($availableAddons) }} Available Addons</a>
      </div>
    </div>
  @endif --}}

  {{-- Provider Statistics --}}
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
              <h5 class="mb-0">{{ $providers->where('is_active', true)->count() }}</h5>
              <small>{{ __('Active Providers') }}</small>
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
              <h5 class="mb-0">{{ $providers->sum(function($p) { return $p->models->count(); }) }}</h5>
              <small>{{ __('Total Models') }}</small>
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
                <i class="bx bx-server bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $providers->where('type', 'openai')->count() }}</h5>
              <small>{{ __('OpenAI Providers') }}</small>
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
                <i class="bx bx-brain bx-sm"></i>
              </span>
            </div>
            <div class="card-info text-end">
              <h5 class="mb-0">{{ $providers->whereIn('type', ['claude', 'gemini'])->count() }}</h5>
              <small>{{ __('Other Providers') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Providers Table --}}
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover" id="providers-table">
          <thead>
            <tr>
              <th>{{ __('Provider') }}</th>
              <th>{{ __('Type') }}</th>
              <th>{{ __('Models') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Rate Limit') }}</th>
              <th>{{ __('Priority') }}</th>
              <th>{{ __('Connection') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($providers as $provider)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-3">
                    <span class="avatar-initial rounded bg-label-{{ $provider->type == 'openai' ? 'success' : ($provider->type == 'claude' ? 'info' : 'warning') }}">
                      @switch($provider->type)
                        @case('openai')
                          <i class="bx bx-bot"></i>
                          @break
                        @case('claude')
                          <i class="bx bx-brain"></i>
                          @break
                        @case('gemini')
                          <i class="bx bx-diamond"></i>
                          @break
                        @default
                          <i class="bx bx-server"></i>
                      @endswitch
                    </span>
                  </div>
                  <div>
                    <h6 class="mb-0">{{ $provider->name }}</h6>
                    <small class="text-muted">ID: {{ $provider->id }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-label-secondary">{{ ucfirst($provider->type) }}</span>
              </td>
              <td>
                <span class="badge bg-label-info">{{ $provider->models->count() }} {{ __('models') }}</span>
              </td>
              <td>
                @if($provider->is_active)
                  <span class="badge bg-label-success">{{ __('Active') }}</span>
                @else
                  <span class="badge bg-label-secondary">{{ __('Inactive') }}</span>
                @endif
              </td>
              <td>
                <small class="text-muted">{{ $provider->max_requests_per_minute }}/min</small>
              </td>
              <td>
                <span class="badge bg-label-primary">{{ $provider->priority }}</span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-primary test-connection"
                        data-provider-id="{{ $provider->id }}"
                        data-bs-toggle="tooltip"
                        title="{{ __('Test Connection') }}">
                  <i class="bx bx-wifi"></i>
                </button>
              </td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('aicore.providers.show', $provider) }}">
                      <i class="bx bx-show-alt me-1"></i> {{ __('View Details') }}
                    </a>
                    <a class="dropdown-item" href="{{ route('aicore.providers.edit', $provider) }}">
                      <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
                    </a>
                    <a class="dropdown-item" href="{{ route('aicore.models.index', ['provider' => $provider->id]) }}">
                      <i class="bx bx-chip me-1"></i> {{ __('Manage Models') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('aicore.providers.destroy', $provider) }}" method="POST" class="d-inline delete-form">
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

  {{-- @if(count($availableAddons) > 0)
    <!-- Available Provider Addons Section -->
    <div class="card mt-4" id="available-addons">
      <div class="card-header">
        <h5 class="card-title mb-0">Available AI Provider Addons</h5>
        <p class="card-subtitle text-muted mt-1">Expand your AI capabilities with additional providers</p>
      </div>
      <div class="card-body">
        <div class="row">
          @foreach($availableAddons as $addonName => $addon)
            <div class="col-lg-6 col-xl-4 mb-4">
              <div class="card h-100 border">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 class="card-title mb-1">{{ $addon['name'] }}</h6>
                      <span class="badge bg-label-primary">{{ $addon['models_count'] }} models</span>
                    </div>
                    <div class="text-end">
                      <h6 class="text-primary mb-0">{{ $addon['price'] }}</h6>
                    </div>
                  </div>

                  <p class="card-text text-muted mb-3">{{ $addon['description'] }}</p>

                  <div class="mb-3">
                    <h6 class="mb-2">Features:</h6>
                    <ul class="list-unstyled">
                      @foreach($addon['features'] as $feature)
                        <li class="mb-1">
                          <i class="bx bx-check text-success me-1"></i>
                          <small>{{ $feature }}</small>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <button class="btn btn-primary w-100" onclick="purchaseAddon('{{ $addonName }}')">
                    <i class="bx bx-shopping-bag me-1"></i> Purchase Addon
                  </button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif --}}
</div>

<script>
  /**
   * Purchase addon functionality
   */
  function purchaseAddon(addonName) {
    const addons = @json($availableAddons);
    const addon = addons[addonName];

    Swal.fire({
      title: 'Purchase AI Provider Addon',
      html: `
        <div class="text-start">
          <h6>${addon.name}</h6>
          <p class="text-muted">${addon.description}</p>
          <p><strong>Price:</strong> ${addon.price}</p>
          <p><strong>Models:</strong> ${addon.models_count} AI models included</p>
          <p class="small text-muted">This will redirect you to our marketplace where you can complete the purchase.</p>
        </div>
      `,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Continue to Marketplace',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-2',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Redirect to marketplace or purchase page
        window.open(`/marketplace/addons/${addonName}`, '_blank');
      }
    });
  }
</script>

@endsection
