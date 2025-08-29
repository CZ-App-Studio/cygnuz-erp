/**
 * HRCore Employee Create Form
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
    initializeSelect2();
    initializeFlatpickr();
    initializeFormValidation();
    bindAttendanceTypeChange();
});

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
    $('.select2').select2({
        width: '100%'
    });
}

/**
 * Initialize Flatpickr for date inputs
 */
function initializeFlatpickr() {
    // Date of birth
    $('#date_of_birth').flatpickr({
        dateFormat: 'Y-m-d',
        maxDate: new Date().fp_incr(-18 * 365), // Maximum age: must be at least 18 years old
        minDate: new Date().fp_incr(-100 * 365) // Minimum age: up to 100 years old
    });
    
    // Date of joining
    $('#date_of_joining').flatpickr({
        dateFormat: 'Y-m-d',
        maxDate: new Date()
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const form = document.getElementById('createEmployeeForm');
    
    // FormValidation instance
    const fv = FormValidation.formValidation(form, {
        fields: {
            first_name: {
                validators: {
                    notEmpty: {
                        message: 'First name is required'
                    },
                    stringLength: {
                        max: 255,
                        message: 'First name must be less than 255 characters'
                    }
                }
            },
            last_name: {
                validators: {
                    notEmpty: {
                        message: 'Last name is required'
                    },
                    stringLength: {
                        max: 255,
                        message: 'Last name must be less than 255 characters'
                    }
                }
            },
            email: {
                validators: {
                    notEmpty: {
                        message: 'Email is required'
                    },
                    emailAddress: {
                        message: 'Please enter a valid email address'
                    }
                }
            },
            phone: {
                validators: {
                    notEmpty: {
                        message: 'Phone is required'
                    },
                    phone: {
                        country: 'US',
                        message: 'Please enter a valid phone number'
                    }
                }
            },
            code: {
                validators: {
                    notEmpty: {
                        message: 'Employee code is required'
                    }
                }
            },
            date_of_birth: {
                validators: {
                    notEmpty: {
                        message: 'Date of birth is required'
                    },
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'Please enter a valid date'
                    }
                }
            },
            gender: {
                validators: {
                    notEmpty: {
                        message: 'Gender is required'
                    }
                }
            },
            date_of_joining: {
                validators: {
                    notEmpty: {
                        message: 'Date of joining is required'
                    },
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'Please enter a valid date'
                    }
                }
            },
            designation_id: {
                validators: {
                    notEmpty: {
                        message: 'Designation is required'
                    }
                }
            },
            team_id: {
                validators: {
                    notEmpty: {
                        message: 'Team is required'
                    }
                }
            },
            shift_id: {
                validators: {
                    notEmpty: {
                        message: 'Shift is required'
                    }
                }
            },
            role: {
                validators: {
                    notEmpty: {
                        message: 'Role is required'
                    }
                }
            },
            attendance_type: {
                validators: {
                    notEmpty: {
                        message: 'Attendance type is required'
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.mb-3, .col-md-6, .col-md-3, .col-md-12'
            }),
            submitButton: new FormValidation.plugins.SubmitButton(),
            autoFocus: new FormValidation.plugins.AutoFocus()
        }
    });
    
    // Handle form submission after validation
    fv.on('core.form.valid', function() {
        // Allow the form to submit normally
        form.submit();
    });
    
    // Update validation when select2 changes
    $('.select2').on('change', function() {
        fv.revalidateField($(this).attr('name'));
    });
    
    // Update validation when flatpickr changes
    $('.flatpickr-date').on('change', function() {
        fv.revalidateField($(this).attr('name'));
    });
}

/**
 * Handle attendance type change
 */
function bindAttendanceTypeChange() {
    $('#attendance_type').on('change', function() {
        const type = $(this).val();
        const container = $('#attendanceConfigContainer');
        
        // Clear existing content
        container.empty();
        
        // Add configuration fields based on type
        switch(type) {
            case 'geofence':
                container.html(`
                    <label for="geofence_group_id" class="form-label">Geofence Group <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="geofence_group_id" name="geofence_group_id" required>
                        <option value="">Select Geofence Group</option>
                    </select>
                `);
                loadGeofenceGroups();
                break;
                
            case 'ip_address':
                container.html(`
                    <label for="ip_address_group_id" class="form-label">IP Address Group <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="ip_address_group_id" name="ip_address_group_id" required>
                        <option value="">Select IP Address Group</option>
                    </select>
                `);
                loadIPGroups();
                break;
                
            case 'qr_code':
                container.html(`
                    <label for="qr_group_id" class="form-label">QR Code Group <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="qr_group_id" name="qr_group_id" required>
                        <option value="">Select QR Code Group</option>
                    </select>
                `);
                loadQRGroups();
                break;
                
            case 'site':
                container.html(`
                    <label for="site_id" class="form-label">Site <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="site_id" name="site_id" required>
                        <option value="">Select Site</option>
                    </select>
                `);
                loadSites();
                break;
                
            case 'dynamic_qr':
                container.html(`
                    <label for="dynamic_qr_device_id" class="form-label">Dynamic QR Device <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="dynamic_qr_device_id" name="dynamic_qr_device_id" required>
                        <option value="">Select Dynamic QR Device</option>
                    </select>
                `);
                loadDynamicQRDevices();
                break;
                
            default:
                // Open or face recognition - no additional config needed
                container.empty();
        }
        
        // Reinitialize select2 for new elements
        container.find('.select2').select2({
            width: '100%'
        });
    });
}

// Placeholder functions for loading attendance configuration options
// These would make AJAX calls to fetch the respective data

function loadGeofenceGroups() {
    // TODO: Implement AJAX call to load geofence groups
}

function loadIPGroups() {
    // TODO: Implement AJAX call to load IP groups
}

function loadQRGroups() {
    // TODO: Implement AJAX call to load QR groups
}

function loadSites() {
    // TODO: Implement AJAX call to load sites
}

function loadDynamicQRDevices() {
    // TODO: Implement AJAX call to load dynamic QR devices
}