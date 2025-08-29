@php
  $configData = Helper::appClasses();
  use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Addons'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-style')
<style>
  .category-sidebar {
    height: calc(100vh - 200px);
    overflow-y: auto;
    border-right: 1px solid rgba(67, 89, 113, 0.1);
  }

  .category-item {
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .category-item:hover {
    background-color: rgba(105, 108, 255, 0.08);
  }

  .category-item.active {
    background-color: rgba(105, 108, 255, 0.16);
    border-left: 3px solid #696cff;
  }

  .module-content {
    height: calc(100vh - 200px);
    overflow-y: auto;
  }

  .module-table th {
    font-weight: 600;
    background-color: #f5f5f9;
  }

  .module-status {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
  }

  .dependency-badge {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
  }
</style>
@endsection

@section('content')
  <div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="fw-bold mb-0">{{ __('Addon Management') }}</h4>
          <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#uploadSection">
            <i class="bx bx-upload me-1"></i> {{ __('Upload Addon') }}
          </button>
        </div>
      </div>
    </div>

    {{-- Upload Form (Initially Collapsed) --}}
    <div class="collapse mb-4" id="uploadSection">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">{{ __('Upload New Addon') }}</h5>
          <form action="{{ route('module.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
              <div class="col-md-8">
                <input type="file" name="module" class="form-control" accept=".zip" required>
                <div class="form-text">{{ __('Select a zip file containing the addon module') }}</div>
              </div>
              <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="bx bx-upload me-1"></i> {{ __('Upload & Install') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Demo Mode Alert --}}
    @if($isDemo)
      <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <i class="bx bx-info-circle me-1"></i>
        <strong>{{ __('Demo Mode') }}:</strong> {{ __('Purchase links are available for premium addons. Module management features are disabled.') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    {{-- Main Content --}}
    <div class="card">
      <div class="card-body p-0">
        <div class="row g-0">
          {{-- Left Sidebar - Categories --}}
          <div class="col-md-3">
            <div class="category-sidebar p-4">
              <h6 class="text-uppercase text-muted mb-3">{{ __('Categories') }}</h6>

              {{-- All Modules --}}
              <div class="category-item p-3 active" data-category="all">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center">
                    <i class="bx bx-grid-alt me-2 text-primary"></i>
                    <span class="fw-semibold">{{ __('All Modules') }}</span>
                  </div>
                  <span class="badge bg-primary">
                    {{ array_sum(array_column($categoryData, 'count')) }}
                  </span>
                </div>
              </div>

              {{-- Categories --}}
              @foreach($categoryData as $categoryName => $category)
                <div class="category-item p-3" data-category="{{ Str::slug($categoryName) }}">
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                      <i class="bx {{ $category['info']['icon'] }} me-2 text-primary"></i>
                      <div>
                        <div class="fw-semibold">{{ __($categoryName) }}</div>
                        <div class="text-muted small">{{ __($category['info']['description']) }}</div>
                      </div>
                    </div>
                    <span class="badge bg-label-secondary">{{ $category['count'] }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          {{-- Right Content - Modules --}}
          <div class="col-md-9">
            <div class="module-content p-4">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0" id="categoryTitle">{{ __('All Modules') }}</h5>
                <div class="d-flex gap-2">
                  <input type="text" class="form-control form-control-sm" id="searchModules"
                         placeholder="{{ __('Search modules...') }}" style="width: 250px;">
                  <select class="form-select form-select-sm" id="filterStatus" style="width: 150px;">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="enabled">{{ __('Enabled') }}</option>
                    <option value="disabled">{{ __('Disabled') }}</option>
                    <option value="not-installed">{{ __('Not Installed') }}</option>
                  </select>
                </div>
              </div>

              {{-- Modules Table --}}
              <div class="table-responsive">
                <table class="table module-table">
                  <thead>
                    <tr>
                      <th>{{ __('Module') }}</th>
                      <th>{{ __('Version') }}</th>
                      <th>{{ __('Status') }}</th>
                      <th>{{ __('Dependencies') }}</th>
                      <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                  </thead>
                  <tbody id="modulesTableBody">
                    {{-- All modules will be loaded here --}}
                    @foreach($categoryData as $categoryName => $category)
                      @foreach($category['modules'] as $module)
                        <tr class="module-row"
                            data-category="{{ Str::slug($categoryName) }}"
                            data-status="{{ $module['installed'] ? ($module['enabled'] ? 'enabled' : 'disabled') : 'not-installed' }}"
                            data-name="{{ strtolower($module['displayName'] ?? $module['name']) }}">
                          <td>
                            <div>
                              <div class="fw-semibold">{{ $module['displayName'] ?? $module['name'] }}</div>
                              <div class="text-muted small">{{ Str::limit($module['description'] ?? '', 60) }}</div>
                              @if(isset($module['isApplication']) && $module['isApplication'])
                                <div class="mt-1">
                                  <span class="badge bg-label-info me-1">{{ $module['platform'] }}</span>
                                  @if(!empty($module['technology']))
                                    <span class="badge bg-label-secondary">{{ $module['technology'] }}</span>
                                  @endif
                                </div>
                              @endif
                            </div>
                          </td>
                          <td>
                            <span class="badge bg-label-info">v{{ $module['version'] ?? '1.0.0' }}</span>
                          </td>
                          <td>
                            <div class="module-status">
                              @if($module['installed'])
                                @if($module['enabled'])
                                  <i class="bx bx-check-circle text-success"></i>
                                  <span class="text-success">{{ __('Enabled') }}</span>
                                @else
                                  <i class="bx bx-x-circle text-warning"></i>
                                  <span class="text-warning">{{ __('Disabled') }}</span>
                                @endif
                              @else
                                <i class="bx bx-download text-muted"></i>
                                <span class="text-muted">{{ __('Not Installed') }}</span>
                              @endif
                            </div>
                          </td>
                          <td>
                            @if(!empty($module['dependencies']))
                              @foreach($module['dependencies'] as $dependency)
                                <span class="badge bg-label-primary dependency-badge">{{ $dependency }}</span>
                              @endforeach
                            @else
                              <span class="text-muted">-</span>
                            @endif
                          </td>
                          <td class="text-end">
                            @if($module['installed'])
                              <div class="btn-group btn-group-sm">
                                {{-- Core modules cannot be disabled or deleted --}}
                                @if(!isset($module['isCoreModule']) || !$module['isCoreModule'])
                                  @if($module['enabled'])
                                    <form action="{{ route('module.deactivate') }}" method="POST" class="d-inline">
                                      @csrf
                                      <input type="hidden" name="module" value="{{ $module['name'] }}">
                                      <button type="submit" class="btn btn-outline-warning" title="{{ __('Deactivate') }}">
                                        <i class="bx bx-power-off"></i>
                                      </button>
                                    </form>
                                  @else
                                    <form action="{{ route('module.activate') }}" method="POST" class="d-inline">
                                      @csrf
                                      <input type="hidden" name="module" value="{{ $module['name'] }}">
                                      <button type="submit" class="btn btn-outline-success" title="{{ __('Activate') }}">
                                        <i class="bx bx-power-off"></i>
                                      </button>
                                    </form>
                                  @endif

                                  @if(!$isDemo)
                                    <button type="button" class="btn btn-outline-danger uninstall-module"
                                            data-module="{{ $module['name'] }}" title="{{ __('Uninstall') }}">
                                      <i class="bx bx-trash"></i>
                                    </button>
                                    <form id="uninstall-form-{{ $module['name'] }}" action="{{ route('module.uninstall') }}"
                                          method="POST" class="d-none">
                                      @csrf
                                      @method('DELETE')
                                      <input type="hidden" name="module" value="{{ $module['name'] }}">
                                    </form>
                                  @endif
                                @else
                                  {{-- Core module - show as always enabled --}}
                                  <span class="text-muted small">{{ __('Core Module') }}</span>
                                @endif

                                @if($module['documentationUrl'] ?? false)
                                  <a href="{{ $module['documentationUrl'] }}" target="_blank"
                                     class="btn btn-outline-info" title="{{ __('Documentation') }}">
                                    <i class="bx bx-book"></i>
                                  </a>
                                @endif

                                {{-- Show purchase link in demo mode for all modules --}}
                                @if($isDemo && isset(ModuleConstants::ALL_ADDONS_ARRAY[$module['name']]['purchase_link']))
                                  <a href="{{ ModuleConstants::ALL_ADDONS_ARRAY[$module['name']]['purchase_link'] }}"
                                     target="_blank" class="btn btn-outline-primary" title="{{ __('Purchase') }}">
                                    <i class="bx bx-cart"></i>
                                  </a>
                                @endif
                              </div>
                            @else
                              {{-- Not installed modules/applications --}}
                              @if(isset(ModuleConstants::ALL_ADDONS_ARRAY[$module['key'] ?? $module['name']]['purchase_link']))
                                <a href="{{ ModuleConstants::ALL_ADDONS_ARRAY[$module['key'] ?? $module['name']]['purchase_link'] }}"
                                   target="_blank" class="btn btn-primary btn-sm">
                                  <i class="bx bx-cart me-1"></i> 
                                  @if(isset($module['isApplication']) && $module['isApplication'])
                                    {{ __('Get Application') }}
                                  @else
                                    {{ __('Get Addon') }}
                                  @endif
                                </a>
                              @else
                                <span class="text-muted">{{ __('Coming Soon') }}</span>
                              @endif
                            @endif
                          </td>
                        </tr>
                      @endforeach
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Category selection
      const categoryItems = document.querySelectorAll('.category-item');
      const moduleRows = document.querySelectorAll('.module-row');
      const categoryTitle = document.getElementById('categoryTitle');
      const searchInput = document.getElementById('searchModules');
      const filterStatus = document.getElementById('filterStatus');

      // Category click handler
      categoryItems.forEach(item => {
        item.addEventListener('click', function() {
          // Update active state
          categoryItems.forEach(i => i.classList.remove('active'));
          this.classList.add('active');

          // Get selected category
          const selectedCategory = this.dataset.category;
          const categoryName = this.querySelector('.fw-semibold').textContent;
          categoryTitle.textContent = categoryName;

          // Filter modules
          filterModules();
        });
      });

      // Search and filter handlers
      searchInput.addEventListener('input', filterModules);
      filterStatus.addEventListener('change', filterModules);

      function filterModules() {
        const activeCategory = document.querySelector('.category-item.active').dataset.category;
        const searchTerm = searchInput.value.toLowerCase();
        const statusFilter = filterStatus.value;

        moduleRows.forEach(row => {
          const category = row.dataset.category;
          const name = row.dataset.name;
          const status = row.dataset.status;

          let showRow = true;

          // Category filter
          if (activeCategory !== 'all' && category !== activeCategory) {
            showRow = false;
          }

          // Search filter
          if (searchTerm && !name.includes(searchTerm)) {
            showRow = false;
          }

          // Status filter
          if (statusFilter && status !== statusFilter) {
            showRow = false;
          }

          row.style.display = showRow ? '' : 'none';
        });
      }

      // Uninstall confirmation
      document.querySelectorAll('.uninstall-module').forEach(button => {
        button.addEventListener('click', function () {
          const moduleName = this.getAttribute('data-module');
          const uninstallForm = document.getElementById(`uninstall-form-${moduleName}`);

          Swal.fire({
            title: '{{ __('Confirm Uninstall') }}',
            text: `{{ __('Are you sure you want to uninstall') }} ${moduleName}? {{ __('This action cannot be undone.') }}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, uninstall') }}',
            cancelButtonText: '{{ __('Cancel') }}',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
          }).then((result) => {
            if (result.isConfirmed) {
              uninstallForm.submit();
            }
          });
        });
      });
    });
  </script>
@endsection
