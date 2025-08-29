/**
 * WMS Inventory Warehouses JavaScript
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
  let dt_warehouses = $('.datatables-warehouses');
  if (dt_warehouses.length && typeof pageData !== 'undefined') {
    let dataTable = dt_warehouses.DataTable({
      processing: true,
      serverSide: true,
      ajax: pageData.urls.warehousesData,
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'code' },
        { data: 'address' },
        { data: 'contact_person' },
        { data: 'contact_email' },
        { data: 'contact_phone' },
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
  }

  // Handle warehouse actions
  $(document).on('click', '.btn-view', function () {
    const warehouseId = $(this).data('id');
    // Handle view action
  });

  $(document).on('click', '.btn-edit', function () {
    const warehouseId = $(this).data('id');
    if (pageData && pageData.urls.warehousesEdit) {
      const url = pageData.urls.warehousesEdit.replace('__WAREHOUSE_ID__', warehouseId);
      window.location.href = url;
    }
  });

  $(document).on('click', '.btn-delete', function () {
    const warehouseId = $(this).data('id');
    const warehouseName = $(this).data('name');

    Swal.fire({
      title: pageData.labels.areYouSure,
      text: `You are about to delete "${warehouseName}". This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: pageData.labels.yesDeleteIt
    }).then((result) => {
      if (result.isConfirmed) {
        deleteWarehouse(warehouseId);
      }
    });
  });

  function deleteWarehouse(warehouseId) {
    if (pageData && pageData.urls.warehousesDelete) {
      const url = pageData.urls.warehousesDelete.replace('__WAREHOUSE_ID__', warehouseId);
      
      $.post(url, {
        _method: 'DELETE'
      })
      .done(function (response) {
        if (response.status === 'success') {
          Swal.fire(pageData.labels.deleted, response.data.message || pageData.labels.warehouseDeletedSuccessfully, 'success');
          $('.datatables-warehouses').DataTable().ajax.reload();
        } else {
          Swal.fire(pageData.labels.error, response.data || pageData.labels.failedToDeleteWarehouse, 'error');
        }
      })
      .fail(function () {
        Swal.fire(pageData.labels.error, pageData.labels.failedToDeleteWarehouse, 'error');
      });
    }
  }
});