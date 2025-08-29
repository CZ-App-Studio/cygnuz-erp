/**
 * Permissions Management
 */

'use strict';

// DataTable instance
let dt;

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize components
    initDataTable();
    bindEventHandlers();
});

/**
 * Initialize DataTable
 */
function initDataTable() {
    dt = $('#permissionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function (d) {
                d.module = $('#moduleFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', width: '5%' },
            { data: 'name', name: 'name' },
            { data: 'module', name: 'module' },
            { data: 'description', name: 'description' },
            { data: 'roles', name: 'roles', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '10%' }
        ],
        order: [[2, 'asc'], [1, 'asc']], // Order by module, then name
        language: {
            search: pageData.labels.search,
            processing: pageData.labels.processing,
            lengthMenu: pageData.labels.lengthMenu,
            info: pageData.labels.info,
            infoEmpty: pageData.labels.infoEmpty,
            emptyTable: pageData.labels.emptyTable,
            paginate: pageData.labels.paginate
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function () {
            $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
        }
    });
}

/**
 * Bind event handlers
 */
function bindEventHandlers() {
    // Module filter change
    $('#moduleFilter').on('change', function () {
        dt.ajax.reload();
    });

    // Add permission form submit
    $('#addPermissionForm').on('submit', function (e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = form.serialize();
        const submitBtn = form.find('button[type="submit"]');
        
        // Disable submit button
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: pageData.urls.store,
            method: 'POST',
            data: formData,
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.createSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    form[0].reset();
                    $('#addPermissionOffcanvas').offcanvas('hide');
                    dt.ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response.data || pageData.labels.error
                    });
                }
            },
            error: function (xhr) {
                handleAjaxError(xhr);
            },
            complete: function () {
                submitBtn.prop('disabled', false);
            }
        });
    });

}

/**
 * Delete permission
 */
function deletePermission(id) {
    Swal.fire({
        title: pageData.labels.confirmDelete,
        text: pageData.labels.deleteWarning,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.delete,
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then(function (result) {
        if (result.isConfirmed) {
            const url = pageData.urls.destroy.replace(':id', id);
            
            $.ajax({
                url: url,
                method: 'DELETE',
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.deleteSuccess,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        dt.ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data || pageData.labels.error
                        });
                    }
                },
                error: function (xhr) {
                    handleAjaxError(xhr);
                }
            });
        }
    });
}

/**
 * Sync permissions for super admin
 */
function syncSuperAdmin() {
    Swal.fire({
        title: pageData.labels.confirmSync,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sync',
        customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.syncSuperAdmin,
                method: 'POST',
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.syncSuccess,
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
                    handleAjaxError(xhr);
                }
            });
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