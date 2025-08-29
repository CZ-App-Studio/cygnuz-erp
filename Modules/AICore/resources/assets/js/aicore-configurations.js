/**
 * AI Core Configurations JavaScript
 * Handles configuration management functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Core Configurations loaded');
    
    initializeConfigurations();
    setupEventListeners();
    setupFormValidation();
});

/**
 * Initialize configurations functionality
 */
function initializeConfigurations() {
    // Initialize Select2 for all fields with select2-basic class
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-basic').each(function() {
            const $this = $(this);
            const placeholder = $this.data('placeholder') || 'Select an option';
            
            $this.select2({
                theme: 'bootstrap-5',
                placeholder: placeholder,
                allowClear: !$this.prop('required') && !$this.prop('multiple'),
                width: '100%'
            });
        });
    }

    // Show first category by default
    showCategory('general');
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Category navigation
    document.querySelectorAll('[data-category]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.getAttribute('data-category');
            showCategory(category);
            
            // Update active state
            document.querySelectorAll('.list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Form submissions
    document.querySelectorAll('.config-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveConfiguration(this);
        });
    });

    // Form field dependencies
    setupFieldDependencies();
}

/**
 * Show configuration category
 */
function showCategory(category) {
    // Hide all sections
    document.querySelectorAll('.config-section').forEach(section => {
        section.classList.add('d-none');
    });

    // Show selected section
    const targetSection = document.querySelector(`#${category}-settings, #${category}`);
    if (targetSection) {
        targetSection.classList.remove('d-none');
    }
}

/**
 * Setup field dependencies
 */
function setupFieldDependencies() {
    // API key requirement based on provider type
    const typeSelect = document.querySelector('#type');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            toggleApiKeyRequirement(this.value);
        });
        
        // Initial state
        toggleApiKeyRequirement(typeSelect.value);
    }

    // Auto-disable dependency
    const autoBudgetCheckbox = document.querySelector('#auto_disable_on_budget');
    if (autoBudgetCheckbox) {
        autoBudgetCheckbox.addEventListener('change', function() {
            const monthlyBudget = document.querySelector('#monthly_budget');
            const dailyBudget = document.querySelector('#daily_budget');
            
            if (this.checked) {
                if (monthlyBudget) monthlyBudget.required = true;
                if (dailyBudget) dailyBudget.required = true;
            } else {
                if (monthlyBudget) monthlyBudget.required = false;
                if (dailyBudget) dailyBudget.required = false;
            }
        });
    }

    // Alert settings dependency
    const enableAlertsCheckbox = document.querySelector('#enable_alerts');
    if (enableAlertsCheckbox) {
        enableAlertsCheckbox.addEventListener('change', function() {
            const alertEmail = document.querySelector('#alert_email');
            const alertFrequency = document.querySelector('#alert_frequency');
            
            const isRequired = this.checked;
            if (alertEmail) alertEmail.required = isRequired;
            if (alertFrequency) alertFrequency.required = isRequired;
        });
    }
}

/**
 * Toggle API key requirement
 */
function toggleApiKeyRequirement(providerType) {
    const apiKeyInput = document.querySelector('#api_key');
    const apiKeyGroup = apiKeyInput?.closest('.col-md-6');
    
    if (!apiKeyInput || !apiKeyGroup) return;

    if (providerType === 'local') {
        apiKeyGroup.style.display = 'none';
        apiKeyInput.required = false;
    } else {
        apiKeyGroup.style.display = 'block';
        apiKeyInput.required = true;
    }
}

/**
 * Save configuration
 */
function saveConfiguration(form) {
    const category = form.getAttribute('data-category');
    if (!category) {
        console.error('Form missing data-category attribute');
        return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Handle multi-select fields
    const multiSelectFields = ['allowed_file_types', 'fallback_providers'];
    multiSelectFields.forEach(field => {
        const fieldElement = form.querySelector(`[name="${field}[]"]`);
        if (fieldElement) {
            const values = formData.getAll(`${field}[]`);
            data[field] = values.length > 0 ? values : [];
        }
    });

    // Handle checkboxes
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (!data[checkbox.name]) {
            data[checkbox.name] = false;
        } else {
            data[checkbox.name] = true;
        }
    });

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Update button state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';

    // Prepare request
    const isUpdate = window.pageData?.currentConfigs?.[category] ? true : false;
    const url = isUpdate ? 
        window.pageData.routes.updateConfig.replace(':key', category) :
        window.pageData.routes.saveConfig;
    
    const method = isUpdate ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            category: category,
            settings: data
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSaveResult(form, true, data.message || 'Settings saved successfully');
            
            // Update local config cache
            if (window.pageData?.currentConfigs) {
                window.pageData.currentConfigs[category] = data.data || {};
            }
        } else {
            showSaveResult(form, false, data.message || 'Failed to save settings');
        }
    })
    .catch(error => {
        console.error('Save failed:', error);
        showSaveResult(form, false, 'Failed to save settings: ' + error.message);
    })
    .finally(() => {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

/**
 * Show save result
 */
function showSaveResult(form, success, message) {
    const alertClass = success ? 'alert-success' : 'alert-danger';
    const icon = success ? 'bx-check-circle' : 'bx-x-circle';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="bx ${icon} me-2"></i>
        <strong>${success ? 'Success!' : 'Error!'}</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert alert at the top of the form
    form.insertBefore(alert, form.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

/**
 * Setup form validation
 */
function setupFormValidation() {
    // Add custom validation if FormValidation library is available
    if (typeof FormValidation !== 'undefined') {
        document.querySelectorAll('.config-form').forEach(form => {
            const fields = {};
            
            // Common validations
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                fields[field.name] = {
                    validators: {
                        notEmpty: {
                            message: 'This field is required'
                        }
                    }
                };
            });

            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if (!fields[field.name]) fields[field.name] = { validators: {} };
                fields[field.name].validators.emailAddress = {
                    message: 'Please enter a valid email address'
                };
            });

            // Number range validations
            const numberFields = form.querySelectorAll('input[type="number"]');
            numberFields.forEach(field => {
                if (!fields[field.name]) fields[field.name] = { validators: {} };
                
                const min = field.getAttribute('min');
                const max = field.getAttribute('max');
                
                if (min !== null || max !== null) {
                    fields[field.name].validators.between = {
                        min: min ? parseInt(min) : null,
                        max: max ? parseInt(max) : null,
                        message: `Value must be between ${min || 'minimum'} and ${max || 'maximum'}`
                    };
                }
            });

            if (Object.keys(fields).length > 0) {
                FormValidation.formValidation(form, {
                    fields: fields,
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: '.mb-3'
                        }),
                        submitButton: new FormValidation.plugins.SubmitButton()
                    }
                });
            }
        });
    }
}

/**
 * Reset form to defaults
 */
function resetToDefaults(category) {
    const form = document.querySelector(`[data-category="${category}"]`);
    if (!form) return;

    if (confirm('Are you sure you want to reset all settings in this category to default values?')) {
        form.reset();
        
        // Reset Select2 fields
        if (typeof $ !== 'undefined' && $.fn.select2) {
            form.querySelectorAll('.select2-basic').forEach(select => {
                $(select).val(null).trigger('change');
            });
        }
    }
}

// Global functions
window.resetToDefaults = resetToDefaults;