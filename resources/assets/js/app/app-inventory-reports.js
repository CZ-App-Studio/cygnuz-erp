// WMS Inventory Reports JS
// Handles common UI for inventory valuation, low stock, and related reports

$(function () {
  'use strict';

  // Set up CSRF for AJAX (if needed for future enhancements)
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize Select2 for all select2 fields
  $('.select2').each(function () {
    $(this).select2({
      width: '100%',
      dropdownParent: $(this).parent()
    });
  });

  // Initialize Flatpickr for date range fields
  $('.flatpickr-range').flatpickr({
    mode: 'range',
    dateFormat: 'Y-m-d',
    allowInput: true
  });

  // Initialize Flatpickr for single date fields (used in stock movement)
  $('.flatpickr-date').flatpickr({
    dateFormat: 'Y-m-d',
    allowInput: true
  });

  // Auto-submit form on warehouse change (optional UX improvement)
  $('#warehouse_id').on('change', function () {
    $(this).closest('form').submit();
  });
});
