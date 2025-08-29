/**
 * WMS Inventory Products JavaScript
 */
$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  let dt_products = $('#products-table');
  if (dt_products.length && typeof pageData !== 'undefined') {
    let dataTable = dt_products.DataTable({
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
        { data: 'id' },
        { data: 'name' },
        { data: 'sku' },
        { data: 'barcode' },
        { data: 'category' },
        { data: 'unit' },
        { data: 'stock' },
        { data: 'cost_price' },
        { data: 'selling_price' },
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
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });

    // Filter functionality
    $('#category-filter, #warehouse-filter').on('change', function () {
      dataTable.draw();
    });

    // Initialize Select2
    $('.select2').select2();
  }

  // Handle product actions
  $(document).on('click', '.btn-view', function () {
    const productId = $(this).data('id');
    if (pageData && pageData.urls.productsShow) {
      const url = pageData.urls.productsShow.replace('__PRODUCT_ID__', productId);
      window.location.href = url;
    }
  });

  $(document).on('click', '.btn-edit', function () {
    const productId = $(this).data('id');
    if (pageData && pageData.urls.productsEdit) {
      const url = pageData.urls.productsEdit.replace('__PRODUCT_ID__', productId);
      window.location.href = url;
    }
  });

  $(document).on('click', '.btn-delete', function () {
    const productId = $(this).data('id');
    const productName = $(this).data('name');

    Swal.fire({
      title: pageData.labels.areYouSure,
      text: `You are about to delete "${productName}". This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: pageData.labels.yesDeleteIt
    }).then((result) => {
      if (result.isConfirmed) {
        deleteProduct(productId);
      }
    });
  });

  /**
   * Delete product
   */
  function deleteProduct(productId) {
    if (pageData && pageData.urls.productsDelete) {
      const url = pageData.urls.productsDelete.replace('__PRODUCT_ID__', productId);
      
      $.post(url, {
        _method: 'DELETE'
      })
      .done(function (response) {
        if (response.status === 'success') {
          Swal.fire(pageData.labels.deleted, response.data.message || pageData.labels.productDeletedSuccessfully, 'success');
          $('#products-table').DataTable().ajax.reload();
        } else {
          Swal.fire(pageData.labels.error, response.data || pageData.labels.failedToDeleteProduct, 'error');
        }
      })
      .fail(function () {
        Swal.fire(pageData.labels.error, pageData.labels.failedToDeleteProduct, 'error');
      });
    }
  }
});