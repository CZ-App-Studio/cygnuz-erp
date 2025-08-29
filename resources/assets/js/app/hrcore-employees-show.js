/**
 * Employee Show Page
 */

'use strict';

$(document).ready(function () {
  const changeStateOffcanvas = document.getElementById('changeStateOffcanvas');
  const changeStateForm = $('#changeStateForm');
  const offcanvasInstance = changeStateOffcanvas ? new bootstrap.Offcanvas(changeStateOffcanvas) : null;
  
  // Check if DataTable is available
  if (typeof $.fn.DataTable === 'undefined') {
    console.error('DataTable plugin not loaded');
    return;
  }
  
  // Function to safely initialize DataTable
  function initDataTable(tableId, options) {
    const $table = $(tableId);
    if (!$table.length) return;
    
    // Check if already initialized
    if ($.fn.DataTable.isDataTable(tableId)) {
      return;
    }
    
    // Check for empty table with colspan
    const hasColspan = $table.find('tbody tr td[colspan]').length > 0;
    if (hasColspan && $table.find('tbody tr').length === 1) {
      // Table only has empty message, skip initialization
      console.log(`Skipping DataTable for ${tableId} - empty table`);
      return;
    }
    
    // Disable responsive if extension not loaded
    if (options.responsive && typeof $.fn.dataTable.Responsive === 'undefined') {
      console.warn('DataTables Responsive extension not loaded, disabling responsive option');
      delete options.responsive;
    }
    
    try {
      $table.DataTable(options);
    } catch (e) {
      console.error(`Error initializing DataTable for ${tableId}:`, e);
    }
  }
  
  // Initialize Flatpickr for effective date
  if ($('#effective_date').length) {
    $('#effective_date').flatpickr({
      dateFormat: 'Y-m-d',
      maxDate: new Date().fp_incr(30) // Allow up to 30 days in future
    });
  }

  // Initialize Select2 for state dropdown
  if ($('#new_state').length) {
    $('#new_state').select2({
      dropdownParent: $('#changeStateOffcanvas'),
      placeholder: 'Select new state'
    });
  }

  // Handle form submission
  changeStateForm.on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = changeStateForm.find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    // Show loading state
    submitBtn.prop('disabled', true).html(
      '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...'
    );
    
    // Get form data
    const formData = {
      state: $('#new_state').val(),
      effective_date: $('#effective_date').val(),
      reason: $('#change_reason').val(),
      remarks: $('#change_remarks').val(),
      _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    // Submit the form
    $.ajax({
      url: changeStateForm.attr('action'),
      type: 'POST',
      data: formData,
      success: function(response) {
        if (response.success) {
          // Show success message
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message,
            confirmButtonText: 'OK',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          }).then((result) => {
            // Redirect to refresh the page
            if (response.redirect) {
              window.location.href = response.redirect;
            } else {
              window.location.reload();
            }
          });
        }
      },
      error: function(xhr) {
        // Show error message
        let errorMessage = 'An error occurred. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: errorMessage,
          confirmButtonText: 'OK',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
        
        // Reset button state
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Reset form when offcanvas is closed
  if (changeStateOffcanvas) {
    changeStateOffcanvas.addEventListener('hidden.bs.offcanvas', function() {
      changeStateForm[0].reset();
      $('#new_state').val(null).trigger('change');
    });
  }

  // Initialize DataTable for lifecycle history
  setTimeout(function() {
    initDataTable('#lifecycleHistoryTable', {
      order: [[0, 'desc']],
      pageLength: 10,
      lengthMenu: [[10, 25, 50], [10, 25, 50]],
      responsive: true,
      language: {
        emptyTable: "No lifecycle history found"
      },
      columnDefs: [
        {
          targets: 0,
          type: 'date'
        },
        {
          targets: [1, 2], // Previous State and New State columns
          orderable: true
        },
        {
          targets: [3, 4, 5], // Reason, Changed By, Approved By columns
          orderable: false
        }
      ]
    });
  }, 100);

  // Initialize DataTable for employee history
  setTimeout(function() {
    initDataTable('#employeeHistoryTable', {
      order: [[0, 'desc']],
      pageLength: 10,
      lengthMenu: [[10, 25, 50], [10, 25, 50]],
      responsive: true,
      language: {
        emptyTable: "No change history found"
      },
      columnDefs: [
        {
          targets: 0,
          type: 'date'
        },
        {
          targets: [2, 3], // Changes and Changed By columns
          orderable: false
        }
      ]
    });
  }, 100);
});