/**
 * Page: WMS & Inventory Transfers
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

  // DataTable initialization
  let dt_transfers_table = $('.datatables-transfers');
  
  if (dt_transfers_table.length) {
    const dt_transfers = dt_transfers_table.DataTable({
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
        { data: 'id', name: 'id' },
        { data: 'date', name: 'date' },
        { data: 'reference', name: 'reference' },
        { data: 'source_warehouse', name: 'sourceWarehouse.name', orderable: false },
        { data: 'destination_warehouse', name: 'destinationWarehouse.name', orderable: false },
        { data: 'status', name: 'status' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-primary dropdown-toggle me-2',
          text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-1"></i> Print',
              className: 'dropdown-item'
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-spreadsheet me-1"></i> CSV',
              className: 'dropdown-item'
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-1"></i> Excel',
              className: 'dropdown-item'
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-description me-1"></i> PDF',
              className: 'dropdown-item'
            }
          ]
        }
      ]
    });

    // Filter form control to default size
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');

    // Apply filters on change
    $('#source-warehouse-filter, #destination-warehouse-filter, #status-filter, #date-range').on('change', function () {
      if (typeof dt_transfers !== 'undefined') {
        dt_transfers.ajax.reload();
      }
    });
  }

  // Initialize Flatpickr for date range
  if (document.querySelector('#date-range')) {
    $('#date-range').flatpickr({
      mode: 'range',
      dateFormat: 'Y-m-d',
      onClose: function() {
        if (typeof dt_transfers !== 'undefined') {
          dt_transfers.ajax.reload();
        }
      }
    });
  }

  // Global function for approving records
  window.approveRecord = function(id) {
    const approveUrl = pageData.urls.transfersApprove.replace('__TRANSFER_ID__', id);

    Swal.fire({
      title: pageData.labels?.confirmApprove || 'Confirm Approval',
      text: pageData.labels?.confirmApproveText || 'Are you sure you want to approve this transfer?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels?.confirmApproveButton || 'Approve',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Create a form and submit it
        const form = $('<form>', {
          'method': 'POST',
          'action': approveUrl
        });
        
        form.append($('<input>', {
          'type': 'hidden',
          'name': '_token',
          'value': $('meta[name="csrf-token"]').attr('content')
        }));
        
        form.append($('<input>', {
          'type': 'hidden',
          'name': '_method',
          'value': 'POST'
        }));
        
        $('body').append(form);
        form.submit();
      }
    });
  };

  // Global function for shipping records
  window.shipRecord = function(id) {
    const shipUrl = pageData.urls.transfersShip.replace('__TRANSFER_ID__', id);

    Swal.fire({
      title: pageData.labels?.shipTransfer || 'Ship Transfer',
      html: `
        <div class="mb-3">
          <label for="ship_date" class="form-label">Ship Date</label>
          <input type="date" id="ship_date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
        </div>
        <div class="mb-3">
          <label for="shipping_notes" class="form-label">Shipping Notes</label>
          <textarea id="shipping_notes" class="form-control" rows="3" placeholder="Enter shipping notes (optional)"></textarea>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ship Now',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false,
      preConfirm: () => {
        const shipDate = document.getElementById('ship_date').value;
        const shippingNotes = document.getElementById('shipping_notes').value;
        
        if (!shipDate) {
          Swal.showValidationMessage('Ship date is required');
          return false;
        }
        
        return {
          actual_ship_date: shipDate,
          shipping_notes: shippingNotes
        };
      }
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          url: shipUrl,
          type: 'POST',
          data: result.value,
          success: function (response) {
            // Reload DataTable if it exists (index page), otherwise reload page
            if (typeof dt_transfers !== 'undefined') {
              dt_transfers.ajax.reload();
            } else {
              location.reload();
            }
            Swal.fire({
              icon: 'success',
              title: pageData.labels?.shipped || 'Success',
              text: response.message || 'Transfer has been shipped successfully.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || 'Could not ship transfer.';
            Swal.fire({
              title: pageData.labels.error,
              text: errorMessage,
              icon: 'error',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  };

  // Global function for receiving records
  window.receiveRecord = function(id) {
    const receiveUrl = pageData.urls.transfersReceive.replace('__TRANSFER_ID__', id);

    Swal.fire({
      title: pageData.labels?.receiveTransfer || 'Receive Transfer',
      html: `
        <div class="mb-3">
          <label for="arrival_date" class="form-label">Arrival Date</label>
          <input type="date" id="arrival_date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
        </div>
        <div class="mb-3">
          <label for="receiving_notes" class="form-label">Receiving Notes</label>
          <textarea id="receiving_notes" class="form-control" rows="3" placeholder="Enter receiving notes (optional)"></textarea>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Confirm Receipt',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-success me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false,
      preConfirm: () => {
        const arrivalDate = document.getElementById('arrival_date').value;
        const receivingNotes = document.getElementById('receiving_notes').value;
        
        if (!arrivalDate) {
          Swal.showValidationMessage('Arrival date is required');
          return false;
        }
        
        return {
          actual_arrival_date: arrivalDate,
          receiving_notes: receivingNotes
        };
      }
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          url: receiveUrl,
          type: 'POST',
          data: result.value,
          success: function (response) {
            // Reload DataTable if it exists (index page), otherwise reload page
            if (typeof dt_transfers !== 'undefined') {
              dt_transfers.ajax.reload();
            } else {
              location.reload();
            }
            Swal.fire({
              icon: 'success',
              title: pageData.labels.received,
              text: response.message || 'Transfer has been received successfully.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || 'Could not receive transfer.';
            Swal.fire({
              title: pageData.labels.error,
              text: errorMessage,
              icon: 'error',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  };

  // Global function for cancelling records
  window.cancelRecord = function(id) {
    const cancelUrl = pageData.urls.transfersCancel.replace('__TRANSFER_ID__', id);

    Swal.fire({
      title: pageData.labels.cancelTransfer,
      html: `
        <div class="mb-3">
          <label for="cancellation_reason" class="form-label">Cancellation Reason *</label>
          <textarea id="cancellation_reason" class="form-control" rows="3" placeholder="Enter reason for cancellation" required></textarea>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Cancel Transfer',
      cancelButtonText: 'Keep Transfer',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false,
      preConfirm: () => {
        const cancellationReason = document.getElementById('cancellation_reason').value;
        
        if (!cancellationReason.trim()) {
          Swal.showValidationMessage('Cancellation reason is required');
          return false;
        }
        
        return {
          cancellation_reason: cancellationReason
        };
      }
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          url: cancelUrl,
          type: 'POST',
          data: result.value,
          success: function (response) {
            // Reload DataTable if it exists (index page), otherwise reload page
            if (typeof dt_transfers !== 'undefined') {
              dt_transfers.ajax.reload();
            } else {
              location.reload();
            }
            Swal.fire({
              icon: 'success',
              title: pageData.labels.cancelled,
              text: response.message || 'Transfer has been cancelled successfully.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || 'Could not cancel transfer.';
            Swal.fire({
              title: pageData.labels.error,
              text: errorMessage,
              icon: 'error',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  };

  // Global function for deleting records
  window.deleteRecord = function(id) {

    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.confirmDeleteText,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.confirmDeleteButton,
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Delete the transfer
        $.ajax({
          url: pageData.urls.transfersDelete.replace('__TRANSFER_ID__', id),
          type: 'DELETE',
          success: function (response) {
            // Reload DataTable if it exists (index page), otherwise redirect to index
            if (typeof dt_transfers !== 'undefined') {
              dt_transfers.ajax.reload();
            } else {
              // If not on index page, redirect to index after delete
              window.location.href = pageData.urls.transfersIndex || '/wmsinventorycore/transfers';
            }
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: response.message || 'Transfer has been deleted.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || 'Could not delete transfer.';
            if (error.responseJSON?.errors) {
              errorMessage = Object.values(error.responseJSON.errors).flat().join('<br>');
            }

            Swal.fire({
              title: pageData.labels.error,
              html: errorMessage,
              icon: 'error',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  };

  // Event handlers for ship, cancel, and print buttons
  $(document).on('click', '.ship-record', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (typeof window.shipRecord === 'function') {
      window.shipRecord(id);
    }
  });

  $(document).on('click', '.cancel-record', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (typeof window.cancelRecord === 'function') {
      window.cancelRecord(id);
    }
  });

  $(document).on('click', '.receive-record', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (typeof window.receiveRecord === 'function') {
      window.receiveRecord(id);
    }
  });

  // Print button handler
  $(document).on('click', '#print-transfer', function(e) {
    e.preventDefault();
    const transferId = $(this).data('id');
    
    if (pageData.urls.transfersPrint) {
      const printUrl = pageData.urls.transfersPrint.replace('__TRANSFER_ID__', transferId);
      // Open print page in new window
      window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes');
    } else {
      // Fallback: use browser print for current page
      window.print();
    }
  });
});
