@php
  use Modules\Announcement\app\Models\Announcement;
  use Illuminate\Support\Str;
  
  // Get latest announcements for the current user
  $latestAnnouncements = Announcement::forUser(auth()->user())
    ->active()
    ->orderBy('is_pinned', 'desc')
    ->byPriority()
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();
    
  $unreadCount = Announcement::forUser(auth()->user())
    ->active()
    ->whereDoesntHave('reads', function ($q) {
        $q->where('user_id', auth()->id());
    })
    ->count();
@endphp

<div class="col-lg-6 col-md-12 mb-4">
  <div class="card h-100">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="card-title m-0 me-2">
        <i class="bx bx-bell me-2"></i>
        @lang('Latest Announcements')
      </h5>
      @if($unreadCount > 0)
        <span class="badge bg-danger rounded-pill">{{ $unreadCount }} @lang('New')</span>
      @endif
    </div>
    <div class="card-body">
      @if($latestAnnouncements->count() > 0)
        <ul class="timeline timeline-left">
          @foreach($latestAnnouncements as $announcement)
            <li class="timeline-item {{ !$announcement->isReadBy(auth()->user()) ? 'timeline-item-primary' : 'timeline-item-secondary' }}">
              <span class="timeline-point {{ !$announcement->isReadBy(auth()->user()) ? 'timeline-point-primary' : 'timeline-point-secondary' }}">
                @if($announcement->is_pinned)
                  <i class="bx bx-pin"></i>
                @elseif(!$announcement->isReadBy(auth()->user()))
                  <i class="bx bx-bell"></i>
                @else
                  <i class="bx bx-check"></i>
                @endif
              </span>
              <div class="timeline-event">
                <div class="d-flex">
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <h6 class="mb-0">
                        <a href="{{ route('announcements.show', $announcement->id) }}" class="text-body">
                          {{ $announcement->title }}
                        </a>
                      </h6>
                      <small class="text-muted">{{ $announcement->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="text-muted mb-2">{{ Str::limit($announcement->description, 100) }}</p>
                    <div class="d-flex flex-wrap gap-2">
                      @if($announcement->priority === 'urgent')
                        <span class="badge bg-label-danger">@lang('Urgent')</span>
                      @elseif($announcement->priority === 'high')
                        <span class="badge bg-label-warning">@lang('High Priority')</span>
                      @endif
                      
                      @if($announcement->type === 'important')
                        <span class="badge bg-label-info">@lang('Important')</span>
                      @elseif($announcement->type === 'policy')
                        <span class="badge bg-label-primary">@lang('Policy')</span>
                      @elseif($announcement->type === 'event')
                        <span class="badge bg-label-success">@lang('Event')</span>
                      @endif
                      
                      @if($announcement->requires_acknowledgment && !$announcement->isAcknowledgedBy(auth()->user()))
                        <span class="badge bg-label-warning">@lang('Requires Acknowledgment')</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>
        <div class="text-center mt-3">
          <a href="{{ route('announcements.my') }}" class="btn btn-sm btn-primary">
            @lang('View All Announcements')
          </a>
        </div>
      @else
        <div class="text-center py-4">
          <i class="bx bx-bell-off mb-3" style="font-size: 3rem; color: #ccc;"></i>
          <p class="text-muted">@lang('No announcements available')</p>
        </div>
      @endif
    </div>
  </div>
</div>