{{-- Version Display Component --}}
@props([
    'showCodename' => false,
    'showBadge' => true,
    'showDate' => false,
    'size' => 'sm', // sm, md, lg
    'format' => 'inline' // inline, block, detailed
])

@php
    use App\Helpers\VersionHelper;
    
    $version = config('app.version');
    $codename = config('app.version_codename');
    $stage = config('app.version_stage');
    $date = config('app.version_date');
    
    $sizeClass = match($size) {
        'lg' => 'fs-5',
        'md' => 'fs-6',
        default => 'small'
    };
    
    $stageColors = [
        'alpha' => 'danger',
        'beta' => 'warning',
        'rc' => 'info',
        'stable' => 'success'
    ];
    $stageColor = $stageColors[$stage] ?? 'secondary';
@endphp

@if($format === 'detailed')
    {{-- Detailed Format --}}
    <div class="version-display-detailed {{ $attributes->get('class') }}">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bx bx-info-circle me-2"></i>System Information
                </h6>
                
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td class="text-muted">Version</td>
                            <td class="fw-semibold">
                                {{ $version }}
                                @if($showBadge && $stage !== 'stable')
                                    <span class="badge bg-{{ $stageColor }} ms-2">{{ ucfirst($stage) }}</span>
                                @endif
                            </td>
                        </tr>
                        @if($codename)
                            <tr>
                                <td class="text-muted">Codename</td>
                                <td>{{ $codename }}</td>
                            </tr>
                        @endif
                        @if($date)
                            <tr>
                                <td class="text-muted">Release Date</td>
                                <td>{{ $date }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted">PHP Version</td>
                            <td>{{ PHP_VERSION }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Laravel Version</td>
                            <td>{{ app()->version() }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Environment</td>
                            <td>
                                <span class="badge bg-label-{{ config('app.env') === 'production' ? 'success' : 'warning' }}">
                                    {{ ucfirst(config('app.env')) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                @if($stage === 'alpha')
                    <div class="alert alert-danger mb-0">
                        <i class="bx bx-error me-2"></i>
                        <strong>Alpha Software:</strong> Not for production use
                    </div>
                @elseif($stage === 'beta')
                    <div class="alert alert-warning mb-0">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Beta Software:</strong> Use with caution
                    </div>
                @endif
            </div>
        </div>
    </div>

@elseif($format === 'block')
    {{-- Block Format --}}
    <div class="version-display-block {{ $attributes->get('class') }}">
        <div class="{{ $sizeClass }}">
            <div class="mb-1">
                <strong>Version:</strong> {{ $version }}
                @if($showBadge && $stage !== 'stable')
                    <span class="badge bg-{{ $stageColor }} ms-1">{{ ucfirst($stage) }}</span>
                @endif
            </div>
            
            @if($showCodename && $codename)
                <div class="text-muted">Codename: {{ $codename }}</div>
            @endif
            
            @if($showDate && $date)
                <div class="text-muted">Released: {{ $date }}</div>
            @endif
        </div>
    </div>

@else
    {{-- Inline Format (Default) --}}
    <span class="version-display-inline {{ $sizeClass }} {{ $attributes->get('class') }}">
        v{{ $version }}
        
        @if($showBadge && $stage !== 'stable')
            <span class="badge bg-label-{{ $stageColor }} ms-1">{{ ucfirst($stage) }}</span>
        @endif
        
        @if($showCodename && $codename)
            <span class="text-muted">({{ $codename }})</span>
        @endif
        
        @if($showDate && $date)
            <span class="text-muted">- {{ $date }}</span>
        @endif
    </span>
@endif

{{-- Add custom styles if needed --}}
@once
    @push('page-style')
    <style>
        .version-display-detailed .card {
            border: 1px solid rgba(0,0,0,.125);
        }
        .version-display-detailed table td:first-child {
            width: 40%;
        }
    </style>
    @endpush
@endonce