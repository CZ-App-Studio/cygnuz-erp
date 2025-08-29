@csrf
<div class="row g-3">
  <div class="col-md-6 mb-3">
    <label for="first_name" class="form-label">@lang('First Name') <span class="text-danger">*</span></label>
    <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror"
           value="{{ old('first_name', $contact->first_name ?? '') }}" required>
    @error('first_name')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6 mb-3">
    <label for="last_name" class="form-label">@lang('Last Name')</label>
    <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror"
           value="{{ old('last_name', $contact->last_name ?? '') }}">
    @error('last_name')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6 mb-3">
    <label for="email_primary" class="form-label">@lang('Primary Email')</label>
    <input type="email" name="email_primary" id="email_primary" class="form-control @error('email_primary') is-invalid @enderror"
           value="{{ old('email_primary', $contact->email_primary ?? '') }}">
    @error('email_primary')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6 mb-3">
    <label for="phone_primary" class="form-label">@lang('Primary Phone')</label>
    <input type="tel" name="phone_primary" id="phone_primary" class="form-control @error('phone_primary') is-invalid @enderror"
           value="{{ old('phone_primary', $contact->phone_primary ?? '') }}">
    @error('phone_primary')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6 mb-3">
    <label for="company_id" class="form-label">@lang('Company')</label>
    <select name="company_id" id="company_id" class="form-select select2-companies @error('company_id') is-invalid @enderror"
            data-placeholder="@lang('Search & Select Company...')"
            data-ajax--url="{{ route('companies.selectSearch') }}">
      @if(old('company_id', $contact->company_id ?? $company->id ?? null))
        @php
          $preselectedCompanyId = old('company_id', $contact->company_id ?? $company->id ?? null);
          $preselectedCompanyName = \Modules\CRMCore\app\Models\Company::find($preselectedCompanyId)?->name ?? 'Unknown Company';
        @endphp
        <option value="{{ $preselectedCompanyId }}" selected="selected">{{ $preselectedCompanyName }}</option>
      @endif
    </select>
    @error('company_id')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6 mb-3">
    <label for="job_title" class="form-label">@lang('Job Title')</label>
    <input type="text" name="job_title" id="job_title" class="form-control @error('job_title') is-invalid @enderror"
           value="{{ old('job_title', $contact->job_title ?? '') }}">
    @error('job_title')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6 mb-3">
    <label for="assigned_to_user_id" class="form-label">@lang('Assigned To')</label>
    <select name="assigned_to_user_id" id="assigned_to_user_id" class="form-select select2-users @error('assigned_to_user_id') is-invalid @enderror"
            data-placeholder="@lang('Search & Select User...')"
            data-ajax--url="{{ route('users.selectSearch') }}">
      @if(old('assigned_to_user_id', $contact->assigned_to_user_id ?? null))
        @php
          $preselectedUserId = old('assigned_to_user_id', $contact->assigned_to_user_id ?? null);
          $preselectedUserName = \App\Models\User::find($preselectedUserId)?->getFullName() ?? 'Unknown User';
        @endphp
        <option value="{{ $preselectedUserId }}" selected="selected">{{ $preselectedUserName }}</option>
      @endif
    </select>
    @error('assigned_to_user_id')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

<hr class="my-3">

<h6 class="mb-3">{{ __('Additional Information') }}</h6>
<div class="row g-3">
  <div class="col-md-12 mb-3">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
        {{ old('is_active', $contact->is_active ?? true) ? 'checked' : '' }}>
      <label class="form-check-label" for="is_active">@lang('Active Contact')</label>
    </div>
  </div>
</div>
