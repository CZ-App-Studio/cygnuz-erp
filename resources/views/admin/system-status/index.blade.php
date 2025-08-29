@extends('layouts.layoutMaster')

@section('title', __('System Status'))

@section('vendor-style')
<style>
.status-card {
  transition: all 0.3s ease;
}

.status-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 25px 0 rgba(0, 0, 0, 0.1);
}

.status-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 8px;
}

.status-healthy { background-color: #28a745; }
.status-warning { background-color: #ffc107; }
.status-error { background-color: #dc3545; }

.metric-value {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0;
}

.metric-label {
  font-size: 0.875rem;
  color: #6c757d;
  margin-bottom: 0;
}

.progress-thin {
  height: 6px;
}

.system-info-table th {
  width: 200px;
  font-weight: 600;
  color: #495057;
}

.system-info-table td {
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
}

.refresh-button {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.action-button {
  min-width: 120px;
}

.health-check-item {
  border-left: 4px solid transparent;
  transition: all 0.3s ease;
}

.health-check-item.healthy {
  border-left-color: #28a745;
  background-color: rgba(40, 167, 69, 0.05);
}

.health-check-item.warning {
  border-left-color: #ffc107;
  background-color: rgba(255, 193, 7, 0.05);
}

.health-check-item.error {
  border-left-color: #dc3545;
  background-color: rgba(220, 53, 69, 0.05);
}
</style>
@endsection

@section('content')
<div class="container-fluid">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-1">{{ __('System Status') }}</h4>
          <p class="text-muted mb-0">{{ __('Monitor your application\'s health and performance') }}</p>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-primary" onclick="refreshStatus()">
            <i class="bx bx-refresh me-1"></i>
            {{ __('Refresh') }}
          </button>
          <button type="button" class="btn btn-info" onclick="refreshMenuCache()">
            <i class="bx bx-menu me-1"></i>
            {{ __('Refresh Menu') }}
          </button>
          <button type="button" class="btn btn-primary" onclick="clearCache()">
            <i class="bx bx-trash me-1"></i>
            {{ __('Clear Cache') }}
          </button>
          <button type="button" class="btn btn-success" onclick="optimizeSystem()">
            <i class="bx bx-rocket me-1"></i>
            {{ __('Optimize') }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Overall Status Card -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center py-4">
          <div id="overall-status-indicator" class="mb-3">
            <div class="status-indicator status-healthy d-inline-block me-2" style="width: 20px; height: 20px;"></div>
            <h3 class="d-inline-block mb-0" id="overall-status-text">{{ __('All Systems Operational') }}</h3>
          </div>
          <p class="text-muted mb-0" id="last-updated">{{ __('Last updated') }}: {{ now()->format('M d, Y H:i:s') }}</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Health Checks -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Health Checks') }}</h5>
        </div>
        <div class="card-body">
          <div class="row" id="health-checks-container">
            @foreach($healthChecks as $check => $data)
              <div class="col-lg-6 mb-3">
                <div class="health-check-item {{ $data['status'] }} p-3 rounded">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="d-flex align-items-center mb-1">
                        <span class="status-indicator status-{{ $data['status'] }}"></span>
                        <h6 class="mb-0 text-capitalize">{{ __(ucfirst(str_replace('_', ' ', $check))) }}</h6>
                      </div>
                      <p class="mb-0 text-muted small">{{ $data['message'] }}</p>
                    </div>
                    <div class="text-end">
                      @if($data['status'] === 'healthy')
                        <i class="bx bx-check-circle text-success fs-4"></i>
                      @elseif($data['status'] === 'warning')
                        <i class="bx bx-error-circle text-warning fs-4"></i>
                      @else
                        <i class="bx bx-x-circle text-danger fs-4"></i>
                      @endif
                    </div>
                  </div>
                  @if(isset($data['details']) && !empty($data['details']))
                    <div class="mt-2">
                      <small class="text-muted">
                        @foreach($data['details'] as $key => $value)
                          <span class="badge bg-light text-dark me-1">{{ ucfirst($key) }}: {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</span>
                        @endforeach
                      </small>
                    </div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- System Information -->
  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Application Information') }}</h5>
        </div>
        <div class="card-body">
          <table class="table table-borderless system-info-table">
            <tr>
              <th>{{ __('Application Name') }}</th>
              <td>{{ $systemInfo['app']['name'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Version') }}</th>
              <td>{{ $systemInfo['app']['version'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Environment') }}</th>
              <td>
                <span class="badge bg-{{ $systemInfo['app']['environment'] === 'production' ? 'success' : 'warning' }}">
                  {{ ucfirst($systemInfo['app']['environment']) }}
                </span>
              </td>
            </tr>
            <tr>
              <th>{{ __('Debug Mode') }}</th>
              <td>
                <span class="badge bg-{{ $systemInfo['app']['debug'] ? 'danger' : 'success' }}">
                  {{ $systemInfo['app']['debug'] ? 'Enabled' : 'Disabled' }}
                </span>
              </td>
            </tr>
            <tr>
              <th>{{ __('Laravel Version') }}</th>
              <td>{{ $systemInfo['laravel']['version'] }}</td>
            </tr>
            <tr>
              <th>{{ __('PHP Version') }}</th>
              <td>{{ $systemInfo['laravel']['php_version'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Timezone') }}</th>
              <td>{{ $systemInfo['app']['timezone'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Locale') }}</th>
              <td>{{ $systemInfo['app']['locale'] }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Server Information') }}</h5>
        </div>
        <div class="card-body">
          <table class="table table-borderless system-info-table">
            <tr>
              <th>{{ __('Server Software') }}</th>
              <td>{{ $serverInfo['server_software'] }}</td>
            </tr>
            <tr>
              <th>{{ __('PHP SAPI') }}</th>
              <td>{{ $serverInfo['php_sapi'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Memory Limit') }}</th>
              <td>{{ $serverInfo['memory_limit'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Max Execution Time') }}</th>
              <td>{{ $serverInfo['max_execution_time'] }}s</td>
            </tr>
            <tr>
              <th>{{ __('Upload Max Filesize') }}</th>
              <td>{{ $serverInfo['upload_max_filesize'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Post Max Size') }}</th>
              <td>{{ $serverInfo['post_max_size'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Max Input Vars') }}</th>
              <td>{{ $serverInfo['max_input_vars'] }}</td>
            </tr>
            <tr>
              <th>{{ __('Server Time') }}</th>
              <td>{{ $serverInfo['server_time'] }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Services Status -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Services Configuration') }}</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 mb-3">
              <div class="text-center p-3 bg-light rounded">
                <i class="bx bx-data fs-1 text-primary mb-2"></i>
                <h6 class="mb-1">{{ __('Database') }}</h6>
                <p class="mb-0 text-muted small">{{ ucfirst($systemInfo['database']['driver']) }}</p>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="text-center p-3 bg-light rounded">
                <i class="bx bx-layer fs-1 text-info mb-2"></i>
                <h6 class="mb-1">{{ __('Cache') }}</h6>
                <p class="mb-0 text-muted small">{{ ucfirst($systemInfo['cache']['driver']) }}</p>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="text-center p-3 bg-light rounded">
                <i class="bx bx-list-ol fs-1 text-warning mb-2"></i>
                <h6 class="mb-1">{{ __('Queue') }}</h6>
                <p class="mb-0 text-muted small">{{ ucfirst($systemInfo['queue']['driver']) }}</p>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="text-center p-3 bg-light rounded">
                <i class="bx bx-envelope fs-1 text-success mb-2"></i>
                <h6 class="mb-1">{{ __('Mail') }}</h6>
                <p class="mb-0 text-muted small">{{ ucfirst($systemInfo['mail']['driver']) }}</p>
              </div>
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
// Global CSRF token
window.Laravel = {
    csrfToken: '{{ csrf_token() }}'
};

$(function () {
  // Set up CSRF token for AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Auto-refresh every 30 seconds
  setInterval(refreshStatus, 30000);
});

/**
 * Refresh system status
 */
function refreshStatus() {
  const refreshBtn = $('.btn-outline-primary i');
  refreshBtn.addClass('refresh-button');

  $.ajax({
    url: '{{ route("admin.system-status.ajax") }}',
    method: 'GET',
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      'Accept': 'application/json'
    },
    success: function(response) {
      updateStatusDisplay(response);
      updateLastUpdated();
    },
    error: function(xhr) {
      Swal.fire({
        icon: 'error',
        title: '{{ __("Error") }}',
        text: '{{ __("Failed to refresh system status") }}',
      });
    },
    complete: function() {
      refreshBtn.removeClass('refresh-button');
    }
  });
}

/**
 * Clear application cache
 */
function clearCache() {
  Swal.fire({
    title: '{{ __("Clear Cache") }}',
    text: '{{ __("This will clear all application caches. Continue?") }}',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: '{{ __("Yes, clear it!") }}',
    cancelButtonText: '{{ __("Cancel") }}'
  }).then((result) => {
    if (result.isConfirmed) {
      performCacheAction();
    }
  });
}

/**
 * Refresh menu cache
 */
function refreshMenuCache() {
  Swal.fire({
    title: '{{ __("Refresh Menu Cache") }}',
    text: '{{ __("This will refresh all menu caches and reload menu items from modules. Continue?") }}',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#17a2b8',
    cancelButtonColor: '#d33',
    confirmButtonText: '{{ __("Yes, refresh!") }}',
    cancelButtonText: '{{ __("Cancel") }}'
  }).then((result) => {
    if (result.isConfirmed) {
      performMenuRefresh();
    }
  });
}

/**
 * Optimize system
 */
function optimizeSystem() {
  Swal.fire({
    title: '{{ __("Optimize System") }}',
    text: '{{ __("This will optimize your application for better performance. Continue?") }}',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#d33',
    confirmButtonText: '{{ __("Yes, optimize!") }}',
    cancelButtonText: '{{ __("Cancel") }}'
  }).then((result) => {
    if (result.isConfirmed) {
      performOptimization();
    }
  });
}

/**
 * Perform cache clearing action
 */
function performCacheAction() {
  Swal.fire({
    title: '{{ __("Clearing Cache...") }}',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  // Get CSRF token
  const csrfToken = $('meta[name="csrf-token"]').attr('content');
  
  // Debug: Check if token exists
  if (!csrfToken) {
    console.error('CSRF token not found');
    Swal.fire({
      icon: 'error',
      title: '{{ __("Error") }}',
      text: 'CSRF token not found. Please refresh the page.',
    });
    return;
  }

  $.ajax({
    url: '{{ route("admin.system-status.clear-cache") }}',
    method: 'POST',
    data: {
      _token: csrfToken
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    },
    success: function(response) {
      Swal.fire({
        icon: 'success',
        title: '{{ __("Success") }}',
        text: response.message,
        timer: 3000,
        showConfirmButton: false
      });
      refreshStatus();
    },
    error: function(xhr) {
      console.error('Cache clear error:', xhr);
      Swal.fire({
        icon: 'error',
        title: '{{ __("Error") }}',
        text: xhr.responseJSON?.message || '{{ __("Failed to clear cache") }}',
      });
    }
  });
}

/**
 * Perform menu refresh action
 */
function performMenuRefresh() {
  Swal.fire({
    title: '{{ __("Refreshing Menu Cache...") }}',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  // Get CSRF token
  const csrfToken = $('meta[name="csrf-token"]').attr('content');
  
  // Debug: Check if token exists
  if (!csrfToken) {
    console.error('CSRF token not found');
    Swal.fire({
      icon: 'error',
      title: '{{ __("Error") }}',
      text: 'CSRF token not found. Please refresh the page.',
    });
    return;
  }

  $.ajax({
    url: '{{ route("admin.system-status.refresh-menu") }}',
    method: 'POST',
    data: {
      _token: csrfToken
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    },
    success: function(response) {
      let message = response.data.message || response.message;
      if (response.data && response.data.data) {
        message += ` (Vertical: ${response.data.data.vertical_items} items, Horizontal: ${response.data.data.horizontal_items} items)`;
      }
      
      Swal.fire({
        icon: 'success',
        title: '{{ __("Success") }}',
        text: message,
        timer: 3000,
        showConfirmButton: false
      });
      
      // Reload the page after a short delay to show the refreshed menu
      setTimeout(function() {
        window.location.reload();
      }, 3100);
    },
    error: function(xhr) {
      console.error('Menu refresh error:', xhr);
      Swal.fire({
        icon: 'error',
        title: '{{ __("Error") }}',
        text: xhr.responseJSON?.message || '{{ __("Failed to refresh menu cache") }}',
      });
    }
  });
}

/**
 * Perform system optimization
 */
function performOptimization() {
  Swal.fire({
    title: '{{ __("Optimizing System...") }}',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  // Get CSRF token
  const csrfToken = $('meta[name="csrf-token"]').attr('content');
  
  // Debug: Check if token exists
  if (!csrfToken) {
    console.error('CSRF token not found');
    Swal.fire({
      icon: 'error',
      title: '{{ __("Error") }}',
      text: 'CSRF token not found. Please refresh the page.',
    });
    return;
  }

  $.ajax({
    url: '{{ route("admin.system-status.optimize") }}',
    method: 'POST',
    data: {
      _token: csrfToken
    },
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    },
    success: function(response) {
      Swal.fire({
        icon: 'success',
        title: '{{ __("Success") }}',
        text: response.message,
        timer: 3000,
        showConfirmButton: false
      });
      refreshStatus();
    },
    error: function(xhr) {
      console.error('Optimization error:', xhr);
      Swal.fire({
        icon: 'error',
        title: '{{ __("Error") }}',
        text: xhr.responseJSON?.message || '{{ __("Failed to optimize system") }}',
      });
    }
  });
}

/**
 * Update status display
 */
function updateStatusDisplay(response) {
  // Update overall status
  const statusIndicator = $('#overall-status-indicator .status-indicator');
  const statusText = $('#overall-status-text');

  statusIndicator.removeClass('status-healthy status-warning status-error')
                 .addClass('status-' + response.status);

  statusText.text(response.message);

  // Update health checks if provided
  if (response.details) {
    updateHealthChecks(response.details);
  }
}

/**
 * Update health checks display
 */
function updateHealthChecks(healthChecks) {
  Object.keys(healthChecks).forEach(function(check) {
    const checkData = healthChecks[check];
    const checkElement = $(`.health-check-item:contains("${check.replace('_', ' ')}")`).closest('.health-check-item');

    if (checkElement.length) {
      checkElement.removeClass('healthy warning error').addClass(checkData.status);
      checkElement.find('.status-indicator').removeClass('status-healthy status-warning status-error')
               .addClass('status-' + checkData.status);
      checkElement.find('p').text(checkData.message);
    }
  });
}

/**
 * Update last updated timestamp
 */
function updateLastUpdated() {
  const now = new Date();
  const formatted = now.toLocaleString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
  $('#last-updated').text('{{ __("Last updated") }}: ' + formatted);
}
</script>
@endsection
