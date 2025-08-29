$(function () {
    'use strict';

    // Variables
    let dt_regularization;
    let isEditMode = false;
    let currentRegularizationId = null;

    // CSRF Token Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize DataTable
    if ($('.datatables-regularization').length) {
        dt_regularization = $('.datatables-regularization').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: pageData.routes.datatable,
                data: function (d) {
                    d.status = $('#status-filter').val();
                    d.type = $('#type-filter').val();
                    d.date_from = $('#date-from').val();
                    d.date_to = $('#date-to').val();
                    d.user_id = $('#user-filter').val();
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'user', name: 'user.name', searchable: true },
                { data: 'date', name: 'date' },
                { data: 'type', name: 'type', orderable: false },
                { data: 'status', name: 'status', orderable: false },
                { data: 'requested_times', name: 'requested_times', orderable: false, searchable: false },
                { data: 'approved_by', name: 'approvedBy.name', searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                paginate: {
                    previous: '<i class="bx bx-chevron-left"></i>',
                    next: '<i class="bx bx-chevron-right"></i>'
                }
            }
        });
    }

    // Initialize date pickers
    if ($('#date-from').length) {
        $('#date-from').flatpickr({
            dateFormat: 'Y-m-d',
            maxDate: 'today'
        });
    }

    if ($('#date-to').length) {
        $('#date-to').flatpickr({
            dateFormat: 'Y-m-d',
            maxDate: 'today'
        });
    }

    // Initialize user filter dropdown
    if (pageData.permissions.canView && $('#user-filter').length) {
        $('#user-filter').select2({
            placeholder: pageData.labels.allEmployees || 'All Employees',
            allowClear: true,
            ajax: {
                url: pageData.routes.userSearch || '/hrcore/employees/search',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    if (!data || !data.data) {
                        return { results: [], pagination: { more: false } };
                    }
                    
                    const results = data.data.map(function(user) {
                        return {
                            id: user.id,
                            text: user.name + (user.code ? ' (' + user.code + ')' : '')
                        };
                    });
                    
                    return {
                        results: results,
                        pagination: {
                            more: data.has_more || false
                        }
                    };
                },
                cache: true
            }
        });
    }

    // Filter change handlers
    $('#status-filter, #type-filter, #date-from, #date-to, #user-filter').on('change', function () {
        dt_regularization.ajax.reload();
    });

    // Form submission handler
    $('#regularizationForm').on('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitForm(this);
        }
    });

    // Manual form validation
    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Validate date
        const date = $('#date').val();
        if (!date) {
            showFieldError('#date', pageData.labels.required || 'This field is required');
            isValid = false;
        }
        
        // Validate type
        const type = $('#type').val();
        if (!type) {
            showFieldError('#type', pageData.labels.required || 'This field is required');
            isValid = false;
        }
        
        // Validate reason
        const reason = $('#reason').val();
        if (!reason) {
            showFieldError('#reason', pageData.labels.required || 'This field is required');
            isValid = false;
        } else if (reason.length > 1000) {
            showFieldError('#reason', 'Maximum 1000 characters allowed');
            isValid = false;
        }
        
        // Validate time comparison if both times are provided
        const checkinTime = $('#requested_check_in_time').val();
        const checkoutTime = $('#requested_check_out_time').val();
        if (checkinTime && checkoutTime && checkoutTime <= checkinTime) {
            showFieldError('#requested_check_out_time', 'Check-out time must be after check-in time');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showFieldError(fieldSelector, message) {
        const field = $(fieldSelector);
        field.addClass('is-invalid');
        field.after(`<div class="invalid-feedback">${message}</div>`);
    }

    // Form submission
    function submitForm(form) {
        const formData = new FormData(form);
        const url = isEditMode 
            ? pageData.routes.update.replace(':id', currentRegularizationId)
            : pageData.routes.store;
        const method = isEditMode ? 'PUT' : 'POST';

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        $('#submitBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> ' + 
            (isEditMode ? 'Updating...' : 'Submitting...'));

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    $('#regularizationOffcanvas').offcanvas('hide');
                    dt_regularization.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    resetForm();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data || pageData.labels.error
                    });
                }
            },
            error: function (xhr) {
                let errorMessage = pageData.labels.error;
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorList = '';
                    $.each(errors, function (key, value) {
                        errorList += value[0] + '<br>';
                    });
                    errorMessage = errorList;
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            },
            complete: function () {
                $('#submitBtn').prop('disabled', false).html(isEditMode ? 'Update Request' : 'Submit Request');
            }
        });
    }

    // Approval form submission
    $('#approvalForm').on('submit', function (e) {
        e.preventDefault();
        
        const isApproval = $('#approvalBtn').hasClass('btn-success');
        const url = isApproval 
            ? pageData.routes.approve.replace(':id', currentRegularizationId)
            : pageData.routes.reject.replace(':id', currentRegularizationId);
        
        $('#approvalBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Processing...');

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.status === 'success') {
                    $('#approvalModal').modal('hide');
                    dt_regularization.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data || pageData.labels.error
                    });
                }
            },
            error: function (xhr) {
                let errorMessage = pageData.labels.error;
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            },
            complete: function () {
                const btnText = $('#approvalBtn').hasClass('btn-success') ? 'Approve' : 'Reject';
                $('#approvalBtn').prop('disabled', false).html(btnText);
            }
        });
    });

    // Reset form
    function resetForm() {
        $('#regularizationForm')[0].reset();
        $('#regularizationForm').find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $('#regularizationForm').find('.invalid-feedback, .valid-feedback').remove();
        isEditMode = false;
        currentRegularizationId = null;
        $('#regularizationOffcanvasLabel').text('Attendance Regularization Request');
        $('#submitBtn').html('Submit Request');
    }

    // Offcanvas close handler
    $('#regularizationOffcanvas').on('hidden.bs.offcanvas', function () {
        resetForm();
    });

    // Initialize flatpickr for offcanvas date field
    function initializeOffcanvasDatePicker() {
        if ($('#date').length && !$('#date').hasClass('flatpickr-input')) {
            $('#date').flatpickr({
                dateFormat: 'Y-m-d',
                maxDate: 'today',
                allowInput: true
            });
        }
        // Time fields now use HTML5 type="time" for better browser support
    }

    // Global functions (called from DataTable actions)
    window.openCreateOffcanvas = function () {
        resetForm();
        $('#regularizationOffcanvas').offcanvas('show');
        // Initialize date picker after offcanvas is shown
        setTimeout(function() {
            initializeOffcanvasDatePicker();
        }, 100);
    };

    window.viewRegularization = function (id) {
        $.ajax({
            url: pageData.routes.show.replace(':id', id),
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    const data = response.data;
                    let html = `
                        <div class="row g-3">
                            <div class="col-12">
                                <strong>Employee:</strong><br>
                                ${data.user.name} (${data.user.code || 'N/A'})
                            </div>
                            <div class="col-12">
                                <strong>Date:</strong><br>
                                ${new Date(data.regularization.date).toLocaleDateString()}
                            </div>
                            <div class="col-12">
                                <strong>Type:</strong><br>
                                <span class="badge bg-label-info">${getTypeLabel(data.regularization.type)}</span>
                            </div>
                            <div class="col-12">
                                <strong>Status:</strong><br>
                                <span class="badge ${getStatusBadgeClass(data.regularization.status)}">${getStatusLabel(data.regularization.status)}</span>
                            </div>
                            <div class="col-12">
                                <strong>Requested Check-in:</strong><br>
                                ${data.regularization.requested_check_in_time || 'N/A'}
                            </div>
                            <div class="col-12">
                                <strong>Requested Check-out:</strong><br>
                                ${data.regularization.requested_check_out_time || 'N/A'}
                            </div>
                            <div class="col-12">
                                <strong>Actual Check-in:</strong><br>
                                ${data.regularization.actual_check_in_time || 'N/A'}
                            </div>
                            <div class="col-12">
                                <strong>Actual Check-out:</strong><br>
                                ${data.regularization.actual_check_out_time || 'N/A'}
                            </div>
                            <div class="col-12">
                                <strong>Reason:</strong><br>
                                <p class="mb-0">${data.regularization.reason}</p>
                            </div>`;
                    
                    if (data.regularization.manager_comments) {
                        html += `
                            <div class="col-12">
                                <strong>Manager Comments:</strong><br>
                                <p class="mb-0">${data.regularization.manager_comments}</p>
                            </div>`;
                    }

                    if (data.approved_by) {
                        html += `
                            <div class="col-12">
                                <strong>Approved/Rejected By:</strong><br>
                                ${data.approved_by.name}
                            </div>
                            <div class="col-12">
                                <strong>Action Date:</strong><br>
                                ${new Date(data.regularization.approved_at).toLocaleString()}
                            </div>`;
                    }

                    if (data.regularization.attachments && data.regularization.attachments.length > 0) {
                        html += `
                            <div class="col-12">
                                <strong>Attachments:</strong><br>`;
                        data.regularization.attachments.forEach(function(attachment) {
                            html += `<a href="/storage/${attachment.path}" target="_blank" class="badge bg-label-primary me-1">${attachment.name}</a>`;
                        });
                        html += `</div>`;
                    }

                    html += `</div>`;
                    
                    $('#viewContent').html(html);
                    $('#viewOffcanvas').offcanvas('show');
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: pageData.labels.error
                });
            }
        });
    };

    window.editRegularization = function (id) {
        $.ajax({
            url: pageData.routes.edit.replace(':id', id),
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    const data = response.data;
                    isEditMode = true;
                    currentRegularizationId = id;
                    
                    // Format date properly for HTML date input (YYYY-MM-DD)
                    const dateValue = data.date ? new Date(data.date).toISOString().split('T')[0] : '';
                    $('#date').val(dateValue);
                    $('#type').val(data.type);
                    $('#requested_check_in_time').val(data.requested_check_in_time);
                    $('#requested_check_out_time').val(data.requested_check_out_time);
                    $('#reason').val(data.reason);
                    
                    $('#regularizationOffcanvasLabel').text('Edit Regularization Request');
                    $('#submitBtn').html('Update Request');
                    $('#regularizationOffcanvas').offcanvas('show');
                    // Initialize date picker after offcanvas is shown
                    setTimeout(function() {
                        initializeOffcanvasDatePicker();
                    }, 100);
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: pageData.labels.error
                });
            }
        });
    };

    window.approveRegularization = function (id) {
        currentRegularizationId = id;
        $('#approvalModalTitle').text('Approve Request');
        $('#approvalBtn').removeClass('btn-danger').addClass('btn-success').text('Approve');
        $('#manager_comments').prop('required', false);
        $('#approvalModal').modal('show');
    };

    window.rejectRegularization = function (id) {
        currentRegularizationId = id;
        $('#approvalModalTitle').text('Reject Request');
        $('#approvalBtn').removeClass('btn-success').addClass('btn-danger').text('Reject');
        $('#manager_comments').prop('required', true);
        $('#approvalModal').modal('show');
    };

    window.deleteRegularization = function (id) {
        Swal.fire({
            title: 'Are you sure?',
            text: pageData.labels.confirmDelete,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.routes.destroy.replace(':id', id),
                    type: 'DELETE',
                    success: function (response) {
                        if (response.status === 'success') {
                            dt_regularization.ajax.reload();
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: pageData.labels.error
                        });
                    }
                });
            }
        });
    };

    window.clearFilters = function () {
        $('#status-filter, #type-filter').val('');
        $('#date-from, #date-to').val('');
        if ($('#user-filter').length) {
            $('#user-filter').val(null).trigger('change');
        }
        dt_regularization.ajax.reload();
    };

    // Helper functions
    function getTypeLabel(type) {
        const types = {
            'missing_checkin': 'Missing Check-in',
            'missing_checkout': 'Missing Check-out',
            'wrong_time': 'Wrong Time',
            'forgot_punch': 'Forgot to Punch',
            'other': 'Other'
        };
        return types[type] || type;
    }

    function getStatusLabel(status) {
        const statuses = {
            'pending': 'Pending',
            'approved': 'Approved',
            'rejected': 'Rejected'
        };
        return statuses[status] || status;
    }

    function getStatusBadgeClass(status) {
        const classes = {
            'pending': 'bg-label-warning',
            'approved': 'bg-label-success',
            'rejected': 'bg-label-danger'
        };
        return classes[status] || 'bg-label-secondary';
    }
});