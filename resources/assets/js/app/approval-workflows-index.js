/**
 * Approval Workflows Index
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

  // Workflows datatable
  if ($('#workflowsTable').length) {
    var dt_workflows = $('#workflowsTable').DataTable({
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
                    return column === 2 || column === 3 || column === 5
                      ? data.replace(/<[^>]*>/g, '')
                      : data;
                  }
                }
              },
              customize: function (win) {
                // Customize print view
                $(win.document.body)
                  .css('color', headingColor)
                  .css('border-color', borderColor)
                  .css('background-color', bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
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
              return 'Details of ' + data[1]; // Workflow name
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
        searchPlaceholder: 'Search workflows...',
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
        emptyTable: 'No approval workflows available',
        aria: {
          sortAscending: ': activate to sort column ascending',
          sortDescending: ': activate to sort column descending'
        }
      }
    });

    // Set header label text
    $('.head-label').html('<h5 class="card-title mb-0">Approval Workflows</h5>');

    // Filter by type
    $('#typeFilter').on('change', function () {
      dt_workflows.column(3).search($(this).val()).draw();
    });

    // Filter by status
    $('#statusFilter').on('change', function () {
      if ($(this).val() === '1') {
        dt_workflows.column(5).search('Active').draw();
      } else if ($(this).val() === '0') {
        dt_workflows.column(5).search('Inactive').draw();
      } else {
        dt_workflows.column(5).search('').draw();
      }
    });

    // Delete confirmation
    $(document).on('submit', '.delete-form', function (e) {
      e.preventDefault();
      const form = this;

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
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  }
});
