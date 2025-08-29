@extends('filemanagercore::layouts.master')

@section('title', $moduleName)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="{{ $moduleIcon }} me-2"></i>
                        <h4 class="card-title mb-0">{{ $moduleName }}</h4>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" id="testConfigBtn">
                            <i class="bx bx-test-tube me-1"></i> Test Configuration
                        </button>
                        <button type="button" class="btn btn-outline-warning me-2" id="resetDefaultsBtn">
                            <i class="bx bx-reset me-1"></i> Reset to Defaults
                        </button>
                        <button type="button" class="btn btn-primary" id="saveSettingsBtn">
                            <i class="bx bx-save me-1"></i> Save Settings
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ $moduleDescription }}</p>
                    
                    <form id="settingsForm">
                        @csrf
                        
                        @php
                            // Handle both our custom view and the module settings controller view
                            $settingsDefinition = $definition ?? $settings ?? [];
                            $currentVals = $currentValues ?? $values ?? [];
                        @endphp
                        
                        @foreach($settingsDefinition as $sectionKey => $section)
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="bx bx-cog me-2"></i>
                                    {{ __(ucwords(str_replace('_', ' ', $sectionKey))) }}
                                </h5>
                                
                                <div class="row">
                                    @foreach($section as $settingKey => $setting)
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="form-group">
                                                @if($setting['type'] === 'toggle')
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               name="{{ $settingKey }}" 
                                                               id="{{ $settingKey }}" 
                                                               value="1"
                                                               {{ isset($currentVals[$settingKey]) && $currentVals[$settingKey] ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="{{ $settingKey }}">
                                                            {{ $setting['label'] }}
                                                        </label>
                                                    </div>
                                                    @if(isset($setting['help']))
                                                        <small class="form-text text-muted">{{ $setting['help'] }}</small>
                                                    @endif
                                                @elseif($setting['type'] === 'select')
                                                    <label for="{{ $settingKey }}" class="form-label">{{ $setting['label'] }}</label>
                                                    <select class="form-select" name="{{ $settingKey }}" id="{{ $settingKey }}">
                                                        @foreach($setting['options'] as $optionValue => $optionLabel)
                                                            <option value="{{ $optionValue }}" 
                                                                {{ isset($currentVals[$settingKey]) && $currentVals[$settingKey] == $optionValue ? 'selected' : '' }}>
                                                                {{ $optionLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if(isset($setting['help']))
                                                        <small class="form-text text-muted">{{ $setting['help'] }}</small>
                                                    @endif
                                                @elseif($setting['type'] === 'number')
                                                    <label for="{{ $settingKey }}" class="form-label">{{ $setting['label'] }}</label>
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="{{ $settingKey }}" 
                                                           id="{{ $settingKey }}" 
                                                           value="{{ $currentVals[$settingKey] ?? $setting['default'] }}">
                                                    @if(isset($setting['help']))
                                                        <small class="form-text text-muted">{{ $setting['help'] }}</small>
                                                    @endif
                                                @elseif($setting['type'] === 'multiselect')
                                                    <label for="{{ $settingKey }}" class="form-label">{{ $setting['label'] }}</label>
                                                    <select class="form-select" 
                                                            name="{{ $settingKey }}[]" 
                                                            id="{{ $settingKey }}" 
                                                            multiple>
                                                        @php
                                                            // Handle both our custom view and the module settings controller view
                                                            $currentVals = $currentValues ?? $values ?? [];
                                                            $selectedValues = isset($currentVals[$settingKey]) 
                                                                ? (is_array($currentVals[$settingKey]) ? $currentVals[$settingKey] : json_decode($currentVals[$settingKey], true) ?? [])
                                                                : [];
                                                        @endphp
                                                        @foreach($setting['options'] as $optionValue => $optionLabel)
                                                            <option value="{{ $optionValue }}" 
                                                                {{ in_array($optionValue, $selectedValues) ? 'selected' : '' }}>
                                                                {{ $optionLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if(isset($setting['help']))
                                                        <small class="form-text text-muted">{{ $setting['help'] }}</small>
                                                    @endif
                                                @else
                                                    <label for="{{ $settingKey }}" class="form-label">{{ $setting['label'] }}</label>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="{{ $settingKey }}" 
                                                           id="{{ $settingKey }}" 
                                                           value="{{ $currentVals[$settingKey] ?? $setting['default'] }}">
                                                    @if(isset($setting['help']))
                                                        <small class="form-text text-muted">{{ $setting['help'] }}</small>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <hr>
                        @endforeach
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configuration Test Modal -->
<div class="modal fade" id="testResultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configuration Test Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="testResultsContent">
                <!-- Test results will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Save settings
    $('#saveSettingsBtn').click(function() {
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...').prop('disabled', true);
        
        const formData = new FormData($('#settingsForm')[0]);
        
        $.ajax({
            url: '{{ route("filemanagercore.settings.update") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Settings saved successfully');
                } else {
                    toastr.error(response.message || 'Failed to save settings');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.errors) {
                    let errorMessages = Object.values(response.errors).flat().join('<br>');
                    toastr.error(errorMessages);
                } else {
                    toastr.error('An error occurred while saving settings');
                }
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Reset to defaults
    $('#resetDefaultsBtn').click(function() {
        if (!confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Resetting...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("filemanagercore.settings.reset") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Settings reset successfully');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message || 'Failed to reset settings');
                }
            },
            error: function() {
                toastr.error('An error occurred while resetting settings');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Test configuration
    $('#testConfigBtn').click(function() {
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("filemanagercore.settings.test") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.data) {
                    let resultsHtml = '';
                    
                    Object.entries(response.data).forEach(([key, test]) => {
                        const statusIcon = test.status === 'passed' 
                            ? '<i class="bx bx-check-circle text-success"></i>' 
                            : '<i class="bx bx-x-circle text-danger"></i>';
                        
                        resultsHtml += `
                            <div class="d-flex align-items-center mb-3">
                                ${statusIcon}
                                <div class="ms-3">
                                    <h6 class="mb-1">${test.name}</h6>
                                    <small class="text-muted">${test.message}</small>
                                </div>
                            </div>
                        `;
                    });
                    
                    $('#testResultsContent').html(resultsHtml);
                    $('#testResultsModal').modal('show');
                } else {
                    toastr.error('Failed to run configuration test');
                }
            },
            error: function() {
                toastr.error('An error occurred while testing configuration');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush