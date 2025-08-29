@extends('layouts.layoutMaster')

@section('title', $pageTitle)

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Welcome Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">{{ __('HR Dashboard') }}</h4>
                    <p class="text-muted">{{ __('Manage your workforce efficiently') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Employee Statistics --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text text-muted">{{ __('Total Employees') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $employeeStats['total_employees'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded p-2">
                                <i class="bx bx-group bx-sm"></i>
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
                            <p class="card-text text-muted">{{ __('Present Today') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $employeeStats['present_today'] ?? 0 }}</h4>
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
                            <p class="card-text text-muted">{{ __('On Leave') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $employeeStats['on_leave'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="bx bx-calendar-x bx-sm"></i>
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
                            <p class="card-text text-muted">{{ __('New Joiners') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $employeeStats['new_joiners'] ?? 0 }}</h4>
                                <small class="text-muted">{{ __('this month') }}</small>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded p-2">
                                <i class="bx bx-user-plus bx-sm"></i>
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
                            <a href="{{ route('hrcore.employees.create') }}" class="btn btn-primary w-100">
                                <i class="bx bx-plus me-1"></i>
                                {{ __('Add Employee') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('hrcore.attendance.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-time-five me-1"></i>
                                {{ __('View Attendance') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('hrcore.leaves.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-calendar-check me-1"></i>
                                {{ __('Leave Requests') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('hrcore.holidays.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-calendar-event me-1"></i>
                                {{ __('Manage Holidays') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Addon Widgets --}}
    @if(count($addonWidgets) > 0)
    <div class="row mb-4">
        @foreach($addonWidgets as $key => $widget)
            @if($widget['data'])
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ $widget['title'] }}</h5>
                        @if(isset($widget['data']['url']))
                        <a href="{{ $widget['data']['url'] }}" class="btn btn-sm btn-outline-primary">
                            {{ __('View All') }}
                        </a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($key === 'shiftplus')
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="bx bx-calendar"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $widget['data']['today_shifts'] }}</h6>
                                    <small class="text-muted">{{ __('Shifts scheduled today') }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Leave Requests & Upcoming Holidays --}}
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Pending Leave Requests') }}</h5>
                    <a href="{{ route('hrcore.leaves.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('View All') }}
                    </a>
                </div>
                <div class="card-body">
                    @if(count($leaveRequests) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Duration') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaveRequests as $request)
                                    <tr>
                                        <td>{{ $request['employee'] ?? '-' }}</td>
                                        <td>{{ $request['type'] ?? '-' }}</td>
                                        <td>{{ $request['duration'] ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-label-warning">{{ __('Pending') }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-icon btn-text-primary">
                                                <i class="bx bx-show"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-4">{{ __('No pending leave requests') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Upcoming Holidays') }}</h5>
                </div>
                <div class="card-body">
                    @if(count($upcomingHolidays) > 0)
                        @foreach($upcomingHolidays as $holiday)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">{{ $holiday['name'] ?? '' }}</h6>
                                <small class="text-muted">{{ $holiday['date'] ?? '' }}</small>
                            </div>
                            <span class="badge bg-label-primary">{{ $holiday['day'] ?? '' }}</span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center py-4">{{ __('No upcoming holidays') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection