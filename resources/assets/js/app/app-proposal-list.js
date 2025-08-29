/**
 * App Proposal List (jQuery)
 */

'use strict';

$(function () {
  // Setup AJAX defaults with CSRF token
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Initialize Select2
  $('.select2').select2();

  // Initialize DataTable
  var dt_proposals_table = $('#proposals-table');

  if (dt_proposals_table.length) {
    var dt_proposals = dt_proposals_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.ajax,
        type: 'POST',
        data: function (d) {
          d.status = $('#status-filter').val();
          d.contact_id = $('#contact-filter').val();
        }
      },
      columns: [
        { data: 'proposal_number' },
        { data: 'customer' },
        { data: 'proposal_date' },
        { data: 'expiry_date' },
        { data: 'total' },
        { data: 'status' },
        { data: 'actions', orderable: false, searchable: false }
      ],
      columnDefs: [
        {
          targets: 0,
          render: function (data, type, full, meta) {
            return '<a href="/invoice/proposals/' + full.id + '" class="fw-medium">#' + data + '</a>';
          }
        }
      ],
      order: [[0, 'desc']],
      dom:
        '<"row mx-1"' +
        '<"col-sm-12 col-md-3" l>' +
        '<"col-sm-12 col-md-9"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-md-end justify-content-center flex-wrap me-1"<"me-3"f>B>>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        processing: pageData.labels.processing,
        search: pageData.labels.search + ':',
        lengthMenu: pageData.labels.show + ' _MENU_ ' + pageData.labels.entries,
        info: pageData.labels.showing + ' _START_ ' + pageData.labels.to + ' _END_ ' + pageData.labels.of + ' _TOTAL_ ' + pageData.labels.entries,
        infoEmpty: pageData.labels.showing + ' 0 ' + pageData.labels.to + ' 0 ' + pageData.labels.of + ' 0 ' + pageData.labels.entries,
        infoFiltered: '(filtered from _MAX_ total entries)',
        paginate: {
          first: pageData.labels.first,
          previous: pageData.labels.previous,
          next: pageData.labels.next,
          last: pageData.labels.last
        },
        zeroRecords: pageData.labels.noMatchingRecords,
        emptyTable: pageData.labels.noDataAvailable
      },
      buttons: [],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of Proposal #' + data.proposal_number;
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
      }
    });

    // Filter
    $('#status-filter, #contact-filter').on('change', function () {
      dt_proposals.draw();
    });

    // Contact filter with Select2 AJAX
    $('#contact-filter').select2({
      ajax: {
        url: pageData.urls.contactSearch,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            search: params.term
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data.data, function (item) {
              return {
                id: item.id,
                text: item.full_name
              };
            })
          };
        },
        cache: true
      },
      placeholder: 'Select Customer',
      allowClear: true
    });
  }

  // Delete Proposal
  $(document).on('click', '.delete-proposal', function (e) {
    e.preventDefault();
    var deleteUrl = $(this).data('url');

    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.confirmDeleteText,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.confirmButtonText,
      cancelButtonText: pageData.labels.cancelButtonText,
      customClass: {
        confirmButton: 'btn btn-primary me-2',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          url: deleteUrl,
          type: 'DELETE',
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.deleteSuccess,
                text: pageData.labels.deleteSuccessText,
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
              dt_proposals.ajax.reload();
            } else {
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: response.data || 'Something went wrong!',
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
            }
          },
          error: function () {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: 'Something went wrong!',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        });
      }
    });
  });
});