$(function() {
    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Initialize Flatpickr for date inputs
    const flatpickrConfig = {
        dateFormat: 'Y-m-d',
        allowInput: true
    };
    
    const payPeriodStartPicker = flatpickr('#pay_period_start', flatpickrConfig);
    const payPeriodEndPicker = flatpickr('#pay_period_end', flatpickrConfig);
    const payDatePicker = flatpickr('#pay_date', flatpickrConfig);

    // PayrollCycle DataTable
    const table = $('.datatables-payroll-cycles').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.frequency = $('#frequencyFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'code', name: 'code' },
            { data: 'frequency_formatted', name: 'frequency' },
            { data: 'period', name: 'period', orderable: false },
            { data: 'pay_date_formatted', name: 'pay_date' },
            { data: 'status', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        dom: '<"card-header d-flex align-items-center"<"me-auto"l><"dt-action-buttons text-xl-end text-lg-start text-md-end text-start"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            search: '',
            searchPlaceholder: pageData.labels.payrollCycles
        }
    });

    // Filter functionality
    $('#applyFilters').on('click', function() {
        table.ajax.reload();
    });

    $('#clearFilters').on('click', function() {
        $('#statusFilter, #frequencyFilter').val('');
        table.ajax.reload();
    });

    // Form submission
    $('#payrollCycleForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        const btnText = submitBtn.find('.btn-text');
        const formData = new FormData(this);
        const cycleId = $('#payrollCycleId').val();
        
        // Show loading state
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        // Clear previous validation errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        
        const url = cycleId ? pageData.urls.update.replace(':id', cycleId) : pageData.urls.store;
        const method = cycleId ? 'PUT' : 'POST';
        
        if (cycleId) {
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
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('payrollCycleOffcanvas'));
                    offcanvas.hide();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.createSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Reload table
                    table.ajax.reload();
                    
                    // Reset form
                    form[0].reset();
                    $('#payrollCycleId').val('');
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
    $('#payrollCycleOffcanvas').on('hidden.bs.offcanvas', function() {
        $('#payrollCycleForm')[0].reset();
        $('#payrollCycleId').val('');
        $('#payrollCycleOffcanvasLabel').text(pageData.labels.addPayrollCycle);
        $('#payrollCycleForm').find('.is-invalid').removeClass('is-invalid');
        $('#payrollCycleForm').find('.invalid-feedback').text('');
    });
});

// Global functions for DataTable actions
window.showAddPayrollCycleOffcanvas = function() {
    $('#payrollCycleOffcanvasLabel').text(pageData.labels.addPayrollCycle);
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('payrollCycleOffcanvas'));
    offcanvas.show();
}

window.editPayrollCycle = function(id) {
    $.ajax({
        url: pageData.urls.show.replace(':id', id),
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const cycle = response.data;
                
                $('#payrollCycleId').val(cycle.id);
                $('#name').val(cycle.name);
                $('#code').val(cycle.code);
                $('#frequency').val(cycle.frequency);
                $('#pay_period_start').val(cycle.pay_period_start);
                $('#pay_period_end').val(cycle.pay_period_end);
                $('#pay_date').val(cycle.pay_date);
                
                $('#payrollCycleOffcanvasLabel').text(pageData.labels.editPayrollCycle);
                const offcanvas = new bootstrap.Offcanvas(document.getElementById('payrollCycleOffcanvas'));
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
}

window.processPayrollCycle = function(id) {
    Swal.fire({
        title: pageData.labels.process,
        text: 'Are you sure you want to process this payroll cycle?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: pageData.labels.process,
        cancelButtonText: pageData.labels.cancel
    }).then((result) => {
        if (result.isConfirmed) {
            updatePayrollCycleStatus(id, 'processed');
        }
    });
}

window.completePayrollCycle = function(id) {
    Swal.fire({
        title: pageData.labels.complete,
        text: 'Are you sure you want to complete this payroll cycle?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: pageData.labels.complete,
        cancelButtonText: pageData.labels.cancel
    }).then((result) => {
        if (result.isConfirmed) {
            updatePayrollCycleStatus(id, 'completed');
        }
    });
}

window.updatePayrollCycleStatus = function(id, status) {
    $.ajax({
        url: pageData.urls.updateStatus.replace(':id', id),
        method: 'POST',
        data: {
            status: status,
            _method: 'PUT'
        },
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.statusUpdated,
                    showConfirmButton: false,
                    timer: 1500
                });
                $('.datatables-payroll-cycles').DataTable().ajax.reload();
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

window.deletePayrollCycle = function(id) {
    Swal.fire({
        title: pageData.labels.confirmDelete,
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.deletePayrollCycle,
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
                        $('.datatables-payroll-cycles').DataTable().ajax.reload();
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
}