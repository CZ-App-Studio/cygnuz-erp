$(function() {
  // Initialize DataTable
  const table = $('#leavesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function(d) {
        d.status = $('#filterStatus').val();
        d.leave_type_id = $('#filterLeaveType').val();
        d.date_from = $('#filterDateFrom').val();
        d.date_to = $('#filterDateTo').val();
      }
    },
    columns: [
      { data: 'created_at', name: 'created_at' },
      { data: 'leave_type', name: 'leave_type' },
      { data: 'from_date', name: 'from_date' },
      { data: 'to_date', name: 'to_date' },
      { data: 'total_days', name: 'total_days' },
      { data: 'status', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[0, 'desc']],
    dom: '<"card-header d-flex flex-wrap"<"me-5 ms-n2"f><"d-flex justify-content-start justify-content-md-end align-items-baseline gap-2"<"dt-action-buttons"B>l>>t<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    displayLength: 10,
    lengthMenu: [10, 25, 50, 100],
    buttons: []
  });

  // Apply filters
  $('#filterStatus, #filterLeaveType, #filterDateFrom, #filterDateTo').on('change', function() {
    table.ajax.reload();
  });
});

// View leave details
function viewMyLeave(id) {
  window.location.href = pageData.urls.show.replace('__ID__', id);
}

// Cancel leave request
function cancelMyLeave(id) {
  Swal.fire({
    title: pageData.labels.cancelTitle,
    text: pageData.labels.confirmCancel,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: pageData.labels.cancelButton,
    cancelButtonText: pageData.labels.cancelButtonText,
    customClass: {
      confirmButton: 'btn btn-danger me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: pageData.urls.cancel.replace('__ID__', id),
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.success,
              text: pageData.labels.cancelled,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            });
            $('#leavesTable').DataTable().ajax.reload();
          }
        },
        error: function(xhr) {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: xhr.responseJSON?.message || 'Failed to cancel leave request',
            customClass: {
              confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
          });
        }
      });
    }
  });
}

// Make functions available globally
window.viewMyLeave = viewMyLeave;
window.cancelMyLeave = cancelMyLeave;