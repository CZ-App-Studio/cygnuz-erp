<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Attendance Settings') }}</h4>
</div>

<form id="attendanceSettingsForm">
    @csrf
    <div class="row g-4">
        <!-- Check-in/out Settings -->
        <div class="col-12">
            <h6 class="text-muted fw-light mb-3">{{ __('Check-in/out Settings') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-toggle
                name="enable_web_checkin"
                :label="__('Enable Web Check-in')"
                :value="$settings['enable_web_checkin'] ?? true"
                :help="__('Allow employees to check-in via web')"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-toggle
                name="allow_multiple_checkin"
                :label="__('Allow Multiple Check-in/out')"
                :value="$settings['allow_multiple_checkin'] ?? false"
                :help="__('Allow employees to check-in/out multiple times per day')"
            />
        </div>
        
        <!-- Time Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Time Settings') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="grace_time_minutes"
                type="number"
                :label="__('Grace Time (minutes)')"
                :value="$settings['grace_time_minutes'] ?? '15'"
                :help="__('Minutes allowed after shift start')"
                min="0"
                max="60"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="early_checkin_minutes"
                type="number"
                :label="__('Early Check-in (minutes)')"
                :value="$settings['early_checkin_minutes'] ?? '30'"
                :help="__('Minutes allowed before shift start')"
                min="0"
                max="120"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="auto_checkout_hours"
                type="number"
                :label="__('Auto Checkout After (hours)')"
                :value="$settings['auto_checkout_hours'] ?? '24'"
                :help="__('Automatically checkout after hours (0 = disabled)')"
                min="0"
                max="48"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="week_start_day"
                :label="__('Week Start Day')"
                :value="$settings['week_start_day'] ?? 'monday'"
                :options="[
                    'sunday' => __('Sunday'),
                    'monday' => __('Monday'),
                    'tuesday' => __('Tuesday'),
                    'wednesday' => __('Wednesday'),
                    'thursday' => __('Thursday'),
                    'friday' => __('Friday'),
                    'saturday' => __('Saturday')
                ]"
                :help="__('First day of attendance week')"
            />
        </div>
        
        <!-- Overtime Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Overtime Settings') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-toggle
                name="enable_overtime"
                :label="__('Enable Overtime Calculation')"
                :value="$settings['enable_overtime'] ?? true"
                :help="__('Calculate overtime hours automatically')"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="overtime_after_hours"
                type="number"
                :label="__('Overtime After (hours)')"
                :value="$settings['overtime_after_hours'] ?? '8'"
                :help="__('Hours before overtime starts')"
                min="6"
                max="12"
                step="0.5"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="overtime_rate"
                type="number"
                :label="__('Overtime Rate Multiplier')"
                :value="$settings['overtime_rate'] ?? '1.5'"
                :help="__('Multiplier for overtime pay calculation')"
                min="1"
                max="3"
                step="0.1"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-toggle
                name="require_overtime_approval"
                :label="__('Require Overtime Approval')"
                :value="$settings['require_overtime_approval'] ?? true"
                :help="__('Manager approval needed for overtime')"
            />
        </div>
        
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                {{ __('These settings control how attendance tracking works across the system.') }}
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
    </div>
</form>

@push('scripts')
<script>
$(function() {
    // Form submission
    $('#attendanceSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Fix toggle values
        const toggleFields = ['enable_web_checkin', 'allow_multiple_checkin', 'enable_overtime', 'require_overtime_approval'];
        toggleFields.forEach(field => {
            const isChecked = $(`#${field}`).is(':checked');
            formData.delete(field);
            formData.append(field, isChecked ? '1' : '0');
        });
        
        $.ajax({
            url: "{{ route('settings.system.update', 'attendance') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("Success") }}',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                let message = '{{ __("An error occurred") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("Error") }}',
                    text: message
                });
            }
        });
    });
});
</script>
@endpush