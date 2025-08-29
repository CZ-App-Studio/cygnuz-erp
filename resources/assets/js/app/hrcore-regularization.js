/**
 * Attendance Regularization
 */

'use strict';

$(document).ready(function() {
  // Initialize Select2
  if ($('.select2').length) {
    $('.select2').select2({
      placeholder: 'Select an option',
      allowClear: true
    });
  }

  // Initialize Flatpickr for date
  const attendanceDate = document.getElementById('date');
  if (attendanceDate) {
    flatpickr(attendanceDate, {
      dateFormat: 'Y-m-d',
      maxDate: 'today',
      minDate: new Date().fp_incr(-7), // Only last 7 days
      disable: [
        function(date) {
          // Disable future dates
          return date > new Date();
        }
      ]
    });
  }

  // Time inputs now use HTML5 type="time" for better browser support
  // No additional initialization needed

  // Regularization type change handler
  $('#type').on('change', function() {
    const type = $(this).val();
    
    // Show/hide time inputs based on type
    switch(type) {
      case 'missing_checkin':
        $('#requested_check_in_time').prop('required', true).closest('.col-md-6').show();
        $('#requested_check_out_time').prop('required', false).closest('.col-md-6').hide();
        break;
      case 'missing_checkout':
        $('#requested_check_in_time').prop('required', false).closest('.col-md-6').hide();
        $('#requested_check_out_time').prop('required', true).closest('.col-md-6').show();
        break;
      case 'forgot_punch':
      case 'wrong_time':
        $('#requested_check_in_time').prop('required', true).closest('.col-md-6').show();
        $('#requested_check_out_time').prop('required', true).closest('.col-md-6').show();
        break;
      default:
        $('#requested_check_in_time').prop('required', false).closest('.col-md-6').show();
        $('#requested_check_out_time').prop('required', false).closest('.col-md-6').show();
    }
  });

  // Form submission
  $('#regularizationForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate form
    if (!this.checkValidity()) {
      e.stopPropagation();
      $(this).addClass('was-validated');
      return;
    }

    // Check monthly limit
    const monthlyLimit = 3;
    const usedRequests = parseInt($('.progress-bar').attr('aria-valuenow')) || 0;
    
    if (usedRequests >= monthlyLimit) {
      Swal.fire({
        icon: 'error',
        title: 'Monthly Limit Exceeded',
        text: 'You have reached your monthly regularization limit.',
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
      return;
    }

    // Show confirmation
    Swal.fire({
      title: 'Submit Regularization Request?',
      text: 'Your request will be sent to your manager for approval.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Submit',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Submit form via AJAX
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Disable submit button
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Submitting...');
        
        $.ajax({
          url: $(this).attr('action'),
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function(response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.data.message || 'Regularization request submitted successfully',
                customClass: {
                  confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
              }).then(() => {
                // Reset form
                $('#regularizationForm')[0].reset();
                $('.select2').val(null).trigger('change');
                
                // Reload page or redirect
                window.location.reload();
              });
            }
          },
          error: function(xhr) {
            let errorMessage = 'Failed to submit regularization request';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
              errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            
            Swal.fire({
              icon: 'error',
              title: 'Submission Failed',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          },
          complete: function() {
            // Re-enable submit button
            submitBtn.prop('disabled', false).html('<i class="bx bx-send me-1"></i> Submit Request');
          }
        });
      }
    });
  });

  // File upload validation
  $('#attachments').on('change', function() {
    const files = this.files;
    if (files.length > 0) {
      const maxSize = 5 * 1024 * 1024; // 5MB per file
      const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Check file size
        if (file.size > maxSize) {
          Swal.fire({
            icon: 'error',
            title: 'File Too Large',
            text: `File "${file.name}" exceeds 5MB limit.`,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          this.value = '';
          return;
        }

        // Check file type
        if (!allowedTypes.includes(file.type)) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid File Type',
            text: `File "${file.name}" is not allowed. Only PDF, JPG, and PNG files are accepted.`,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          this.value = '';
          return;
        }
      }
    }
  });

  // Initialize DataTable for regularization history
  const regularizationTable = $('#regularizationTable');
  if (regularizationTable.length) {
    regularizationTable.DataTable({
      order: [[0, 'desc']],
      pageLength: 10,
      responsive: true,
      language: {
        paginate: {
          previous: '<i class="bx bx-chevron-left"></i>',
          next: '<i class="bx bx-chevron-right"></i>'
        }
      },
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });
  }

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});

// Cancel request function
window.cancelRequest = function(requestId) {
  Swal.fire({
    title: 'Cancel Request?',
    text: 'Are you sure you want to cancel this regularization request?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Cancel It',
    cancelButtonText: 'No, Keep It',
    customClass: {
      confirmButton: 'btn btn-danger',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then((result) => {
    if (result.isConfirmed) {
      // Make AJAX call to cancel
      $.ajax({
        url: `/hrcore/attendance-regularization/${requestId}`,
        type: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          Swal.fire({
            icon: 'success',
            title: 'Cancelled!',
            text: 'Your regularization request has been cancelled.',
            customClass: {
              confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
          }).then(() => {
            location.reload();
          });
        },
        error: function(xhr) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to cancel the request. Please try again.',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        }
      });
    }
  });
};