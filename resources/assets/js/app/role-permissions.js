/**
 * Role Permissions Management
 */

'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize
    updatePermissionCounts();
    updateModuleCheckboxes();
    bindEventHandlers();
});

/**
 * Bind event handlers
 */
function bindEventHandlers() {
    // Save permissions
    $('#savePermissions').on('click', function () {
        const btn = $(this);
        const originalText = btn.html();
        
        // Disable button and show loading
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + pageData.labels.saving);
        
        // Get form data
        const formData = $('#permissionsForm').serialize();
        
        $.ajax({
            url: pageData.urls.updatePermissions,
            method: 'PUT',
            data: formData,
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.saveSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response.data || pageData.labels.error
                    });
                }
            },
            error: function (xhr) {
                console.log('AJAX Error Details:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON
                });
                handleAjaxError(xhr);
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Select all permissions
    $('#selectAll').on('click', function () {
        $('.permission-checkbox').prop('checked', true);
        updatePermissionCounts();
        updateModuleCheckboxes();
    });

    // Deselect all permissions
    $('#deselectAll').on('click', function () {
        $('.permission-checkbox').prop('checked', false);
        updatePermissionCounts();
        updateModuleCheckboxes();
    });

    // Module checkbox change
    $('.module-checkbox').on('change', function () {
        const module = $(this).data('module');
        const isChecked = $(this).is(':checked');
        
        // Check/uncheck all permissions in this module
        $(`.permission-checkbox[data-module="${module}"]`).prop('checked', isChecked);
        
        updatePermissionCounts();
    });

    // Permission checkbox change
    $('.permission-checkbox').on('change', function () {
        updatePermissionCounts();
        updateModuleCheckboxes();
    });
}

/**
 * Update permission counts for each module
 */
function updatePermissionCounts() {
    $('.permission-count').each(function () {
        const module = $(this).data('module');
        const totalPermissions = $(`.permission-checkbox[data-module="${module}"]`).length;
        const checkedPermissions = $(`.permission-checkbox[data-module="${module}"]:checked`).length;
        
        $(this).find('.selected-count').text(checkedPermissions);
        
        // Update badge color based on selection
        if (checkedPermissions === 0) {
            $(this).removeClass('bg-primary bg-success').addClass('bg-secondary');
        } else if (checkedPermissions === totalPermissions) {
            $(this).removeClass('bg-primary bg-secondary').addClass('bg-success');
        } else {
            $(this).removeClass('bg-success bg-secondary').addClass('bg-primary');
        }
    });
}

/**
 * Update module checkboxes based on permission selections
 */
function updateModuleCheckboxes() {
    $('.module-checkbox').each(function () {
        const module = $(this).data('module');
        const totalPermissions = $(`.permission-checkbox[data-module="${module}"]`).length;
        const checkedPermissions = $(`.permission-checkbox[data-module="${module}"]:checked`).length;
        
        if (checkedPermissions === 0) {
            $(this).prop('checked', false).prop('indeterminate', false);
        } else if (checkedPermissions === totalPermissions) {
            $(this).prop('checked', true).prop('indeterminate', false);
        } else {
            $(this).prop('checked', false).prop('indeterminate', true);
        }
    });
}

/**
 * Handle AJAX errors
 */
function handleAjaxError(xhr) {
    if (xhr.status === 422) {
        // Validation errors
        const errors = xhr.responseJSON.errors;
        let errorMessage = '';
        
        Object.keys(errors).forEach(function (field) {
            errorMessage += errors[field][0] + '<br>';
        });
        
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errorMessage
        });
    } else if (xhr.status === 403) {
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            text: 'You do not have permission to perform this action.'
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: xhr.responseJSON?.message || pageData.labels.error
        });
    }
}