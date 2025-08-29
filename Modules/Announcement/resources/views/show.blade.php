@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('View Announcement'))

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
  @vite([
    'Modules/Announcement/resources/assets/js/announcement-show.js'
  ])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('View Announcement')"
      :breadcrumbs="[
        ['name' => __('Communication'), 'url' => ''],
        ['name' => __('Announcements'), 'url' => route('announcements.index')],
        ['name' => __('View'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    <div class="row">
      <!-- Main Content -->
      <div class="col-12 col-lg-8">
        <div class="card mb-4">
          <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="card-title mb-1">
                  @if($announcement->is_pinned)
                    <i class="bx bx-pin text-primary me-2"></i>
                  @endif
                  {{ $announcement->title }}
                </h4>
                <p class="text-muted mb-0">{{ $announcement->description }}</p>
              </div>
              <div class="d-flex gap-2">
                <span class="badge bg-label-{{ $announcement->type === 'important' ? 'danger' : ($announcement->type === 'event' ? 'info' : 'secondary') }}">
                  {{ ucfirst($announcement->type) }}
                </span>
                <span class="badge bg-label-{{ $announcement->priority === 'urgent' ? 'danger' : ($announcement->priority === 'high' ? 'warning' : ($announcement->priority === 'normal' ? 'primary' : 'secondary')) }}">
                  {{ ucfirst($announcement->priority) }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="card-body">
            <!-- Meta Information -->
            <div class="d-flex flex-wrap gap-4 mb-4 pb-3 border-bottom">
              <div>
                <small class="text-muted">{{ __('Created by') }}</small>
                <p class="mb-0 fw-medium">{{ $announcement->creator->name }}</p>
              </div>
              <div>
                <small class="text-muted">{{ __('Created at') }}</small>
                <p class="mb-0 fw-medium">{{ $announcement->created_at->format('M d, Y h:i A') }}</p>
              </div>
              @if($announcement->publish_date)
                <div>
                  <small class="text-muted">{{ __('Published at') }}</small>
                  <p class="mb-0 fw-medium">{{ $announcement->publish_date->format('M d, Y h:i A') }}</p>
                </div>
              @endif
              @if($announcement->expiry_date)
                <div>
                  <small class="text-muted">{{ __('Expires at') }}</small>
                  <p class="mb-0 fw-medium">{{ $announcement->expiry_date->format('M d, Y h:i A') }}</p>
                </div>
              @endif
            </div>

            <!-- Content -->
            <div class="announcement-content mb-4">
              {!! $announcement->content !!}
            </div>

            <!-- Attachment -->
            @if($announcement->attachment)
              <div class="alert alert-info">
                <h6 class="alert-heading mb-1">
                  <i class="bx bx-paperclip me-1"></i> {{ __('Attachment') }}
                </h6>
                <a href="{{ Storage::url($announcement->attachment) }}" target="_blank" class="btn btn-sm btn-primary">
                  <i class="bx bx-download me-1"></i> {{ __('Download') }} {{ basename($announcement->attachment) }}
                </a>
              </div>
            @endif

            <!-- Acknowledgment Section -->
            @if($announcement->requires_acknowledgment && auth()->check())
              @if(!$announcement->isAcknowledgedBy(auth()->user()))
                <div class="alert alert-warning">
                  <h6 class="alert-heading mb-2">
                    <i class="bx bx-info-circle me-1"></i> {{ __('Acknowledgment Required') }}
                  </h6>
                  <p class="mb-2">{{ __('Please acknowledge that you have read and understood this announcement.') }}</p>
                  <button type="button" class="btn btn-warning btn-sm" id="acknowledge-btn" data-id="{{ $announcement->id }}">
                    <i class="bx bx-check me-1"></i> {{ __('Acknowledge') }}
                  </button>
                </div>
              @else
                <div class="alert alert-success">
                  <i class="bx bx-check-circle me-1"></i> 
                  {{ __('You acknowledged this announcement on') }} 
                  {{ $announcement->reads->where('user_id', auth()->id())->first()->acknowledged_at->format('M d, Y h:i A') }}
                </div>
              @endif
            @endif
          </div>

          <div class="card-footer">
            <div class="d-flex justify-content-between">
              <a href="{{ route('announcements.index') }}" class="btn btn-label-secondary">
                <i class="bx bx-arrow-back me-1"></i> {{ __('Back to List') }}
              </a>
              @can('announcements.edit')
                <a href="{{ route('announcements.edit', $announcement->id) }}" class="btn btn-primary">
                  <i class="bx bx-edit me-1"></i> {{ __('Edit') }}
                </a>
              @endcan
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-12 col-lg-4">
        <!-- Status Card -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Status Information') }}</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="text-muted small">{{ __('Current Status') }}</label>
              <div>
                <span class="badge bg-label-{{ $announcement->status === 'published' ? 'success' : ($announcement->status === 'scheduled' ? 'warning' : ($announcement->status === 'draft' ? 'secondary' : 'danger')) }}">
                  {{ ucfirst($announcement->status) }}
                </span>
              </div>
            </div>

            @if($announcement->is_pinned)
              <div class="mb-3">
                <label class="text-muted small">{{ __('Pinned') }}</label>
                <div>
                  <span class="badge bg-label-info">
                    <i class="bx bx-pin me-1"></i> {{ __('Yes') }}
                  </span>
                </div>
              </div>
            @endif

            @if($announcement->send_notification)
              <div class="mb-3">
                <label class="text-muted small">{{ __('Notifications') }}</label>
                <div>
                  <span class="badge bg-label-primary">
                    <i class="bx bx-bell me-1"></i> {{ __('In-app') }}
                  </span>
                </div>
              </div>
            @endif

            @if($announcement->send_email)
              <div class="mb-3">
                <label class="text-muted small">{{ __('Email') }}</label>
                <div>
                  <span class="badge bg-label-primary">
                    <i class="bx bx-envelope me-1"></i> {{ __('Sent') }}
                  </span>
                </div>
              </div>
            @endif
          </div>
        </div>

        <!-- Target Audience Card -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Target Audience') }}</h5>
          </div>
          <div class="card-body">
            @if($announcement->target_audience === 'all')
              <span class="badge bg-label-primary">{{ __('All Employees') }}</span>
            @elseif($announcement->target_audience === 'departments')
              <label class="text-muted small d-block mb-2">{{ __('Departments') }}</label>
              @foreach($announcement->departments as $department)
                <span class="badge bg-label-info mb-1">{{ $department->name }}</span>
              @endforeach
            @elseif($announcement->target_audience === 'teams')
              <label class="text-muted small d-block mb-2">{{ __('Teams') }}</label>
              @foreach($announcement->teams as $team)
                <span class="badge bg-label-warning mb-1">{{ $team->name }}</span>
              @endforeach
            @else
              <label class="text-muted small d-block mb-2">{{ __('Specific Users') }}</label>
              <p class="mb-0">{{ $announcement->users->count() }} {{ __('users selected') }}</p>
            @endif
          </div>
        </div>

        <!-- Read Statistics Card -->
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Read Statistics') }}</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">{{ __('Read Progress') }}</span>
                <span class="fw-medium">{{ $readStats['read_percentage'] }}%</span>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar" style="width: {{ $readStats['read_percentage'] }}%"></div>
              </div>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">{{ __('Total Recipients:') }}</span>
              <strong>{{ $readStats['total_targets'] }}</strong>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">{{ __('Total Reads:') }}</span>
              <strong>{{ $readStats['total_reads'] }}</strong>
            </div>

            @if($announcement->requires_acknowledgment)
              <div class="d-flex justify-content-between">
                <span class="text-muted">{{ __('Acknowledged:') }}</span>
                <strong>{{ $readStats['acknowledged_count'] }}</strong>
              </div>
            @endif

            @can('announcements.reports.read_tracking')
              <hr>
              <h6 class="mb-3">{{ __('Recent Readers') }}</h6>
              @foreach($announcement->reads()->with('user')->latest('read_at')->limit(5)->get() as $read)
                <div class="d-flex align-items-center mb-2">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ substr($read->user->name, 0, 2) }}
                    </span>
                  </div>
                  <div class="flex-grow-1">
                    <p class="mb-0 small">{{ $read->user->name }}</p>
                    <small class="text-muted">{{ $read->read_at->diffForHumans() }}</small>
                  </div>
                  @if($read->acknowledged)
                    <i class="bx bx-check-circle text-success"></i>
                  @endif
                </div>
              @endforeach
            @endcan
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection