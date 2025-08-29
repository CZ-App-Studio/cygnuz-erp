<!-- Basic Information Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Basic Information') }}</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">{{ __('Company Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $company->name ?? '') }}" required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="website" class="form-label">{{ __('Website') }}</label>
                <input type="url" name="website" id="website" class="form-control @error('website') is-invalid @enderror"
                       value="{{ old('website', $company->website ?? '') }}" placeholder="https://example.com">
                @error('website')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="email_office" class="form-label">{{ __('Office Email') }}</label>
                <input type="email" name="email_office" id="email_office" class="form-control @error('email_office') is-invalid @enderror"
                       value="{{ old('email_office', $company->email_office ?? '') }}">
                @error('email_office')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="phone_office" class="form-label">{{ __('Office Phone') }}</label>
                <input type="tel" name="phone_office" id="phone_office" class="form-control @error('phone_office') is-invalid @enderror"
                       value="{{ old('phone_office', $company->phone_office ?? '') }}">
                @error('phone_office')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="industry" class="form-label">{{ __('Industry') }}</label>
                <input type="text" name="industry" id="industry" class="form-control @error('industry') is-invalid @enderror"
                       value="{{ old('industry', $company->industry ?? '') }}">
                @error('industry')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="assigned_to_user_id" class="form-label">{{ __('Assigned To') }}</label>
                <select name="assigned_to_user_id" id="assigned_to_user_id"
                        class="form-select select2-users @error('assigned_to_user_id') is-invalid @enderror"
                        data-placeholder="{{ __('Search & Select User...') }}"
                        data-ajax--url="{{ route('users.selectSearch') }}">
                    @if(isset($company) && $company->assignedToUser)
                        <option value="{{ $company->assigned_to_user_id }}" selected="selected">
                            {{ $company->assignedToUser->getFullNameAttribute() }}
                        </option>
                    @endif
                </select>
                @error('assigned_to_user_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                          rows="3">{{ old('description', $company->description ?? '') }}</textarea>
                @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<!-- Address Information Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Address Information') }}</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-12">
                <label for="address_street" class="form-label">{{ __('Street') }}</label>
                <input type="text" name="address_street" id="address_street" class="form-control @error('address_street') is-invalid @enderror"
                       value="{{ old('address_street', $company->address_street ?? '') }}">
                @error('address_street')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-md-6">
                <label for="address_city" class="form-label">{{ __('City') }}</label>
                <input type="text" name="address_city" id="address_city" class="form-control @error('address_city') is-invalid @enderror"
                       value="{{ old('address_city', $company->address_city ?? '') }}">
                @error('address_city')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-md-6">
                <label for="address_state" class="form-label">{{ __('State/Province') }}</label>
                <input type="text" name="address_state" id="address_state" class="form-control @error('address_state') is-invalid @enderror"
                       value="{{ old('address_state', $company->address_state ?? '') }}">
                @error('address_state')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-md-6">
                <label for="address_postal_code" class="form-label">{{ __('Postal Code') }}</label>
                <input type="text" name="address_postal_code" id="address_postal_code" class="form-control @error('address_postal_code') is-invalid @enderror"
                       value="{{ old('address_postal_code', $company->address_postal_code ?? '') }}">
                @error('address_postal_code')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-md-6">
                <label for="address_country" class="form-label">{{ __('Country') }}</label>
                <input type="text" name="address_country" id="address_country" class="form-control @error('address_country') is-invalid @enderror"
                       value="{{ old('address_country', $company->address_country ?? '') }}">
                @error('address_country')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<!-- Status Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Status') }}</h5>
    </div>
    <div class="card-body">
        <div class="form-check form-switch">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input @error('is_active') is-invalid @enderror"
                   value="1" {{ old('is_active', $company->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">{{ __('Active Company') }}</label>
            @error('is_active')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>