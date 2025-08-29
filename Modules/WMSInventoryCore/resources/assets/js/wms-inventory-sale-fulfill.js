$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize date picker
  $('#fulfilled_date').flatpickr({
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'F j, Y',
    defaultDate: new Date()
  });

  // Handle fulfill checkbox changes
  $('.fulfill-item').on('change', function() {
    const row = $(this).closest('tr');
    const isChecked = $(this).is(':checked');
    const quantityInput = row.find('.quantity-fulfilled');
    const statusRadios = row.find('input[type="radio"]');
    const notesInput = row.find('.fulfillment-notes');

    if (isChecked) {
      quantityInput.prop('disabled', false);
      statusRadios.prop('disabled', false);
      
      // Set default quantity if empty
      if (!quantityInput.val()) {
        const maxQuantity = parseFloat(quantityInput.attr('max'));
        quantityInput.val(maxQuantity);
      }
      
      // Set default status to fulfilled
      row.find('input[value="fulfilled"]').prop('checked', true);
    } else {
      quantityInput.prop('disabled', true).val('');
      statusRadios.prop('disabled', true).prop('checked', false);
      notesInput.prop('disabled', true).val('');
    }
    
    updateFormButtons();
  });

  // Handle status radio changes
  $('input[name$="[status]"]').on('change', function() {
    const row = $(this).closest('tr');
    const notesInput = row.find('.fulfillment-notes');
    const status = $(this).val();

    if (status === 'cancelled' || status === 'back_ordered') {
      notesInput.prop('disabled', false).prop('required', true);
      notesInput.closest('.form-group').show();
    } else {
      notesInput.prop('disabled', true).prop('required', false);
      notesInput.closest('.form-group').hide();
      notesInput.val('');
    }
  });

  // Handle quantity changes
  $('.quantity-fulfilled').on('input', function() {
    const max = parseFloat($(this).attr('max'));
    const value = parseFloat($(this).val());
    
    if (value > max) {
      $(this).val(max);
    } else if (value < 0) {
      $(this).val(0);
    }
    
    // Check stock availability
    const row = $(this).closest('tr');
    const availableStock = parseFloat(row.find('.available-stock').text()) || 0;
    
    if (value > availableStock) {
      $(this).val(Math.min(max, availableStock));
      
      Swal.fire({
        icon: 'warning',
        title: pageData.labels.warning,
        text: pageData.labels.insufficientStock || `Insufficient stock. Available: ${availableStock}`,
        customClass: {
          confirmButton: 'btn btn-warning'
        }
      });
    }
  });

  // Select/Deselect all functionality
  $('#select-all').on('change', function() {
    const isChecked = $(this).is(':checked');
    $('.fulfill-item').prop('checked', isChecked).trigger('change');
  });

  // Batch number and expiry date handling
  $('.batch-select').on('change', function() {
    const row = $(this).closest('tr');
    const selectedBatch = $(this).find('option:selected');
    const expiryDate = selectedBatch.data('expiry');
    const availableQty = selectedBatch.data('quantity');
    
    // Update expiry date display
    if (expiryDate) {
      row.find('.expiry-date-display').text(expiryDate);
    } else {
      row.find('.expiry-date-display').text('N/A');
    }
    
    // Update available quantity for this batch
    if (availableQty !== undefined) {
      row.find('.batch-available-qty').text('Batch Qty: ' + availableQty);
      
      // Update max quantity for fulfillment
      const quantityInput = row.find('.quantity-fulfilled');
      const orderQuantity = parseFloat(quantityInput.attr('data-order-quantity')) || 0;
      const maxFulfillable = Math.min(orderQuantity, availableQty);
      
      quantityInput.attr('max', maxFulfillable);
      
      // Adjust current value if it exceeds batch quantity
      const currentQty = parseFloat(quantityInput.val()) || 0;
      if (currentQty > maxFulfillable) {
        quantityInput.val(maxFulfillable);
      }
    }
  });

  // Form submission
  $('#fulfillForm').on('submit', function(e) {
    e.preventDefault();

    // Validate at least one item is selected
    if ($('.fulfill-item:checked').length === 0) {
      Swal.fire({
        icon: 'warning',
        title: pageData.labels.error,
        text: pageData.labels.selectAtLeastOne || 'Please select at least one item to fulfill.',
        customClass: {
          confirmButton: 'btn btn-warning'
        }
      });
      return;
    }

    // Validate stock availability for all selected items
    let stockValidationErrors = [];
    $('.fulfill-item:checked').each(function() {
      const row = $(this).closest('tr');
      const quantityToFulfill = parseFloat(row.find('.quantity-fulfilled').val()) || 0;
      const availableStock = parseFloat(row.find('.available-stock').text()) || 0;
      const productName = row.find('.product-name').text();
      
      if (quantityToFulfill > availableStock) {
        stockValidationErrors.push(`${productName}: Requested ${quantityToFulfill}, Available ${availableStock}`);
      }
    });

    if (stockValidationErrors.length > 0) {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.insufficientStock || 'Insufficient Stock',
        html: stockValidationErrors.join('<br>'),
        customClass: {
          confirmButton: 'btn btn-danger'
        }
      });
      return;
    }

    // Show confirmation
    Swal.fire({
      title: pageData.labels.confirmFulfill,
      text: pageData.labels.confirmFulfillText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: pageData.labels.confirmFulfill,
      customClass: {
        confirmButton: 'btn btn-success me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        processFulfillment();
      }
    });
  });

  function updateFormButtons() {
    const hasSelectedItems = $('.fulfill-item:checked').length > 0;
    $('#fulfill-btn').prop('disabled', !hasSelectedItems);
  }

  function processFulfillment() {
    const formData = new FormData(document.getElementById('fulfillForm'));
    const submitBtn = $('#fulfill-btn');
    const originalText = submitBtn.html();

    // Show loading state
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

    $.ajax({
      url: pageData.urls.processFulfillment,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data?.message || pageData.labels.itemsFulfilled,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            if (response.data?.redirect) {
              window.location.href = response.data.redirect;
            } else {
              window.location.href = pageData.urls.showSale || '/inventory/sales';
            }
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.data || pageData.labels.validationError,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      error: function(xhr) {
        const errors = xhr.responseJSON?.errors || {};
        
        // Clear previous errors
        $('.text-danger').remove();
        $('.is-invalid').removeClass('is-invalid');

        // Display validation errors
        Object.keys(errors).forEach(function(field) {
          const input = $(`[name="${field}"]`);
          input.addClass('is-invalid');
          input.after(`<div class="text-danger">${errors[field][0]}</div>`);
        });

        let errorMessage = pageData.labels.validationError;
        if (xhr.responseJSON?.message) {
          errorMessage = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: errorMessage,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      complete: function() {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  }

  // Auto-fulfill all functionality
  $('#auto-fulfill-all').on('click', function() {
    Swal.fire({
      title: pageData.labels.confirmAutoFulfill || 'Auto-fulfill All Items?',
      text: pageData.labels.confirmAutoFulfillText || 'This will automatically fulfill all items with available stock.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: pageData.labels.autoFulfill || 'Auto-fulfill',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        autoFulfillItems();
      }
    });
  });

  function autoFulfillItems() {
    $('.fulfill-item').each(function() {
      const row = $(this).closest('tr');
      const availableStock = parseFloat(row.find('.available-stock').text()) || 0;
      const orderQuantity = parseFloat(row.find('.quantity-fulfilled').attr('max')) || 0;
      
      // Only check items that have available stock
      if (availableStock > 0 && orderQuantity > 0) {
        $(this).prop('checked', true).trigger('change');
        
        // Set quantity to minimum of order quantity and available stock
        const fulfillQuantity = Math.min(orderQuantity, availableStock);
        row.find('.quantity-fulfilled').val(fulfillQuantity);
      }
    });
    
    updateFormButtons();
  }

  // Initial state
  updateFormButtons();
  
  // Initialize batch selects if they exist
  $('.batch-select').each(function() {
    if ($(this).find('option').length > 1) {
      $(this).trigger('change');
    }
  });
});