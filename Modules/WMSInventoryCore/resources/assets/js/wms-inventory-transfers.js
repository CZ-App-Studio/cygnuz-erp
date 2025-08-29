/**
 * WMS Inventory Transfers JavaScript
 */
$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable for transfers
  let dt_transfers = $('.datatables-transfers');
  if (dt_transfers.length && typeof pageData !== 'undefined') {
    let dataTable = dt_transfers.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.transfersData,
        data: function (d) {
          d.source_warehouse_id = $('#source-warehouse-filter').val();
          d.destination_warehouse_id = $('#destination-warehouse-filter').val();
          d.status = $('#status-filter').val();
          d.date_range = $('#date-range').val();
        }
      },
      columns: [
        { data: 'id' },
        { data: 'date' },
        { data: 'reference' },
        { data: 'source_warehouse' },
        { data: 'destination_warehouse' },
        { data: 'status' },
        { data: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [],
      language: {
        processing: pageData.labels.processing || 'Processing...',
        search: pageData.labels.search + ':' || 'Search:',
        lengthMenu: `${pageData.labels.show || 'Show'} _MENU_ ${pageData.labels.entries || 'entries'}`,
        info: `${pageData.labels.showing || 'Showing'} _START_ ${pageData.labels.to || 'to'} _END_ ${pageData.labels.of || 'of'} _TOTAL_ ${pageData.labels.entries || 'entries'}`,
        infoEmpty: `${pageData.labels.showing || 'Showing'} 0 ${pageData.labels.to || 'to'} 0 ${pageData.labels.of || 'of'} 0 ${pageData.labels.entries || 'entries'}`,
        infoFiltered: `(${pageData.labels['entries filtered from'] || 'filtered from'} _MAX_ ${pageData.labels['total entries'] || 'total entries'})`,
        infoPostFix: '',
        loadingRecords: pageData.labels.loading || 'Loading...',
        zeroRecords: pageData.labels['No matching records found'] || 'No matching records found',
        emptyTable: pageData.labels['No data available in table'] || 'No data available in table',
        paginate: {
          first: pageData.labels.first || 'First',
          previous: pageData.labels.previous || 'Previous',
          next: pageData.labels.next || 'Next',
          last: pageData.labels.last || 'Last'
        }
      },
      responsive: true
    });

    // Filter functionality
    $('#source-warehouse-filter, #destination-warehouse-filter, #status-filter, #date-range').on('change', function () {
      dataTable.draw();
    });
  }

  // Initialize Flatpickr for date range
  if (document.getElementById('date-range')) {
    flatpickr('#date-range', {
      mode: 'range',
      dateFormat: 'Y-m-d',
      locale: {
        rangeSeparator: ' to '
      }
    });
  }

  // Handle transfer actions
  $(document).on('click', '.btn-approve', function () {
    const transferId = $(this).data('id');
    
    Swal.fire({
      title: pageData.labels.approveTransferTitle,
      text: pageData.labels.approveTransferText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: pageData.labels.yesApproveIt
    }).then((result) => {
      if (result.isConfirmed) {
        approveTransfer(transferId);
      }
    });
  });

  $(document).on('click', '.btn-ship', function () {
    const transferId = $(this).data('id');
    
    Swal.fire({
      title: pageData.labels.shipTransferTitle,
      text: pageData.labels.shipTransferText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#007bff',
      cancelButtonColor: '#6c757d',
      confirmButtonText: pageData.labels.yesShipIt
    }).then((result) => {
      if (result.isConfirmed) {
        shipTransfer(transferId);
      }
    });
  });

  $(document).on('click', '.btn-receive', function () {
    const transferId = $(this).data('id');
    
    Swal.fire({
      title: pageData.labels.completeTransferTitle,
      text: pageData.labels.completeTransferText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: pageData.labels.yesCompleteIt
    }).then((result) => {
      if (result.isConfirmed) {
        receiveTransfer(transferId);
      }
    });
  });

  function approveTransfer(transferId) {
    if (pageData && pageData.urls.transfersApprove) {
      const url = pageData.urls.transfersApprove.replace('__TRANSFER_ID__', transferId);
      
      $.post(url)
        .done(function (response) {
          if (response.status === 'success') {
            Swal.fire(pageData.labels.approved, response.data.message || pageData.labels.transferApprovedSuccessfully, 'success');
            $('.datatables-transfers').DataTable().ajax.reload();
          } else {
            Swal.fire(pageData.labels.error, response.data || pageData.labels.failedToApproveTransfer, 'error');
          }
        })
        .fail(function () {
          Swal.fire('Error!', 'Failed to approve transfer.', 'error');
        });
    }
  }

  function shipTransfer(transferId) {
    if (pageData && pageData.urls.transfersShip) {
      const url = pageData.urls.transfersShip.replace('__TRANSFER_ID__', transferId);
      
      $.post(url)
        .done(function (response) {
          if (response.status === 'success') {
            Swal.fire('Shipped!', response.data.message || 'Transfer shipped successfully.', 'success');
            $('.datatables-transfers').DataTable().ajax.reload();
          } else {
            Swal.fire('Error!', response.data || 'Failed to ship transfer.', 'error');
          }
        })
        .fail(function () {
          Swal.fire('Error!', 'Failed to ship transfer.', 'error');
        });
    }
  }

  function receiveTransfer(transferId) {
    if (pageData && pageData.urls.transfersReceive) {
      const url = pageData.urls.transfersReceive.replace('__TRANSFER_ID__', transferId);
      
      $.post(url)
        .done(function (response) {
          if (response.status === 'success') {
            Swal.fire('Completed!', response.data.message || 'Transfer completed successfully.', 'success');
            $('.datatables-transfers').DataTable().ajax.reload();
          } else {
            Swal.fire('Error!', response.data || 'Failed to complete transfer.', 'error');
          }
        })
        .fail(function () {
          Swal.fire('Error!', 'Failed to complete transfer.', 'error');
        });
    }
  }
});