/**
 * WMS Inventory Adjustments JavaScript
 */
$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable for adjustments
  let dt_adjustments = $('.datatables-adjustments');
  if (dt_adjustments.length && typeof pageData !== 'undefined') {
    let dataTable = dt_adjustments.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.adjustmentsData,
        data: function (d) {
          d.warehouse_id = $('#warehouse-filter').val();
          d.type_id = $('#type-filter').val();
          d.date_from = $('#date-from').val();
          d.date_to = $('#date-to').val();
        }
      },
      columns: [
        { data: 'id' },
        { data: 'date' },
        { data: 'code' },
        { data: 'warehouse' },
        { data: 'type' },
        { data: 'total' },
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
    $('#warehouse-filter, #type-filter, #date-from, #date-to').on('change', function () {
      dataTable.draw();
    });
  }

  // Handle adjustment actions
  $(document).on('click', '.btn-approve', function () {
    const adjustmentId = $(this).data('id');
    
    Swal.fire({
      title: 'Approve Adjustment?',
      text: 'This will finalize the adjustment and update inventory levels.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, approve it!'
    }).then((result) => {
      if (result.isConfirmed) {
        approveAdjustment(adjustmentId);
      }
    });
  });

  function approveAdjustment(adjustmentId) {
    if (pageData && pageData.urls.adjustmentsApprove) {
      const url = pageData.urls.adjustmentsApprove.replace('__ADJUSTMENT_ID__', adjustmentId);
      
      $.post(url)
        .done(function (response) {
          if (response.status === 'success') {
            Swal.fire('Approved!', response.data.message || 'Adjustment approved successfully.', 'success');
            $('.datatables-adjustments').DataTable().ajax.reload();
          } else {
            Swal.fire('Error!', response.data || 'Failed to approve adjustment.', 'error');
          }
        })
        .fail(function () {
          Swal.fire('Error!', 'Failed to approve adjustment.', 'error');
        });
    }
  }
});