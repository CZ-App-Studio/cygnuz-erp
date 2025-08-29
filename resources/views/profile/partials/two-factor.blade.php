@if(app(\App\Services\AddonService\AddonService::class)->isAddonEnabled('TwoFactorAuth'))
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Two-Factor Authentication') }}</h5>
            </div>
            <div class="card-body">
                @if($has2FA)
                    <!-- 2FA Enabled -->
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bx bx-shield fs-3 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">{{ __('Two-Factor Authentication is Enabled') }}</h6>
                            <p class="mb-0">{{ __('Your account is protected with two-factor authentication.') }}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-label-primary me-2">
                                    <i class="bx bx-calendar"></i>
                                </div>
                                <div>
                                    <small class="text-muted">{{ __('Enabled On') }}</small>
                                    <p class="mb-0 fw-semibold">{{ \Carbon\Carbon::parse($twoFactorData['enabled_at'])->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-label-info me-2">
                                    <i class="bx bx-key"></i>
                                </div>
                                <div>
                                    <small class="text-muted">{{ __('Recovery Codes') }}</small>
                                    <p class="mb-0 fw-semibold">{{ $twoFactorData['recovery_codes_count'] }} {{ __('remaining') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-label-success me-2">
                                    <i class="bx bx-devices"></i>
                                </div>
                                <div>
                                    <small class="text-muted">{{ __('Trusted Devices') }}</small>
                                    <p class="mb-0 fw-semibold">{{ count($twoFactorData['trusted_devices']) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('twofactorauth.manage') }}" class="btn btn-primary">
                            <i class="bx bx-cog me-1"></i> {{ __('Manage 2FA Settings') }}
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="regenerateRecoveryCodes()">
                            <i class="bx bx-refresh me-1"></i> {{ __('Regenerate Recovery Codes') }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="disable2FA()">
                            <i class="bx bx-x-circle me-1"></i> {{ __('Disable 2FA') }}
                        </button>
                    </div>
                    
                    @if(count($twoFactorData['trusted_devices']) > 0)
                    <hr class="my-4">
                    <h6 class="mb-3">{{ __('Trusted Devices') }}</h6>
                    <div class="list-group">
                        @foreach($twoFactorData['trusted_devices'] as $device)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $device['name'] ?? __('Unknown Device') }}</h6>
                                <small class="text-muted">
                                    {{ __('Trusted on') }}: {{ \Carbon\Carbon::parse($device['trusted_at'])->format('M d, Y H:i') }}
                                    @if(isset($device['ip']))
                                        • IP: {{ $device['ip'] }}
                                    @endif
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTrustedDevice('{{ $device['token'] }}')">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                @else
                    <!-- 2FA Not Enabled -->
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bx bx-shield fs-3 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">{{ __('Two-Factor Authentication is Not Enabled') }}</h6>
                            <p class="mb-0">{{ __('Add an extra layer of security to your account by enabling two-factor authentication.') }}</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="mb-3">{{ __('What is Two-Factor Authentication?') }}</h6>
                        <p>{{ __('Two-factor authentication (2FA) adds an extra layer of security to your account. When enabled, you will need to enter both your password and a verification code from your authenticator app to sign in.') }}</p>
                        
                        <h6 class="mb-3">{{ __('Benefits') }}</h6>
                        <ul>
                            <li>{{ __('Protects your account even if your password is compromised') }}</li>
                            <li>{{ __('Prevents unauthorized access to your account') }}</li>
                            <li>{{ __('Easy to set up and use with any authenticator app') }}</li>
                        </ul>
                    </div>
                    
                    <a href="{{ route('twofactorauth.setup') }}" class="btn btn-primary">
                        <i class="bx bx-shield me-1"></i> {{ __('Enable Two-Factor Authentication') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function regenerateRecoveryCodes() {
    window.location.href = '{{ route("twofactorauth.manage") }}';
}

function disable2FA() {
    window.location.href = '{{ route("twofactorauth.manage") }}';
}

function removeTrustedDevice(token) {
    window.location.href = '{{ route("twofactorauth.manage") }}';
}
</script>
@else
<div class="alert alert-info">
    <i class="bx bx-info-circle me-1"></i>
    {{ __('Two-Factor Authentication module is not available.') }}
</div>
@endif