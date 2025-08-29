$(function () {
  // Setup CSRF token for all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Check if pageData is available
  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.warn('WMS Integration: pageData object with URLs is not defined in the Blade view.');
    return;
  }

  // Initialize WMS integration features after DOM is fully ready
  setTimeout(function() {
    initializeWMSIntegration();
  }, 500); // Increased delay to ensure all DOM elements are ready

  function initializeWMSIntegration() {
    console.log('Initializing WMS Integration...');
    console.log('PageData URLs:', pageData.urls);
    console.log('WMS Enabled:', pageData.wmsInventoryEnabled);
    console.log('Multi-currency Enabled:', pageData.multiCurrencyEnabled);

    // Initialize product selection with inventory awareness
    initializeProductSelection();

    // Initialize warehouse selection (if WMS is enabled)
    if (pageData.wmsInventoryEnabled) {
      initializeWarehouseSelection();
    }

    // Initialize currency selection (if MultiCurrency is available)
    if (pageData.multiCurrencyEnabled) {
      initializeCurrencySelection();
    }

    // Initialize stock validation
    initializeStockValidation();

    // Handle dynamic repeater items
    handleDynamicRepeaterItems();
  }

  function initializeProductSelection() {
    // Enhanced product selection with inventory awareness
    console.log('Looking for product select elements...');

    // Check for all possible product select variations
    const $allProductSelects = $('.product-select');
    const $uninitializedSelects = $('.product-select:not(.select2-hidden-accessible)');

    console.log('Found', $allProductSelects.length, 'total product selects');
    console.log('Found', $uninitializedSelects.length, 'uninitialized product selects');

    if ($allProductSelects.length === 0) {
      console.warn('No product select elements found with class .product-select');
      console.log('Available select elements:', $('select').length);
      console.log('Available form selects:', $('.form-select').length);

      // Try to find elements with different possible classes
      $('.form-select').each(function() {
        console.log('Form select found:', $(this).attr('class'), 'name:', $(this).attr('name'));
      });
      return;
    }

    if ($uninitializedSelects.length === 0) {
      console.log('All product select elements are already initialized');
      return;
    }

    console.log('Initializing product search for', $uninitializedSelects.length, 'elements');

    $uninitializedSelects.each(function() {
      const $element = $(this);
      console.log('Initializing element:', $element.attr('name'), 'classes:', $element.attr('class'));
    });

    $uninitializedSelects.select2({
      ajax: {
        url: pageData.urls.productSearch,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          const $row = $(this).closest('.repeater-item, .repeater-wrapper');
          return {
            search: params.term || '',
            warehouse_id: $row.find('.warehouse-select').val() || '',
            currency_id: $('#currency_id').val() || '',
            sellable_only: true
          };
        },
        processResults: function (data, params) {
          console.log('Product search response:', data);

          // Handle empty or invalid responses
          if (!data) {
            console.warn('Product search returned null/undefined response');
            return {
              results: [],
              pagination: { more: false }
            };
          }

          // Ensure data is an array
          if (!Array.isArray(data)) {
            console.warn('Product search response is not an array:', data);
            return {
              results: [],
              pagination: { more: false }
            };
          }

          try {
            const results = data.map(function (item) {
              // Validate item structure
              if (!item || !item.id || !item.name) {
                console.warn('Invalid product item:', item);
                return null;
              }

              const stockInfo = item.track_quantity ? ` - Stock: ${item.available_stock || 0}` : '';
              return {
                id: item.id,
                text: `${item.name} (${item.sku || 'N/A'})${stockInfo}`,
                data: item
              };
            }).filter(item => item !== null); // Remove null items

            console.log('Processed product results:', results.length, 'items');

            return {
              results: results,
              pagination: { more: false }
            };
          } catch (error) {
            console.error('Error processing product search results:', error);
            return {
              results: [],
              pagination: { more: false }
            };
          }
        },
        cache: false, // Disable cache to avoid stale data
        error: function(xhr, status, error) {
          console.error('Product search AJAX error:', {
            status: status,
            error: error,
            response: xhr.responseText
          });
        }
      },
      placeholder: 'Search products...',
      allowClear: true,
      minimumInputLength: 2,
      escapeMarkup: function (markup) {
        return markup; // Allow HTML
      },
      templateResult: function(item) {
        if (item.loading) return item.text;

        if (!item.data) return $('<span>').text(item.text);

        try {
          const product = item.data;
          const stockBadge = product.track_quantity
            ? `<span class="badge ${(product.available_stock || 0) > 0 ? 'bg-success' : 'bg-danger'} ms-2">Stock: ${product.available_stock || 0}</span>`
            : '';

          return $(`
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${product.name || 'N/A'}</strong> <small class="text-muted">(${product.sku || 'N/A'})</small>
                <br><small class="text-muted">${product.description || ''}</small>
              </div>
              <div>
                <span class="badge bg-primary">$${product.selling_price || 0}</span>
                ${stockBadge}
              </div>
            </div>
          `);
        } catch (error) {
          console.error('Error rendering product template:', error);
          return $('<span>').text(item.text);
        }
      },
      templateSelection: function(item) {
        if (item.data) {
          const stockInfo = item.data.track_quantity ? ` (Stock: ${item.data.available_stock || 0})` : '';
          return `${item.data.name} (${item.data.sku})${stockInfo}`;
        }
        return item.text;
      }
    });

    // Handle product selection
    $(document).on('select2:select', '.product-select', function (e) {
      const product = e.params.data.data;
      const $row = $(this).closest('.repeater-item, .repeater-wrapper');

      console.log('Product selected:', product);

      if (product) {
        // Auto-fill product details - try multiple selector patterns
        $row.find('.item-name, input[name*="item_name"]').val(product.name);
        $row.find('.item-description, textarea[name*="item_description"]').val(product.description || '');

        // Update price and trigger change event
        const $priceInput = $row.find('.unit-price, .item-price, input[name*="unit_price"]');

        // Check if we need to convert the price to current currency
        if (pageData.multiCurrencyEnabled && pageData.currentCurrency &&
            pageData.urls.convertCurrency && pageData.baseCurrency &&
            pageData.currentCurrency.id !== pageData.baseCurrency.id) {

          // Convert product price from base currency to current currency
          const conversionData = {
            from_currency_id: pageData.baseCurrency.id,
            to_currency_id: pageData.currentCurrency.id,
            amounts: { product_price: product.selling_price },
            _token: $('meta[name="csrf-token"]').attr('content')
          };

          $.post(pageData.urls.convertCurrency, conversionData)
            .done(function(response) {
              if (response.success && response.converted_amounts && response.converted_amounts.product_price) {
                const convertedPrice = response.converted_amounts.product_price;
                $priceInput.val(convertedPrice);

                // Store the base currency price as original (not the converted price)
                if (typeof window.storeOriginalPrice === 'function') {
                  const rowIndex = $('.repeater-wrapper').index($row);
                  window.storeOriginalPrice(rowIndex, product.selling_price);
                }

                $priceInput.trigger('change');
              } else {
                // Fallback to base price if conversion fails
                $priceInput.val(product.selling_price);

                if (typeof window.storeOriginalPrice === 'function') {
                  const rowIndex = $('.repeater-wrapper').index($row);
                  window.storeOriginalPrice(rowIndex, product.selling_price);
                }

                $priceInput.trigger('change');
              }
            })
            .fail(function() {
              // Fallback to base price if conversion request fails
              $priceInput.val(product.selling_price);

              if (typeof window.storeOriginalPrice === 'function') {
                const rowIndex = $('.repeater-wrapper').index($row);
                window.storeOriginalPrice(rowIndex, product.selling_price);
              }

              $priceInput.trigger('change');
            });
        } else {
          // No currency conversion needed - use base price
          $priceInput.val(product.selling_price);

          // Store as original price for currency conversion (if function exists)
          if (typeof window.storeOriginalPrice === 'function') {
            const rowIndex = $('.repeater-wrapper').index($row);
            window.storeOriginalPrice(rowIndex, product.selling_price);
          }

          $priceInput.trigger('change'); // Trigger change event to recalculate
        }

        $row.find('.unit-name').text(product.unit_name || '');
        $row.find('.item-product-id, input[name*="product_id"]').val(product.id);

        // Ensure quantity is set to 1 if empty
        const $quantityInput = $row.find('.item-quantity, .quantity-input');
        if (!$quantityInput.val() || parseFloat($quantityInput.val()) <= 0) {
          $quantityInput.val(1);
        }

        // Store product data for validation
        $row.data('product', product);

        // Update stock display
        updateStockDisplay($row, product);

        // Validate quantity against stock
        validateQuantityInput($row);

        // Trigger calculation if function exists
        if (typeof calculateTotals === 'function') {
          setTimeout(function() {
            calculateTotals();
          }, 100); // Small delay to ensure DOM updates are complete
        }

        // Also trigger input events to ensure any other handlers are called
        $row.find('.item-quantity, .quantity-input').trigger('input');
      }
    });
  }

  function initializeWarehouseSelection() {
    console.log('Initializing warehouse selection...');

    // Find uninitialized warehouse selects
    const $uninitializedWarehouseSelects = $('.warehouse-select:not(.select2-hidden-accessible)');
    console.log('Found', $uninitializedWarehouseSelects.length, 'uninitialized warehouse selects');

    if ($uninitializedWarehouseSelects.length === 0) {
      console.log('All warehouse select elements are already initialized');
      return;
    }

    $uninitializedWarehouseSelects.select2({
      ajax: {
        url: pageData.urls.getWarehouses,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            search: params.term || ''
          };
        },
        processResults: function (data, params) {
          console.log('Warehouse search response:', data);
          // Handle empty or invalid responses
          if (!data || !Array.isArray(data)) {
            console.warn('Warehouse search response is not an array:', data);
            return {
              results: [],
              pagination: { more: false }
            };
          }

          try {
            const results = data.map(function (item) {
              if (!item || !item.id || !item.name) {
                console.warn('Invalid warehouse item:', item);
                return null;
              }

              return {
                id: item.id,
                text: `${item.name} (${item.code || 'N/A'})`
              };
            }).filter(item => item !== null);

            return {
              results: results,
              pagination: { more: false }
            };
          } catch (error) {
            console.error('Error processing warehouse search results:', error);
            return {
              results: [],
              pagination: { more: false }
            };
          }
        },
        cache: false,
        error: function(xhr, status, error) {
          console.error('Warehouse search AJAX error:', {
            status: status,
            error: error,
            response: xhr.responseText
          });
        }
      },
      placeholder: 'Select warehouse...',
      allowClear: true
    });

    // Update product stock when warehouse changes
    $(document).on('change', '.warehouse-select', function() {
      const $row = $(this).closest('.repeater-item');
      const $productSelect = $row.find('.product-select');

      if ($productSelect.val()) {
        // Refresh product selection to get updated stock
        $productSelect.trigger('change');
      }
    });
  }

  function initializeCurrencySelection() {
    if (pageData.multiCurrencyEnabled) {
      $('#currency_id').select2({
        ajax: {
          url: pageData.urls.getCurrencies,
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              search: params.term || ''
            };
          },
          processResults: function (data, params) {
            // Handle empty or invalid responses
            if (!data || !Array.isArray(data)) {
              console.warn('Currency search response is not an array:', data);
              return {
                results: [],
                pagination: { more: false }
              };
            }

            try {
              const results = data.map(function (item) {
                if (!item || !item.id || !item.name) {
                  console.warn('Invalid currency item:', item);
                  return null;
                }

                return {
                  id: item.id,
                  text: `${item.name} (${item.code || 'N/A'}) ${item.symbol || ''}`,
                  code: item.code,
                  symbol: item.symbol,
                  exchange_rate: item.exchange_rate,
                  position: item.position,
                  is_default: item.is_default
                };
              }).filter(item => item !== null);

              return {
                results: results,
                pagination: { more: false }
              };
            } catch (error) {
              console.error('Error processing currency search results:', error);
              return {
                results: [],
                pagination: { more: false }
              };
            }
          },
          cache: false,
          error: function(xhr, status, error) {
            console.error('Currency search AJAX error:', {
              status: status,
              error: error,
              response: xhr.responseText
            });
          }
        },
        placeholder: 'Select currency...',
        allowClear: true
      });

      // Update prices when currency changes and handle currency conversion
      $('#currency_id').on('change.wms-integration', function() {
        const newCurrencyId = $(this).val();

        if (pageData.multiCurrencyEnabled && newCurrencyId) {
          // Get currency details from the selected option
          const selectedData = $(this).select2('data');
          if (selectedData && selectedData.length > 0) {
            const selected = selectedData[0];

            // Update pageData.currentCurrency for the formatting function
            // (This will be overridden by app-invoice-add.js currency conversion logic)
            pageData.currentCurrency = {
              id: selected.id,
              code: selected.code,
              symbol: selected.symbol,
              exchange_rate: selected.exchange_rate,
              position: selected.position || 'before'
            };
          }
        }

        // Note: Currency conversion is handled by app-invoice-add.js
        // This handler only updates the currency data for formatting
      });

      // Handle currency change on the currency dropdown itself
      $(document).on('select2:select', '#currency_id', function(e) {
        const currencyData = e.params.data;

        if (pageData.multiCurrencyEnabled && currencyData) {
          // Update current currency in pageData
          pageData.currentCurrency = {
            id: currencyData.id,
            code: currencyData.code,
            symbol: currencyData.symbol,
            exchange_rate: currencyData.exchange_rate || 1,
            position: currencyData.position || 'before'
          };

          console.log('Currency changed to:', pageData.currentCurrency);

          // Trigger currency conversion if there's a conversion function available
          if (typeof window.convertCurrencyValues === 'function') {
            window.convertCurrencyValues(null, currencyData.id);
          }
        }
      });
    }
  }

  function initializeStockValidation() {
    // Validate quantity input against available stock
    $(document).on('input', '.quantity-input', function() {
      const $row = $(this).closest('.repeater-item');
      validateQuantityInput($row);
    });
  }

  function updateStockDisplay($row, product) {
    const $stockDisplay = $row.find('.stock-display');

    if (product.track_quantity) {
      const stockClass = product.available_stock > 0 ? 'text-success' : 'text-danger';
      const stockText = `Available Stock: ${product.available_stock} ${product.unit_name || ''}`;

      $stockDisplay.html(`<small class="${stockClass}">${stockText}</small>`).show();
    } else {
      $stockDisplay.hide();
    }
  }

  function validateQuantityInput($row) {
    const product = $row.data('product');
    const $quantityInput = $row.find('.quantity-input');
    const $stockWarning = $row.find('.stock-warning');
    const quantity = parseFloat($quantityInput.val()) || 0;

    if (product && product.track_quantity && quantity > product.available_stock) {
      $quantityInput.addClass('is-invalid');
      $stockWarning.html(`
        <small class="text-danger">
          <i class="bx bx-error"></i>
          Insufficient stock! Available: ${product.available_stock}, Requested: ${quantity}
        </small>
      `).show();

      // Disable form submission
      $('#submitBtn').prop('disabled', true);
    } else {
      $quantityInput.removeClass('is-invalid');
      $stockWarning.hide();

      // Check if all items are valid
      if ($('.quantity-input.is-invalid').length === 0) {
        $('#submitBtn').prop('disabled', false);
      }
    }
  }

  // Form submission with stock validation
  $('#invoiceForm, #proposalForm').on('submit', function(e) {
    const hasStockErrors = $('.quantity-input.is-invalid').length > 0;

    if (hasStockErrors) {
      e.preventDefault();
      Swal.fire({
        title: 'Stock Validation Error',
        text: 'Please resolve stock availability issues before submitting.',
        icon: 'error',
        confirmButtonColor: '#d33'
      });
      return false;
    }

    // Show loading state
    $('#submitBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Processing...');
  });

  // Finalize invoice with inventory processing
  $(document).on('click', '.finalize-invoice-btn', function() {
    const invoiceId = $(this).data('invoice-id');
    const url = pageData.urls.finalizeInvoice.replace('__ID__', invoiceId);

    Swal.fire({
      title: 'Finalize Invoice',
      text: 'This will process inventory transactions and mark the invoice as sent. Continue?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, finalize it!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(url)
          .done(function(response) {
            Swal.fire({
              title: 'Success!',
              text: response.message,
              icon: 'success'
            }).then(() => {
              location.reload();
            });
          })
          .fail(function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire({
              title: 'Error!',
              text: response.message || 'Failed to finalize invoice',
              icon: 'error'
            });
          });
      }
    });
  });

  // Convert proposal to invoice with stock validation
  $(document).on('click', '.convert-to-invoice-btn', function() {
    const proposalId = $(this).data('proposal-id');
    const url = pageData.urls.convertToInvoice.replace('__ID__', proposalId);

    // Show conversion modal with form
    showConversionModal(url);
  });

  function showConversionModal(url) {
    const modalHtml = `
      <div class="modal fade" id="conversionModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Convert to Invoice</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="conversionForm">
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">Invoice Number</label>
                  <input type="text" name="invoice_number" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Invoice Date</label>
                  <input type="date" name="invoice_date" class="form-control" value="${new Date().toISOString().split('T')[0]}" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Due Date</label>
                  <input type="date" name="due_date" class="form-control" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Convert to Invoice</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    `;

    $('body').append(modalHtml);
    $('#conversionModal').modal('show');

    $('#conversionForm').on('submit', function(e) {
      e.preventDefault();

      const formData = $(this).serialize();

      $.post(url, formData)
        .done(function(response) {
          $('#conversionModal').modal('hide');
          Swal.fire({
            title: 'Success!',
            text: response.message,
            icon: 'success'
          }).then(() => {
            if (response.data && response.data.redirect_url) {
              window.location.href = response.data.redirect_url;
            } else {
              location.reload();
            }
          });
        })
        .fail(function(xhr) {
          const response = xhr.responseJSON;
          Swal.fire({
            title: 'Error!',
            text: response.message || 'Failed to convert proposal',
            icon: 'error'
          });
        });
    });

    $('#conversionModal').on('hidden.bs.modal', function() {
      $(this).remove();
    });
  }

  function handleDynamicRepeaterItems() {
    // Use MutationObserver instead of deprecated DOMNodeInserted
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
              const $newNode = $(node);

              // Check if this is a repeater wrapper or contains product selects
              if ($newNode.hasClass('repeater-wrapper') || $newNode.hasClass('repeater-item') ||
                  $newNode.find('.product-select').length > 0 || $newNode.find('.warehouse-select').length > 0) {

                console.log('New repeater item detected, initializing WMS elements');

                setTimeout(function() {
                  initializeProductSelection();

                  // Also initialize warehouse selection for new items
                  if (pageData.wmsInventoryEnabled) {
                    initializeWarehouseSelection();
                  }
                }, 100);
              }
            }
          });
        }
      });
    });

    // Start observing the document for changes
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    // Also listen for jQuery repeater's show event if available
    $(document).on('shown.bs.modal', function() {
      setTimeout(function() {
        initializeProductSelection();

        // Also initialize warehouse selection for modal items
        if (pageData.wmsInventoryEnabled) {
          initializeWarehouseSelection();
        }
      }, 100);
    });

    // Listen for repeater add events
    $(document).on('click', '[data-repeater-create]', function() {
      setTimeout(function() {
        console.log('Repeater create button clicked, re-initializing WMS elements');
        initializeProductSelection();

        // Also initialize warehouse selection for new items
        if (pageData.wmsInventoryEnabled) {
          initializeWarehouseSelection();
        }
      }, 200);
    });
  }

  // Global debugging function for troubleshooting
  window.debugWMSIntegration = function() {
    console.log('=== WMS Integration Debug ===');
    console.log('PageData:', pageData);
    console.log('Product selects found:', $('.product-select').length);
    console.log('Product selects initialized:', $('.product-select.select2-hidden-accessible').length);
    console.log('Warehouse selects found:', $('.warehouse-select').length);
    console.log('Warehouse selects initialized:', $('.warehouse-select.select2-hidden-accessible').length);
    console.log('Repeater items found:', $('.repeater-wrapper').length);

    $('.repeater-wrapper').each(function(index) {
      const $row = $(this);
      console.log(`Row ${index}:`, {
        productId: $row.find('.product-select').val(),
        warehouseId: $row.find('.warehouse-select').val(),
        itemName: $row.find('.item-name').val(),
        quantity: $row.find('.item-quantity').val(),
        price: $row.find('.item-price').val(),
        total: $row.find('.item-total').text(),
        productSelectInitialized: $row.find('.product-select').hasClass('select2-hidden-accessible'),
        warehouseSelectInitialized: $row.find('.warehouse-select').hasClass('select2-hidden-accessible')
      });
    });

    if (typeof calculateTotals === 'function') {
      console.log('calculateTotals function exists, calling it...');
      calculateTotals();
    } else {
      console.warn('calculateTotals function not found');
    }
  };

  // Global function to reinitialize WMS integration (useful for debugging)
  window.reinitializeWMSIntegration = function() {
    console.log('Manually re-initializing WMS Integration...');
    initializeProductSelection();

    if (pageData.wmsInventoryEnabled) {
      initializeWarehouseSelection();
    }

    if (pageData.multiCurrencyEnabled) {
      initializeCurrencySelection();
    }
  };
});
