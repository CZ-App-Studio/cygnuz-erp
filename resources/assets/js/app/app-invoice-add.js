'use strict';

$(function () {
  // 1. SETUP & VARIABLE DECLARATIONS
  // =================================================================================================
  // Ensure pageData object from Blade is available
  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.error('JS pageData object with URLs is not defined in the Blade view.');
    return;
  }

  console.log('Invoice Add JS - Page Data:', pageData);
  console.log('Current Currency:', pageData.currentCurrency);
  console.log('Multi-currency enabled:', pageData.multiCurrencyEnabled);

  const repeaterElement = $('.source-item');
  const contactSelect = $('#contact_id');
  const customerDetailsDiv = $('#customer-details');
  const calculationsDiv = $('.invoice-calculations');
  let repeaterInstance;

  // Store original/base unit prices for currency conversion
  // This prevents compounding errors when switching currencies multiple times
  let originalPrices = new Map(); // Maps row index to original price
  let baseCurrencyId = pageData.currentCurrency ? pageData.currentCurrency.id : null;
  let originalCurrencyId = baseCurrencyId; // The currency that the original prices are in

  console.log('Initial base currency ID:', baseCurrencyId);
  console.log('Initial original currency ID:', originalCurrencyId);

  // 2. HELPER FUNCTIONS
  // =================================================================================================
  const getUrl = (template, id) => template.replace(':id', id);

  // Function to format currency using your Laravel Helper's logic as a reference
  // This is a basic JS equivalent. For full multi-currency support, this would need currency data.
  const formatCurrency = (value) => {
    const amount = parseFloat(value) || 0;

    console.log('formatCurrency called with:', value, 'amount:', amount);
    console.log('pageData.multiCurrencyEnabled:', pageData.multiCurrencyEnabled);
    console.log('pageData.currentCurrency:', pageData.currentCurrency);

    // Use current currency if available
    if (pageData.multiCurrencyEnabled && pageData.currentCurrency && pageData.currentCurrency.symbol) {
      const currency = pageData.currentCurrency;
      const formattedAmount = amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });

      // Get currency symbol, fallback to currency code or $
      const symbol = currency.symbol || currency.code || '$';

      console.log('Using currency:', currency, 'symbol:', symbol, 'position:', currency.position);

      // Position the symbol based on currency settings
      if (currency.position === 'after') {
        return `${formattedAmount} ${symbol}`;
      } else {
        return `${symbol}${formattedAmount}`;
      }
    }

    console.log('Using fallback USD formatting');
    // Fallback to basic USD formatting
    return amount.toLocaleString('en-US', {
      style: 'currency',
      currency: 'USD'
    });
  };

  // 3. CORE LOGIC: CALCULATIONS
  // =================================================================================================
  function calculateTotals() {
    console.log('calculateTotals() called');

    // Check if calculations div exists
    if (calculationsDiv.length === 0) {
      console.error('Calculations div not found!');
      return;
    }

    const visibleRows = $('.repeater-wrapper:visible');
    console.log(`Found ${visibleRows.length} visible repeater rows`);

    let subtotal = 0;
    let totalTax = 0;
    let grandTotal = 0;

    $('.repeater-wrapper:visible').each(function(index) {
      const $this = $(this);
      const quantity = parseFloat($this.find('.item-quantity').val()) || 0;
      const price = parseFloat($this.find('.item-price').val()) || 0;
      const lineSubtotal = quantity * price;

      // Calculate tax for this line item
      let lineTax = 0;
      const taxSelect = $this.find('.tax-select');
      if (taxSelect.length && taxSelect.val()) {
        const taxRate = parseFloat(taxSelect.find('option:selected').data('rate')) || 0;
        lineTax = lineSubtotal * (taxRate / 100);
      }

      const lineTotal = lineSubtotal + lineTax;

      console.log(`Row ${index}: qty=${quantity}, price=${price}, subtotal=${lineSubtotal}, tax=${lineTax}, total=${lineTotal}`);

      // Update line item display
      $this.find('.item-total').text(lineSubtotal.toFixed(2));
      $this.find('.item-tax-amount').text('$' + lineTax.toFixed(2) + ' tax');

      subtotal += lineSubtotal;
      totalTax += lineTax;
    });

    // Calculate grand total
    grandTotal = subtotal + totalTax;

    console.log(`Subtotal: ${subtotal}, Total Tax: ${totalTax}, Grand Total: ${grandTotal}`);

    // Update the summary table in the UI
    const summaryHtml = `
            <div class="d-flex justify-content-between mb-2">
                <span class="w-px-100">Subtotal:</span>
                <span class="fw-medium text-heading">${formatCurrency(subtotal)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="w-px-100">Discount:</span>
                <span class="fw-medium text-heading">${formatCurrency(0)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="w-px-100">Tax:</span>
                <span class="fw-medium text-heading">${formatCurrency(totalTax)}</span>
            </div>
            <hr class="my-2" />
            <div class="d-flex justify-content-between">
                <span class="w-px-100">Total:</span>
                <span class="fw-medium text-heading">${formatCurrency(grandTotal)}</span>
            </div>`;
    calculationsDiv.html(summaryHtml);

    console.log('Calculations updated in DOM:', calculationsDiv.html());
  }

  // Make functions globally available for other scripts
  window.calculateTotals = calculateTotals;
  window.convertCurrencyValues = convertCurrencyValues;
  window.storeOriginalPrice = storeOriginalPrice;
  window.getOriginalPrice = getOriginalPrice;
  window.storeAllOriginalPrices = storeAllOriginalPrices;
  window.restoreOriginalPrices = restoreOriginalPrices;

  // 4. INITIALIZATIONS (Select2, Flatpickr, Repeater)
  // =================================================================================================

  // Init date pickers
  $('.date-picker').flatpickr({ monthSelectorType: 'static', dateFormat: 'Y-m-d' });

  // Init customer search Select2
  contactSelect.select2({
    placeholder: 'Select a Customer/Contact',
    allowClear: true,
    ajax: {
      url: pageData.urls.contactSearch,
      dataType: 'json', delay: 250, data: (params) => ({ q: params.term }),
      processResults: (data) => ({ results: data.results }), cache: true
    }
  });

  // Init jQuery Repeater
  if (repeaterElement.length) {
    repeaterInstance = repeaterElement.repeater({
      show: function () {
        $(this).slideDown();
        // Note: Product search is now handled by billing-wms-integration.js
        // which initializes .product-select elements
        console.log('New repeater item added');

        // Set default quantity to 1 for new items
        $(this).find('.item-quantity, .quantity-input').val(1);

        // Store original price for new row (will be 0 initially)
        const newRowIndex = $('.repeater-wrapper').length - 1;
        storeOriginalPrice(newRowIndex, 0);

        calculateTotals();
      },
      hide: function (deleteBlock) {
        const $deletingRow = $(this);

        $deletingRow.slideUp(deleteBlock, function() {
          // Remove original price entry for deleted row after animation completes
          // We need to rebuild the originalPrices map since indices change
          const currentPrices = new Map();
          $('.repeater-wrapper:visible').each(function(index) {
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            currentPrices.set(index, price);
          });
          originalPrices = currentPrices;

          // Recalculate totals after the row is completely removed
          // Add small delay to ensure DOM is fully updated
          setTimeout(function() {
            calculateTotals();
          }, 50);
        });
      }
    });
    console.log('Repeater initialized');
  }

  // 5. EVENT LISTENERS
  // =================================================================================================

  // When a customer is selected, fetch and display their details
  contactSelect.on('change', function () {
    const contactId = $(this).val();
    customerDetailsDiv.html('');
    if (contactId) {
      const url = getUrl(pageData.urls.getContactDetails, contactId);
      $.get(url, function(data) {
        let detailsHtml = `
                    <p class="mb-1">${data.company ? e(data.company.name) : 'Individual Client'}</p>
                    <p class="mb-1">${e(data.address_street) || ''}</p>
                    <p class="mb-1">${e(data.address_city) || ''}, ${e(data.address_state) || ''} ${e(data.address_postal_code) || ''}</p>
                    <p class="mb-1">${e(data.phone_primary) || ''}</p>
                    <p class="mb-0">${e(data.email_primary) || ''}</p>`;
        customerDetailsDiv.html(detailsHtml);
      });
    }
  });

  // Recalculate on quantity, price, or tax changes
  $(document).on('input change', '.item-quantity, .item-price, .tax-select', () => calculateTotals());

  // Additional event handler for repeater delete buttons
  $(document).on('click', '[data-repeater-delete]', function() {
    console.log('Delete button clicked, will recalculate totals after deletion');
    // Add a small delay to allow the deletion to complete
    setTimeout(function() {
      calculateTotals();
    }, 300);
  });

  // 6. EDIT MODE INITIALIZATION
  // =================================================================================================
  function initializeEditMode() {
    console.log('initializeEditMode called');

    if (!pageData.isEditMode || !pageData.invoiceData) {
      console.log('Not in edit mode or no invoice data');
      return;
    }

    const invoice = pageData.invoiceData;
    console.log('Invoice data:', invoice);

    // Populate customer
    if (invoice.contact) {
      console.log('Populating customer:', invoice.contact);
      const customerName = invoice.contact.first_name + ' ' + invoice.contact.last_name;
      const option = new Option(customerName, invoice.contact.id, true, true);
      contactSelect.append(option).trigger('change');

      // Make the field non-editable in edit mode but keep it enabled for form submission
      contactSelect.prop('disabled', true);

      // Create a hidden field to ensure the value is submitted
      const hiddenContactField = $('<input type="hidden" name="contact_id" />');
      hiddenContactField.val(invoice.contact.id);
      contactSelect.after(hiddenContactField);

      // Add visual styling to show it's not editable
      contactSelect.addClass('bg-light');
    }

    // Initialize any existing Select2 elements (they should already be populated by Blade)
    // The billing-wms-integration.js will handle the Select2 initialization

    // Store original prices from loaded invoice data
    setTimeout(() => {
      console.log('Storing original prices for edit mode');
      storeAllOriginalPrices();
    }, 500);

    // Calculate totals for existing items
    setTimeout(() => {
      console.log('Calculating totals for existing items');
      calculateTotals();
    }, 1000); // Give time for WMS integration to initialize
  }

  // --- INITIALIZATION CALL ---
  if (pageData.isEditMode) {
    initializeEditMode();
  } else {
    // For a new blank invoice, calculate initial totals and store original prices
    console.log('Initializing create mode');
    setTimeout(() => {
      storeAllOriginalPrices();
      calculateTotals();
    }, 500);
  }

  // Also call calculateTotals immediately to ensure summary is visible
  calculateTotals();

  // Watch for changes in the repeater container (items being added/removed)
  const repeaterContainer = document.querySelector('.source-item');
  if (repeaterContainer) {
    const observer = new MutationObserver(function(mutations) {
      let shouldRecalculate = false;

      mutations.forEach(function(mutation) {
        // Check for removed nodes that are repeater items
        if (mutation.type === 'childList') {
          mutation.removedNodes.forEach(function(node) {
            if (node.nodeType === Node.ELEMENT_NODE &&
                ($(node).hasClass('repeater-wrapper') ||
                 $(node).find('.repeater-wrapper').length > 0)) {
              shouldRecalculate = true;
            }
          });
        }
      });

      if (shouldRecalculate) {
        console.log('MutationObserver detected repeater item removal, recalculating...');
        setTimeout(function() {
          calculateTotals();
        }, 100);
      }
    });

    observer.observe(repeaterContainer, {
      childList: true,
      subtree: true
    });
  }

  // A small helper function to prevent HTML injection
  function e(str) {
    return $('<div>').text(str).html();
  }

  // 7. ORIGINAL PRICE MANAGEMENT
  // =================================================================================================

  // Store original price for a repeater row
  function storeOriginalPrice(rowIndex, price) {
    const numericPrice = parseFloat(price) || 0;
    originalPrices.set(rowIndex, numericPrice);
    console.log(`Stored original price for row ${rowIndex}: ${numericPrice}`);
    console.log('All original prices:', Array.from(originalPrices.entries()));
  }

  // Get original price for a repeater row
  function getOriginalPrice(rowIndex) {
    return originalPrices.get(rowIndex) || 0;
  }

  // Store original prices for all current repeater rows
  function storeAllOriginalPrices() {
    $('.repeater-wrapper').each(function(index) {
      const price = parseFloat($(this).find('.item-price').val()) || 0;
      storeOriginalPrice(index, price);
    });
    console.log('Stored all original prices:', Array.from(originalPrices.entries()));
  }

  // Update original prices when products are selected or prices are manually changed
  $(document).on('change', '.item-price', function() {
    const $row = $(this).closest('.repeater-wrapper');
    const rowIndex = $('.repeater-wrapper').index($row);
    const price = parseFloat($(this).val()) || 0;

    // Only store as original price if we're not in the middle of a currency conversion
    if (!$(this).data('converting')) {
      storeOriginalPrice(rowIndex, price);
    }
  });

  // 8. CURRENCY CONVERSION LOGIC
  // =================================================================================================
  function convertCurrencyValues(fromCurrencyId, toCurrencyId) {
    if (!pageData.multiCurrencyEnabled || !pageData.urls.convertCurrency) {
      return;
    }

    console.log('Converting currency from', fromCurrencyId, 'to', toCurrencyId);

    // Collect original amounts for conversion (not current displayed amounts)
    const amounts = {};
    let amountIndex = 0;

    $('.repeater-wrapper').each(function(index) {
      const $row = $(this);
      // Use original price instead of current displayed price
      const originalPrice = getOriginalPrice(index);

      if (originalPrice > 0) {
        amounts[`item_${amountIndex}`] = originalPrice;
        $row.data('amount-index', amountIndex);
        amountIndex++;
      }
    });

    if (Object.keys(amounts).length === 0) {
      console.log('No original amounts to convert');
      return;
    }

    console.log('Converting original amounts:', amounts);

    // Send conversion request using original prices
    $.post(pageData.urls.convertCurrency, {
      from_currency_id: originalCurrencyId, // Always convert from original currency
      to_currency_id: toCurrencyId,
      amounts: amounts,
      _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
      if (response.success && response.converted_amounts) {
        console.log('Currency conversion successful:', response);

        // Update the prices with converted amounts
        $('.repeater-wrapper').each(function(index) {
          const $row = $(this);
          const amountIndex = $row.data('amount-index');
          if (amountIndex !== undefined) {
            const convertedAmount = response.converted_amounts[`item_${amountIndex}`];
            if (convertedAmount !== undefined) {
              // Mark as converting to prevent storing as new original price
              $row.find('.item-price').data('converting', true);
              $row.find('.item-price').val(convertedAmount).trigger('input');
              // Remove converting flag after a short delay
              setTimeout(() => {
                $row.find('.item-price').removeData('converting');
              }, 100);
            }
          }
        });

        // Update current currency for future conversions and formatting
        if (pageData.currentCurrency) {
          pageData.currentCurrency = response.to_currency;
        }

        // Recalculate totals with new converted prices
        calculateTotals();
      }
    })
    .fail(function(xhr, status, error) {
      console.error('Currency conversion failed:', error);
    });
  }

  // Restore original prices (for switching back to base currency)
  function restoreOriginalPrices() {
    console.log('Restoring original prices');
    console.log('Original prices stored:', Array.from(originalPrices.entries()));

    $('.repeater-wrapper').each(function(index) {
      const $row = $(this);
      const originalPrice = getOriginalPrice(index);

      console.log(`Row ${index}: original price = ${originalPrice}`);

      if (originalPrice >= 0) { // Allow 0 prices
        // Mark as converting to prevent storing as new original price
        $row.find('.item-price').data('converting', true);
        $row.find('.item-price').val(originalPrice).trigger('input');
        // Remove converting flag after a short delay
        setTimeout(() => {
          $row.find('.item-price').removeData('converting');
        }, 100);
      }
    });

    // Recalculate totals
    calculateTotals();
  }

  // Handle currency change event
  $(document).on('change', '#currency_id', function() {
    if (!pageData.multiCurrencyEnabled) {
      return;
    }

    const newCurrencyId = $(this).val();
    const currentCurrencyId = pageData.currentCurrency ? pageData.currentCurrency.id : null;

    console.log('Currency changed from', currentCurrencyId, 'to', newCurrencyId);
    console.log('Original currency ID:', originalCurrencyId);

    if (newCurrencyId && newCurrencyId != currentCurrencyId) {
      // Get currency details from Select2 data
      const selectedData = $(this).select2('data');
      let newCurrencyData = null;

      if (selectedData && selectedData.length > 0) {
        const selected = selectedData[0];
        newCurrencyData = {
          id: selected.id,
          code: selected.code,
          symbol: selected.symbol,
          exchange_rate: selected.exchange_rate,
          position: selected.position || 'before'
        };
      }

      // Check if switching back to original currency
      if (newCurrencyId == originalCurrencyId) {
        // Ask user if they want to restore original prices
        if (confirm('Do you want to restore the original prices?')) {
          restoreOriginalPrices();

          // Update current currency for formatting
          if (newCurrencyData) {
            pageData.currentCurrency = newCurrencyData;
          }
        }
      } else {
        // Ask user for confirmation before converting
        if (confirm('Do you want to convert existing prices to the new currency based on exchange rates?')) {
          convertCurrencyValues(currentCurrencyId, newCurrencyId);

          // Update current currency will be done in convertCurrencyValues success handler
        }
      }
    }
  });
});
