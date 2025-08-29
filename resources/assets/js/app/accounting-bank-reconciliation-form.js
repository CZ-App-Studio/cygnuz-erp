$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize form elements
  initializeFormElements();
  initializeValidation();

  function initializeFormElements() {
    // Initialize Select2
    if ($('#bank_account_id').length) {
      $('#bank_account_id').select2({
        placeholder: 'Select Bank Account',
        allowClear: false
      });
    }

    // Initialize Flatpickr
    if ($('#statement_date').length) {
      $('#statement_date').flatpickr({
        dateFormat: 'Y-m-d',
        allowInput: true,
        defaultDate: 'today'
      });
    }

    // Auto-calculate book balance when account or date changes
    $('#bank_account_id, #statement_date').on('change', function () {
      calculateBookBalance();
    });
  }

  function initializeValidation() {
    const form = $('#bankReconciliationForm');

    form.on('submit', function (e) {
      e.preventDefault();

      if (!validateForm()) {
        return false;
      }

      submitForm();
    });
  }

  function validateForm() {
    let isValid = true;

    // Clear previous validation
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    // Bank Account validation
    if (!$('#bank_account_id').val()) {
      showFieldError($('#bank_account_id'), 'Please select a bank account');
      isValid = false;
    }

    // Statement Date validation
    if (!$('#statement_date').val()) {
      showFieldError($('#statement_date'), 'Please select a statement date');
      isValid = false;
    }

    // Statement Balance validation
    const statementBalance = $('#statement_balance').val();
    if (!statementBalance || isNaN(statementBalance)) {
      showFieldError($('#statement_balance'), 'Please enter a valid statement balance');
      isValid = false;
    }

    return isValid;
  }

  function showFieldError(field, message) {
    field.addClass('is-invalid');
    field.siblings('.invalid-feedback').text(message);
  }

  function submitForm() {
    const form = $('#bankReconciliationForm');
    const submitBtn = form.find('button[type="submit"]');
    const originalBtnText = submitBtn.html();

    // Show loading state
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + (pageData.text?.processing || 'Processing...'));

    const formData = new FormData(form[0]);

    $.ajax({
      url: form.attr('action'),
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success || response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message || 'Bank reconciliation created successfully!',
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            // Redirect to the reconciliation detail page or list
            window.location.href = response.redirect_url || '/accounting/transactions/bank-reconciliation';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: response.message || 'An error occurred. Please try again.'
          });
        }
      },
      error: function (xhr) {
        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON.errors;
          if (errors) {
            Object.keys(errors).forEach(function (field) {
              const fieldElement = $('[name="' + field + '"]');
              showFieldError(fieldElement, errors[field][0]);
            });
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: xhr.responseJSON?.message || pageData.text?.error || 'An error occurred. Please try again.'
          });
        }
      },
      complete: function () {
        // Restore button state
        submitBtn.prop('disabled', false).html(originalBtnText);
      }
    });
  }

  function calculateBookBalance() {
    const accountId = $('#bank_account_id').val();
    const statementDate = $('#statement_date').val();

    if (!accountId || !statementDate) {
      $('#book_balance_display').val('');
      return;
    }

    // For now, we'll just show a placeholder
    // In a real implementation, you'd make an AJAX call to calculate this
    $('#book_balance_display').val('Calculating...');

    // Simulate calculation (replace with actual AJAX call)
    setTimeout(() => {
      const mockBalance = (Math.random() * 10000).toFixed(2);
      $('#book_balance_display').val(mockBalance);
    }, 500);
  }
});
