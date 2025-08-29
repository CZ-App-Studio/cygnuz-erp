/**
 * Page: WMS & Inventory Warehouses
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
  let dt_warehouses_table = $('.datatables-warehouses');
  
  if (dt_warehouses_table.length) {
    const dt_warehouses = dt_warehouses_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.warehousesData
      },
      columns: [
        { data: 'id', name: 'id', title: 'ID' },
        { data: 'name', name: 'name', title: 'Name' },
        { data: 'code', name: 'code', title: 'Code' },
        { data: 'address', name: 'address', title: 'Address' },
        { data: 'contact_name', name: 'contact_name', title: 'Contact Person' },
        { data: 'contact_email', name: 'contact_email', title: 'Contact Email' },
        { data: 'contact_phone', name: 'contact_phone', title: 'Contact Phone' },
        { 
          data: 'is_active', 
          name: 'is_active', 
          title: 'Status',
          render: function (data, type, row) {
            const statusClass = row.is_active ? 'success' : 'secondary';
            const statusText = row.is_active ? 'Active' : 'Inactive';
            return `<span class="badge bg-label-${statusClass}">${statusText}</span>`;
          }
        },
        { 
          data: 'actions', 
          name: 'actions', 
          title: 'Actions',
          orderable: false,
          searchable: false,
          render: function(data, type, row) {
            return `
              <div class="d-inline-block">
                <a href="javascript:;" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </a>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="${pageData.urls.warehousesShow.replace('__WAREHOUSE_ID__', row.id)}">
                    <i class="bx bx-show me-1"></i> View
                  </a>
                  <a class="dropdown-item" href="${pageData.urls.warehousesEdit.replace('__WAREHOUSE_ID__', row.id)}">
                    <i class="bx bx-edit-alt me-1"></i> Edit
                  </a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item text-danger delete-record" href="javascript:;" data-id="${row.id}">
                    <i class="bx bx-trash me-1"></i> Delete
                  </a>
                </div>
              </div>
            `;
          }
        }
      ],
      order: [[0, 'desc']],
      pageLength: 10,
      lengthMenu: [10, 25, 50, 75, 100],
      responsive: true,
      language: {
        processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
        emptyTable: 'No warehouses found',
        zeroRecords: 'No matching warehouses found'
      }
    });
  }

  // Delete Record
  $(document).on('click', '.delete-record', function () {
    const id = $(this).data('id');

    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Delete the warehouse
        $.ajax({
          url: pageData.urls.warehousesDelete.replace('__WAREHOUSE_ID__', id),
          type: 'DELETE',
          success: function (response) {
            dt_warehouses.ajax.reload();
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message || 'Warehouse has been deleted.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || 'Could not delete warehouse.';
            if (error.responseJSON?.errors) {
              errorMessage = Object.values(error.responseJSON.errors).flat().join('<br>');
            }

            Swal.fire({
              title: 'Error!',
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
