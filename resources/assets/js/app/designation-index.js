/**
 * Designation Management
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
    const designationTable = initializeDataTable();
    initializeFormHandlers();
    initializeStatusToggle();
    loadDepartments();
});

/**
 * Initialize DataTable with server-side processing
 */
function initializeDataTable() {
    const dt_table = $('.datatables-designations');

    if (!dt_table.length) return;

    return dt_table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            error: function (xhr, error, code) {
                console.error('DataTable Error:', error);
                showErrorMessage(pageData.labels.error);
            }
        },
        columns: [
            { data: null },
            { data: 'id' },
            { data: 'name' },
            { data: 'code' },
            { data: 'department' },
            { data: 'notes' },
            { data: 'status' },
            { data: 'actions' }
        ],
        columnDefs: [
            {
                // For Responsive
                className: 'control',
                searchable: false,
                orderable: false,
                responsivePriority: 2,
                targets: 0,
                render: function (data, type, full, meta) {
                    return '';
                }
            },
            {
                targets: 5,
                render: function (data, type, full, meta) {
                    return data || 'N/A';
                }
            }
        ],
        order: [[1, 'desc']],
        dom: '<"row"<"col-md-2"<"ms-n2"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: pageData.permissions.create ? [
            {
                text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">' + pageData.labels.addDesignation + '</span>',
                className: 'btn btn-primary mx-4',
                action: function () {
                    resetForm();
                    $('#offcanvasDesignationLabel').html(pageData.labels.addDesignation);
                    $('.data-submit').html(pageData.labels.create);
                    $('#offcanvasAddOrUpdateDesignation').offcanvas('show');
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
        },
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'Details of ' + data['name'];
                    }
                }),
                type: 'column',
                renderer: function (api, rowIdx, columns) {
                    var data = $.map(columns, function (col, i) {
                        return col.title !== ''
                            ? '<tr data-dt-row="' +
                                col.rowIndex +
                                '" data-dt-column="' +
                                col.columnIndex +
                                '">' +
                                '<td>' +
                                col.title +
                                ':' +
                                '</td> ' +
                                '<td>' +
                                col.data +
                                '</td>' +
                                '</tr>'
                            : '';
                    }).join('');

                    return data ? $('<table class="table"/><tbody />').append(data) : false;
                }
            }
        }
    });
}

/**
 * Initialize form handlers
 */
function initializeFormHandlers() {
    const $form = $('#designationForm');
    const $offcanvas = $('#offcanvasAddOrUpdateDesignation');

    // Form submission
    $form.on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const designationId = formData.get('id');

        // Validate code uniqueness
        const code = formData.get('code');
        if (code) {
            $.ajax({
                url: pageData.urls.checkCode,
                type: 'GET',
                data: { code: code, id: designationId },
                success: function (response) {
                    if (response.valid) {
                        submitDesignationForm(formData, designationId);
                    } else {
                        showErrorMessage(pageData.labels.codeTaken);
                        $('#code').addClass('is-invalid');
                    }
                },
                error: function () {
                    submitDesignationForm(formData, designationId);
                }
            });
        } else {
            submitDesignationForm(formData, designationId);
        }
    });

    // Clear form on offcanvas hide
    $offcanvas.on('hidden.bs.offcanvas', function () {
        resetForm();
    });
}

/**
 * Submit designation form
 */
function submitDesignationForm(formData, designationId) {
    const url = designationId
        ? pageData.urls.update.replace(':id', designationId)
        : pageData.urls.store;

    if (designationId) {
        formData.append('_method', 'PUT');
    }

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.status === 'success') {
                $('#offcanvasAddOrUpdateDesignation').offcanvas('hide');

                Swal.fire({
                    icon: 'success',
                    title: designationId ? pageData.labels.updateSuccess : pageData.labels.createSuccess,
                    text: response.data.message,
                    showConfirmButton: false,
                    timer: 1500
                });

                $('.datatables-designations').DataTable().ajax.reload();
            }
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
}

/**
 * Edit designation
 */
window.editDesignation = function(id) {
    if (!pageData.permissions.edit) {
        showErrorMessage('You do not have permission to edit designations');
        return;
    }
    
    const url = pageData.urls.show.replace(':id', id);

    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const designation = response.data;

                // Update form title and button
                $('#offcanvasDesignationLabel').html(pageData.labels.editDesignation);
                $('.data-submit').html(pageData.labels.update);

                // Populate form fields
                $('#id').val(designation.id);
                $('#name').val(designation.name);
                $('#code').val(designation.code);
                $('#department_id').val(designation.department_id).trigger('change');
                $('#notes').val(designation.notes);

                // Show offcanvas
                $('#offcanvasAddOrUpdateDesignation').offcanvas('show');
            }
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
}

/**
 * Delete designation
 */
window.deleteDesignation = function(id) {
    if (!pageData.permissions.delete) {
        showErrorMessage('You do not have permission to delete designations');
        return;
    }
    
    Swal.fire({
        title: pageData.labels.confirmDelete,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.delete || 'Delete',
        cancelButtonText: pageData.labels.cancel,
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
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.deleteSuccess,
                            text: response.data.message,
                            showConfirmButton: false,
                            timer: 1500
                        });

                        $('.datatables-designations').DataTable().ajax.reload();
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
 * Toggle designation status
 */
window.toggleStatus = function(id) {
    if (!pageData.permissions.edit) {
        showErrorMessage('You do not have permission to change designation status');
        return;
    }
    
    const url = pageData.urls.toggleStatus.replace(':id', id);

    $.ajax({
        url: url,
        method: 'POST',
        success: function (response) {
            if (response.status === 'success') {
                showSuccessMessage(pageData.labels.statusChanged);
                $('.datatables-designations').DataTable().ajax.reload();
            }
        },
        error: function (xhr) {
            handleAjaxError(xhr);
            $('.datatables-designations').DataTable().ajax.reload();
        }
    });
}

/**
 * Initialize status toggle handlers
 */
function initializeStatusToggle() {
    $(document).on('change', '.status-toggle', function () {
        const id = $(this).data('id');
        toggleStatus(id);
    });
}

/**
 * Load departments for dropdown
 */
function loadDepartments() {
    if (pageData.urls.departmentList) {
        $.ajax({
            url: pageData.urls.departmentList,
            method: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    const $select = $('#department_id');
                    $select.empty();
                    $select.append('<option value="">' + pageData.labels.selectDepartment + '</option>');

                    const departments = response.data || [];
                    departments.forEach(function (department) {
                        $select.append(`<option value="${department.id}">${department.name}</option>`);
                    });

                    // Initialize Select2
                    $select.select2({
                        placeholder: pageData.labels.selectDepartment,
                        allowClear: true,
                        dropdownParent: $('#offcanvasAddOrUpdateDesignation')
                    });
                }
            }
        });
    }
}

/**
 * Open add designation form
 */
window.openAddForm = function() {
    if (!pageData.permissions.create) {
        showErrorMessage('You do not have permission to create designations');
        return;
    }
    
    resetForm();
    $('#offcanvasDesignationLabel').html(pageData.labels.addDesignation);
    $('.data-submit').html(pageData.labels.create);
    $('#offcanvasAddOrUpdateDesignation').offcanvas('show');
}

/**
 * Reset form to initial state
 */
function resetForm() {
    $('#designationForm')[0].reset();
    $('#id').val('');
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    $('#offcanvasDesignationLabel').html(pageData.labels.addDesignation);
    $('.data-submit').html(pageData.labels.create);
    $('#department_id').val('').trigger('change');
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
                    // Add invalid class to field
                    $(`#${field}`).addClass('is-invalid');
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

/**
 * Show success message
 */
function showSuccessMessage(message) {
    Swal.fire({
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 1500
    });
}

/**
 * Show error message
 */
function showErrorMessage(message) {
    Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        text: message
    });
}
