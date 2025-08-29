@extends('layouts.layoutMaster')

@section('title', $pageTitle)

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/apex-charts/apexcharts.js',
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/dashboards-employee.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Welcome Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">{{ __('Welcome') }}, {{ auth()->user()->full_name }}!</h4>
                    <p class="text-muted">{{ __("Here's your personal dashboard") }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Personal Statistics --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text text-muted">{{ __('Present Days') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $attendanceOverview['present_days'] ?? 0 }}</h4>
                                <small class="text-muted">{{ __('this month') }}</small>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded p-2">
                                <i class="bx bx-check-circle bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text text-muted">{{ __('Leave Balance') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $leaveBalance['available'] ?? 0 }}</h4>
                                <small class="text-muted">{{ __('days') }}</small>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded p-2">
                                <i class="bx bx-calendar-check bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text text-muted">{{ __('Pending Tasks') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ count($myTasks) }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="bx bx-task bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text text-muted">{{ __('Announcements') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ count($announcements) }}</h4>
                                <small class="text-muted">{{ __('new') }}</small>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded p-2">
                                <i class="bx bx-bell bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Quick Actions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('hrcore.attendance.web-attendance') }}" class="btn btn-primary w-100">
                                <i class="bx bx-time-five me-1"></i>
                                {{ __('Web Check-in') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('hrcore.leaves.apply') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-calendar-x me-1"></i>
                                {{ __('Apply Leave') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('tasks.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-task me-1"></i>
                                {{ __('My Tasks') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('hrcore.self-service.profile') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-user me-1"></i>
                                {{ __('My Profile') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- My Tasks --}}
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('My Tasks') }}</h5>
                    <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('View All') }}
                    </a>
                </div>
                <div class="card-body">
                    @if(count($myTasks) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($myTasks as $task)
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $task['title'] ?? '' }}</h6>
                                        <small class="text-muted">{{ $task['due_date'] ?? '' }}</small>
                                    </div>
                                    <span class="badge bg-label-{{ $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'secondary') }}">
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

        {{-- Announcements --}}
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Latest Announcements') }}</h5>
                    @if(config('custom.addons.NoticeBoard'))
                    <a href="{{ route('noticeboard.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('View All') }}
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    @if(count($announcements) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($announcements as $announcement)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $announcement['title'] ?? '' }}</h6>
                                    <small class="text-muted">{{ $announcement['date'] ?? '' }}</small>
                                </div>
                                <p class="mb-1 text-muted">{{ $announcement['excerpt'] ?? '' }}</p>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-4">{{ __('No announcements') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance Overview --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('My Attendance This Month') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-2">{{ __('Working Days') }}</h6>
                            <h4 class="mb-0">{{ $attendanceOverview['working_days'] ?? 0 }}</h4>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-2">{{ __('Present') }}</h6>
                            <h4 class="mb-0 text-success">{{ $attendanceOverview['present_days'] ?? 0 }}</h4>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-2">{{ __('Absent') }}</h6>
                            <h4 class="mb-0 text-danger">{{ $attendanceOverview['absent_days'] ?? 0 }}</h4>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6 class="text-muted mb-2">{{ __('Leaves') }}</h6>
                            <h4 class="mb-0 text-warning">{{ $attendanceOverview['leave_days'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

