@extends('layouts.layoutMaster')

@section('title', $pageTitle)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Welcome Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4 class="card-title mb-0 text-white">{{ __('Field Dashboard') }}</h4>
                    <p class="mb-0">{{ __('Welcome') }}, {{ Auth::user()->full_name }}!</p>
                    <small>{{ __("Today's date") }}: {{ now()->format('l, F j, Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Overview --}}
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mb-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="bx bx-map bx-sm"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ count($todayVisits) }}</h4>
                    <p class="text-muted mb-0">{{ __('Visits Today') }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mb-3">
                        <span class="avatar-initial rounded-circle bg-label-warning">
                            <i class="bx bx-task bx-sm"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ count($pendingTasks) }}</h4>
                    <p class="text-muted mb-0">{{ __('Pending Tasks') }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mb-3">
                        <span class="avatar-initial rounded-circle bg-label-{{ $attendanceStatus['checked_in'] ? 'success' : 'danger' }}">
                            <i class="bx bx-{{ $attendanceStatus['checked_in'] ? 'check-circle' : 'x-circle' }} bx-sm"></i>
                        </span>
                    </div>
                    <h6 class="mb-0">{{ $attendanceStatus['checked_in'] ? __('Checked In') : __('Not Checked In') }}</h6>
                    <p class="text-muted mb-0">{{ $attendanceStatus['time'] ?? '-' }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mb-3">
                        <span class="avatar-initial rounded-circle bg-label-info">
                            <i class="bx bx-target-lock bx-sm"></i>
                        </span>
                    </div>
                    <h4 class="mb-0">{{ $targets['achieved'] ?? 0 }}/{{ $targets['total'] ?? 0 }}</h4>
                    <p class="text-muted mb-0">{{ __('Target Progress') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('Quick Actions') }}</h5>
                    <div class="row g-2">
                        @if(!$attendanceStatus['checked_in'])
                        <div class="col-6">
                            <a href="{{ route('hrcore.attendance.web-attendance') }}" class="btn btn-primary w-100">
                                <i class="bx bx-log-in me-1"></i>
                                {{ __('Check In') }}
                            </a>
                        </div>
                        @else
                        <div class="col-6">
                            <a href="{{ route('hrcore.attendance.web-attendance') }}" class="btn btn-danger w-100">
                                <i class="bx bx-log-out me-1"></i>
                                {{ __('Check Out') }}
                            </a>
                        </div>
                        @endif
                        <div class="col-6">
                            <a href="{{ route('visits.create') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-plus me-1"></i>
                                {{ __('New Visit') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Visits --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __("Today's Visits") }}</h5>
                    <a href="{{ route('visits.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('View All') }}
                    </a>
                </div>
                <div class="card-body">
                    @if(count($todayVisits) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($todayVisits as $visit)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $visit['customer'] ?? '' }}</h6>
                                        <small class="text-muted">
                                            <i class="bx bx-time-five"></i> {{ $visit['time'] ?? '' }} | 
                                            <i class="bx bx-map"></i> {{ $visit['location'] ?? '' }}
                                        </small>
                                    </div>
                                    <span class="badge bg-label-{{ $visit['status'] === 'completed' ? 'success' : ($visit['status'] === 'in_progress' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($visit['status'] ?? 'pending') }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-4">{{ __('No visits scheduled for today') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Tasks --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Pending Tasks') }}</h5>
                    <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('View All') }}
                    </a>
                </div>
                <div class="card-body">
                    @if(count($pendingTasks) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($pendingTasks as $task)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $task['title'] ?? '' }}</h6>
                                        <p class="mb-1 text-muted">{{ $task['description'] ?? '' }}</p>
                                        <small class="text-muted">
                                            <i class="bx bx-calendar"></i> {{ __('Due') }}: {{ $task['due_date'] ?? '' }}
                                        </small>
                                    </div>
                                    <span class="badge bg-label-{{ $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'secondary') }} ms-2">
                                        {{ ucfirst($task['priority'] ?? 'normal') }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-4">{{ __('No pending tasks') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-style')
<style>
/* Mobile-optimized styles for field employees */
@media (max-width: 576px) {
    .container-xxl {
        padding: 0.5rem;
    }
    .card {
        margin-bottom: 1rem;
    }
    .card-body {
        padding: 1rem;
    }
}
</style>
@endsection