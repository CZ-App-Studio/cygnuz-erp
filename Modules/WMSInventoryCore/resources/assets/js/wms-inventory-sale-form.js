$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize date pickers
  $('.datepicker').flatpickr({
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'F j, Y'
  });

  // Initialize customer select
  $('#customer_id').select2({
    placeholder: pageData.labels.selectCustomer,
    allowClear: true,
    ajax: {
      url: pageData.urls.customerSearch,
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
        // Data is returned as {results: [...], pagination: {...}} from the controller
        return {
          results: data.results || [],
          pagination: data.pagination || { more: false }
        };
      },
      cache: true
    }
  });

  // Initialize warehouse select
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
        // Handle both direct array and paginated response formats
        if (Array.isArray(data)) {
          return {
            results: data
          };
        }
        return {
          results: data.results || [],
          pagination: data.pagination || { more: false }
        };
      },
      cache: true
    }
  });
  
  // Set default warehouse if configured
  if (pageData.settings && pageData.settings.defaultWarehouseId) {
    // Load default warehouse data
    $.ajax({
      url: pageData.urls.warehouseSearch,
      data: { id: pageData.settings.defaultWarehouseId },
      dataType: 'json',
      success: function(data) {
        if (data && data.length > 0) {
          const warehouse = data[0];
          const option = new Option(warehouse.text, warehouse.id, true, true);
          $('#warehouse_id').append(option).trigger('change');
        }
      }
    });
  }

  // Handle warehouse change - refresh product stock counts
  $('#warehouse_id').on('change', function() {
    const warehouseId = $(this).val();
    
    // Clear and reinitialize all product selects to refresh stock counts
    $('.product-select').each(function() {
      const $select = $(this);
      const currentValue = $select.val();
      const currentText = $select.find('option:selected').text();
      
      // Clear the select2
      $select.empty();
      
      // If there was a selected value, add it back as an option
      if (currentValue) {
        $select.append(new Option(currentText, currentValue, true, true));
      }
      
      // Trigger change to update select2
      $select.trigger('change');
      
      // Update available quantity displays
      if (currentValue && warehouseId) {
        updateProductStock($select, currentValue, warehouseId);
      }
    });
    
    // Show warning if products are already selected
    if ($('.product-select').filter(function() { return $(this).val(); }).length > 0) {
      toastr.warning('Warehouse changed. Please verify product availability.', 'Warning');
    }
  });
  
  // Function to update product stock for a specific product
  function updateProductStock($select, productId, warehouseId) {
    $.ajax({
      url: pageData.urls.productSearch,
      data: {
        search: '',
        product_id: productId,
        warehouse_id: warehouseId,
        limit: 1
      },
      success: function(response) {
        const products = Array.isArray(response) ? response : (response.data || []);
        if (products.length > 0) {
          const product = products[0];
          const row = $select.closest('[data-repeater-item]');
          
          // Update available quantity display
          row.find('.available-qty').text('Available: ' + (product.current_stock || 0));
          
          // Update max quantity
          const quantityInput = row.find('.quantity');
          quantityInput.attr('max', product.current_stock || 0);
          
          // Update select2 text to show new stock
          const newText = product.name + ' (' + product.code + ') - Stock: ' + (product.current_stock || 0);
          $select.find('option:selected').text(newText);
          $select.trigger('change.select2');
        }
      }
    });
  }

  // Initialize product repeater using jQuery Repeater
  $('#product-repeater').repeater({
    initEmpty: false,
    show: function () {
      $(this).slideDown();
      
      // Initialize product select for new row
      const productSelect = $(this).find('.product-select');
      initializeProductSelect(productSelect);
      
      // Bind calculation events
      $(this).find('.quantity, .unit-price').on('input change', function() {
        calculateTotals();
      });
      
      calculateTotals();
    },
    hide: function (deleteElement) {
      if ($('[data-repeater-item]').length > 1) {
        $(this).slideUp(deleteElement);
        setTimeout(calculateTotals, 500);
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
    initializeProductSelect($(this));
  });

  // Calculate totals on input change
  $(document).on('input change', '.quantity, .unit-price, #tax_percentage, #discount_percentage, #shipping_cost', function() {
    calculateTotals();
  });

  // Form submission
  $('#saleForm').on('submit', function(e) {
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
    
    // Submit form
    $.ajax({
      url: form.attr('action') || pageData.urls.store,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        // Handle plain redirect response from Laravel
        if (typeof response === 'string') {
          window.location.href = pageData.urls.index || '/inventory/sales';
          return;
        }
        
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data?.message || pageData.labels.saleOrderCreated,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            if (response.data?.redirect) {
              window.location.href = response.data.redirect;
            } else {
              window.location.href = pageData.urls.index || '/inventory/sales';
            }
          });
        } else {
          // If response doesn't have expected format, just redirect
          window.location.href = pageData.urls.index || '/inventory/sales';
        }
      },
      error: function(xhr) {
        // Check if it's actually a redirect (302/301)
        if (xhr.status === 302 || xhr.status === 301) {
          window.location.href = pageData.urls.index || '/inventory/sales';
          return;
        }
        
        let errorMessage = pageData.labels.validationError;
        
        if (xhr.responseJSON) {
          if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            errorMessage = Object.values(errors).flat().join('<br>');
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
          window.location.href = pageData.urls.index || '/inventory/sales';
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
          customer_id: $('#customer_id').val(),
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
              text: product.name + ' (' + product.code + ') - Stock: ' + (product.current_stock || 0),
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
    
    // Set default price if available
    if (product.selling_price) {
      row.find('.unit-price').val(product.selling_price);
    }
    
    // Set available quantity info if tracking inventory
    if (product.current_stock !== undefined) {
      row.find('.available-qty').text('Available: ' + product.current_stock);
      
      // Set max quantity to available stock
      const quantityInput = row.find('.quantity');
      quantityInput.attr('max', product.current_stock);
      
      // Validate current quantity against stock
      const currentQty = parseFloat(quantityInput.val()) || 0;
      if (currentQty > product.current_stock) {
        quantityInput.val(product.current_stock);
      }
    }
    
    calculateTotals();
  });
  
  // Add stock validation on quantity input
  element.closest('[data-repeater-item]').find('.quantity').on('input', function() {
    const row = $(this).closest('[data-repeater-item]');
    const productSelect = row.find('.product-select');
    
    if (productSelect.val()) {
      const productData = productSelect.select2('data')[0]?.data;
      if (productData && productData.current_stock !== undefined) {
        const inputQty = parseFloat($(this).val()) || 0;
        const maxStock = parseFloat(productData.current_stock) || 0;
        
        if (inputQty > maxStock) {
          $(this).val(maxStock);
          
          Swal.fire({
            icon: 'warning',
            title: pageData.labels.warning,
            text: pageData.labels.quantityExceedsStock || `Quantity cannot exceed available stock (${maxStock})`,
            customClass: {
              confirmButton: 'btn btn-warning'
            }
          });
        }
      }
    }
  });
}

// Calculate totals
function calculateTotals() {
  let subtotal = 0;
  
  // Calculate line totals
  $('[data-repeater-item]').each(function() {
    const row = $(this);
    const quantity = parseFloat(row.find('.quantity').val()) || 0;
    const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
    
    // Calculate line total
    const lineTotal = quantity * unitPrice;
    
    // Update line total input field (not text)
    row.find('.line-total').val(lineTotal.toFixed(2));
    
    // Add to subtotal
    subtotal += lineTotal;
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

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(amount);
}

// Validate form
function validateForm() {
  let isValid = true;
  const errors = [];
  
  // Check customer
  if (!$('#customer_id').val()) {
    errors.push('Please select a customer');
    isValid = false;
  }
  
  // Check warehouse
  if (!$('#warehouse_id').val()) {
    errors.push('Please select a warehouse');
    isValid = false;
  }
  
  // Check date
  if (!$('#sale_date').val()) {
    errors.push('Please select a sale date');
    isValid = false;
  }
  
  // Check products and stock availability
  let hasProducts = false;
  let stockErrors = [];
  
  $('[data-repeater-item]').each(function() {
    const productId = $(this).find('.product-select').val();
    const quantity = parseFloat($(this).find('.quantity').val()) || 0;
    const unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
    
    if (productId && quantity > 0 && unitPrice >= 0) {
      hasProducts = true;
      
      // Check stock availability
      const productSelect = $(this).find('.product-select');
      const productData = productSelect.select2('data')[0]?.data;
      
      if (productData && productData.current_stock !== undefined) {
        const availableStock = parseFloat(productData.current_stock) || 0;
        if (quantity > availableStock) {
          stockErrors.push(`${productData.name}: Requested ${quantity}, Available ${availableStock}`);
        }
      }
    }
  });
  
  if (!hasProducts) {
    errors.push('Please add at least one valid product');
    isValid = false;
  }
  
  if (stockErrors.length > 0) {
    errors.push('Insufficient stock for:');
    errors.push(...stockErrors);
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