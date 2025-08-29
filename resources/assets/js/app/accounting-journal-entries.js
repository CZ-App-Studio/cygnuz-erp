$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize variables
  let dt_journal_entries = $('.datatables-journal-entries'),
    reverseEntryModal = $('#reverseEntryModal'),
    reverseEntryForm = $('#reverseEntryForm'),
    currentEntryId = null;

  // Initialize Flatpickr for date filters
  $('#filter-date-from, #filter-date-to').flatpickr({
    dateFormat: 'Y-m-d',
    allowInput: true
  });

  // Initialize DataTable
  if (dt_journal_entries.length) {
    var dt_entries = dt_journal_entries.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.entryData,
        data: function (d) {
          d.status = $('#filter-status').val();
          d.date_from = $('#filter-date-from').val();
          d.date_to = $('#filter-date-to').val();
        }
      },
      columns: [
        { data: 'entry_number_display', name: 'entry_number', orderable: true },
        { data: 'entry_date_formatted', name: 'entry_date', orderable: true },
        { data: 'description', name: 'description', orderable: true },
        { data: 'total_debit_formatted', name: 'total_debit', orderable: true, className: 'text-end' },
        { data: 'total_credit_formatted', name: 'total_credit', orderable: true, className: 'text-end' },
        { data: 'status_display', name: 'is_posted', orderable: true },
        { data: 'balance_status', name: 'balance_status', orderable: false },
        { data: 'created_by_name', name: 'createdBy.name', orderable: true },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[1, 'desc']], // Order by date descending
      dom: '<"row mx-1"<"col-sm-12 col-md-3" l><"col-sm-12 col-md-9"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-md-end justify-content-center flex-wrap me-1"<"me-3"f>>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Journal Entries...'
      },
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['entry_number'];
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
                    ':</td> ' +
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
  }

  // Filter functionality
  $('#btn-apply-filters').on('click', function () {
    dt_entries.ajax.reload();
  });

  // Reset filters
  $('#btn-reset-filters').on('click', function () {
    $('#filter-status, #filter-date-from, #filter-date-to').val('').trigger('change');
    dt_entries.ajax.reload();
  });

  // Post journal entry
  $(document).on('click', '.btn-post-entry', function () {
    const entryId = $(this).data('id');

    Swal.fire({
      title: 'Confirm Post',
      text: pageData.text?.confirmPost || 'Are you sure you want to post this journal entry?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, post it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.entryPost.replace('__ID__', entryId),
          method: 'POST',
          success: function (response) {
            if (response.success || response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Posted!',
                text: response.data || pageData.text?.posted || 'Journal entry posted successfully!',
                timer: 2000,
                showConfirmButton: false,
                customClass: {
                  confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
              });

              dt_entries.ajax.reload();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response.data || response.message || pageData.text?.error || 'An error occurred. Please try again.',
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              });
            }
          },
          error: function (xhr) {
            let errorMessage = pageData.text?.error || 'An error occurred. Please try again.';
            if (xhr.responseJSON) {
              errorMessage = xhr.responseJSON.data || xhr.responseJSON.message || errorMessage;
            }
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
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

  // Reverse journal entry
  $(document).on('click', '.btn-reverse-entry', function () {
    currentEntryId = $(this).data('id');
    reverseEntryModal.modal('show');
  });

  // Handle reverse entry form submission
  reverseEntryForm.on('submit', function (e) {
    e.preventDefault();

    // Clear previous validation errors
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    const formData = new FormData(this);
    const submitBtn = $(this).find('[type="submit"]');
    const originalBtnText = submitBtn.text();

    // Show loading state
    submitBtn.prop('disabled', true).text(pageData.text?.processing || 'Processing...');

    $.ajax({
      url: pageData.urls.entryReverse.replace('__ID__', currentEntryId),
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success || response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Reversed!',
            text: response.data || pageData.text?.reversed || 'Journal entry reversed successfully!',
            timer: 3000,
            showConfirmButton: false,
            customClass: {
              confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
          });

          dt_entries.ajax.reload();
          reverseEntryModal.modal('hide');
          reverseEntryForm[0].reset();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: response.data || response.message || pageData.text?.error || 'An error occurred. Please try again.',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        }
      },
      error: function (xhr) {
        if (xhr.status === 422) {
          // Validation errors
          let errorData = xhr.responseJSON;
          if (errorData.errors) {
            $.each(errorData.errors, function (field, messages) {
              const input = $('[name="' + field + '"]');
              input.addClass('is-invalid');
              const message = Array.isArray(messages) ? messages[0] : messages;
              input.next('.invalid-feedback').text(message);
            });
          }
        } else {
          let errorMessage = pageData.text?.error || 'An error occurred. Please try again.';
          if (xhr.responseJSON) {
            errorMessage = xhr.responseJSON.data || xhr.responseJSON.message || errorMessage;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        }
      },
      complete: function () {
        // Restore button state
        submitBtn.prop('disabled', false).text(originalBtnText);
      }
    });
  });

  // Delete journal entry
  $(document).on('click', '.btn-delete-entry', function () {
    const entryId = $(this).data('id');

    Swal.fire({
      title: 'Are you sure?',
      text: pageData.text?.confirmDelete || 'Are you sure you want to delete this journal entry?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.entryDelete.replace('__ID__', entryId),
          method: 'DELETE',
          success: function (response) {
            if (response.success || response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: response.data || pageData.text?.deleted || 'Journal entry deleted successfully!',
                timer: 2000,
                showConfirmButton: false,
                customClass: {
                  confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
              });

              dt_entries.ajax.reload();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response.data || response.message || pageData.text?.error || 'An error occurred. Please try again.',
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              });
            }
          },
          error: function (xhr) {
            let errorMessage = pageData.text?.error || 'An error occurred. Please try again.';
            if (xhr.responseJSON) {
              errorMessage = xhr.responseJSON.data || xhr.responseJSON.message || errorMessage;
            }
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
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

  // Reset form when modal is hidden
  reverseEntryModal.on('hidden.bs.modal', function () {
    reverseEntryForm[0].reset();
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    currentEntryId = null;
  });
});
