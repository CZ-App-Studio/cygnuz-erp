/**
 * Employee Edit Form
 */

'use strict';

$(function () {
  const form = document.getElementById('editEmployeeForm');
  const select2Elements = $('.select2');
  const flatpickrDate = $('.flatpickr-date');

  // Initialize Select2
  if (select2Elements.length) {
    select2Elements.each(function () {
      const $this = $(this);
      $this.wrap('<div class="position-relative"></div>').select2({
        placeholder: $this.attr('placeholder'),
        dropdownParent: $this.parent(),
        allowClear: true
      });
    });
  }

  // Initialize Flatpickr for date fields
  if (flatpickrDate.length) {
    // Date of birth - max date is today minus 18 years
    $('#date_of_birth').flatpickr({
      dateFormat: 'Y-m-d',
      maxDate: new Date().fp_incr(-18 * 365), // Maximum age: must be at least 18 years old
      minDate: new Date().fp_incr(-100 * 365) // Minimum age: up to 100 years old
    });
    
    // Date of joining - max date is today
    $('#date_of_joining').flatpickr({
      dateFormat: 'Y-m-d',
      maxDate: new Date()
    });
  }

  // Form validation using FormValidation
  if (form) {
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
            stringLength: {
              max: 20,
              message: 'Phone must be less than 20 characters'
            }
          }
        },
        code: {
          validators: {
            notEmpty: {
              message: 'Employee code is required'
            },
            stringLength: {
              max: 50,
              message: 'Employee code must be less than 50 characters'
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
          rowSelector: '.col-md-6, .col-md-3, .col-12'
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    });

    // Handle form submission after validation
    fv.on('core.form.valid', function() {
      // Show loading state
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...';
      submitBtn.disabled = true;
      
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

  // Handle disabled select elements
  $('select[disabled]').each(function() {
    // For disabled selects, ensure the value is submitted
    const name = $(this).attr('name');
    const value = $(this).val();
    if (name && value) {
      $('<input>').attr({
        type: 'hidden',
        name: name,
        value: value
      }).appendTo(form);
    }
  });

  // Handle attendance type change
  bindAttendanceTypeChange();
  
  // Trigger change on page load to load existing configuration
  $('#attendance_type').trigger('change');
});

/**
 * Handle attendance type change
 */
function bindAttendanceTypeChange() {
  $('#attendance_type').on('change', function() {
    const type = $(this).val();
    const container = $('#attendanceConfigContainer');
    
    // Clear existing content
    container.empty();
    
    // Only show configuration for non-open types when not disabled
    if ($(this).prop('disabled')) {
      return;
    }
    
    // Add configuration fields based on type
    switch(type) {
      case 'geofence':
        container.html(`
          <label for="geofence_group_id" class="form-label">Geofence Group <span class="text-danger">*</span></label>
          <select class="form-select select2" id="geofence_group_id" name="geofence_group_id">
            <option value="">Select Geofence Group</option>
          </select>
        `);
        loadGeofenceGroups();
        break;
        
      case 'ip_address':
        container.html(`
          <label for="ip_address_group_id" class="form-label">IP Address Group <span class="text-danger">*</span></label>
          <select class="form-select select2" id="ip_address_group_id" name="ip_address_group_id">
            <option value="">Select IP Address Group</option>
          </select>
        `);
        loadIPGroups();
        break;
        
      case 'qr_code':
        container.html(`
          <label for="qr_group_id" class="form-label">QR Code Group <span class="text-danger">*</span></label>
          <select class="form-select select2" id="qr_group_id" name="qr_group_id">
            <option value="">Select QR Code Group</option>
          </select>
        `);
        loadQRGroups();
        break;
        
      case 'site':
        container.html(`
          <label for="site_id" class="form-label">Site <span class="text-danger">*</span></label>
          <select class="form-select select2" id="site_id" name="site_id">
            <option value="">Select Site</option>
          </select>
        `);
        loadSites();
        break;
        
      case 'dynamic_qr':
        container.html(`
          <label for="dynamic_qr_device_id" class="form-label">Dynamic QR Device <span class="text-danger">*</span></label>
          <select class="form-select select2" id="dynamic_qr_device_id" name="dynamic_qr_device_id">
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
      width: '100%',
      dropdownParent: container
    });
  });
}

// Placeholder functions for loading attendance configuration options
// These would make AJAX calls to fetch the respective data

function loadGeofenceGroups() {
  // TODO: Implement AJAX call to load geofence groups
  // For now, add a placeholder message
  $('#geofence_group_id').append('<option value="">No groups available</option>');
}

function loadIPGroups() {
  // TODO: Implement AJAX call to load IP groups
  $('#ip_address_group_id').append('<option value="">No groups available</option>');
}

function loadQRGroups() {
  // TODO: Implement AJAX call to load QR groups
  $('#qr_group_id').append('<option value="">No groups available</option>');
}

function loadSites() {
  // TODO: Implement AJAX call to load sites
  $('#site_id').append('<option value="">No sites available</option>');
}

function loadDynamicQRDevices() {
  // TODO: Implement AJAX call to load dynamic QR devices
  $('#dynamic_qr_device_id').append('<option value="">No devices available</option>');
}