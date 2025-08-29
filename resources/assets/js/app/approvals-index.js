/**
 * Approvals Index
 */

'use strict';

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Approvals datatable
  if ($('#approvalsTable').length) {
    var dt_approvals = $('#approvalsTable').DataTable({
      dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-primary dropdown-toggle me-2',
          text: '<i class="bx bx-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
          buttons: [
            {
              extend: 'print',
              text: '<i class="bx bx-printer me-1" ></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (data, row, column, node) {
                    // Strip HTML tags for print view
                    return column === 1 || column === 2
                      ? data.replace(/<[^>]*>/g, '')
                      : data;
                  }
                }
              }
            },
            {
              extend: 'csv',
              text: '<i class="bx bx-file me-1" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (data, row, column, node) {
                    // Strip HTML tags for CSV
                    return data.replace(/<[^>]*>/g, '');
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="bx bx-file me-1" ></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (data, row, column, node) {
                    // Strip HTML tags for Excel
                    return data.replace(/<[^>]*>/g, '');
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="bx bx-file me-1" ></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (data, row, column, node) {
                    // Strip HTML tags for PDF
                    return data.replace(/<[^>]*>/g, '');
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="bx bx-copy me-1" ></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (data, row, column, node) {
                    // Strip HTML tags for copy
                    return data.replace(/<[^>]*>/g, '');
                  }
                }
              }
            }
          ]
        }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of request #' + data[0];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
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
      },
      language: {
        search: '',
        searchPlaceholder: 'Search approvals...',
        lengthMenu: '_MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          first: '«',
          previous: '‹',
          next: '›',
          last: '»'
        },
        loadingRecords: 'Loading...',
        zeroRecords: 'No matching records found',
        emptyTable: 'No pending approvals available',
        aria: {
          sortAscending: ': activate to sort column ascending',
          sortDescending: ': activate to sort column descending'
        }
      }
    });

    // Set header label text
    $('.head-label').html('<h5 class="card-title mb-0">Pending Approvals</h5>');

    // Filter by type
    $('#typeFilter').on('change', function () {
      dt_approvals.column(1).search($(this).val()).draw();
    });

    // Filter by date
    $('#dateFilter').on('change', function () {
      // Format date to match the format in the table
      if ($(this).val()) {
        const date = new Date($(this).val());
        const formattedDate = date.toLocaleDateString('en-US', {
          day: 'numeric',
          month: 'short',
          year: 'numeric'
        });
        dt_approvals.column(3).search(formattedDate).draw();
      } else {
        dt_approvals.column(3).search('').draw();
      }
    });

    // Initialize Select2
    if ($('.select2').length) {
      $('.select2').select2();
    }
  }
});
