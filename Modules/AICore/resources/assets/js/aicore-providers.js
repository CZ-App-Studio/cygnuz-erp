/**
 * AI Core Providers Management JavaScript
 * Handles provider listing, connection testing, and management functionality
 */

'use strict';

$(function () {
    console.log('AI Core Providers page loaded');
    
    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Check if pageData is defined
    if (typeof pageData === 'undefined') {
        console.error('pageData object is not defined.');
        return;
    }
    
    initializeProviders();
    setupEventListeners();
    initializeDataTable();
});

/**
 * Initialize providers functionality
 */
function initializeProviders() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
}

/**
 * Initialize DataTable
 */
function initializeDataTable() {
    const dtProviderTableElement = $('#providers-table');
    
    if (!dtProviderTableElement.length) {
        console.warn('Table #providers-table not found');
        return;
    }

    console.log('Initializing jQuery DataTable for providers table');
    try {
        const dtProviderTable = dtProviderTableElement.DataTable({
            responsive: true,
            pageLength: 25,
            order: [[5, 'desc']], // Sort by priority by default
            columnDefs: [
                { orderable: false, targets: [7] } // Actions column not sortable
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search Providers...',
                paginate: {
                    next: '<i class="bx bx-chevron-right bx-sm"></i>',
                    previous: '<i class="bx bx-chevron-left bx-sm"></i>'
                }
            },
            dom: '<"card-header d-flex border-top rounded-0 flex-wrap py-2"' +
                '<"me-5 ms-n2 pe-5"f>' +
                '<"d-flex justify-content-start justify-content-md-end align-items-baseline"<"dt-action-buttons d-flex align-items-start align-items-md-center justify-content-sm-center gap-3"lB>>' +
                '>t' +
                '<"row mx-2"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                '>',
            lengthMenu: [10, 25, 50, 100],
            buttons: [
                {
                    text: '<i class="bx bx-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">Add Provider</span>',
                    className: 'add-new btn btn-primary',
                    action: function() {
                        window.location.href = pageData.urls.create;
                    }
                }
            ]
        });
        
        console.log('DataTable initialized successfully');
    } catch (error) {
        console.error('Error initializing DataTable:', error);
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Connection test buttons
    $(document).on('click', '.test-connection', function() {
        const providerId = $(this).data('provider-id');
        testProviderConnection(providerId);
    });

    // Delete form confirmations
    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        
        const deleteConfirm = pageData?.translations?.deleteConfirm || 'Are you sure you want to delete this provider?';
        
        Swal.fire({
            title: 'Are you sure?',
            text: deleteConfirm,
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
                this.submit();
            }
        });
    });
}

/**
 * Test provider connection
 */
function testProviderConnection(providerId) {
    if (!pageData?.urls?.testConnection) {
        console.error('Test connection route not configured');
        return;
    }

    const button = $(`.test-connection[data-provider-id="${providerId}"]`);
    const originalContent = button.html();
    
    // Update button state
    button.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

    // Make API call
    const url = pageData.urls.testConnection.replace(':id', providerId);
    
    $.ajax({
        url: url,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data) {
            displayConnectionTestResults(data, providerId);
        },
        error: function(xhr, status, error) {
            console.error('Connection test failed:', error);
            displayConnectionTestResults({
                success: false,
                message: 'Connection test failed: ' + error
            }, providerId);
        },
        complete: function() {
            // Restore button state
            button.prop('disabled', false).html(originalContent);
        }
    });
}

/**
 * Display connection test results
 */
function displayConnectionTestResults(data, providerId) {
    const statusText = data.success ? 
        (pageData?.translations?.connectionSuccess || 'Connection successful') :
        (pageData?.translations?.connectionFailed || 'Connection failed');

    const icon = data.success ? 'success' : 'error';
    
    let html = `${statusText}<br><small>${data.message || ''}</small>`;
    
    if (data.success && data.response_time) {
        html += `<br><small>Response Time: ${data.response_time}ms</small>`;
    }

    Swal.fire({
        title: statusText,
        html: html,
        icon: icon,
        customClass: {
            confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
    });
}