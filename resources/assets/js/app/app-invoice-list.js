'use strict';

$(function () {
  // 1. SETUP & VARIABLE DECLARATIONS
  // =================================================================================================
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Check if pageData object from Blade is available
  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.error('JS pageData object with URLs is not defined in the Blade view.');
    return;
  }

  // DOM Elements
  const dtInvoicesTableElement = $('#invoicesTable');
  const filterStatusSelect = $('#filter_status');
  const filterContactSelect = $('#filter_contact');
  let dtInvoicesTable;

  // 2. HELPER FUNCTIONS
  // =================================================================================================
  const getUrl = (template, id) => template.replace(':id', id);

  // 3. FILTER INITIALIZATION & DATATABLES
  // =================================================================================================

  // Initialize Status Filter (Static Select2)
  if (filterStatusSelect.length) {
    filterStatusSelect.select2({
      placeholder: 'Any Status',
      allowClear: true
    });
  }

  // Initialize Customer Filter (AJAX Select2)
  if (filterContactSelect.length) {
    filterContactSelect.select2({
      placeholder: 'Search by Customer',
      allowClear: true,
      ajax: {
        url: pageData.urls.contactSearch,
        dataType: 'json',
        delay: 250,
        data: (params) => ({ q: params.term, page: params.page || 1 }),
        processResults: (data, params) => ({
          results: data.results,
          pagination: { more: data.pagination.more }
        }),
        cache: true
      }
    });
  }

  // Event listener to reload DataTable when any filter changes
  $('.select2-filter').on('change', function () {
    if (dtInvoicesTable) {
      dtInvoicesTable.ajax.reload();
    }
  });

  // Main DataTable Initialization
  if (dtInvoicesTableElement.length) {
    dtInvoicesTable = dtInvoicesTableElement.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.ajax,
        type: 'POST',
        data: function (d) {
          // Send filter data with the request
          d.status = filterStatusSelect.val();
          d.contact_id = filterContactSelect.val();
        }
      },
      columns: [
        { data: 'invoice_number', name: 'invoice_number' },
        { data: 'status', name: 'status', className: 'text-center' },
        { data: 'customer', name: 'contact.first_name' }, // Server-side search on multiple fields
        { data: 'total', name: 'total', className: 'text-end' },
        { data: 'amount_paid', name: 'amount_paid', className: 'text-end' },
        { data: 'invoice_date', name: 'invoice_date' },
        { data: 'due_date', name: 'due_date' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      order: [[0, 'desc']], // Order by invoice number descending by default
      language: { search: '', searchPlaceholder: 'Search Invoices...' },
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    });
  }

  // 4. DELETE INVOICE ACTION
  // =================================================================================================
  dtInvoicesTableElement.on('click', '.delete-invoice', function (e) {
    e.preventDefault();
    const url = $(this).data('url');

    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        $.ajax({
          url: url,
          type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.code === 200) {
              Swal.fire('Deleted!', response.message, 'success');
              dtInvoicesTable.ajax.reload(null, false);
            } else {
              Swal.fire('Error!', response.message || 'Could not delete invoice.', 'error');
            }
          },
          error: function (jqXHR) {
            Swal.close();
            const response = jqXHR.responseJSON;
            Swal.fire('Error!', response?.message || 'An unexpected error occurred.', 'error');
          }
        });
      }
    });
  });
});
