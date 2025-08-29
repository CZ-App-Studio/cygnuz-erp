<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Email Configuration') }}</h4>
</div>

<form id="generalSettingsForm">
    @csrf
    <div class="row g-4">
        <!-- Email Settings -->
        <div class="col-12">
            <h6 class="text-muted fw-light mb-3">{{ __('Email Settings') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="mail_mailer"
                :label="__('Mail Driver')"
                :value="'SMTP'"
                :help="__('Email sending method (SMTP only)')"
                readonly
            />
            <input type="hidden" name="mail_mailer" value="smtp">
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="mail_host"
                :label="__('Mail Host')"
                :value="$settings['mail_host'] ?? ''"
                :help="__('SMTP server address (e.g., smtp.gmail.com)')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="mail_port"
                type="number"
                :label="__('Mail Port')"
                :value="$settings['mail_port'] ?? '587'"
                :help="__('SMTP port (587 for TLS, 465 for SSL)')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="mail_encryption"
                :label="__('Encryption')"
                :value="$settings['mail_encryption'] ?? 'tls'"
                :options="[
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                    '' => 'None'
                ]"
                :help="__('Email encryption method')"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="mail_username"
                :label="__('Mail Username')"
                :value="$settings['mail_username'] ?? ''"
                :help="__('SMTP authentication username')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="mail_password"
                type="password"
                :label="__('Mail Password')"
                :value="$settings['mail_password'] ?? ''"
                :help="__('SMTP authentication password')"
                :placeholder="!empty($settings['mail_password']) ? __('Password is set') : __('Enter password')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="mail_from_address"
                type="email"
                :label="__('From Email Address')"
                :value="$settings['mail_from_address'] ?? ''"
                :help="__('Default sender email address')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="mail_from_name"
                :label="__('From Name')"
                :value="$settings['mail_from_name'] ?? config('app.name')"
                :help="__('Default sender name')"
                required
            />
        </div>
        
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                {{ __('These email settings will be used for all system notifications, password resets, and other automated emails.') }}
            </div>
        </div>
    </div>
    
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
        <button type="button" class="btn btn-secondary" id="testEmailBtn" data-user-email="{{ auth()->user()->email }}">
            <i class="bx bx-envelope me-1"></i> {{ __('Send Test Email') }}
        </button>
    </div>
</form>

