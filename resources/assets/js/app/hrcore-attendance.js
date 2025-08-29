/**
 * HRCore Attendance Management
 */

'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize DataTable
    const attendanceTable = initializeDataTable();
    
    // Initialize Select2
    initializeSelect2();
    
    // Initialize Flatpickr
    initializeFlatpickr();
    
    // Bind Events
    bindFilterEvents();
    bindActionEvents();
    
    // Load initial statistics
    loadAttendanceStatistics();
});

/**
 * Initialize DataTable with server-side processing
 */
function initializeDataTable() {
    return $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            type: 'GET',
            data: function (d) {
                // Always send current filter values
                d.userId = $('#userId').val() || '';
                // Fix date format - ensure it's YYYY-MM-DD
                // Use the main filter date field, not the edit form date
                let dateValue = $('#date').not('#editAttendanceOffcanvas #date').val();
                if (!dateValue) {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    dateValue = `${year}-${month}-${day}`;
                }
                d.date = dateValue;
                d.status = $('#status').val() || '';
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'user', name: 'user' },
            { data: 'shift', name: 'shift' },
            { data: 'check_in_time', name: 'check_in_time' },
            { data: 'check_out_time', name: 'check_out_time' },
            { 
                data: 'status', 
                name: 'status',
                render: function(data, type, row) {
                    let badgeClass = 'bg-label-secondary';
                    let statusText = data || 'N/A';
                    
                    if (data === 'present') {
                        badgeClass = 'bg-label-success';
                    } else if (data === 'late') {
                        badgeClass = 'bg-label-warning';
                    } else if (data === 'absent') {
                        badgeClass = 'bg-label-danger';
                    }
                    
                    return `<span class="badge ${badgeClass}">${statusText}</span>`;
                }
            },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        language: {
            search: pageData.labels.search,
            processing: pageData.labels.processing,
            lengthMenu: pageData.labels.lengthMenu,
            info: pageData.labels.info,
            infoEmpty: pageData.labels.infoEmpty,
            emptyTable: pageData.labels.emptyTable,
            paginate: pageData.labels.paginate
        },
        drawCallback: function() {
            // Update statistics after table draw
            updateStatistics();
        }
    });
}

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
    $('#userId').select2({
        placeholder: pageData.labels.selectEmployee || 'Select Employee',
        allowClear: true,
        width: '100%'
    });
}

/**
 * Initialize Flatpickr for date inputs
 */
function initializeFlatpickr() {
    $('#date').flatpickr({
        dateFormat: 'Y-m-d',
        defaultDate: new Date(),
        maxDate: new Date(),
        onChange: function(selectedDates, dateStr) {
            $('#attendanceTable').DataTable().ajax.reload();
            loadAttendanceStatistics();
        }
    });
}

/**
 * Bind filter events
 */
function bindFilterEvents() {
    // Apply filters button
    $('#filterBtn').on('click', function() {
        $('#attendanceTable').DataTable().ajax.reload();
    });
    
    // Reset filters button
    $('#resetBtn').on('click', function() {
        // Reset Flatpickr to today
        const flatpickrInstance = document.querySelector('#date')._flatpickr;
        flatpickrInstance.setDate(new Date());
        
        $('#userId').val('').trigger('change');
        $('#status').val('');
        $('#attendanceTable').DataTable().ajax.reload();
    });
    
    // Auto-reload on filter change
    $('#userId, #status').on('change', function() {
        $('#attendanceTable').DataTable().ajax.reload();
    });
}

/**
 * Bind action button events
 */
function bindActionEvents() {
    // Web Check-In button - redirect to web attendance page
    $('#webCheckInBtn').on('click', function() {
        window.location.href = pageData.urls.webAttendance;
    });
    
    // Export button removed - handled by DataImportExport addon
}

/**
 * Load attendance statistics
 */
function loadAttendanceStatistics() {
    const date = $('#date').val();
    
    $.ajax({
        url: pageData.urls.statistics,
        method: 'GET',
        data: { date: date },
        success: function(response) {
            if (response.status === 'success') {
                $('#presentCount').text(response.data.present || 0);
                $('#lateCount').text(response.data.late || 0);
                $('#absentCount').text(response.data.absent || 0);
            }
        },
        error: function(xhr) {
            console.error('Failed to load statistics');
        }
    });
}

/**
 * Update statistics from DataTable data
 */
function updateStatistics() {
    const table = $('#attendanceTable').DataTable();
    const data = table.rows({ page: 'current' }).data();
    
    let present = 0, late = 0, absent = 0;
    
    data.each(function(row) {
        if (row.status === 'present') present++;
        else if (row.status === 'late') late++;
        else if (row.status === 'absent') absent++;
    });
    
    // Note: This only updates based on current page data
    // For accurate counts, use loadAttendanceStatistics()
}


/**
 * Export attendance data
 */
function exportAttendance() {
    const params = {
        date: $('#date').val(),
        userId: $('#userId').val(),
        status: $('#status').val()
    };
    
    const queryString = $.param(params);
    window.location.href = pageData.urls.export + '?' + queryString;
}

/**
 * View attendance details
 */
window.viewAttendanceDetails = function(id) {
    // Implement view logic
    console.log('View attendance:', id);
};

/**
 * Edit attendance record
 */
window.editAttendance = function(id) {
    // Fetch attendance data
    const editUrl = pageData.urls.edit.replace(':id', id);
    
    $.ajax({
        url: editUrl,
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const data = response.data;
                const attendance = data.attendance;
                
                // Populate modal fields
                $('#editAttendanceId').val(attendance.id);
                $('#editEmployeeName').val(attendance.user.first_name + ' ' + attendance.user.last_name);
                $('#editDate').val(data.date);
                $('#editCheckInTime').val(data.checkInTime);
                $('#editCheckOutTime').val(data.checkOutTime);
                $('#editStatus').val(attendance.status || 'present');
                $('#editNotes').val(attendance.notes || '');
                
                // Show offcanvas
                const editOffcanvas = new bootstrap.Offcanvas(document.getElementById('editAttendanceOffcanvas'));
                editOffcanvas.show();
            }
        },
        error: function(xhr) {
            handleAjaxError(xhr);
        }
    });
};

/**
 * Handle edit form submission
 */
$(document).on('submit', '#editAttendanceForm', function(e) {
    e.preventDefault();
    
    const attendanceId = $('#editAttendanceId').val();
    const updateUrl = pageData.urls.update.replace(':id', attendanceId);
    
    const formData = {
        check_in_time: $('#editCheckInTime').val(),
        check_out_time: $('#editCheckOutTime').val(),
        status: $('#editStatus').val(),
        notes: $('#editNotes').val()
    };
    
    $.ajax({
        url: updateUrl,
        method: 'PUT',
        data: formData,
        success: function(response) {
            if (response.status === 'success') {
                // Get offcanvas instance
                const editOffcanvasElement = document.getElementById('editAttendanceOffcanvas');
                const editOffcanvas = bootstrap.Offcanvas.getInstance(editOffcanvasElement);
                
                // Store the current filter date before resetting form
                const filterDate = $('#date').val();
                
                // Reset form first
                $('#editAttendanceForm')[0].reset();
                
                // Restore the filter date in case it was affected
                $('#date').val(filterDate);
                
                // Listen for the offcanvas hidden event to reload data
                editOffcanvasElement.addEventListener('hidden.bs.offcanvas', function handleHidden() {
                    // Remove the event listener to prevent multiple calls
                    editOffcanvasElement.removeEventListener('hidden.bs.offcanvas', handleHidden);
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.success || 'Success',
                        text: response.data.message || pageData.labels.updateSuccess,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Ensure date field has value before reload
                    const currentDate = $('#date').val();
                    
                    // If date is empty, set it to today
                    if (!currentDate) {
                        const today = new Date();
                        const formattedDate = today.getFullYear() + '-' + 
                            String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(today.getDate()).padStart(2, '0');
                        $('#date').val(formattedDate);
                        
                        // Update flatpickr instance
                        if (document.querySelector('#date')._flatpickr) {
                            document.querySelector('#date')._flatpickr.setDate(formattedDate);
                        }
                    }
                    
                    // Force reload table with a small delay to ensure DOM is ready
                    setTimeout(function() {
                        // Force DataTable to reload with current filter values
                        const table = $('#attendanceTable').DataTable();
                        table.ajax.reload(function(json) {
                            console.log('DataTable reloaded, records found:', json.recordsTotal);
                        }, false);
                        loadAttendanceStatistics();
                    }, 100);
                }, { once: true });
                
                // Hide the offcanvas
                editOffcanvas.hide();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: response.data || pageData.labels.updateError
                });
            }
        },
        error: function(xhr) {
            handleAjaxError(xhr);
        }
    });
});

/**
 * Handle AJAX errors
 */
function handleAjaxError(xhr) {
    if (xhr.status === 422) {
        // Validation errors
        const errors = xhr.responseJSON.errors;
        let errorMessage = '';
        
        Object.keys(errors).forEach(field => {
            errorMessage += errors[field][0] + '<br>';
        });
        
        Swal.fire({
            icon: 'error',
            title: pageData.labels.validationError || 'Validation Error',
            html: errorMessage
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: pageData.labels.genericError || 'An error occurred. Please try again.'
        });
    }
}