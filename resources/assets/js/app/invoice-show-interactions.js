'use strict';

$(function () {
  // Setup CSRF token for all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Check if pageData is available
  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.warn('Invoice Show: pageData object with URLs is not defined in the Blade view.');
    return;
  }

  // console.log('Invoice Show JS - Page Data:', pageData);

  // Initialize date picker for payment form
  if ($('.date-picker').length) {
    $('.date-picker').flatpickr({
      dateFormat: 'Y-m-d',
      defaultDate: new Date()
    });
  }

  // Initialize Select2 for payment method dropdown
  if ($('.select2-basic').length) {
    $('.select2-basic').select2({
      dropdownParent: $('#recordPaymentOffcanvas')
    });
  }

  // Handle Record Payment Form Submission
  $('#recordPaymentForm').on('submit', function (e) {
    e.preventDefault();
    
    console.log('Payment form submitted');
    
    const form = $(this);
    const formData = form.serialize();
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.text();

    console.log('Form data:', formData);
    console.log('Payment URL:', pageData.urls.recordPayment);

    // Clear previous errors
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').text('');

    // Set loading state
    submitBtn.prop('disabled', true).text('Processing...');

    $.ajax({
      url: pageData.urls.recordPayment,
      method: 'POST',
      data: formData,
      dataType: 'json',
      success: function (response) {
        console.log('Payment response:', response);
        if (response.code === 200) {
          // Show success message
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message || 'Payment recorded successfully.',
            confirmButtonText: 'OK'
          }).then(() => {
            // Reload the page to show updated payment info
            window.location.reload();
          });
        } else {
          console.error('Payment failed with response:', response);
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: response.message || 'An error occurred.',
            confirmButtonText: 'OK'
          });
        }
      },
      error: function (xhr, status, error) {
        console.error('Payment AJAX error:', {
          status: xhr.status,
          statusText: xhr.statusText,
          responseText: xhr.responseText,
          error: error
        });
        
        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
          console.log('Validation errors:', errors);
          for (const field in errors) {
            const input = form.find(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(errors[field][0]);
          }
        } else {
          const errorMessage = xhr.responseJSON && xhr.responseJSON.message 
            ? xhr.responseJSON.message 
            : 'Failed to record payment. Please try again.';
          
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            confirmButtonText: 'OK'
          });
        }
      },
      complete: function () {
        // Reset button state
        submitBtn.prop('disabled', false).text(originalText);
      }
    });
  });

  // Handle Send Invoice Form Submission
  $('#sendInvoiceForm').on('submit', function (e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = form.serialize();
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.text();

    // Clear previous errors
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').text('');

    // Set loading state
    submitBtn.prop('disabled', true).text('Sending...');

    $.ajax({
      url: pageData.urls.sendInvoice,
      method: 'POST',
      data: formData,
      dataType: 'json',
      success: function (response) {
        if (response.code === 200) {
          // Show success message
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message || 'Invoice sent successfully.',
            confirmButtonText: 'OK'
          }).then(() => {
            // Close the offcanvas
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('sendInvoiceOffcanvas'));
            if (offcanvas) {
              offcanvas.hide();
            }
            // Reload the page to show updated invoice status
            window.location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: response.message || 'An error occurred.',
            confirmButtonText: 'OK'
          });
        }
      },
      error: function (xhr) {
        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON.errors;
          for (const field in errors) {
            const input = form.find(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(errors[field][0]);
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to send invoice. Please try again.',
            confirmButtonText: 'OK'
          });
        }
      },
      complete: function () {
        // Reset button state
        submitBtn.prop('disabled', false).text(originalText);
      }
    });
  });

  // Auto-calculate payment amount based on amount due
  $('#payment_amount').on('focus', function () {
    const amountDue = parseFloat($(this).attr('max'));
    if (!$(this).val() || parseFloat($(this).val()) === 0) {
      $(this).val(amountDue.toFixed(2));
    }
  });
});
