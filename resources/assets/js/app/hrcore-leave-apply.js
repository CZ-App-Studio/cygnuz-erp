/**
 * Leave Application Form
 */

'use strict';

(function () {
  // Initialize components
  const form = document.getElementById('leaveApplicationForm');
  const leaveTypeSelect = document.getElementById('leave_type_id');
  const fromDateInput = document.getElementById('from_date');
  const toDateInput = document.getElementById('to_date');
  const isHalfDayCheckbox = document.getElementById('is_half_day');
  const halfDayTypeContainer = document.getElementById('half_day_type_container');
  const totalDaysInput = document.getElementById('total_days');
  const isAbroadCheckbox = document.getElementById('is_abroad');
  const abroadLocationContainer = document.getElementById('abroad_location_container');

  // Initialize Select2
  if ($('.select2').length) {
    $('.select2').select2({
      placeholder: 'Select an option',
      allowClear: true
    });
  }

  // Initialize Flatpickr for date inputs
  if (fromDateInput) {
    flatpickr(fromDateInput, {
      dateFormat: 'Y-m-d',
      minDate: 'today',
      onChange: function(selectedDates, dateStr) {
        if (toDateInput._flatpickr) {
          toDateInput._flatpickr.set('minDate', dateStr);
        }
        calculateTotalDays();
      }
    });
  }

  if (toDateInput) {
    flatpickr(toDateInput, {
      dateFormat: 'Y-m-d',
      minDate: 'today',
      onChange: function() {
        calculateTotalDays();
      }
    });
  }

  // Leave type change event - update balance summary
  if (leaveTypeSelect) {
    leaveTypeSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      const availableDays = selectedOption.dataset.days;
      const leaveTypeId = this.value;
      
      // Store available days for validation
      if (availableDays) {
        leaveTypeSelect.dataset.availableDays = availableDays;
      }
      
      // Update balance summary
      if (leaveTypeId) {
        updateBalanceSummary(leaveTypeId);
      } else {
        // Reset summary if no type selected
        const summaryDiv = document.getElementById('leave-balance-summary');
        if (summaryDiv) {
          summaryDiv.innerHTML = '<p class="mb-0">Select a leave type to view balance details</p>';
        }
      }
      
      // Recalculate total days to validate against available balance
      calculateTotalDays();
    });
  }

  // Half day checkbox change
  if (isHalfDayCheckbox) {
    isHalfDayCheckbox.addEventListener('change', function() {
      if (this.checked) {
        halfDayTypeContainer.style.display = 'block';
        // Disable date range for half day
        toDateInput.value = fromDateInput.value;
        toDateInput.disabled = true;
        totalDaysInput.value = 0.5;
      } else {
        halfDayTypeContainer.style.display = 'none';
        toDateInput.disabled = false;
        calculateTotalDays();
      }
    });
  }

  // Abroad checkbox change
  if (isAbroadCheckbox) {
    isAbroadCheckbox.addEventListener('change', function() {
      abroadLocationContainer.style.display = this.checked ? 'block' : 'none';
      if (this.checked) {
        document.getElementById('abroad_location').required = true;
      } else {
        document.getElementById('abroad_location').required = false;
        document.getElementById('abroad_location').value = '';
      }
    });
  }

  // Calculate total days
  function calculateTotalDays() {
    if (fromDateInput.value && toDateInput.value && !isHalfDayCheckbox.checked) {
      const fromDate = new Date(fromDateInput.value);
      const toDate = new Date(toDateInput.value);
      const timeDiff = toDate - fromDate;
      const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24)) + 1;
      
      totalDaysInput.value = daysDiff > 0 ? daysDiff : 0;
      
      // Check if exceeds available balance
      checkBalanceAvailability();
    }
  }

  // Update balance summary
  function updateBalanceSummary(leaveTypeId) {
    const summaryDiv = document.getElementById('leave-balance-summary');
    if (!summaryDiv) return;
    
    // Get balance from API
    fetch(`/hrcore/leaves/balance/${leaveTypeId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const balance = data.balance;
          summaryDiv.innerHTML = `
            <div class="row">
              <div class="col-6">
                <small class="text-muted">Entitled:</small>
                <div class="fw-semibold">${balance.entitled || 0} days</div>
              </div>
              <div class="col-6">
                <small class="text-muted">Carried Forward:</small>
                <div class="fw-semibold">${balance.carried_forward || 0} days</div>
              </div>
              <div class="col-6 mt-2">
                <small class="text-muted">Used:</small>
                <div class="fw-semibold text-warning">${balance.used || 0} days</div>
              </div>
              <div class="col-6 mt-2">
                <small class="text-muted">Pending:</small>
                <div class="fw-semibold text-info">${balance.pending || 0} days</div>
              </div>
              <div class="col-12 mt-3 pt-2 border-top">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted">Available Balance:</span>
                  <span class="h5 mb-0 text-success">${balance.available || 0} days</span>
                </div>
              </div>
            </div>
          `;
        } else {
          summaryDiv.innerHTML = '<p class="mb-0 text-danger">Failed to load balance details</p>';
        }
      })
      .catch(error => {
        console.error('Error fetching balance:', error);
        summaryDiv.innerHTML = '<p class="mb-0 text-danger">Error loading balance details</p>';
      });
  }

  // Check balance availability
  function checkBalanceAvailability() {
    const requestedDays = parseFloat(totalDaysInput.value) || 0;
    const availableDays = parseFloat(leaveTypeSelect.dataset.availableDays) || 0;
    
    if (requestedDays > availableDays && availableDays > 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Insufficient Leave Balance',
        text: `You are requesting ${requestedDays} days but only have ${availableDays} days available.`,
        customClass: {
          confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
      });
    }
  }

  // Form submission
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Validate form
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }

      // Check balance before submission
      const requestedDays = parseFloat(totalDaysInput.value) || 0;
      const availableDays = parseFloat(leaveTypeSelect.dataset.availableDays) || 0;
      
      if (requestedDays > availableDays && availableDays > 0) {
        Swal.fire({
          icon: 'error',
          title: 'Cannot Submit',
          text: 'You do not have sufficient leave balance for this request.',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
        return;
      }

      // Show confirmation
      Swal.fire({
        title: 'Submit Leave Application?',
        text: `You are requesting ${requestedDays} day(s) of leave.`,
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
          const formData = new FormData(form);
          
          // Handle half_day_type field - remove it if half day is not selected
          const isHalfDay = document.getElementById('is_half_day');
          if (!isHalfDay.checked) {
            formData.delete('half_day_type');
          }
          
          // Handle checkbox values
          formData.delete('is_half_day');
          formData.append('is_half_day', isHalfDay.checked ? '1' : '0');
          
          const isAbroad = document.getElementById('is_abroad');
          if (isAbroad) {
            formData.delete('is_abroad');
            formData.append('is_abroad', isAbroad.checked ? '1' : '0');
          }
          
          const submitBtn = form.querySelector('button[type="submit"]');
          
          // Disable submit button
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
          
          fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Accept': 'application/json'
            }
          })
          .then(response => {
            return response.json().then(data => {
              return { status: response.status, data: data };
            });
          })
          .then(result => {
            const { status, data } = result;
            
            if (status === 200 && data.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.data?.message || data.message || 'Leave request submitted successfully!',
                customClass: {
                  confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
              }).then(() => {
                window.location.href = '/hrcore/my/leaves';
              });
            } else {
              // Handle validation errors or other errors
              let errorMessage = 'Failed to submit leave request. Please try again.';
              
              if (data.errors) {
                // Format validation errors
                const errorMessages = [];
                for (const field in data.errors) {
                  if (Array.isArray(data.errors[field])) {
                    errorMessages.push(...data.errors[field]);
                  } else {
                    errorMessages.push(data.errors[field]);
                  }
                }
                errorMessage = errorMessages.join('<br>');
              } else if (data.message) {
                errorMessage = data.message;
              } else if (data.data) {
                errorMessage = typeof data.data === 'string' ? data.data : data.data.message || errorMessage;
              }
              
              Swal.fire({
                icon: 'error',
                title: 'Submission Failed',
                html: errorMessage,
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'An unexpected error occurred. Please try again.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          })
          .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bx bx-send me-1"></i>Submit Application';
          });
        }
      });
    });
  }

  // File upload validation
  const documentInput = document.getElementById('document');
  if (documentInput) {
    documentInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        // Check file size (2MB limit)
        const maxSize = 2 * 1024 * 1024; // 2MB in bytes
        if (file.size > maxSize) {
          Swal.fire({
            icon: 'error',
            title: 'File Too Large',
            text: 'The file size must not exceed 2MB.',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          this.value = '';
          return;
        }

        // Check file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid File Type',
            text: 'Only PDF, JPG, and PNG files are allowed.',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
          this.value = '';
        }
      }
    });
  }
})();