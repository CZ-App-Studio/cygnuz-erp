/**
 * Roles Management
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
    dt = $('#rolesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description' },
            { data: 'users_count', name: 'users_count', searchable: false },
            { data: 'permissions_count', name: 'permissions_count', searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '15%' }
        ],
        order: [[0, 'asc']],
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
    // Add role form submit
    $('#addRoleForm').on('submit', function (e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = new FormData(this);
        const submitBtn = form.find('button[type="submit"]');
        
        // Disable submit button
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: pageData.urls.store,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.createSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    form[0].reset();
                    $('#addRoleOffcanvas').offcanvas('hide');
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

    // Edit role form submit
    $('#editRoleForm').on('submit', function (e) {
        e.preventDefault();
        
        const form = $(this);
        const roleId = $('#editRoleId').val();
        const url = pageData.urls.update.replace(':id', roleId);
        const formData = new FormData(this);
        const submitBtn = form.find('button[type="submit"]');
        
        // Add PUT method
        formData.append('_method', 'PUT');
        
        // Disable submit button
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.updateSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    $('#editRoleOffcanvas').offcanvas('hide');
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
 * Edit role
 */
window.editRole = function(id) {
    const url = pageData.urls.edit.replace(':id', id);
    
    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const role = response.data;
                
                // Populate form fields
                $('#editRoleId').val(role.id);
                $('#editName').val(role.name);
                $('#editDescription').val(role.description);
                
                // Show offcanvas
                const editOffcanvas = new bootstrap.Offcanvas(document.getElementById('editRoleOffcanvas'));
                editOffcanvas.show();
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

/**
 * Delete role
 */
window.deleteRole = function(id) {
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