<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Change Password') }}</h5>
            </div>
            <div class="card-body">
                <form id="changePasswordForm">
                    @csrf
                    <div class="mb-3">
                        <label for="current_password" class="form-label">{{ __('Current Password') }}</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="bx bx-show"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">{{ __('New Password') }}</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="bx bx-show"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            {{ __('Password must be at least 8 characters and contain uppercase, lowercase, numbers and symbols') }}
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">{{ __('Confirm New Password') }}</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password_confirmation')">
                                <i class="bx bx-show"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-lock me-1"></i> {{ __('Change Password') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Security Information') }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted mb-2">{{ __('Account Created') }}</h6>
                    <p class="mb-0">{{ $user->created_at->format('F d, Y H:i') }}</p>
                </div>
                
                <div class="mb-4">
                    <h6 class="text-muted mb-2">{{ __('Last Password Change') }}</h6>
                    <p class="mb-0">
                        @if($user->password_changed_at)
                            {{ \Carbon\Carbon::parse($user->password_changed_at)->format('F d, Y H:i') }}
                            <span class="text-muted">({{ \Carbon\Carbon::parse($user->password_changed_at)->diffForHumans() }})</span>
                        @else
                            {{ __('Never changed') }}
                        @endif
                    </p>
                </div>
                
                <div class="mb-4">
                    <h6 class="text-muted mb-2">{{ __('Account Status') }}</h6>
                    <span class="badge bg-success">{{ __('Active') }}</span>
                </div>
                
                <div class="mb-4">
                    <h6 class="text-muted mb-2">{{ __('Security Recommendations') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bx bx-check-circle text-success me-1"></i>
                            {{ __('Use a strong, unique password') }}
                        </li>
                        <li class="mb-2">
                            @if($has2FA)
                                <i class="bx bx-check-circle text-success me-1"></i>
                                {{ __('Two-factor authentication enabled') }}
                            @else
                                <i class="bx bx-info-circle text-warning me-1"></i>
                                {{ __('Enable two-factor authentication for extra security') }}
                            @endif
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-check-circle text-success me-1"></i>
                            {{ __('Regularly review active sessions') }}
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-check-circle text-success me-1"></i>
                            {{ __('Change password every 90 days') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>