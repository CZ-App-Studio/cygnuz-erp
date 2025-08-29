/**
 * Page: WMS & Inventory Warehouse Details
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

  // Initialize DataTable for warehouse inventory
  let dt_warehouse_inventory = $('.datatable-warehouse-inventory');
  
  if (dt_warehouse_inventory.length) {
    dt_warehouse_inventory.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.warehouseInventoryData
      },
      columns: [
        { data: 'product_code' },
        { data: 'product_name' },
        { data: 'category' },
        { data: 'stock_level' },
        { data: 'min_stock_level' },
        { data: 'max_stock_level' },
        { data: 'status' }
      ],
      columnDefs: [
        {
          targets: 6,
          render: function (data, type, full, meta) {
            // Calculate status based on stock levels
            let statusClass = 'bg-label-success';
            let statusText = pageData.labels.normal;
            
            if (full.stock_level <= full.min_stock_level) {
              statusClass = 'bg-label-danger';
              statusText = pageData.labels.lowStock;
            } else if (full.stock_level >= full.max_stock_level) {
              statusClass = 'bg-label-warning';
              statusText = pageData.labels.overstocked;
            }
            
            return `<span class="badge ${statusClass}">${statusText}</span>`;
          }
        }
      ],
      order: [[0, 'asc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100]
    });

    // Filter form control to default size
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }

  // Handle warehouse deletion
  $('.delete-warehouse').on('click', function () {
    const warehouseId = $(this).data('id');

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
        $.ajax({
          url: pageData.urls.warehousesDelete,
          type: 'DELETE',
          success: function (response) {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: response.message || pageData.labels.deletedText,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(function() {
              window.location.href = pageData.urls.warehousesIndex;
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || pageData.labels.couldNotDelete;
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
  });
});
