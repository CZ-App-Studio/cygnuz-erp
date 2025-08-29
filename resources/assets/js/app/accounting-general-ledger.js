$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize variables
  let generalLedgerTable;
  let currentFilters = {
    account_id: pageData.filters.accountId || '',
    account_type: '',
    date_from: pageData.filters.dateFrom || '',
    date_to: pageData.filters.dateTo || ''
  };

  // Initialize DataTable
  function initializeDataTable() {
    if (generalLedgerTable) {
      generalLedgerTable.destroy();
    }

    generalLedgerTable = $('.datatables-general-ledger').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.generalLedgerData,
        data: function (d) {
          d.account_id = currentFilters.account_id;
          d.account_type = currentFilters.account_type;
          d.date_from = currentFilters.date_from;
          d.date_to = currentFilters.date_to;
        }
      },
      columns: [
        { data: 'transaction_date_formatted', name: 'transaction_date' },
        { data: 'account_info', name: 'chart_of_account_id', orderable: false, searchable: false },
        { data: 'journal_entry_link', name: 'journal_entry_line.journal_entry.entry_number', orderable: false, searchable: false },
        { data: 'description', name: 'description' },
        { data: 'debit_formatted', name: 'debit_amount', className: 'text-end', orderable: false, searchable: false },
        { data: 'credit_formatted', name: 'credit_amount', className: 'text-end', orderable: false, searchable: false },
        { data: 'running_balance_formatted', name: 'running_balance', className: 'text-end', orderable: false, searchable: false },
        { data: 'created_by_name', name: 'created_by_id', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        },
        emptyTable: 'No general ledger entries found'
      }
    });
  }

  // Initialize Select2
  $('.select2').select2({
    placeholder: 'Select an option',
    allowClear: true
  });

  // Initialize Flatpickr
  $('.flatpickr-date').flatpickr({
    dateFormat: 'Y-m-d',
    allowInput: true
  });

  // Apply filters
  $('#apply-filters').on('click', function () {
    currentFilters.account_id = $('#account_id').val();
    currentFilters.account_type = $('#account_type').val();
    currentFilters.date_from = $('#date_from').val();
    currentFilters.date_to = $('#date_to').val();

    // Reload DataTable
    generalLedgerTable.ajax.reload();

    // Load account balance summary if specific account is selected
    if (currentFilters.account_id) {
      loadAccountBalance();
    } else {
      $('#balance-summary-card').hide();
    }
  });

  // Reset filters
  $('#reset-filters').on('click', function () {
    $('#filterForm')[0].reset();
    $('#account_id').val('').trigger('change');
    $('#account_type').val('').trigger('change');

    currentFilters = {
      account_id: '',
      account_type: '',
      date_from: '',
      date_to: ''
    };

    // Reload DataTable
    generalLedgerTable.ajax.reload();

    // Hide balance summary
    $('#balance-summary-card').hide();
  });

  // Load account balance summary
  function loadAccountBalance() {
    $.ajax({
      url: pageData.urls.accountBalance,
      method: 'GET',
      data: {
        account_id: currentFilters.account_id,
        date_from: currentFilters.date_from,
        date_to: currentFilters.date_to
      },
      success: function (response) {
        $('#opening-balance').text(formatCurrency(response.opening_balance));
        $('#period-debits').text(formatCurrency(response.period_debits));
        $('#period-credits').text(formatCurrency(response.period_credits));
        $('#closing-balance').text(formatCurrency(response.closing_balance));
        $('#account-type-badge').text(response.account.type.toUpperCase());

        // Show balance summary card
        $('#balance-summary-card').show();

        // Update balance colors
        updateBalanceColors(response.opening_balance, response.closing_balance);
      },
      error: function (xhr, status, error) {
        console.error('Error loading account balance:', error);
      }
    });
  }

  // Update balance colors based on account type and balance
  function updateBalanceColors(openingBalance, closingBalance) {
    const openingElement = $('#opening-balance');
    const closingElement = $('#closing-balance');

    // Reset classes
    openingElement.removeClass('text-success text-danger');
    closingElement.removeClass('text-success text-danger');

    // Apply color based on balance
    if (openingBalance >= 0) {
      openingElement.addClass('text-success');
    } else {
      openingElement.addClass('text-danger');
    }

    if (closingBalance >= 0) {
      closingElement.addClass('text-success');
    } else {
      closingElement.addClass('text-danger');
    }
  }

  // Format currency
  function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount);
  }

  // Export to Excel
  $('#export-excel').on('click', function () {
    // Build export URL with current filters
    const exportUrl = new URL(pageData.urls.generalLedgerData, window.location.origin);
    exportUrl.searchParams.append('export', 'excel');
    exportUrl.searchParams.append('account_id', currentFilters.account_id);
    exportUrl.searchParams.append('account_type', currentFilters.account_type);
    exportUrl.searchParams.append('date_from', currentFilters.date_from);
    exportUrl.searchParams.append('date_to', currentFilters.date_to);

    // Open export URL in new tab
    window.open(exportUrl.toString(), '_blank');
  });

  // Print report
  $('#print-report').on('click', function () {
    window.print();
  });

  // Account change handler
  $('#account_id').on('change', function () {
    const accountId = $(this).val();
    if (accountId) {
      // Auto-apply filters when account is selected
      currentFilters.account_id = accountId;
      generalLedgerTable.ajax.reload();
      loadAccountBalance();
    } else {
      $('#balance-summary-card').hide();
    }
  });

  // Initialize DataTable on page load
  initializeDataTable();

  // Load account balance on page load if account is pre-selected
  if (currentFilters.account_id) {
    loadAccountBalance();
  }
});
