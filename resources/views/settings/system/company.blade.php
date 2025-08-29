<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Company Information') }}</h4>
</div>

<form id="companySettingsForm">
    @csrf
    <div class="row g-4">
        <!-- Company Information -->
        <div class="col-12">
            <h6 class="text-muted fw-light mb-3">{{ __('Company Information') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="company_name"
                :label="__('Company Name')"
                :value="$settings['company_name'] ?? ''"
                :help="__('Your company legal name')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="company_email"
                type="email"
                :label="__('Company Email')"
                :value="$settings['company_email'] ?? ''"
                :help="__('Primary company email address')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="company_phone"
                :label="__('Company Phone')"
                :value="$settings['company_phone'] ?? ''"
                :help="__('Primary company phone number')"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="company_website"
                type="url"
                :label="__('Company Website')"
                :value="$settings['company_website'] ?? ''"
                :help="__('Company website URL')"
            />
        </div>
        
        <div class="col-12">
            <x-settings-textarea
                name="company_address"
                :label="__('Company Address')"
                :value="$settings['company_address'] ?? ''"
                :help="__('Complete company address')"
                rows="3"
            />
        </div>
        
        <!-- Legal Information -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Legal Information') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="company_registration_number"
                :label="__('Registration Number')"
                :value="$settings['company_registration_number'] ?? ''"
                :help="__('Company registration or incorporation number')"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="company_tax_number"
                :label="__('Tax Number')"
                :value="$settings['company_tax_number'] ?? ''"
                :help="__('Company tax identification number')"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="company_vat_number"
                :label="__('VAT Number')"
                :value="$settings['company_vat_number'] ?? ''"
                :help="__('Value Added Tax number if applicable')"
            />
        </div>
        
        <!-- Logo Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Company Logo') }}</h6>
        </div>
        
        <div class="col-md-6">
            <label class="form-label">{{ __('Company Logo') }}</label>
            <div class="d-flex align-items-center gap-3">
                @if(!empty($settings['company_logo']))
                    <img src="{{ asset($settings['company_logo']) }}" alt="Company Logo" class="rounded" style="height: 60px;">
                @else
                    <div class="avatar avatar-xl">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-building"></i>
                        </span>
                    </div>
                @endif
                <div>
                    <input type="file" name="company_logo" class="form-control" accept="image/*">
                    <div class="form-text">{{ __('Recommended size: 200x60 pixels') }}</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <label class="form-label">{{ __('Company Icon') }}</label>
            <div class="d-flex align-items-center gap-3">
                @if(!empty($settings['company_icon']))
                    <img src="{{ asset($settings['company_icon']) }}" alt="Company Icon" class="rounded" style="height: 40px;">
                @else
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-building"></i>
                        </span>
                    </div>
                @endif
                <div>
                    <input type="file" name="company_icon" class="form-control" accept="image/*">
                    <div class="form-text">{{ __('Square image for favicon') }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
    </div>
</form>

