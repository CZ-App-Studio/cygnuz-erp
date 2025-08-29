/**
 * App Proposal Add/Edit (jQuery)
 */

'use strict';

$(function () {
  var repeater,
    row = $('.invoice-item-row'),
    productSelect = $('.product-select');

  // CSRF Setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Form Repeater
  if ($('.source-item').length) {
    repeater = $('.source-item').repeater({
      show: function () {
        $(this).slideDown();
        // Initialize Select2 for new rows
        if (pageData.wmsInventoryEnabled) {
          initializeProductSelect($(this).find('.product-select'));
        }
        // Initialize tax select
        $(this).find('.item-tax').select2();
        // Set default values
        $(this).find('.item-quantity').val('1');
        $(this).find('.item-price').val('0.00');
        // Re-calculate total
        calculateTotal();
      },
      hide: function (deleteBlock) {
        $(this).slideUp(deleteBlock);
        calculateTotal();
      }
    });
  }

  // Initialize Date Pickers
  if ($('.date-picker').length) {
    $('.date-picker').each(function() {
      flatpickr(this, {
        dateFormat: 'Y-m-d',
        defaultDate: $(this).val()
      });
    });
  }

  // Initialize Select2
  $('.select2').select2();

  // Initialize Contact Select2 with AJAX
  $('#contact_id').select2({
    ajax: {
      url: pageData.urls.contactSearch,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          q: params.term
        };
      },
      processResults: function (data) {
        return {
          results: data.results
        };
      },
      cache: true
    },
    placeholder: 'Select Customer',
    allowClear: true
  });

  // Handle contact selection
  $('#contact_id').on('change', function () {
    var contactId = $(this).val();
    if (contactId) {
      $.ajax({
        url: pageData.urls.getContactDetails.replace(':id', contactId),
        type: 'GET',
        success: function (response) {
          // Update customer details display
          if (response) {
            var details = '';
            if (response.company && response.company.name) {
              details += '<p class="mb-1">' + response.company.name + '</p>';
            }
            if (response.email_primary) {
              details += '<p class="mb-1">' + response.email_primary + '</p>';
            }
            if (response.phone_primary) {
              details += '<p class="mb-0">' + response.phone_primary + '</p>';
            }
            $('#customer-details').html(details);
          }
        }
      });
    } else {
      $('#customer-details').html('');
    }
  });

  // Initialize Product Select2 for WMS Integration
  function initializeProductSelect(element) {
    element.select2({
      ajax: {
        url: pageData.urls.productSearch,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            search: params.term
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data.data, function (item) {
              return {
                id: item.id,
                text: item.name + ' (' + item.sku + ')',
                name: item.name,
                sku: item.sku,
                price: item.selling_price,
                description: item.description
              };
            })
          };
        },
        cache: true
      },
      placeholder: 'Type to search products...',
      templateResult: function (data) {
        if (!data.id) {
          return data.text;
        }
        return $('<span>' + data.text + '</span>');
      }
    });

    // Handle product selection
    element.on('select2:select', function (e) {
      var data = e.params.data;
      var container = $(this).closest('.repeater-wrapper');
      
      // Update fields
      container.find('input[name*="[item_name]"]').val(data.name);
      container.find('textarea[name*="[item_description]"]').val(data.description || '');
      container.find('input[name*="[unit_price]"]').val(data.price).trigger('change');
    });
  }

  // Initialize existing product selects
  if (pageData.wmsInventoryEnabled) {
    $('.product-select').each(function () {
      initializeProductSelect($(this));
    });
  }

  // Calculate line item total
  function calculateLineTotal(row) {
    var quantity = parseFloat(row.find('.item-quantity').val()) || 0;
    var price = parseFloat(row.find('.item-price').val()) || 0;
    var discount = parseFloat(row.find('.item-discount').val()) || 0;
    var taxRate = parseFloat(row.find('.item-tax option:selected').data('rate')) || 0;

    var subtotal = quantity * price;
    var discountAmount = discount;
    var taxAmount = (subtotal - discountAmount) * (taxRate / 100);
    var total = subtotal - discountAmount + taxAmount;

    // Store tax amount for backend
    row.find('.item-tax-amount').val(taxAmount.toFixed(2));

    return {
      subtotal: subtotal,
      discount: discountAmount,
      tax: taxAmount,
      total: total
    };
  }

  // Calculate total
  function calculateTotal() {
    var subtotal = 0;
    var totalDiscount = 0;
    var totalTax = 0;
    var total = 0;

    $('.repeater-wrapper:visible').each(function () {
      var lineTotal = calculateLineTotal($(this));
      subtotal += lineTotal.subtotal;
      totalDiscount += lineTotal.discount;
      totalTax += lineTotal.tax;
      total += lineTotal.total;
    });

    // Update display
    $('.subtotal-amount').text(formatCurrency(subtotal));
    $('.discount-amount').text(formatCurrency(totalDiscount));
    $('.tax-amount').text(formatCurrency(totalTax));
    $('.total-amount').text(formatCurrency(total));
  }

  // Format currency
  function formatCurrency(amount) {
    var currencySymbol = '$';
    if (pageData.multiCurrencyEnabled && $('#currency_id').length) {
      var selectedOption = $('#currency_id').find('option:selected');
      currencySymbol = selectedOption.data('symbol') || '$';
    }
    return currencySymbol + amount.toFixed(2);
  }

  // Bind calculation events
  $(document).on('change keyup', '.item-quantity, .item-price, .item-discount, .item-tax', function () {
    calculateTotal();
  });

  // Currency change handler
  $('#currency_id').on('change', function () {
    var selectedOption = $(this).find('option:selected');
    var symbol = selectedOption.data('symbol') || '$';
    
    // Update all currency symbols
    $('.currency-symbol').text(symbol);
    
    // Recalculate totals to update currency display
    calculateTotal();
  });

  // Set default expiry date based on settings
  if (pageData.defaultValidityDays && !pageData.isEditMode) {
    var expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + parseInt(pageData.defaultValidityDays));
    var formattedDate = expiryDate.toISOString().split('T')[0];
    $('input[name="expiry_date"]').val(formattedDate);
  }

  // Form validation
  $('.invoice-form').on('submit', function (e) {
    var isValid = true;
    
    // Check if at least one item exists
    if ($('.repeater-wrapper:visible').length === 0) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'No Items',
        text: 'Please add at least one item to the proposal.'
      });
      return false;
    }
    
    // Validate each item
    $('.repeater-wrapper:visible').each(function () {
      var itemName = $(this).find('input[name*="[item_name]"]').val();
      var quantity = $(this).find('.item-quantity').val();
      var price = $(this).find('.item-price').val();
      
      if (!itemName || !quantity || !price) {
        isValid = false;
        $(this).find('input:required').each(function() {
          if (!$(this).val()) {
            $(this).addClass('is-invalid');
          }
        });
      }
    });
    
    if (!isValid) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please fill in all required fields.'
      });
    }
  });

  // Initialize tax selects
  $('.item-tax').select2();

  // Initialize calculations on page load
  calculateTotal();

  // If in edit mode, populate data
  if (pageData.isEditMode && typeof populateEditData === 'function') {
    populateEditData();
  }
});