$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Show warning if not editable
  if (pageData.isEditable === false) {
    Swal.fire({
      icon: 'warning',
      title: pageData.labels.limitedEditing || 'Limited Editing',
      text: pageData.labels.limitedEditingText || 'This purchase order can only be partially edited because it has been approved or is being processed.',
      customClass: {
        confirmButton: 'btn btn-warning'
      }
    });
  }

  // Initialize date pickers
  $('.datepicker').flatpickr({
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'F j, Y'
  });

  // Initialize vendor select with existing value
  if (pageData.purchase && pageData.purchase.vendor_id) {
    $('#vendor_id').html(new Option(pageData.purchase.vendor.name, pageData.purchase.vendor_id, true, true));
  }
  
  $('#vendor_id').select2({
    placeholder: pageData.labels.selectVendor,
    allowClear: true,
    ajax: {
      url: pageData.urls.vendorSearch,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          active_only: true,
          limit: 20
        };
      },
      processResults: function (data) {
        return {
          results: data
        };
      },
      cache: true
    }
  });

  // Initialize warehouse select with existing value
  if (pageData.purchase && pageData.purchase.warehouse_id) {
    $('#warehouse_id').html(new Option(pageData.purchase.warehouse.name, pageData.purchase.warehouse_id, true, true));
  }
  
  $('#warehouse_id').select2({
    placeholder: pageData.labels.selectWarehouse,
    allowClear: true,
    ajax: {
      url: pageData.urls.warehouseSearch,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          active_only: true,
          limit: 20
        };
      },
      processResults: function (data) {
        return {
          results: data
        };
      },
      cache: true
    }
  });

  // Initialize product repeater
  $('#product-repeater').repeater({
    initEmpty: false,
    show: function () {
      $(this).slideDown();
      
      // Initialize product select for new row
      const productSelect = $(this).find('.product-select');
      initializeProductSelect(productSelect);
      
      // Bind calculation events
      $(this).find('.quantity, .unit-price').on('input change', function() {
        calculateRowTotal($(this).closest('[data-repeater-item]'));
      });
    },
    hide: function (deleteElement) {
      if ($('[data-repeater-item]').length > 1) {
        $(this).slideUp(deleteElement);
        setTimeout(calculateGrandTotal, 500);
      } else {
        Swal.fire({
          icon: 'warning',
          title: 'Cannot Remove',
          text: 'At least one product is required',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    },
    isFirstItemUndeletable: true
  });

  // Initialize existing product selects
  $('.product-select').each(function() {
    const $select = $(this);
    const existingProduct = $select.data('product');
    
    if (existingProduct && existingProduct.id) {
      // Add the existing option
      const option = new Option(
        existingProduct.name + ' (' + existingProduct.code + ')',
        existingProduct.id,
        true,
        true
      );
      $select.append(option);
    }
    
    initializeProductSelect($select);
  });

  // Calculate totals on input change
  $(document).on('input change', '.quantity, .unit-price, #tax_percentage, #discount_percentage, #shipping_cost', function() {
    if ($(this).hasClass('quantity') || $(this).hasClass('unit-price')) {
      calculateRowTotal($(this).closest('[data-repeater-item]'));
    } else {
      calculateGrandTotal();
    }
  });

  // Form submission
  $('#purchaseForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    // Validate form
    if (!validateForm()) {
      return false;
    }
    
    // Disable submit button
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
    
    // Prepare form data
    const formData = new FormData(this);
    formData.append('_method', 'PUT'); // For Laravel PUT request
    
    // Submit form
    $.ajax({
      url: pageData.urls.update,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        // Handle plain redirect response from Laravel
        if (typeof response === 'string') {
          window.location.href = pageData.urls.show || '/inventory/purchases';
          return;
        }
        
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data?.message || pageData.labels.purchaseOrderUpdated,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            if (response.data?.redirect) {
              window.location.href = response.data.redirect;
            } else {
              window.location.href = pageData.urls.show || '/inventory/purchases';
            }
          });
        } else {
          // If response doesn't have expected format, just redirect
          window.location.href = pageData.urls.show || '/inventory/purchases';
        }
      },
      error: function(xhr) {
        // Check if it's actually a redirect (302/301)
        if (xhr.status === 302 || xhr.status === 301) {
          window.location.href = pageData.urls.show || '/inventory/purchases';
          return;
        }
        
        let errorMessage = pageData.labels.validationError;
        
        if (xhr.responseJSON) {
          if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            errorMessage = Object.values(errors).flat().join('<br>');
            
            // Clear previous errors
            $('.text-danger').remove();
            $('.is-invalid').removeClass('is-invalid');

            // Display validation errors
            Object.keys(errors).forEach(function(field) {
              const input = $(`[name="${field}"]`);
              input.addClass('is-invalid');
              input.after(`<div class="text-danger">${errors[field][0]}</div>`);
            });
          }
        }
        
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          html: errorMessage,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
        
        submitBtn.prop('disabled', false).html(originalText);
      },
      complete: function(xhr) {
        // Check for redirect header
        if (xhr.status === 200 && xhr.responseText && xhr.responseText.includes('<!DOCTYPE html>')) {
          // The server returned an HTML page (likely a redirect), navigate to it
          window.location.href = pageData.urls.show || '/inventory/purchases';
        }
      }
    });
  });
});

// Initialize product select
function initializeProductSelect(element) {
  element.select2({
    placeholder: pageData.labels.selectProduct,
    allowClear: true,
    ajax: {
      url: pageData.urls.productSearch,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          vendor_id: $('#vendor_id').val(),
          warehouse_id: $('#warehouse_id').val(),
          limit: 20
        };
      },
      processResults: function (data) {
        // Check if data is wrapped in an object or is a direct array
        const results = Array.isArray(data) ? data : (data.data || []);
        
        return {
          results: results.map(function(product) {
            return {
              id: product.id,
              text: product.name + ' (' + product.code + ')',
              data: product
            };
          })
        };
      },
      cache: true
    }
  });
  
  // Handle product selection
  element.on('select2:select', function(e) {
    const product = e.params.data.data;
    const row = $(this).closest('[data-repeater-item]');
    
    // Set default cost if available
    if (product.cost_price) {
      row.find('.unit-price').val(product.cost_price);
    }
    
    // Set available quantity info if tracking inventory
    if (product.current_stock !== undefined) {
      row.find('.available-qty').text('Available: ' + product.current_stock);
    }
    
    calculateRowTotal(row);
  });
}

// Calculate row total
function calculateRowTotal(row) {
  const quantity = parseFloat(row.find('.quantity').val()) || 0;
  const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
  const total = quantity * unitPrice;
  
  row.find('.line-total').val(total.toFixed(2));
  calculateGrandTotal();
}

// Calculate grand total
function calculateGrandTotal() {
  let subtotal = 0;
  
  $('.line-total').each(function() {
    subtotal += parseFloat($(this).val()) || 0;
  });

  // Update subtotal
  $('#subtotal').val(subtotal.toFixed(2));
  
  // Calculate discount
  const discountPercentage = parseFloat($('#discount_percentage').val()) || 0;
  const discountAmount = subtotal * (discountPercentage / 100);
  $('#discount_amount').val(discountAmount.toFixed(2));
  
  // Calculate subtotal after discount
  const subtotalAfterDiscount = subtotal - discountAmount;
  
  // Calculate tax (on discounted amount)
  const taxPercentage = parseFloat($('#tax_percentage').val()) || 0;
  const taxAmount = subtotalAfterDiscount * (taxPercentage / 100);
  $('#tax_amount').val(taxAmount.toFixed(2));
  
  // Get shipping cost
  const shippingCost = parseFloat($('#shipping_cost').val()) || 0;
  
  // Calculate grand total
  const grandTotal = subtotalAfterDiscount + taxAmount + shippingCost;
  $('#total_amount').val(grandTotal.toFixed(2));
}

// Validate form
function validateForm() {
  let isValid = true;
  const errors = [];
  
  // Check vendor
  if (!$('#vendor_id').val()) {
    errors.push('Please select a vendor');
    isValid = false;
  }
  
  // Check warehouse
  if (!$('#warehouse_id').val()) {
    errors.push('Please select a warehouse');
    isValid = false;
  }
  
  // Check date
  if (!$('#po_date').val()) {
    errors.push('Please select a purchase date');
    isValid = false;
  }
  
  // Check products
  let hasProducts = false;
  $('[data-repeater-item]').each(function() {
    const productId = $(this).find('.product-select').val();
    const quantity = parseFloat($(this).find('.quantity').val()) || 0;
    const unitCost = parseFloat($(this).find('.unit-price').val()) || 0;
    
    if (productId && quantity > 0 && unitCost >= 0) {
      hasProducts = true;
    }
  });
  
  if (!hasProducts) {
    errors.push('Please add at least one valid product');
    isValid = false;
  }
  
  // Show errors if any
  if (!isValid) {
    Swal.fire({
      icon: 'error',
      title: 'Validation Error',
      html: errors.join('<br>'),
      customClass: {
        confirmButton: 'btn btn-success'
      }
    });
  }
  
  return isValid;
}