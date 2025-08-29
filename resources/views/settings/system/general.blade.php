<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('General Settings') }}</h4>
</div>

<form id="generalSettingsForm">
    @csrf
    <div class="row g-4">
        <!-- Date & Time Settings -->
        <div class="col-12">
            <h6 class="text-muted fw-light mb-3">{{ __('Date & Time Settings') }}</h6>
        </div>
        
        <div class="col-md-6">
            <label class="form-label" for="default_timezone">{{ __('Default Timezone') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" id="default_timezone" name="default_timezone" required>
                @foreach(\App\Helpers\TimezoneHelper::getTimezoneList() as $value => $label)
                    <option value="{{ $value }}" {{ ($settings['default_timezone'] ?? 'Asia/Kolkata') == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">{{ __('Timezone for date and time display') }}</small>
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="date_format"
                :label="__('Date Format')"
                :value="$settings['date_format'] ?? 'd-m-Y'"
                :options="[
                    'd-m-Y' => date('d-m-Y') . ' (DD-MM-YYYY)',
                    'm-d-Y' => date('m-d-Y') . ' (MM-DD-YYYY)',
                    'Y-m-d' => date('Y-m-d') . ' (YYYY-MM-DD)',
                    'd/m/Y' => date('d/m/Y') . ' (DD/MM/YYYY)',
                    'm/d/Y' => date('m/d/Y') . ' (MM/DD/YYYY)',
                    'd.m.Y' => date('d.m.Y') . ' (DD.MM.YYYY)',
                    'd F Y' => date('d F Y') . ' (DD Month YYYY)'
                ]"
                :help="__('Format for displaying dates')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="time_format"
                :label="__('Time Format')"
                :value="$settings['time_format'] ?? 'h:i A'"
                :options="[
                    'h:i A' => date('h:i A') . ' (12-hour with AM/PM)',
                    'H:i' => date('H:i') . ' (24-hour)',
                    'h:i:s A' => date('h:i:s A') . ' (12-hour with seconds)',
                    'H:i:s' => date('H:i:s') . ' (24-hour with seconds)'
                ]"
                :help="__('Format for displaying time')"
                required
            />
        </div>
        
        <!-- Currency Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Currency Settings') }}</h6>
        </div>
        
        <div class="col-md-4">
            <label class="form-label" for="default_currency">{{ __('Default Currency') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" id="default_currency" name="default_currency" required>
                @foreach(\App\Helpers\CurrencyHelper::getCurrencyOptions() as $value => $label)
                    <option value="{{ $value }}" {{ ($settings['default_currency'] ?? 'USD') == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">{{ __('Default currency for the system') }}</small>
        </div>
        
        <div class="col-md-2">
            <label class="form-label">{{ __('Currency Symbol') }}</label>
            <div class="form-control-plaintext">
                <span id="currency_symbol" class="fs-5 fw-bold text-primary">{{ \App\Helpers\CurrencyHelper::getSymbol($settings['default_currency'] ?? 'USD') }}</span>
            </div>
        </div>
        
        <div class="col-md-2">
            <x-settings-select
                name="currency_position"
                :label="__('Symbol Position')"
                :value="$settings['currency_position'] ?? 'left'"
                :options="[
                    'left' => __('Before (') . \App\Helpers\CurrencyHelper::getSymbol($settings['default_currency'] ?? 'USD') . '100)',
                    'right' => __('After (100') . \App\Helpers\CurrencyHelper::getSymbol($settings['default_currency'] ?? 'USD') . ')'
                ]"
                :help="__('Position of currency symbol')"
                required
                id="currency_position"
            />
        </div>
        
        <div class="col-md-4">
            <x-settings-select
                name="decimal_places"
                :label="__('Decimal Places')"
                :value="$settings['decimal_places'] ?? '2'"
                :options="[
                    '0' => '0',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3'
                ]"
                :help="__('Number of decimal places for amounts')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="thousand_separator"
                :label="__('Thousand Separator')"
                :value="$settings['thousand_separator'] ?? ','"
                :options="[
                    ',' => __('Comma (1,000)'),
                    '.' => __('Period (1.000)'),
                    ' ' => __('Space (1 000)'),
                    '' => __('None (1000)')
                ]"
                :help="__('Character to separate thousands')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="decimal_separator"
                :label="__('Decimal Separator')"
                :value="$settings['decimal_separator'] ?? '.'"
                :options="[
                    '.' => __('Period (1.50)'),
                    ',' => __('Comma (1,50)')
                ]"
                :help="__('Character for decimal point')"
                required
            />
        </div>
        
        <!-- System Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('System Preferences') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="default_language"
                :label="__('Default Language')"
                :value="$settings['default_language'] ?? 'en'"
                :options="[
                    'en' => __('English'),
                    'ar' => __('Arabic')
                ]"
                :help="__('Default language for new users')"
                required
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-toggle
                name="is_helper_text_enabled"
                :label="__('Show Helper Text')"
                :value="$settings['is_helper_text_enabled'] ?? true"
                :help="__('Display help text below form fields')"
            />
        </div>
        
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                {{ __('These settings affect how dates, times, and currency are displayed throughout the system.') }}
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
    </div>
</form>

@section('page-styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('page-scripts')
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script>
// Currency data
const currencySymbols = @json(\App\Helpers\CurrencyHelper::getAllCurrencies());

$(function() {
    // Initialize Select2 for dropdowns
    $('#default_timezone').select2({
        placeholder: "{{ __('Select Timezone') }}",
        allowClear: false,
        width: '100%'
    });
    
    $('#default_currency').select2({
        placeholder: "{{ __('Select Currency') }}",
        allowClear: false,
        width: '100%'
    });
    
    // Update currency symbol when currency changes
    $('#default_currency').on('change', function() {
        const selectedCurrency = $(this).val();
        const symbol = currencySymbols[selectedCurrency] ? currencySymbols[selectedCurrency].symbol : selectedCurrency;
        $('#currency_symbol').text(symbol);
        
        // Update position examples
        updatePositionExamples(symbol);
    });
    
    // Function to update position examples
    function updatePositionExamples(symbol) {
        $('#currency_position option[value="left"]').text(`Before (${symbol}100)`);
        $('#currency_position option[value="right"]').text(`After (100${symbol})`);
        
        // Trigger Select2 update if it's initialized
        if ($('#currency_position').hasClass('select2-hidden-accessible')) {
            $('#currency_position').trigger('change.select2');
        }
    }
    
    // Form submission
    $('#generalSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Fix toggle values
        const toggleFields = ['is_helper_text_enabled'];
        toggleFields.forEach(field => {
            const isChecked = $(`#${field}`).is(':checked');
            formData.delete(field);
            formData.append(field, isChecked ? '1' : '0');
        });
        
        $.ajax({
            url: "{{ route('settings.system.update', 'general') }}",
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
@endsection