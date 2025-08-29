$(function() {
    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    let settingsData = {};
    let settingsStructure = {};
    
    // Make functions available globally
    window.settingsData = settingsData;
    window.settingsStructure = settingsStructure;
    window.renderSettingsForm = renderSettingsForm;

    // Load settings on page load
    loadSettings();

    // Form submission
    $('#payrollSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        const btnText = submitBtn.find('.btn-text');
        const formData = new FormData(this);
        
        // Show loading state
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        // Clear previous validation errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        
        $.ajax({
            url: pageData.urls.update,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.updateSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Update local settings data
                    settingsData = response.data.settings;
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                
                if (xhr.status === 422 && response.data) {
                    // Handle validation errors
                    if (typeof response.data === 'object') {
                        Object.keys(response.data).forEach(function(field) {
                            const input = form.find(`[name="${field}"]`);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(response.data[field][0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response?.data || pageData.labels.error
                    });
                }
            },
            complete: function() {
                // Hide loading state
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    function loadSettings() {
        $.ajax({
            url: pageData.urls.show,
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    settingsData = response.data.settings;
                    settingsStructure = response.data.structure;
                    
                    renderSettingsForm();
                    $('#loadingPlaceholder').hide();
                }
            },
            error: function() {
                $('#loadingPlaceholder').html('<div class="alert alert-danger">Failed to load settings</div>');
            }
        });
    }

    function renderSettingsForm() {
        const tabContent = $('#settingsTabContent');
        tabContent.empty();

        Object.keys(settingsStructure).forEach(function(groupKey, index) {
            const group = settingsStructure[groupKey];
            const isActive = index === 0 ? 'show active' : '';
            
            let groupHtml = `
                <div class="tab-pane fade ${isActive}" id="${groupKey}">
                    <div class="row">
            `;

            Object.keys(group).forEach(function(settingKey) {
                const setting = group[settingKey];
                const value = settingsData[settingKey] || setting.default || '';
                
                groupHtml += `<div class="col-md-6 mb-3">`;
                groupHtml += renderSettingField(settingKey, setting, value);
                groupHtml += `</div>`;
            });

            groupHtml += `
                    </div>
                </div>
            `;

            tabContent.append(groupHtml);
        });

        // Initialize any special components (like toggles)
        initializeComponents();
    }

    function renderSettingField(key, setting, value) {
        let fieldHtml = `
            <label class="form-label" for="${key}">
                ${setting.label}
                ${setting.validation && setting.validation.includes('required') ? '<span class="text-danger">*</span>' : ''}
            </label>
        `;

        if (setting.help) {
            fieldHtml += `<div class="form-text mb-2">${setting.help}</div>`;
        }

        switch (setting.type) {
            case 'text':
            case 'number':
                const inputType = setting.type === 'number' ? 'number' : 'text';
                const step = setting.step ? `step="${setting.step}"` : '';
                const min = setting.min !== undefined ? `min="${setting.min}"` : '';
                const max = setting.max !== undefined ? `max="${setting.max}"` : '';
                
                fieldHtml += '<div class="input-group">';
                if (setting.prefix) {
                    fieldHtml += `<span class="input-group-text">${setting.prefix}</span>`;
                }
                fieldHtml += `
                    <input type="${inputType}" id="${key}" name="${key}" class="form-control" 
                           value="${value}" ${step} ${min} ${max}>
                `;
                if (setting.suffix) {
                    fieldHtml += `<span class="input-group-text">${setting.suffix}</span>`;
                }
                fieldHtml += '</div>';
                break;

            case 'select':
                fieldHtml += `<select id="${key}" name="${key}" class="form-select">`;
                Object.keys(setting.options).forEach(function(optionKey) {
                    const selected = value === optionKey ? 'selected' : '';
                    fieldHtml += `<option value="${optionKey}" ${selected}>${setting.options[optionKey]}</option>`;
                });
                fieldHtml += '</select>';
                break;

            case 'toggle':
                const checked = value === true || value === 'true' ? 'checked' : '';
                fieldHtml += `
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="${key}" name="${key}" value="true" ${checked}>
                        <label class="form-check-label" for="${key}">${setting.label}</label>
                    </div>
                `;
                break;

            case 'textarea':
                fieldHtml += `
                    <textarea id="${key}" name="${key}" class="form-control" rows="3">${value}</textarea>
                `;
                break;
                
            case 'date':
                fieldHtml += `
                    <input type="date" id="${key}" name="${key}" class="form-control" value="${value}">
                `;
                break;
                
            case 'time':
                fieldHtml += `
                    <input type="time" id="${key}" name="${key}" class="form-control" value="${value}">
                `;
                break;
        }

        fieldHtml += '<div class="invalid-feedback"></div>';
        
        return fieldHtml;
    }

    function initializeComponents() {
        // Handle checkbox values properly for form submission
        $('input[type="checkbox"]').on('change', function() {
            const checkbox = $(this);
            if (checkbox.is(':checked')) {
                checkbox.val('true');
            } else {
                // Create a hidden input to ensure false value is sent
                const hiddenInput = $(`<input type="hidden" name="${checkbox.attr('name')}" value="false">`);
                checkbox.after(hiddenInput);
                checkbox.removeAttr('name'); // Remove name to avoid duplicate submission
            }
        });
    }

    // Reset import form when modal is hidden
    $('#importSettingsModal').on('hidden.bs.modal', function() {
        $('#importSettingsForm')[0].reset();
        $('#importSettingsForm').find('.is-invalid').removeClass('is-invalid');
        $('#importSettingsForm').find('.invalid-feedback').text('');
    });
});

// Global functions
window.resetSettings = function() {
    Swal.fire({
        title: pageData.labels.confirmReset,
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.reset,
        cancelButtonText: pageData.labels.cancel,
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-label-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.reset,
                method: 'POST',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.resetSuccess,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        
                        // Reload settings
                        settingsData = response.data.settings;
                        window.renderSettingsForm();
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response?.data || pageData.labels.error
                    });
                }
            });
        }
    });
}

window.exportSettings = function() {
    window.location.href = pageData.urls.export;
};

window.showImportModal = function() {
    const modal = new bootstrap.Modal(document.getElementById('importSettingsModal'));
    modal.show();
};

window.importSettings = function() {
    const form = document.getElementById('importSettingsForm');
    const formData = new FormData(form);
    const submitBtn = $('.modal-footer .btn-primary');
    const spinner = submitBtn.find('.spinner-border');
    const btnText = submitBtn.find('.btn-text');
    
    if (!formData.get('settings_file')) {
        Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: 'Please select a settings file.'
        });
        return;
    }
    
    // Show loading state
    submitBtn.prop('disabled', true);
    spinner.removeClass('d-none');
    
    $.ajax({
        url: pageData.urls.import,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status === 'success') {
                // Hide modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('importSettingsModal'));
                modal.hide();
                
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.importSuccess,
                    showConfirmButton: false,
                    timer: 1500
                });
                
                // Reload settings
                settingsData = response.data.settings;
                window.renderSettingsForm();
                
                // Reset form
                form.reset();
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: response?.data || pageData.labels.error
            });
        },
        complete: function() {
            // Hide loading state
            submitBtn.prop('disabled', false);
            spinner.addClass('d-none');
        }
    });
}