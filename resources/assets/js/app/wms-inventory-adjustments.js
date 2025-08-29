/**
 * Page: WMS & Inventory Adjustments
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
  let dt_adjustments_table = $('.datatables-adjustments');
  
  if (dt_adjustments_table.length) {
    const dt_adjustments = dt_adjustments_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.adjustmentsData,
        data: function (d) {
          d.warehouse_id = $('#warehouse-filter').val();
          d.adjustment_type_id = $('#type-filter').val();
          d.date_from = $('#date-from').val();
          d.date_to = $('#date-to').val();
        }
      },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'date', name: 'date' },
        { data: 'code', name: 'code' },
        { data: 'warehouse', name: 'warehouse.name', orderable: false },
        { data: 'adjustment_type', name: 'adjustmentType.name', orderable: false },
        { data: 'total_amount', name: 'total_amount' },
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
    $('#warehouse-filter, #type-filter, #date-from, #date-to').on('change', function () {
      dt_adjustments.ajax.reload();
    });
  }

  // Global function for approving records
  window.approveRecord = function(id) {
    const approveUrl = pageData.urls.adjustmentsApprove.replace('__ADJUSTMENT_ID__', id);

    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: "This will approve the adjustment and update inventory levels!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, approve it!',
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
          'value': 'PATCH'
        }));
        
        $('body').append(form);
        form.submit();
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
        // Delete the adjustment
        $.ajax({
          url: pageData.urls.adjustmentsDelete.replace('__ADJUSTMENT_ID__', id),
          type: 'DELETE',
          success: function (response) {
            dt_adjustments.ajax.reload();
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: response.message || 'Adjustment has been deleted.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || 'Could not delete adjustment.';
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
});
