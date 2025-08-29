/**
 * HRCore Employee Management
 */

'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize components
    const employeeTable = initializeDataTable();
    initializeSelect2();
    bindFilterEvents();
    bindActionEvents();
});

/**
 * Initialize DataTable with server-side processing
 */
function initializeDataTable() {
    return $('#employeesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function (d) {
                d.roleFilter = $('#roleFilter').val();
                d.teamFilter = $('#teamFilter').val();
                d.designationFilter = $('#designationFilter').val();
                d.statusFilter = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { 
                data: 'user', 
                name: 'first_name',
                searchable: true
            },
            { data: 'role', name: 'role', orderable: false, searchable: false },
            { data: 'team', name: 'team', orderable: false, searchable: false },
            { data: 'designation', name: 'designation', orderable: false, searchable: false },
            { data: 'attendance_type', name: 'attendance_type' },
            { 
                data: 'status', 
                name: 'status'
            },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        dom: '<"row"<"col-md-2"<"ms-n2"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: pageData.permissions.create ? [
            {
                text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">' + pageData.labels.addEmployee + '</span>',
                className: 'btn btn-primary mx-4',
                action: function () {
                    window.location.href = pageData.urls.create;
                }
            }
        ] : [],
        language: {
            search: pageData.labels.search,
            processing: pageData.labels.processing,
            lengthMenu: pageData.labels.lengthMenu,
            info: pageData.labels.info,
            infoEmpty: pageData.labels.infoEmpty,
            emptyTable: pageData.labels.emptyTable,
            paginate: pageData.labels.paginate
        }
    });
}

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
    $('.select2').select2({
        width: '100%',
        allowClear: true
    });
}

/**
 * Bind filter events
 */
function bindFilterEvents() {
    // Auto-reload on filter change
    $('#roleFilter, #teamFilter, #designationFilter, #statusFilter').on('change', function() {
        $('#employeesTable').DataTable().ajax.reload();
    });
}

/**
 * Bind action button events
 */
function bindActionEvents() {
    // Edit employee
    $(document).on('click', '.edit-employee', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        editEmployee(id);
    });

    // Delete employee
    $(document).on('click', '.delete-employee', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        deleteEmployee(id);
    });

}

/**
 * Edit employee - redirect to edit page
 */
window.editEmployee = function(id) {
    const url = pageData.urls.edit.replace(':id', id);
    window.location.href = url;
}


/**
 * Delete employee
 */
window.deleteEmployee = function(id) {
    Swal.fire({
        title: pageData.labels.confirmDelete,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.delete,
        cancelButtonText: pageData.labels.cancel || 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const url = pageData.urls.destroy.replace(':id', id);
            
            $.ajax({
                url: url,
                method: 'DELETE',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.deleteSuccess,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        
                        // Reload table
                        $('#employeesTable').DataTable().ajax.reload();
                    }
                },
                error: function(xhr) {
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
        const errors = xhr.responseJSON.errors || xhr.responseJSON.data;
        let errorMessage = '';
        
        if (typeof errors === 'object') {
            Object.keys(errors).forEach(field => {
                if (Array.isArray(errors[field])) {
                    errorMessage += errors[field][0] + '<br>';
                } else {
                    errorMessage += errors[field] + '<br>';
                }
            });
        } else {
            errorMessage = errors;
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errorMessage
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: xhr.responseJSON?.message || xhr.responseJSON?.data || pageData.labels.error
        });
    }
}