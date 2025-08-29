/**
 * AI Core Model Details JavaScript
 * Handles model detail page functionality
 */

'use strict';

$(function () {
    // Initialize components
    initializeModelDetails();
    setupEventListeners();
    setupDeleteConfirmation();
    setupTestConnection();
});

/**
 * Initialize model details functionality
 */
function initializeModelDetails() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize perfect scrollbar if needed
    if ($('.perfect-scrollbar').length) {
        new PerfectScrollbar('.perfect-scrollbar');
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Toggle model status
    $(document).on('click', '.toggle-status', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const modelId = $btn.data('model-id');
        const currentStatus = $btn.data('current-status');
        
        $.ajax({
            url: `/aicore/models/${modelId}/toggle-status`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update model status',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update model status',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            }
        });
    });
}

/**
 * Setup delete confirmation
 */
function setupDeleteConfirmation() {
    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        const form = this;
        
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will permanently delete the AI model. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
}

/**
 * Setup test connection button
 */
function setupTestConnection() {
    $(document).on('click', '.test-model-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const modelId = $btn.data('model-id');
        const originalText = $btn.html();
        
        // Show loading state
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Testing...');
        
        $.ajax({
            url: `/aicore/models/${modelId}/test`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Model test completed successfully',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Test Failed',
                        text: response.message || 'Model test failed',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to test model';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            },
            complete: function() {
                // Restore button state
                $btn.prop('disabled', false);
                $btn.html(originalText);
            }
        });
    });
}