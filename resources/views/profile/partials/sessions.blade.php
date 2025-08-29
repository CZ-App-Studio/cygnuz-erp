<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('Active Sessions') }}</h5>
                <button type="button" class="btn btn-sm btn-outline-danger" id="terminateAllSessions">
                    <i class="bx bx-log-out me-1"></i> {{ __('Sign Out All Other Sessions') }}
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    {{ __('Manage and log out your active sessions on other browsers and devices.') }}
                </p>
                
                <div id="sessionsList">
                    @foreach($sessions as $session)
                    <div class="session-card card mb-3 {{ $session['is_current'] ? 'current' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bx {{ str_contains(strtolower($session['user_agent']), 'mobile') ? 'bx-mobile' : 'bx-desktop' }} fs-4 me-2"></i>
                                        <div>
                                            <h6 class="mb-0">
                                                {{ $session['is_current'] ? __('Current Session') : __('Active Session') }}
                                            </h6>
                                            <small class="text-muted">{{ $session['user_agent'] }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-3 text-muted small">
                                        <span><i class="bx bx-map me-1"></i> {{ $session['ip_address'] }}</span>
                                        <span><i class="bx bx-time me-1"></i> {{ __('Last active') }}: {{ $session['last_activity']->diffForHumans() }}</span>
                                    </div>
                                </div>
                                @if(!$session['is_current'])
                                <button type="button" class="btn btn-sm btn-outline-danger terminate-session" 
                                        data-session-id="{{ $session['id'] }}">
                                    <i class="bx bx-x"></i>
                                </button>
                                @else
                                <span class="badge bg-success">{{ __('Current') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if(count($sessions) === 0)
                <div class="text-center py-4">
                    <i class="bx bx-devices fs-1 text-muted mb-3"></i>
                    <p class="text-muted">{{ __('No active sessions found') }}</p>
                    @if(config('session.driver') !== 'database')
                    <div class="alert alert-info mt-3">
                        <i class="bx bx-info-circle me-1"></i>
                        {{ __('Session tracking requires database session driver. Please restart your application after changing SESSION_DRIVER=database in .env file.') }}
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        
        @if(count($loginHistory) > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Recent Login Activity') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Date & Time') }}</th>
                                <th>{{ __('IP Address') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th>{{ __('Device') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loginHistory as $history)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($history->created_at)->format('M d, Y H:i') }}</td>
                                <td>{{ $history->ip_address }}</td>
                                <td>{{ $history->location ?? '-' }}</td>
                                <td>{{ $history->device ?? '-' }}</td>
                                <td>
                                    @if($history->status === 'success')
                                        <span class="badge bg-success">{{ __('Success') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Failed') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Terminate All Sessions Modal -->
<div class="modal fade" id="terminateAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Confirm Password') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Please enter your password to confirm you want to sign out all other sessions.') }}</p>
                <form id="terminateAllForm">
                    @csrf
                    <div class="mb-3">
                        <label for="terminate_password" class="form-label">{{ __('Password') }}</label>
                        <input type="password" class="form-control" id="terminate_password" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-danger" id="confirmTerminateAll">
                    {{ __('Sign Out Other Sessions') }}
                </button>
            </div>
        </div>
    </div>
</div>