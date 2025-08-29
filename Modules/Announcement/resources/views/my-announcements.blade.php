@php
  $configData = Helper::appClasses();
  use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutMaster')

@section('title', __('My Announcements'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    // Acknowledge announcement
    function acknowledgeAnnouncement(id) {
      fetch(`/announcements/${id}/acknowledge`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: data.message || 'Announcement acknowledged successfully.',
            customClass: {
              confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
          }).then(() => {
            location.reload();
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    }
  </script>
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('My Announcements')"
      :breadcrumbs="[
        ['name' => __('Dashboard'), 'url' => '/'],
        ['name' => __('My Announcements'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    <!-- Pinned Announcements -->
    @php
      $pinnedAnnouncements = $announcements->where('is_pinned', true);
      $regularAnnouncements = $announcements->where('is_pinned', false);
    @endphp

    @if($pinnedAnnouncements->count() > 0)
      <h5 class="mb-3">
        <i class="bx bx-pin text-primary me-2"></i> {{ __('Pinned Announcements') }}
      </h5>
      <div class="row mb-4">
        @foreach($pinnedAnnouncements as $announcement)
          <div class="col-12 mb-3">
            <div class="card border-primary">
              <div class="card-header bg-label-primary">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h5 class="card-title mb-0">
                      <i class="bx bx-pin me-2"></i>
                      {{ $announcement->title }}
                    </h5>
                    <small class="text-muted">
                      {{ __('Posted by') }} {{ $announcement->creator->name }} • 
                      {{ $announcement->created_at->diffForHumans() }}
                    </small>
                  </div>
                  <div class="d-flex gap-2">
                    <span class="badge bg-label-{{ $announcement->priority === 'urgent' ? 'danger' : ($announcement->priority === 'high' ? 'warning' : 'primary') }}">
                      {{ ucfirst($announcement->priority) }}
                    </span>
                    <span class="badge bg-label-{{ $announcement->type === 'important' ? 'danger' : 'info' }}">
                      {{ ucfirst($announcement->type) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <p class="card-text mb-3">{{ $announcement->description }}</p>
                
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    @if($announcement->requires_acknowledgment)
                      @if($announcement->reads->where('user_id', auth()->id())->where('acknowledged', true)->count() > 0)
                        <span class="badge bg-label-success">
                          <i class="bx bx-check-circle me-1"></i> {{ __('Acknowledged') }}
                        </span>
                      @else
                        <button class="btn btn-sm btn-warning" onclick="acknowledgeAnnouncement({{ $announcement->id }})">
                          <i class="bx bx-check me-1"></i> {{ __('Acknowledge') }}
                        </button>
                      @endif
                    @elseif($announcement->reads->where('user_id', auth()->id())->count() > 0)
                      <span class="badge bg-label-secondary">
                        <i class="bx bx-check me-1"></i> {{ __('Read') }}
                      </span>
                    @else
                      <span class="badge bg-label-info">{{ __('New') }}</span>
                    @endif
                  </div>
                  
                  <a href="{{ route('announcements.show', $announcement->id) }}" class="btn btn-sm btn-primary">
                    {{ __('Read More') }} <i class="bx bx-right-arrow-alt ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <!-- Regular Announcements -->
    <h5 class="mb-3">{{ __('Recent Announcements') }}</h5>
    <div class="row">
      @forelse($regularAnnouncements as $announcement)
        <div class="col-md-6 col-lg-4 mb-3">
          <div class="card h-100">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="card-title mb-1">{{ $announcement->title }}</h6>
                  <small class="text-muted">
                    {{ $announcement->created_at->diffForHumans() }}
                  </small>
                </div>
                @if($announcement->reads->where('user_id', auth()->id())->count() == 0)
                  <span class="badge bg-label-info">{{ __('New') }}</span>
                @endif
              </div>
            </div>
            <div class="card-body">
              <p class="card-text">{{ Str::limit($announcement->description, 100) }}</p>
              
              <div class="mb-2">
                <span class="badge bg-label-{{ $announcement->priority === 'urgent' ? 'danger' : ($announcement->priority === 'high' ? 'warning' : ($announcement->priority === 'normal' ? 'primary' : 'secondary')) }}">
                  {{ ucfirst($announcement->priority) }}
                </span>
                <span class="badge bg-label-{{ $announcement->type === 'important' ? 'danger' : ($announcement->type === 'event' ? 'info' : 'secondary') }}">
                  {{ ucfirst($announcement->type) }}
                </span>
              </div>

              @if($announcement->expiry_date)
                <small class="text-muted d-block mb-2">
                  <i class="bx bx-time-five me-1"></i>
                  {{ __('Expires') }}: {{ $announcement->expiry_date->format('M d, Y') }}
                </small>
              @endif

              @if($announcement->attachment)
                <small class="text-primary d-block mb-2">
                  <i class="bx bx-paperclip me-1"></i> {{ __('Has attachment') }}
                </small>
              @endif
            </div>
            <div class="card-footer bg-transparent">
              <div class="d-flex justify-content-between align-items-center">
                @if($announcement->requires_acknowledgment)
                  @if($announcement->reads->where('user_id', auth()->id())->where('acknowledged', true)->count() > 0)
                    <span class="badge bg-label-success">
                      <i class="bx bx-check-circle"></i> {{ __('Done') }}
                    </span>
                  @else
                    <span class="badge bg-label-warning">
                      <i class="bx bx-info-circle"></i> {{ __('Action Required') }}
                    </span>
                  @endif
                @else
                  @if($announcement->reads->where('user_id', auth()->id())->count() > 0)
                    <span class="badge bg-label-secondary">
                      <i class="bx bx-check"></i> {{ __('Read') }}
                    </span>
                  @else
                    <span></span>
                  @endif
                @endif
                
                <a href="{{ route('announcements.show', $announcement->id) }}" class="btn btn-sm btn-outline-primary">
                  {{ __('View') }} <i class="bx bx-right-arrow-alt"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="card">
            <div class="card-body text-center py-5">
              <i class="bx bx-message-square-detail bx-lg text-muted mb-3"></i>
              <h5 class="text-muted">{{ __('No announcements found') }}</h5>
              <p class="text-muted">{{ __('There are no announcements for you at this time.') }}</p>
            </div>
          </div>
        </div>
      @endforelse
    </div>

    @if($announcements->hasPages())
      <div class="mt-4">
        {{ $announcements->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>
@endsection