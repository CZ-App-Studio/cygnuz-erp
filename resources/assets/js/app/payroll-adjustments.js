$(function() {
    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Initialize Select2 for employee selection
    $('#user_id').select2({
        dropdownParent: $('#payrollAdjustmentOffcanvas'),
        placeholder: pageData.labels.selectEmployee,
        allowClear: true,
        data: pageData.employees.map(emp => ({
            id: emp.id,
            text: `${emp.first_name} ${emp.last_name} (${emp.email})`
        }))
    });

    // Show/hide employee field based on applicability
    $('#applicability').on('change', function() {
        const applicability = $(this).val();
        const employeeField = $('#employeeField');
        const userIdField = $('#user_id');
        
        if (applicability === 'employee') {
            employeeField.show();
            userIdField.attr('required', true);
        } else {
            employeeField.hide();
            userIdField.attr('required', false);
            userIdField.val('').trigger('change');
        }
    });

    // PayrollAdjustment DataTable
    const table = $('.datatables-payroll-adjustments').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function(d) {
                d.type = $('#typeFilter').val();
                d.applicability = $('#applicabilityFilter').val();
                d.user_id = $('#userFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'code', name: 'code' },
            { data: 'type_formatted', name: 'type' },
            { data: 'applicability_formatted', name: 'applicability' },
            { data: 'user', name: 'user.first_name', orderable: false },
            { data: 'amount_formatted', name: 'amount' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        dom: '<"card-header d-flex align-items-center"<"me-auto"l><"dt-action-buttons text-xl-end text-lg-start text-md-end text-start"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            search: '',
            searchPlaceholder: pageData.labels.payrollAdjustments
        }
    });

    // Filter functionality
    $('#applyFilters').on('click', function() {
        table.ajax.reload();
    });

    $('#clearFilters').on('click', function() {
        $('#typeFilter, #applicabilityFilter, #userFilter').val('');
        table.ajax.reload();
    });

    // Form submission
    $('#payrollAdjustmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        const btnText = submitBtn.find('.btn-text');
        const formData = new FormData(this);
        const adjustmentId = $('#payrollAdjustmentId').val();
        
        // Show loading state
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        // Clear previous validation errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        
        const url = adjustmentId ? pageData.urls.update.replace(':id', adjustmentId) : pageData.urls.store;
        const method = adjustmentId ? 'PUT' : 'POST';
        
        if (adjustmentId) {
            formData.append('_method', 'PUT');
        }
        
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    // Hide offcanvas
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('payrollAdjustmentOffcanvas'));
                    offcanvas.hide();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: adjustmentId ? pageData.labels.updateSuccess : pageData.labels.createSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Reload table
                    table.ajax.reload();
                    
                    // Reset form
                    form[0].reset();
                    $('#payrollAdjustmentId').val('');
                    $('#applicability').trigger('change'); // Hide employee field
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                
                if (xhr.status === 422 && response.data) {
                    // Handle validation errors
                    if (typeof response.data === 'object') {
                        Object.keys(response.data).forEach(function(field) {
                            const input = form.find(`[name="${field}"]`);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(response.data[field][0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response?.data || pageData.labels.error
                    });
                }
            },
            complete: function() {
                // Hide loading state
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Reset form when offcanvas is hidden
    $('#payrollAdjustmentOffcanvas').on('hidden.bs.offcanvas', function() {
        $('#payrollAdjustmentForm')[0].reset();
        $('#payrollAdjustmentId').val('');
        $('#payrollAdjustmentOffcanvasLabel').text(pageData.labels.addPayrollAdjustment);
        $('#payrollAdjustmentForm').find('.is-invalid').removeClass('is-invalid');
        $('#payrollAdjustmentForm').find('.invalid-feedback').text('');
        $('#applicability').trigger('change'); // Hide employee field
        $('#user_id').val('').trigger('change'); // Clear select2
    });
});

// Global functions for DataTable actions
window.showAddPayrollAdjustmentOffcanvas = function() {
    $('#payrollAdjustmentOffcanvasLabel').text(pageData.labels.addPayrollAdjustment);
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('payrollAdjustmentOffcanvas'));
    offcanvas.show();
};

window.editPayrollAdjustment = function(id) {
    $.ajax({
        url: pageData.urls.show.replace(':id', id),
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const adjustment = response.data;
                
                $('#payrollAdjustmentId').val(adjustment.id);
                $('#name').val(adjustment.name);
                $('#code').val(adjustment.code);
                $('#type').val(adjustment.type);
                $('#applicability').val(adjustment.applicability);
                $('#amount').val(adjustment.amount);
                $('#percentage').val(adjustment.percentage);
                $('#notes').val(adjustment.notes);
                
                // Trigger applicability change to show/hide employee field
                $('#applicability').trigger('change');
                
                // Set employee if applicable
                if (adjustment.user_id) {
                    $('#user_id').val(adjustment.user_id).trigger('change');
                }
                
                $('#payrollAdjustmentOffcanvasLabel').text(pageData.labels.editPayrollAdjustment);
                const offcanvas = new bootstrap.Offcanvas(document.getElementById('payrollAdjustmentOffcanvas'));
                offcanvas.show();
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: pageData.labels.error
            });
        }
    });
};

window.deletePayrollAdjustment = function(id) {
    Swal.fire({
        title: pageData.labels.confirmDelete,
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.deletePayrollAdjustment,
        cancelButtonText: pageData.labels.cancel,
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-label-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.destroy.replace(':id', id),
                method: 'POST',
                data: {
                    _method: 'DELETE'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.deleteSuccess,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        $('.datatables-payroll-adjustments').DataTable().ajax.reload();
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response?.data || pageData.labels.error
                    });
                }
            });
        }
    });
};