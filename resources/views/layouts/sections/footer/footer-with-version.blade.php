{{-- Footer with Version Display --}}
<footer class="content-footer footer bg-footer-theme">
    <div class="{{ !empty($containerNav) ? $containerNav : 'container-fluid' }}">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="text-body">
                © {{ date('Y') }} {{ config('variables.templateName') }}
                <span class="mx-2">|</span>
                Made with ❤️ by
                <a href="{{ config('variables.creatorUrl') }}" target="_blank" class="footer-link">{{ config('variables.creatorName') }}</a>
            </div>
            
            <div class="d-flex align-items-center">
                {{-- Version Display --}}
                <div class="version-info me-3">
                    <small class="text-muted">
                        v{{ config('app.version') }}
                        
                        @if(config('app.version_stage') !== 'stable')
                            @php
                                $stageColors = [
                                    'alpha' => 'danger',
                                    'beta' => 'warning',
                                    'rc' => 'info'
                                ];
                                $stageColor = $stageColors[config('app.version_stage')] ?? 'secondary';
                            @endphp
                            <span class="badge bg-label-{{ $stageColor }} ms-1">
                                {{ ucfirst(config('app.version_stage')) }}
                            </span>
                        @endif
                        
                        @if(config('app.version_codename'))
                            <span class="text-muted ms-1">
                                ({{ config('app.version_codename') }})
                            </span>
                        @endif
                    </small>
                </div>
                
                {{-- Social Links --}}
                <div class="d-none d-lg-inline-block">
                    @if(config('variables.linkedInUrl'))
                        <a href="{{ config('variables.linkedInUrl') }}" class="footer-link me-2" target="_blank">
                            <i class="bx bxl-linkedin"></i>
                        </a>
                    @endif
                    
                    @if(config('variables.githubUrl'))
                        <a href="{{ config('variables.githubUrl') }}" class="footer-link me-2" target="_blank">
                            <i class="bx bxl-github"></i>
                        </a>
                    @endif
                    
                    @if(config('variables.instagramUrl'))
                        <a href="{{ config('variables.instagramUrl') }}" class="footer-link" target="_blank">
                            <i class="bx bxl-instagram"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Development Warning for Alpha/Beta --}}
        @if(in_array(config('app.version_stage'), ['alpha', 'beta']))
            <div class="alert alert-{{ config('app.version_stage') === 'alpha' ? 'danger' : 'warning' }} mb-0 text-center">
                <small>
                    <i class="bx bx-error-circle me-1"></i>
                    @if(config('app.version_stage') === 'alpha')
                        <strong>Alpha Version:</strong> This software is under heavy development and NOT suitable for production use.
                    @else
                        <strong>Beta Version:</strong> This software is in testing phase. Use with caution in production environments.
                    @endif
                </small>
            </div>
        @endif
    </div>
</footer>

{{-- Optional: Floating Version Badge (for development) --}}
@if(config('app.debug') && config('app.version_stage') !== 'stable')
    <div class="position-fixed bottom-0 start-0 m-3 d-none d-lg-block" style="z-index: 1080;">
        <div class="card bg-dark text-white">
            <div class="card-body p-2">
                <small>
                    <strong>{{ config('app.name') }}</strong><br>
                    Version: {{ config('app.version') }}<br>
                    Stage: {{ ucfirst(config('app.version_stage')) }}<br>
                    Env: {{ config('app.env') }}
                </small>
            </div>
        </div>
    </div>
@endif