<form id="basicInfoForm">
    @csrf
    <div class="row g-4">
        <div class="col-md-6">
            <label for="name" class="form-label">{{ __('Full Name') }}</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
        </div>
        
        <div class="col-md-6">
            <label for="email" class="form-label">{{ __('Email Address') }}</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
        </div>
        
        <div class="col-md-6">
            <label for="phone" class="form-label">{{ __('Phone Number') }}</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="{{ $user->phone }}">
        </div>
        
        <div class="col-md-6">
            <label for="date_of_birth" class="form-label">{{ __('Date of Birth') }}</label>
            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ $user->date_of_birth }}">
        </div>
        
        <div class="col-md-6">
            <label for="gender" class="form-label">{{ __('Gender') }}</label>
            <select class="form-select" id="gender" name="gender">
                <option value="">{{ __('Select Gender') }}</option>
                <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                <option value="other" {{ $user->gender == 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
            </select>
        </div>
        
        <div class="col-md-6">
            <label for="country" class="form-label">{{ __('Country') }}</label>
            <input type="text" class="form-control" id="country" name="country" value="{{ $user->country }}">
        </div>
        
        <div class="col-12">
            <label for="address" class="form-label">{{ __('Address') }}</label>
            <textarea class="form-control" id="address" name="address" rows="2">{{ $user->address }}</textarea>
        </div>
        
        <div class="col-md-4">
            <label for="city" class="form-label">{{ __('City') }}</label>
            <input type="text" class="form-control" id="city" name="city" value="{{ $user->city }}">
        </div>
        
        <div class="col-md-4">
            <label for="state" class="form-label">{{ __('State/Province') }}</label>
            <input type="text" class="form-control" id="state" name="state" value="{{ $user->state }}">
        </div>
        
        <div class="col-md-4">
            <label for="postal_code" class="form-label">{{ __('Postal Code') }}</label>
            <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ $user->postal_code }}">
        </div>

        @if(isset($user->team) || isset($user->designation) || isset($user->manager))
        <div class="col-12">
            <hr class="my-4">
            <h5 class="mb-3">{{ __('Employment Information') }}</h5>
        </div>
        
        @if(isset($user->employee_code))
        <div class="col-md-4">
            <label class="form-label">{{ __('Employee Code') }}</label>
            <input type="text" class="form-control" value="{{ $user->employee_code }}" readonly disabled>
        </div>
        @endif
        
        @if(isset($user->team))
        <div class="col-md-4">
            <label class="form-label">{{ __('Team') }}</label>
            <input type="text" class="form-control" value="{{ $user->team->name ?? '-' }}" readonly disabled>
        </div>
        @endif
        
        @if(isset($user->designation))
        <div class="col-md-4">
            <label class="form-label">{{ __('Designation') }}</label>
            <input type="text" class="form-control" value="{{ $user->designation->name ?? '-' }}" readonly disabled>
        </div>
        @endif
        
        @if(isset($user->designation->department))
        <div class="col-md-4">
            <label class="form-label">{{ __('Department') }}</label>
            <input type="text" class="form-control" value="{{ $user->designation->department->name ?? '-' }}" readonly disabled>
        </div>
        @endif
        
        @if(isset($user->shift))
        <div class="col-md-4">
            <label class="form-label">{{ __('Shift') }}</label>
            <input type="text" class="form-control" value="{{ $user->shift->name ?? '-' }}" readonly disabled>
        </div>
        @endif
        
        @if(isset($user->manager))
        <div class="col-md-4">
            <label class="form-label">{{ __('Reporting Manager') }}</label>
            <input type="text" class="form-control" value="{{ $user->manager->name ?? '-' }}" readonly disabled>
        </div>
        @endif
        
        @if(isset($user->joining_date))
        <div class="col-md-4">
            <label class="form-label">{{ __('Joining Date') }}</label>
            <input type="text" class="form-control" value="{{ $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->format('d M Y') : '-' }}" readonly disabled>
        </div>
        @endif
        @endif
        
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Changes') }}
            </button>
        </div>
    </div>
</form>