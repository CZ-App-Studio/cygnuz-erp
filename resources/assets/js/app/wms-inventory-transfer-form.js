/**
 * Page: WMS & Inventory Transfer Form (Create/Edit)
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

  // Initialize Flatpickr for date fields
  if (document.querySelector('.flatpickr-date')) {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d',
      allowInput: true
    });
  }

  // Initialize Select2 for all select fields
  if (document.querySelector('.select2')) {
    $('.select2').select2({
      placeholder: function() {
        return $(this).data('placeholder');
      },
      allowClear: true
    });
  }

  // Product search with Select2 AJAX
  if (document.querySelector('#product-search')) {
    $('#product-search').select2({
      placeholder: 'Search for products...',
      allowClear: true,
      ajax: {
        url: pageData.urls.warehouseProducts,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term,
            warehouse_id: $('#source_warehouse_id').val()
          };
        },
        processResults: function (data, params) {
          return {
            results: data.results || [],
            pagination: {
              more: false
            }
          };
        },
        cache: true
      },
      minimumInputLength: 0,
      templateResult: function (item) {
        if (item.loading) {
          return item.text;
        }
        
        if (item.sku) {
          return $('<div>' + item.name + ' <small class="text-muted">(' + item.sku + ') - Stock: ' + item.current_stock + '</small></div>');
        }
        
        return $('<div>' + item.text + '</div>');
      },
      templateSelection: function (item) {
        return item.text || item.name;
      }
    });
  }

  // Clear product search when source warehouse changes
  $('#source_warehouse_id').on('change', function() {
    $('#product-search').val(null).trigger('change');
    updateProductSearchPlaceholder();
  });

  function updateProductSearchPlaceholder() {
    const sourceWarehouse = $('#source_warehouse_id').val();
    if (sourceWarehouse) {
      $('#product-search').attr('data-placeholder', 'Search for products in selected warehouse...');
    } else {
      $('#product-search').attr('data-placeholder', 'Please select source warehouse first...');
      $('#product-search').prop('disabled', true);
    }
    
    // Reinitialize if needed
    if (sourceWarehouse) {
      $('#product-search').prop('disabled', false);
    }
  }

  // Initialize placeholder state
  updateProductSearchPlaceholder();

  // Add Product Button
  let productIndex = typeof pageData !== 'undefined' && pageData.data && pageData.data.transfer 
    ? pageData.data.transfer.products.length 
    : 0;

  $('#add-product-btn').on('click', function() {
    const productSelect = $('#product-search');
    const selectedProductId = productSelect.val();
    const selectedProductData = productSelect.select2('data')[0];
    
    if (!selectedProductId) {
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

    // Check if product already exists in the table
    const existingRow = $(`.product-row[data-product-id="${selectedProductId}"]`);
    if (existingRow.length > 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Product Already Added',
        text: 'This product is already in the transfer list.',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return;
    }

    addProductRow(selectedProductId, selectedProductData);
    
    // Clear the search
    productSelect.val(null).trigger('change');
  });

  function addProductRow(productId, productData) {
    const template = $('#product-row-template').html();
    const productName = productData.name || productData.text;
    const productSku = productData.sku || '';
    const availableStock = productData.current_stock || 0;
    const productDisplayName = productName + (productSku ? ' (' + productSku + ')' : '');
    
    const newRow = template
      .replace(/{PRODUCT_ID}/g, productId)
      .replace(/{PRODUCT_NAME}/g, productDisplayName)
      .replace(/{AVAILABLE_STOCK}/g, availableStock)
      .replace(/{INDEX}/g, productIndex);

    $('#products-container').append(newRow);
    productIndex++;
    
    // Show the products table if it was hidden
    $('#no-products-message').addClass('d-none');
    
    updateProductIndices();
  }

  // Remove Product
  $(document).on('click', '.remove-product', function() {
    $(this).closest('tr').remove();
    updateProductIndices();
    
    // Show no products message if table is empty
    if ($('#products-container tr').length === 0) {
      $('#no-products-message').removeClass('d-none');
    }
  });

  function updateProductIndices() {
    $('#products-container tr').each(function(index) {
      $(this).find('input[name*="["]').each(function() {
        const name = $(this).attr('name');
        const newName = name.replace(/products\[\d+\]/, `products[${index}]`);
        $(this).attr('name', newName);
      });
    });
    productIndex = $('#products-container tr').length;
  }

  // Form Validation
  $('#transferForm').on('submit', function(e) {
    const productsCount = $('#products-container tr').length;
    
    if (productsCount === 0) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'No Products Added',
        text: 'Please add at least one product to the transfer.',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return false;
    }

    // Validate that all quantity fields have values > 0
    let hasValidQuantities = true;
    $('#products-container input[name*="[quantity]"]').each(function() {
      const quantity = parseFloat($(this).val()) || 0;
      if (quantity <= 0) {
        hasValidQuantities = false;
        $(this).addClass('is-invalid');
      } else {
        $(this).removeClass('is-invalid');
      }
    });

    if (!hasValidQuantities) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Invalid Quantities',
        text: 'Please ensure all products have valid quantities greater than 0.',
        customClass: {
          confirmButton: 'btn btn-primary'
        }
      });
      return false;
    }

    return true;
  });

  // Initialize existing products if in edit mode
  if (typeof pageData !== 'undefined' && pageData.data && pageData.data.transfer && pageData.data.transfer.products) {
    productIndex = pageData.data.transfer.products.length;
  }
});
