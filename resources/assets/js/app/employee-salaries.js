// Helper function to get initials from name
function getInitials(firstName, lastName) {
    const first = firstName ? firstName.charAt(0).toUpperCase() : '';
    const last = lastName ? lastName.charAt(0).toUpperCase() : '';
    return first + last || '?';
}

$(function() {
    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Handle salary type change to show/hide relevant fields
    $('#salary_type').on('change', function() {
        const salaryType = $(this).val();
        const baseSalaryField = $('#baseSalaryField');
        const hourlyRateField = $('#hourlyRateField');
        const baseSalaryInput = $('#base_salary');
        const hourlyRateInput = $('#hourly_rate');
        
        // Reset required attributes
        baseSalaryInput.removeAttr('required');
        hourlyRateInput.removeAttr('required');
        
        // Show/hide fields and set required attributes based on salary type
        switch (salaryType) {
            case 'hourly':
                baseSalaryField.hide();
                hourlyRateField.show();
                hourlyRateInput.attr('required', true);
                break;
            case 'monthly':
            case 'daily':
            case 'weekly':
            case 'commission':
            case 'contract':
                baseSalaryField.show();
                hourlyRateField.show();
                baseSalaryInput.attr('required', true);
                break;
            default:
                baseSalaryField.show();
                hourlyRateField.show();
                break;
        }
        
        // Update labels based on salary type
        const baseSalaryLabel = baseSalaryField.find('label');
        switch (salaryType) {
            case 'daily':
                baseSalaryLabel.html(pageData.labels.dailyRate + ' <span class="text-danger">*</span>');
                break;
            case 'weekly':
                baseSalaryLabel.html(pageData.labels.weeklyRate + ' <span class="text-danger">*</span>');
                break;
            case 'commission':
                baseSalaryLabel.html(pageData.labels.commissionRate + ' <span class="text-danger">*</span>');
                break;
            case 'contract':
                baseSalaryLabel.html(pageData.labels.contractAmount + ' <span class="text-danger">*</span>');
                break;
            default:
                const required = salaryType === 'monthly' ? ' <span class="text-danger">*</span>' : '';
                baseSalaryLabel.html(pageData.labels.baseSalary + required);
                break;
        }
    });

    // Employee Salaries DataTable
    const table = $('.datatables-employee-salaries').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function(d) {
                d.salary_type = $('#salaryTypeFilter').val();
                d.min_salary = $('#minSalaryFilter').val();
                d.max_salary = $('#maxSalaryFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'id', name: 'id' },
            { data: 'user', name: 'first_name' },
            { data: 'code', name: 'code' },
            { data: 'salary_type_formatted', name: 'salary_type' },
            { data: 'base_salary_formatted', name: 'base_salary' },
            { data: 'hourly_rate_formatted', name: 'hourly_rate' },
            { data: 'overtime_rate_formatted', name: 'overtime_rate' },
            { data: 'joining_date', name: 'date_of_joining' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        dom: '<"card-header d-flex align-items-center"<"me-auto"l><"dt-action-buttons text-xl-end text-lg-start text-md-end text-start"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            search: '',
            searchPlaceholder: pageData.labels.employeeSalaries
        }
    });

    // Filter functionality
    $('#applyFilters').on('click', function() {
        table.ajax.reload();
    });

    $('#clearFilters').on('click', function() {
        $('#salaryTypeFilter, #minSalaryFilter, #maxSalaryFilter').val('');
        table.ajax.reload();
    });

    // Load statistics
    window.loadStatistics();

    // Form submission
    $('#editEmployeeSalaryForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        const btnText = submitBtn.find('.btn-text');
        const formData = new FormData(this);
        const employeeId = $('#employeeId').val();
        
        // Show loading state
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        // Clear previous validation errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        
        formData.append('_method', 'PUT');
        
        $.ajax({
            url: pageData.urls.update.replace(':id', employeeId),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    // Hide offcanvas
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('editEmployeeSalaryOffcanvas'));
                    offcanvas.hide();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.updateSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Reload table and statistics
                    table.ajax.reload();
                    window.loadStatistics();
                    
                    // Reset form
                    form[0].reset();
                    $('#employeeId').val('');
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
    $('#editEmployeeSalaryOffcanvas').on('hidden.bs.offcanvas', function() {
        $('#editEmployeeSalaryForm')[0].reset();
        $('#employeeId').val('');
        $('#editEmployeeSalaryForm').find('.is-invalid').removeClass('is-invalid');
        $('#editEmployeeSalaryForm').find('.invalid-feedback').text('');
        $('#salary_type').trigger('change'); // Reset field visibility
    });
});

// Global functions for DataTable actions
window.editEmployeeSalary = function(id) {
    $.ajax({
        url: pageData.urls.show.replace(':id', id),
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const employee = response.data;
                
                $('#employeeId').val(employee.id);
                $('#employeeName').text(`${employee.first_name} ${employee.last_name}`);
                $('#employeeEmail').text(employee.email);
                $('#employeeCode').val(employee.code);
                $('#salary_type').val(employee.salary_type);
                $('#base_salary').val(employee.base_salary);
                $('#hourly_rate').val(employee.hourly_rate);
                $('#overtime_rate').val(employee.overtime_rate);
                
                // Handle avatar display
                const avatarContainer = $('#employeeAvatarContainer');
                avatarContainer.empty(); // Clear existing content
                
                if (employee.profile_picture) {
                    // User has a profile picture
                    avatarContainer.html(`<img src="${employee.profile_picture}" alt="Avatar" class="rounded-circle" />`);
                } else {
                    // Use initials as avatar
                    const initials = getInitials(employee.first_name, employee.last_name);
                    avatarContainer.html(`<span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>`);
                }
                
                // Trigger salary type change to show/hide fields
                $('#salary_type').trigger('change');
                
                const offcanvas = new bootstrap.Offcanvas(document.getElementById('editEmployeeSalaryOffcanvas'));
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

window.resetEmployeeSalary = function(id) {
    Swal.fire({
        title: pageData.labels.confirmReset,
        text: 'This will reset all salary information for this employee.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.resetEmployeeSalary,
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
                            title: pageData.labels.resetSuccess,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        $('.datatables-employee-salaries').DataTable().ajax.reload();
                        window.loadStatistics();
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

window.loadStatistics = function() {
    $.ajax({
        url: pageData.urls.statistics,
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const stats = response.data;
                $('#totalEmployees').text(stats.total_employees);
                $('#employeesWithSalary').text(stats.employees_with_salary);
                $('#averageSalary').text(stats.average_salary ? '$' + Number(stats.average_salary).toFixed(2) : '-');
                
                const minSalary = stats.min_salary ? '$' + Number(stats.min_salary).toFixed(2) : '-';
                const maxSalary = stats.max_salary ? '$' + Number(stats.max_salary).toFixed(2) : '-';
                $('#salaryRange').text(minSalary + ' - ' + maxSalary);
            }
        },
        error: function() {
            console.error('Failed to load statistics');
        }
    });
}

// Global function for viewing employee salary details
window.viewEmployeeSalary = function(id) {
    $.ajax({
        url: pageData.urls.show.replace(':id', id),
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const employee = response.data;
                
                // Personal Information
                $('#viewEmployeeName').text(`${employee.first_name} ${employee.last_name}`);
                $('#viewEmployeeEmail').text(employee.email);
                $('#viewEmployeeCode').text(employee.code);
                
                // Handle avatar display for view modal
                const viewAvatarContainer = $('#viewEmployeeAvatarContainer');
                if (viewAvatarContainer.length) {
                    viewAvatarContainer.empty();
                    if (employee.profile_picture) {
                        viewAvatarContainer.html(`<img src="${employee.profile_picture}" alt="Avatar" class="rounded-circle w-100 h-100" />`);
                    } else {
                        const initials = getInitials(employee.first_name, employee.last_name);
                        viewAvatarContainer.html(`<span class="avatar-initial rounded-circle bg-label-primary w-100 h-100 d-flex align-items-center justify-content-center">${initials}</span>`);
                    }
                }
                
                // Joining date
                const joiningDate = employee.date_of_joining ? new Date(employee.date_of_joining).toLocaleDateString() : 'Not set';
                $('#viewJoiningDate').text(joiningDate);
                
                // Reporting to
                const reportingTo = employee.reporting_to ? `${employee.reporting_to.first_name} ${employee.reporting_to.last_name}` : 'No reporting manager';
                $('#viewReportingTo').text(reportingTo);
                
                // Salary Information
                const salaryType = employee.salary_type;
                let salaryTypeBadge = `<span class="badge bg-label-primary">${salaryType.charAt(0).toUpperCase() + salaryType.slice(1)}</span>`;
                $('#viewSalaryType').html(salaryTypeBadge);
                
                // Update base salary label based on salary type
                let baseSalaryLabel = pageData.labels.baseSalary;
                switch (salaryType) {
                    case 'daily':
                        baseSalaryLabel = pageData.labels.dailyRate;
                        break;
                    case 'weekly':
                        baseSalaryLabel = pageData.labels.weeklyRate;
                        break;
                    case 'commission':
                        baseSalaryLabel = pageData.labels.commissionRate;
                        break;
                    case 'contract':
                        baseSalaryLabel = pageData.labels.contractAmount;
                        break;
                }
                $('#viewBaseSalaryLabel').text(baseSalaryLabel);
                
                // Salary amounts
                const currency = '$';
                const baseSalary = employee.base_salary ? currency + Number(employee.base_salary).toFixed(2) : 'Not set';
                const hourlyRate = employee.hourly_rate ? currency + Number(employee.hourly_rate).toFixed(2) : 'Not set';
                const overtimeRate = employee.overtime_rate ? currency + Number(employee.overtime_rate).toFixed(2) : 'Not set';
                
                $('#viewBaseSalary').text(baseSalary);
                $('#viewHourlyRate').text(hourlyRate);
                $('#viewOvertimeRate').text(overtimeRate);
                
                // Show/hide salary components based on salary type
                if (salaryType === 'hourly') {
                    $('#viewBaseSalaryContainer').hide();
                    $('#viewHourlyRateContainer').show();
                } else {
                    $('#viewBaseSalaryContainer').show();
                    $('#viewHourlyRateContainer').show();
                }
                
                // Show/hide overtime rate
                if (employee.overtime_rate) {
                    $('#viewOvertimeRateContainer').show();
                } else {
                    $('#viewOvertimeRateContainer').hide();
                }
                
                // Calculate and show breakdown for monthly salary types
                if (salaryType === 'monthly' && employee.base_salary) {
                    $('#breakdownBase').text(currency + Number(employee.base_salary).toFixed(2));
                    
                    // Calculate hourly equivalent (assuming 160 working hours per month)
                    const hourlyEquivalent = employee.base_salary / 160;
                    $('#breakdownHourly').text(currency + hourlyEquivalent.toFixed(2) + '/hr');
                    $('#breakdownHourlyContainer').show();
                    
                    $('#breakdownTotal').text(currency + Number(employee.base_salary).toFixed(2));
                    $('#salaryBreakdown').show();
                } else {
                    $('#salaryBreakdown').hide();
                }
                
                // Store employee ID for edit button
                $('#editFromViewBtn').data('employee-id', employee.id);
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('viewEmployeeSalaryModal'));
                modal.show();
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

// Handle edit button click from view modal
$(document).on('click', '#editFromViewBtn', function() {
    const employeeId = $(this).data('employee-id');
    
    // Hide view modal
    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewEmployeeSalaryModal'));
    viewModal.hide();
    
    // Open edit offcanvas
    setTimeout(function() {
        window.editEmployeeSalary(employeeId);
    }, 300); // Small delay to ensure modal is hidden
});