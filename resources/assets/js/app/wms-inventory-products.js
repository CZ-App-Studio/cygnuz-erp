/**
 * Page: WMS & Inventory Products
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
  let dt_products_table = $('#products-table');
  
  if (dt_products_table.length) {
    const dt_products = dt_products_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.productsData,
        data: function (d) {
          d.category_id = $('#category-filter').val();
          d.warehouse_id = $('#warehouse-filter').val();
        }
      },
      columns: [
        { data: 'id', name: 'id', title: 'ID' },
        { data: 'name', name: 'name', title: 'Name' },
        { data: 'sku', name: 'sku', title: 'SKU' },
        { data: 'barcode', name: 'barcode', title: 'Barcode' },
        { data: 'category_name', name: 'category_name', title: 'Category', orderable: false },
        { data: 'unit_name', name: 'unit_name', title: 'Unit', orderable: false },
        { data: 'stock', name: 'stock', title: 'Stock', orderable: false },
        { 
          data: 'cost_price', 
          name: 'cost_price', 
          title: 'Cost Price',
          render: function(data) {
            return formatCurrency(data);
          }
        },
        { 
          data: 'selling_price', 
          name: 'selling_price', 
          title: 'Selling Price',
          render: function(data) {
            return formatCurrency(data);
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
                  <a class="dropdown-item" href="${pageData.urls.productsShow.replace('__PRODUCT_ID__', row.id)}">
                    <i class="bx bx-show me-1"></i> View
                  </a>
                  <a class="dropdown-item" href="${pageData.urls.productsEdit.replace('__PRODUCT_ID__', row.id)}">
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
        emptyTable: 'No products found',
        zeroRecords: 'No matching products found'
      }
    });

    // Initialize Select2
    $('.select2').select2({
      allowClear: true
    });

    // Filter functionality - reload table when filters change
    $('#category-filter, #warehouse-filter').on('change', function () {
      dt_products.ajax.reload();
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
        // Delete the product
        $.ajax({
          url: pageData.urls.productsDelete.replace('__PRODUCT_ID__', id),
          type: 'DELETE',
          success: function () {
            dt_products.draw();
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Product has been deleted.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function (error) {
            Swal.fire({
              title: 'Error!',
              text: error.responseJSON.message || 'Could not delete product.',
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

  // Helper function to format currency
  function formatCurrency(value) {
    if (!value) return '$0.00';
    return '$' + parseFloat(value).toFixed(2);
  }
});
