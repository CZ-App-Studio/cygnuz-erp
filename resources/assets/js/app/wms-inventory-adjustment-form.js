/**
 * Page: WMS & Inventory Adjustment Form (Create/Edit)
 * -----------------------------------------------------------------------------
 */

$(function () {
  'use strict';

  // Add CSRF token to all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize Select2
  if ($('.select2').length) {
    $('.select2').select2({
      placeholder: 'Select an option'
    });
  }

  // Initialize Flatpickr for date fields
  if ($('.flatpickr-date').length) {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d'
    });
  }

  // Product Search Select2
  const productSearch = $('#product-search');
  let productIndex = $('.product-row').length;

  if (productSearch.length) {
    productSearch.select2({
      ajax: {
        url: pageData.urls.warehouseProducts,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            warehouse_id: $('#warehouse_id').val(),
            q: params.term
          };
        },
        processResults: function (data) {
          return {
            results: data.map(function(item) {
              return {
                id: item.id,
                text: item.text,
                purchase_price: item.purchase_price,
                current_stock: item.current_stock,
                unit: item.unit
              };
            })
          };
        },
        cache: true
      },
      placeholder: 'Search for products...',
      minimumInputLength: 2,
      allowClear: true
    });

    // Reset product search when warehouse changes
    $('#warehouse_id').on('change', function() {
      productSearch.val(null).trigger('change');
    });
  }

  // Add Product Button
  $('#add-product-btn').on('click', function() {
    const selectedProduct = productSearch.select2('data')[0];
    
    if (!selectedProduct) {
      Swal.fire({
        icon: 'warning',
        title: 'No Product Selected',
        text: 'Please select a product first.',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return;
    }

    // Check if product already exists
    if ($(`.product-row[data-product-id="${selectedProduct.id}"]`).length > 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Product Already Added',
        text: 'This product is already in the list.',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return;
    }

    addProductRow(selectedProduct);
    productSearch.val(null).trigger('change');
  });

  // Function to add product row
  function addProductRow(product) {
    const template = $('#product-row-template').html();
    const newRow = template
      .replace(/{PRODUCT_ID}/g, product.id)
      .replace(/{INDEX}/g, productIndex)
      .replace(/{PRODUCT_NAME}/g, product.text)
      .replace(/{CURRENT_STOCK}/g, product.current_stock || 0)
      .replace(/{UNIT_COST}/g, product.purchase_price || 0);

    $('#products-container').append(newRow);
    $('#no-products-message').addClass('d-none');
    
    productIndex++;
    updateProductIndexes();
  }

  // Remove product row
  $(document).on('click', '.remove-product', function() {
    $(this).closest('.product-row').remove();
    
    if ($('.product-row').length === 0) {
      $('#no-products-message').removeClass('d-none');
    }
    
    updateProductIndexes();
  });

  // Update product indexes after adding/removing
  function updateProductIndexes() {
    $('.product-row').each(function(index) {
      $(this).find('input[name*="products["]').each(function() {
        const name = $(this).attr('name');
        const newName = name.replace(/products\[\d+\]/, `products[${index}]`);
        $(this).attr('name', newName);
      });
    });
  }

  // Form submission
  $('#adjustmentForm').on('submit', function(e) {
    // Check if we have any products
    if ($('.product-row').length === 0) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'No Products Added',
        text: 'Please add at least one product to the adjustment.',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return false;
    }

    // Validate that all products have required fields
    let hasErrors = false;
    $('.product-row').each(function() {
      const quantity = $(this).find('input[name*="[quantity]"]').val();
      const reason = $(this).find('input[name*="[reason]"]').val();
      
      if (!quantity || parseFloat(quantity) <= 0) {
        hasErrors = true;
        $(this).find('input[name*="[quantity]"]').addClass('is-invalid');
      } else {
        $(this).find('input[name*="[quantity]"]').removeClass('is-invalid');
      }
      
      if (!reason.trim()) {
        hasErrors = true;
        $(this).find('input[name*="[reason]"]').addClass('is-invalid');
      } else {
        $(this).find('input[name*="[reason]"]').removeClass('is-invalid');
      }
    });

    if (hasErrors) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please fill in all required fields for each product.',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return false;
    }

    // Disable submit button to prevent double submission
    $('#submit-btn').prop('disabled', true).text('Processing...');
  });

  // Quantity validation
  $(document).on('input', 'input[name*="[quantity]"]', function() {
    const value = parseFloat($(this).val());
    if (isNaN(value) || value <= 0) {
      $(this).addClass('is-invalid');
    } else {
      $(this).removeClass('is-invalid');
    }
  });

  // Reason validation
  $(document).on('input', 'input[name*="[reason]"]', function() {
    if (!$(this).val().trim()) {
      $(this).addClass('is-invalid');
    } else {
      $(this).removeClass('is-invalid');
    }
  });
});
