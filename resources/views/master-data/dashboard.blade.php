@extends('layouts.layoutMaster')

@section('title', __('Master Data Management'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/master-data-dashboard.js'])
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title mb-0">{{ __('Master Data Management') }}</h4>
          <p class="text-muted mb-0">{{ __('Manage and organize your system\'s master data') }}</p>
        </div>
        @if($hasImportExport)
        <div class="btn-group">
          <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bx bx-transfer-alt me-1"></i>
            {{ __('Import/Export') }}
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('dataImportExport.index') }}?type=master-data">
              <i class="bx bx-download me-2"></i>{{ __('Import Data') }}
            </a></li>
            <li><a class="dropdown-item" href="{{ route('dataImportExport.index') }}?type=master-data&action=export">
              <i class="bx bx-upload me-2"></i>{{ __('Export Data') }}
            </a></li>
          </ul>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

@if(empty($masterDataSections))
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-body text-center py-5">
        <div class="misc-wrapper">
          <h2 class="mb-2 mx-2">{{ __('No Master Data Available') }}</h2>
          <p class="mb-4 mx-2">{{ __('No master data sections are currently available. This may be because no modules are enabled.') }}</p>
          <a href="{{ route('settings.index') }}" class="btn btn-primary">{{ __('Go to Settings') }}</a>
        </div>
      </div>
    </div>
  </div>
</div>
@else
<div class="row mt-4">
  @foreach($masterDataSections as $sectionKey => $section)
  <div class="col-xl-6 col-lg-6 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="avatar flex-shrink-0 me-3">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="{{ $section['icon'] }}"></i>
            </span>
          </div>
          <div>
            <h5 class="mb-0">{{ $section['title'] }}</h5>
            <small class="text-muted">{{ count($section['items']) }} {{ __('items') }}</small>
          </div>
        </div>
      </div>
      <div class="card-body pt-0">
        <div class="row g-3">
          @foreach($section['items'] as $item)
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  <span class="avatar-initial rounded-circle bg-label-{{ $loop->index % 2 == 0 ? 'info' : 'success' }}">
                    <i class="{{ $item['icon'] }} bx-xs"></i>
                  </span>
                </div>
                <div>
                  <h6 class="mb-0">{{ $item['name'] }}</h6>
                  <small class="text-muted">{{ $item['description'] }}</small>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <span class="badge bg-label-primary me-2">{{ number_format($item['count']) }}</span>
                @if($hasImportExport && isset($item['importExport']))
                <div class="btn-group me-2">
                  <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bx bx-transfer-alt"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ $item['importExport']['import'] }}">
                      <i class="bx bx-download me-2"></i>{{ __('Import') }}
                    </a></li>
                    <li><a class="dropdown-item" href="{{ $item['importExport']['export'] }}">
                      <i class="bx bx-upload me-2"></i>{{ __('Export') }}
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ $item['importExport']['template'] }}" target="_blank">
                      <i class="bx bx-file-blank me-2"></i>{{ __('Template') }}
                    </a></li>
                  </ul>
                </div>
                @endif
                <a href="{{ $item['url'] }}" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-edit-alt"></i>
                </a>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

<!-- Master Data Statistics -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Master Data Overview') }}</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3 col-sm-6">
            <div class="d-flex align-items-center">
              <div class="avatar me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="bx bx-data"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0">{{ array_sum(array_column(array_merge(...array_column($masterDataSections, 'items')), 'count')) }}</h5>
                <small class="text-muted">{{ __('Total Records') }}</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="d-flex align-items-center">
              <div class="avatar me-3">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="bx bx-category"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0">{{ count($masterDataSections) }}</h5>
                <small class="text-muted">{{ __('Categories') }}</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="d-flex align-items-center">
              <div class="avatar me-3">
                <span class="avatar-initial rounded bg-label-info">
                  <i class="bx bx-list-ul"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0">{{ array_sum(array_map(function($section) { return count($section['items']); }, $masterDataSections)) }}</h5>
                <small class="text-muted">{{ __('Data Types') }}</small>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="d-flex align-items-center">
              <div class="avatar me-3">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="bx bx-extension"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0">{{ count(array_filter($masterDataSections, function($section, $key) use ($masterDataSections) {
                  // Count sections that come from addons (not core CRM, tasks, expense, hr)
                  return !in_array($key, ['crm', 'tasks', 'expense', 'hr']);
                }, ARRAY_FILTER_USE_BOTH)) }}</h5>
                <small class="text-muted">{{ __('Addon Categories') }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

@endsection

@section('page-script')
<script>
const pageData = {
  labels: {
    masterData: @json(__('Master Data')),
    records: @json(__('Records')),
    categories: @json(__('Categories')),
    dataTypes: @json(__('Data Types')),
    addonCategories: @json(__('Addon Categories'))
  },
  hasImportExport: @json($hasImportExport),
  sections: @json($masterDataSections)
};

$(function () {
  // Initialize any charts or interactive elements here
  console.log('Master Data Dashboard initialized');
});
</script>
@endsection
