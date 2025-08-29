@php
  $configData = Helper::appClasses();
  use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutMaster')

@section('title', __('My Notifications'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  ])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    
    {{-- Header with Statistics --}}
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h4 class="card-title mb-0">
                  <i class="bx bx-bell me-2"></i>{{ __('My Notifications') }}
                </h4>
                <p class="text-muted mb-0 mt-2">
                  {{ __('You have :unread unread notifications out of :total total', ['unread' => $unreadCount ?? 0, 'total' => $totalCount ?? 0]) }}
                </p>
              </div>
              <div class="col-md-4 text-md-end mt-3 mt-md-0">
                @if(($unreadCount ?? 0) > 0)
                  <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                      <i class="bx bx-check-double me-1"></i> {{ __('Mark All as Read') }}
                    </button>
                  </form>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <form method="GET" action="{{ route('notifications.myNotifications') }}" class="row g-3">
              <div class="col-md-4">
                <label class="form-label">{{ __('Status') }}</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                  <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>{{ __('All Notifications') }}</option>
                  <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>{{ __('Unread Only') }}</option>
                  <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>{{ __('Read Only') }}</option>
                </select>
              </div>
              
              @if(isset($notificationTypes) && $notificationTypes->count() > 0)
              <div class="col-md-4">
                <label class="form-label">{{ __('Type') }}</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                  <option value="all">{{ __('All Types') }}</option>
                  @foreach($notificationTypes as $type)
                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                      {{ Str::title(str_replace(['_', '\\'], [' ', ' → '], $type)) }}
                    </option>
                  @endforeach
                </select>
              </div>
              @endif
              
              <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                  <a href="{{ route('notifications.myNotifications') }}" class="btn btn-secondary">
                    <i class="bx bx-reset me-1"></i> {{ __('Reset Filters') }}
                  </a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- Notifications List --}}
    <div class="card">
      <div class="card-body">
        @if($notifications->count() > 0)
          <div class="timeline">
            @foreach($notifications as $notification)
              @php
                $data = $notification->data;
                $isRead = !is_null($notification->read_at);
                
                // Parse notification data
                $title = $data['title'] ?? Str::title(str_replace(['_', '\\'], [' ', ' → '], $notification->type));
                $message = $data['message'] ?? $data['body'] ?? '';
                $url = $data['url'] ?? '#';
                $icon = $data['icon'] ?? 'bx-bell';
                $color = $data['color'] ?? ($isRead ? 'secondary' : 'primary');
                $priority = $data['priority'] ?? 'normal';
                
                // For announcement notifications
                if (isset($data['type']) && $data['type'] === 'announcement') {
                  $icon = match($priority) {
                    'urgent' => 'bx-error-circle',
                    'high' => 'bx-info-circle',
                    default => 'bx-bell'
                  };
                  $color = match($priority) {
                    'urgent' => 'danger',
                    'high' => 'warning',
                    'normal' => 'primary',
                    default => 'info'
                  };
                }
              @endphp
              
              <div class="timeline-item {{ !$isRead ? 'timeline-item-primary' : 'timeline-item-secondary' }} mb-4">
                <span class="timeline-point {{ !$isRead ? 'timeline-point-primary' : 'timeline-point-secondary' }}">
                  <i class="bx {{ $icon }}"></i>
                </span>
                
                <div class="timeline-event">
                  <div class="card {{ !$isRead ? 'border-primary' : '' }}">
                    <div class="card-body">
                      <div class="d-flex justify-content-between flex-wrap mb-2">
                        <div class="flex-grow-1">
                          <h6 class="mb-1">
                            @if($url !== '#')
                              <a href="{{ $url }}" class="text-body {{ !$isRead ? 'fw-bold' : '' }}">
                                {{ $title }}
                              </a>
                            @else
                              <span class="{{ !$isRead ? 'fw-bold' : '' }}">{{ $title }}</span>
                            @endif
                          </h6>
                          
                          @if($message)
                            <p class="mb-2 {{ !$isRead ? 'text-dark' : 'text-muted' }}">
                              {{ Str::limit($message, 200) }}
                            </p>
                          @endif
                          
                          <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                              <i class="bx bx-time-five"></i> {{ $notification->created_at->diffForHumans() }}
                            </small>
                            
                            @if(isset($data['priority']) && in_array($data['priority'], ['urgent', 'high']))
                              <span class="badge bg-label-{{ $color }}">
                                {{ ucfirst($data['priority']) }}
                              </span>
                            @endif
                            
                            @if(isset($data['announcement_type']))
                              <span class="badge bg-label-info">
                                {{ ucfirst($data['announcement_type']) }}
                              </span>
                            @endif
                            
                            @if(isset($data['requires_acknowledgment']) && $data['requires_acknowledgment'])
                              <span class="badge bg-label-warning">
                                {{ __('Requires Acknowledgment') }}
                              </span>
                            @endif
                          </div>
                        </div>
                        
                        <div class="flex-shrink-0 ms-3">
                          <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                              @if($url !== '#')
                                <a class="dropdown-item" href="{{ $url }}">
                                  <i class="bx bx-show me-1"></i> {{ __('View Details') }}
                                </a>
                              @endif
                              
                              @if(!$isRead)
                                <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST">
                                  @csrf
                                  <button type="submit" class="dropdown-item">
                                    <i class="bx bx-check me-1"></i> {{ __('Mark as Read') }}
                                  </button>
                                </form>
                              @endif
                              
                              <form action="{{ route('notifications.delete', $notification->id) }}" method="POST" 
                                    onsubmit="return confirm('{{ __('Are you sure you want to delete this notification?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                  <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
                                </button>
                              </form>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          
          {{-- Pagination --}}
          <div class="mt-4">
            {{ $notifications->appends(request()->query())->links('pagination::bootstrap-5') }}
          </div>
        @else
          <div class="text-center py-5">
            <i class="bx bx-bell-off mb-3" style="font-size: 4rem; color: #ccc;"></i>
            <h5 class="text-muted">{{ __('No notifications found') }}</h5>
            <p class="text-muted">
              @if(request('status') == 'unread')
                {{ __('You have no unread notifications.') }}
              @elseif(request('status') == 'read')
                {{ __('You have no read notifications.') }}
              @else
                {{ __('You have no notifications at this time.') }}
              @endif
            </p>
            
            @if(request()->hasAny(['status', 'type']))
              <a href="{{ route('notifications.myNotifications') }}" class="btn btn-primary mt-3">
                <i class="bx bx-reset me-1"></i> {{ __('Clear Filters') }}
              </a>
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-refresh notifications every 60 seconds
  setInterval(function() {
    // You can add AJAX refresh here if needed
  }, 60000);
});
</script>
@endsection