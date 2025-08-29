$(function () {
    'use strict';

    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Select2 for all dropdowns
    $('.provider-select, .model-select').each(function() {
        $(this).select2({
            theme: 'bootstrap5',
            width: '100%',
            dropdownParent: $(this).parent(),
            placeholder: $(this).data('placeholder') || $(this).find('option:first').text(),
            allowClear: true
        });
    });

    // Track unsaved changes
    let unsavedChanges = {};
    let saveTimeouts = {};

    // Handle provider change - update available models
    $('.provider-select').on('change', function() {
        const moduleId = $(this).data('module-id');
        const providerId = $(this).val();
        const modelSelect = $(`.model-select[data-module-id="${moduleId}"]`);
        
        // Clear model selection when provider changes
        modelSelect.val('').trigger('change');
        
        if (providerId) {
            // Show only models from selected provider
            modelSelect.find('option').each(function() {
                const optionProviderId = $(this).data('provider-id');
                if (!optionProviderId || optionProviderId == providerId) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Load models from server for this provider
            loadProviderModels(providerId, moduleId);
        } else {
            // Show all models when no provider selected
            modelSelect.find('option').show();
        }
        
        // Mark as changed and trigger auto-save
        markAsChanged(moduleId);
    });

    // Load models for a specific provider
    function loadProviderModels(providerId, moduleId) {
        const url = pageData.routes.getModels.replace(':providerId', providerId);
        const modelSelect = $(`.model-select[data-module-id="${moduleId}"]`);
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Clear existing options except the first one
                    modelSelect.find('option:not(:first)').remove();
                    
                    // Add new options
                    response.models.forEach(function(model) {
                        const option = new Option(
                            `${model.name} (${model.type})`,
                            model.id,
                            false,
                            false
                        );
                        $(option).attr('data-provider-id', providerId);
                        modelSelect.append(option);
                    });
                    
                    // Refresh Select2
                    modelSelect.trigger('change');
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: pageData.translations.loadModelsFailed || 'Failed to load models'
                });
            }
        });
    }

    // Handle model selection change
    $('.model-select').on('change', function() {
        const moduleId = $(this).data('module-id');
        markAsChanged(moduleId);
    });

    // Handle form input changes
    $('.module-config-form input, .module-config-form select').on('change', function() {
        const form = $(this).closest('form');
        const moduleId = form.data('module-id');
        markAsChanged(moduleId);
    });

    // Mark module as having unsaved changes
    function markAsChanged(moduleId) {
        unsavedChanges[moduleId] = true;
        
        // Clear existing timeout
        if (saveTimeouts[moduleId]) {
            clearTimeout(saveTimeouts[moduleId]);
        }
        
        // Set new timeout for auto-save (2 seconds after last change)
        saveTimeouts[moduleId] = setTimeout(function() {
            saveModuleConfiguration(moduleId, true);
        }, 2000);
        
        // Update UI to show unsaved state
        const card = $(`.module-config-form[data-module-id="${moduleId}"]`).closest('.card');
        card.find('.card-header').addClass('bg-warning-subtle');
    }

    // Handle form submission
    $('.module-config-form').on('submit', function(e) {
        e.preventDefault();
        const moduleId = $(this).data('module-id');
        saveModuleConfiguration(moduleId, false);
    });

    // Save module configuration
    function saveModuleConfiguration(moduleId, isAutoSave) {
        const form = $(`.module-config-form[data-module-id="${moduleId}"]`);
        const formData = new FormData(form[0]);
        
        // Fix checkbox values
        const streamingCheckbox = form.find('input[name="streaming_enabled"]');
        formData.delete('streaming_enabled');
        formData.append('streaming_enabled', streamingCheckbox.is(':checked') ? '1' : '0');
        
        // Prepare URL
        const url = pageData.routes.update.replace(':id', moduleId);
        
        // Convert FormData to object for AJAX
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        $.ajax({
            url: url,
            type: 'PUT',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Clear unsaved state
                    delete unsavedChanges[moduleId];
                    
                    // Update UI
                    const card = form.closest('.card');
                    card.find('.card-header').removeClass('bg-warning-subtle');
                    
                    // Show success message (only for manual save, not auto-save)
                    if (!isAutoSave) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: pageData.translations.saveSuccess,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        // Show subtle notification for auto-save
                        showAutoSaveNotification(moduleId);
                    }
                }
            },
            error: function(xhr) {
                // Only show error for manual save
                if (!isAutoSave) {
                    let errorMessage = pageData.translations.saveFailed;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            }
        });
    }

    // Show auto-save notification
    function showAutoSaveNotification(moduleId) {
        const card = $(`.module-config-form[data-module-id="${moduleId}"]`).closest('.card');
        const badge = $('<span class="badge bg-success position-absolute" style="top: 10px; right: 10px;">Saved</span>');
        card.find('.card-header').append(badge);
        
        setTimeout(function() {
            badge.fadeOut(function() {
                badge.remove();
            });
        }, 2000);
    }

    // Handle module status toggle
    $('.module-status-toggle').on('change', function() {
        const moduleId = $(this).data('module-id');
        const isActive = $(this).is(':checked');
        const toggle = $(this);
        
        // Prepare URL
        const url = pageData.routes.toggleStatus.replace(':id', moduleId);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                is_active: isActive ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    // Update card appearance
                    const card = toggle.closest('.card');
                    if (response.is_active) {
                        card.removeClass('opacity-50');
                        card.find('input, select, button').not('.module-status-toggle').prop('disabled', false);
                    } else {
                        card.addClass('opacity-50');
                        card.find('input, select, button').not('.module-status-toggle').prop('disabled', true);
                    }
                    
                    // Show notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || pageData.translations.statusToggleSuccess,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            },
            error: function() {
                // Revert toggle on error
                toggle.prop('checked', !isActive);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: pageData.translations.statusToggleFailed
                });
            }
        });
    });

    // Sync modules function
    window.syncModules = function() {
        Swal.fire({
            title: 'Syncing Modules',
            text: 'Detecting AI-enabled modules in your system...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: pageData.routes.sync,
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || pageData.translations.syncSuccess,
                        showConfirmButton: true
                    }).then((result) => {
                        if (response.synced && response.synced.length > 0) {
                            // Reload page if new modules were synced
                            location.reload();
                        }
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = pageData.translations.syncFailed;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    };

    // Advanced settings toggle
    $('.toggle-advanced-settings').on('click', function() {
        const card = $(this).closest('.card');
        const advancedSection = card.find('.advanced-settings');
        const icon = $(this).find('i');
        
        advancedSection.slideToggle();
        icon.toggleClass('bx-chevron-down bx-chevron-up');
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Warn before leaving if there are unsaved changes
    $(window).on('beforeunload', function() {
        if (Object.keys(unsavedChanges).length > 0) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    // Initialize disabled state for inactive modules
    $('.module-status-toggle').each(function() {
        const isActive = $(this).is(':checked');
        const card = $(this).closest('.card');
        
        if (!isActive) {
            card.addClass('opacity-50');
            card.find('input, select, button').not('.module-status-toggle').prop('disabled', true);
        }
    });

    // Quick actions for bulk operations
    $('#selectAllProviders').on('change', function() {
        const providerId = $(this).val();
        if (providerId) {
            $('.provider-select').val(providerId).trigger('change');
        }
    });

    $('#selectAllModels').on('change', function() {
        const modelId = $(this).val();
        if (modelId) {
            $('.model-select').val(modelId).trigger('change');
        }
    });

    // Search/filter modules
    $('#moduleSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.col-lg-6').each(function() {
            const card = $(this);
            const moduleName = card.find('h5').text().toLowerCase();
            const moduleDescription = card.find('.text-muted').text().toLowerCase();
            
            if (moduleName.includes(searchTerm) || moduleDescription.includes(searchTerm)) {
                card.show();
            } else {
                card.hide();
            }
        });
    });
});