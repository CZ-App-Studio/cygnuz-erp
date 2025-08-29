$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  let dt_warnings_table = $('.datatables-my-warnings');

  // Initialize DataTable
  if (dt_warnings_table.length) {
    let dt_warnings = dt_warnings_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatable,
        data: function (d) {
          d.status = $('#filter-status').val();
        }
      },
      columns: [
        { data: 'warning_info', name: 'warning_info' },
        { data: 'dates', name: 'dates' },
        { data: 'status_info', name: 'status_info' },
        { data: 'issued_by', name: 'issued_by' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[1, 'desc']],
      dom: '<"row mx-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['warning'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.columnIndex !== 6
                ? '<tr data-dt-row="' +
                    col.rowIdx +
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
      },
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search..'
      }
    });
  }

  // Filter functionality
  $('#filter-status').on('change', function () {
    dt_warnings_table.DataTable().ajax.reload();
  });

  // Reset filters
  $('#reset-filters').on('click', function () {
    $('#filter-status').val('').trigger('change');
    dt_warnings_table.DataTable().ajax.reload();
  });

  // Load stats
  loadWarningStats();

  // Actions
  $(document).on('click', '.acknowledge-warning', function (e) {
    e.preventDefault();
    const warningId = $(this).data('id');
    acknowledgeWarning(warningId);
  });

  $(document).on('click', '.appeal-warning', function (e) {
    e.preventDefault();
    const warningId = $(this).data('id');
    window.location.href = pageData.urls.appeal.replace(':id', warningId);
  });

  $(document).on('click', '.download-letter', function (e) {
    e.preventDefault();
    const warningId = $(this).data('id');
    window.open(pageData.urls.downloadLetter.replace(':id', warningId), '_blank');
  });

  // Load warning stats
  function loadWarningStats() {
    // This would be implemented to load user-specific warning stats
    // For now, just set to 0
    $('#active-warnings').text('0');
    $('#acknowledged-warnings').text('0');
    $('#appealed-warnings').text('0');
  }

  // Acknowledge warning function
  function acknowledgeWarning(warningId) {
    Swal.fire({
      title: 'Acknowledge Warning',
      text: 'Are you sure you want to acknowledge this warning?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Acknowledge'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.acknowledge.replace(':id', warningId),
          method: 'POST',
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false
              });
             dt_warnings_table.DataTable().ajax.reload();
              loadWarningStats();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.data
              });
            }
          },
          error: function (xhr) {
            let errorMessage = 'An error occurred. Please try again.';

            if (xhr.responseJSON && xhr.responseJSON.data) {
              errorMessage = xhr.responseJSON.data;
            }

            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: errorMessage
            });
          }
        });
      }
    });
  }
});
