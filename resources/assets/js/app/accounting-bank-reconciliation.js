$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  let dtBankReconciliation = $('.datatables-bank-reconciliation');
  let bankReconciliationTable;

  if (dtBankReconciliation.length) {
    bankReconciliationTable = dtBankReconciliation.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.bankReconciliationData,
        data: function (d) {
          d.bank_account_id = $('#filter_bank_account').val();
          d.status = $('#filter_status').val();
          d.date_from = $('#filter_date_from').val();
          d.date_to = $('#filter_date_to').val();
        }
      },
      columns: [
        { data: 'id', name: 'id', visible: false },
        { data: 'reconciliation_number', name: 'reconciliation_number', className: 'fw-semibold' },
        { data: 'bank_account_name', name: 'bank_account_name' },
        { data: 'statement_date_formatted', name: 'statement_date', className: 'text-nowrap' },
        { data: 'statement_balance_formatted', name: 'statement_balance', className: 'text-end text-nowrap' },
        { data: 'book_balance_formatted', name: 'book_balance', className: 'text-end text-nowrap' },
        { data: 'difference_formatted', name: 'difference_amount', className: 'text-end text-nowrap', orderable: false },
        { data: 'status_badge', name: 'status', className: 'text-center', orderable: false },
        { data: 'created_by_name', name: 'created_by_name', orderable: false },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        },
        emptyTable: 'No bank reconciliations found'
      }
    });
  }

  // Initialize date pickers
  if ($('.flatpickr-date').length) {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d',
      allowInput: true
    });
  }

  // Apply filters
  $('#btn-apply-filters').on('click', function () {
    if (bankReconciliationTable) {
      bankReconciliationTable.ajax.reload();
    }
  });

  // Clear filters
  $('#btn-clear-filters').on('click', function () {
    $('#filter_bank_account').val('').trigger('change');
    $('#filter_status').val('').trigger('change');
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');

    if (bankReconciliationTable) {
      bankReconciliationTable.ajax.reload();
    }
  });

  // Delete reconciliation
  $(document).on('click', '.btn-delete-reconciliation', function () {
    const reconciliationId = $(this).data('id');
    const deleteUrl = pageData.urls.bankReconciliationDelete.replace('__ID__', reconciliationId);

    Swal.fire({
      title: pageData.text.confirmDelete || 'Are you sure?',
      text: 'This action cannot be undone!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: deleteUrl,
          method: 'DELETE',
          success: function (response) {
            if (response.success || response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: pageData.text.deleted || 'Bank reconciliation deleted successfully!',
                timer: 2000,
                showConfirmButton: false
              });
              bankReconciliationTable.ajax.reload();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response.message || pageData.text.error || 'An error occurred. Please try again.'
              });
            }
          },
          error: function (xhr) {
            let errorMessage = pageData.text.error || 'An error occurred. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage
            });
          }
        });
      }
    });
  });

  // Initialize Select2 for filters
  if ($('#filter_bank_account').length) {
    $('#filter_bank_account').select2({
      placeholder: 'All Accounts',
      allowClear: true
    });
  }

  if ($('#filter_status').length) {
    $('#filter_status').select2({
      placeholder: 'All Statuses',
      allowClear: true
    });
  }
});
