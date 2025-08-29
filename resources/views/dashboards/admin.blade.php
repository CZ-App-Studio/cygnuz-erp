@extends('layouts.layoutMaster')

@section('title', $pageTitle)

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Welcome Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">{{ __('Welcome back') }}, {{ auth()->user()->full_name }}!</h4>
                    <p class="text-muted">{{ __("Here's what's happening with your business today.") }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Core Statistics --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text text-muted">{{ __('Total Employees') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $stats['total_employees'] ?? 0 }}</h4>
                                <small class="text-muted">({{ $stats['total_users'] ?? 0 }} {{ __('total users') }})</small>
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
                            <p class="card-text text-muted">{{ __('Active Projects') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $stats['active_projects'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded p-2">
                                <i class="bx bx-folder bx-sm"></i>
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
                            <p class="card-text text-muted">{{ __('Pending Approvals') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $stats['pending_approvals'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded p-2">
                                <i class="bx bx-time bx-sm"></i>
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
                            <p class="card-text text-muted">{{ __('System Health') }}</p>
                            <div class="d-flex align-items-end mb-2">
                                <h4 class="card-title mb-0 me-2">{{ $stats['system_health'] ?? 'Good' }}</h4>
                            </div>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded p-2">
                                <i class="bx bx-heart bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Addon Modules Section --}}
    @if(count($enabledAddons) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">{{ __('Active Modules') }}</h5>
        </div>
        @foreach($enabledAddons as $addon)
        <div class="col-md-4 col-lg-3 mb-3">
            <a href="{{ $addon['url'] }}" class="text-decoration-none">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <span class="badge bg-label-primary rounded p-3">
                                <i class="{{ $addon['icon'] }} fs-3"></i>
                            </span>
                        </div>
                        <h6 class="card-title mb-0">{{ $addon['label'] }}</h6>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    @endif

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
                        @elseif($key === 'inventory')
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-label-danger">
                                        <i class="bx bx-error"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $widget['data']['low_stock_items'] }}</h6>
                                    <small class="text-muted">{{ __('Items below reorder level') }}</small>
                                </div>
                            </div>
                        @elseif($key === 'projects')
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">{{ $widget['data']['active_projects'] }}</h6>
                                    <small class="text-muted">{{ __('Active Projects') }}</small>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-danger">{{ $widget['data']['overdue_tasks'] }}</h6>
                                    <small class="text-muted">{{ __('Overdue Tasks') }}</small>
                                </div>
                            </div>
                        @elseif($key === 'accounting')
                            @if(isset($widget['data']['pending_entries']))
                                {{-- AccountingPro widget --}}
                                <div class="d-flex align-items-center">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class="bx bx-file"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $widget['data']['pending_entries'] }}</h6>
                                        <small class="text-muted">{{ __('Pending journal entries') }}</small>
                                    </div>
                                </div>
                            @else
                                {{-- AccountingCore widget --}}
                                <div class="row g-3">
                                    <div class="col-4 text-center">
                                        <h6 class="mb-0 text-success">{{ \App\Helpers\FormattingHelper::formatCurrency($widget['data']['monthly_income'] ?? 0) }}</h6>
                                        <small class="text-muted">{{ __('Income') }}</small>
                                    </div>
                                    <div class="col-4 text-center">
                                        <h6 class="mb-0 text-danger">{{ \App\Helpers\FormattingHelper::formatCurrency($widget['data']['monthly_expense'] ?? 0) }}</h6>
                                        <small class="text-muted">{{ __('Expenses') }}</small>
                                    </div>
                                    <div class="col-4 text-center">
                                        <h6 class="mb-0 {{ ($widget['data']['net_income'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ \App\Helpers\FormattingHelper::formatCurrency($widget['data']['net_income'] ?? 0) }}
                                        </h6>
                                        <small class="text-muted">{{ __('Net') }}</small>
                                    </div>
                                </div>
                            @endif
                        @elseif($key === 'searchplus')
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">{{ number_format($widget['data']['total_indexed'] ?? 0) }}</h6>
                                    <small class="text-muted">{{ __('Indexed Items') }}</small>
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-0">{{ $widget['data']['recent_searches'] ?? 0 }}</h6>
                                    <small class="text-muted">{{ __('Recent Searches') }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">{{ __('Last indexed') }}: {{ $widget['data']['last_indexed'] ?? __('Never') }}</small>
                            </div>
                        @elseif($key === 'hr')
                            {{-- HRCore widget --}}
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-primary">
                                                <i class="bx bx-group bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_employees'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Employees') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-success">
                                                <i class="bx bx-check-circle bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['today_present'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Present Today') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-info">
                                                <i class="bx bx-briefcase bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_departments'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Departments') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-warning">
                                                <i class="bx bx-time-five bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['pending_leaves'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Pending Leaves') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($key === 'crm')
                            {{-- CRMCore widget --}}
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-primary">
                                                <i class="bx bx-building bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_companies'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Companies') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-info">
                                                <i class="bx bx-user bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_contacts'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Contacts') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-success">
                                                <i class="bx bx-dollar bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_deals'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Deals') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-warning">
                                                <i class="bx bx-task bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_tasks'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Tasks') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted">{{ __('Total Revenue') }}</span>
                                    <h5 class="mb-0 text-success">{{ \App\Helpers\FormattingHelper::formatCurrency($widget['data']['total_revenue'] ?? 0) }}</h5>
                                </div>
                            </div>
                        @elseif($key === 'ai')
                            {{-- AICore widget --}}
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_sessions'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Chat Sessions') }}</small>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="mb-0">{{ number_format($widget['data']['total_messages'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Total Messages') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-info">
                                                <i class="bx bx-message-square-dots bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ number_format($widget['data']['today_messages'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Messages Today') }}</small>
                                        </div>
                                    </div>
                                </div>
                                @if(!empty($widget['data']['active_providers']))
                                <div class="col-12 mt-3 pt-3 border-top">
                                    <small class="text-muted d-block mb-2">{{ __('Active Providers') }}</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($widget['data']['active_providers'] as $provider)
                                        <span class="badge bg-label-primary">{{ $provider }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        @elseif($key === 'system')
                            {{-- SystemCore widget --}}
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-primary">
                                                <i class="bx bx-extension bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $widget['data']['enabled_modules'] ?? 0 }}/{{ $widget['data']['total_modules'] ?? 0 }}</h6>
                                            <small class="text-muted">{{ __('Modules') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-info">
                                                <i class="bx bx-data bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $widget['data']['db_size'] ?? 0 }} MB</h6>
                                            <small class="text-muted">{{ __('Database') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded bg-label-success">
                                                <i class="bx bx-memory-card bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ ucfirst($widget['data']['cache_driver'] ?? 'file') }}</h6>
                                            <small class="text-muted">{{ __('Cache') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                                            <span class="avatar-initial rounded {{ ($widget['data']['failed_jobs'] ?? 0) > 0 ? 'bg-label-danger' : 'bg-label-success' }}">
                                                <i class="bx bx-list-check bx-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 {{ ($widget['data']['failed_jobs'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ number_format($widget['data']['failed_jobs'] ?? 0) }}</h6>
                                            <small class="text-muted">{{ __('Failed Jobs') }}</small>
                                        </div>
                                    </div>
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

    {{-- Recent Activities & System Info --}}
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Recent Activities') }}</h5>
                </div>
                <div class="card-body">
                    @if(count($recentActivities) > 0)
                        <div class="timeline">
                            @foreach($recentActivities as $activity)
                                <div class="timeline-item">
                                    <span class="timeline-point timeline-point-primary"></span>
                                    <div class="timeline-event">
                                        <div class="timeline-header mb-1">
                                            <h6 class="mb-0">{{ $activity['title'] ?? '' }}</h6>
                                            <small class="text-muted">{{ $activity['time'] ?? '' }}</small>
                                        </div>
                                        <p class="mb-0">{{ $activity['description'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-4">{{ __('No recent activities') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('System Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('PHP Version') }}</span>
                        <span class="fw-medium">{{ PHP_VERSION }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('Laravel Version') }}</span>
                        <span class="fw-medium">{{ app()->version() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('Server Time') }}</span>
                        <span class="fw-medium">{{ now()->format('H:i:s') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('Timezone') }}</span>
                        <span class="fw-medium">{{ config('app.timezone') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Announcements Widget --}}
    <div class="row">
        @includeIf('announcement::widgets.dashboard-announcements')
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh widgets every 5 minutes
    setInterval(function() {
        // You can add AJAX calls here to refresh widget data
        console.log('Refreshing dashboard widgets...');
    }, 300000);
});
</script>
@endsection
