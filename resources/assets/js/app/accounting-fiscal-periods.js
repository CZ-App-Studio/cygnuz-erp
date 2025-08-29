$(function () {
  'use strict';

  // CSRF Token Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  const dtPeriods = $('.datatables-fiscal-periods');
  let periodsTable;

  if (dtPeriods.length) {
    periodsTable = dtPeriods.DataTable({
      ajax: {
        url: pageData.urls.periodsData,
        data: function (d) {
          d.period_type = $('#filter-period-type').val();
          d.is_active = $('#filter-status').val();
          d.is_closed = $('#filter-closure').val();
        }
      },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'name', name: 'name' },
        { data: 'period_type', name: 'period_type' },
        { data: 'formatted_date_range', name: 'formatted_date_range', orderable: false },
        { data: 'is_active', name: 'is_active' },
        { data: 'is_closed', name: 'is_closed' },
        { data: 'created_by', name: 'created_by' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        }
      }
    });
  }

  // Apply filters
  $('#apply-filters').on('click', function() {
    if (periodsTable) {
      periodsTable.ajax.reload();
    }
  });

  // Clear filters
  $('#clear-filters').on('click', function() {
    $('#filter-period-type, #filter-status, #filter-closure').val('');
    if (periodsTable) {
      periodsTable.ajax.reload();
    }
  });

  // Initialize Flatpickr for date inputs
  $('.flatpickr-date').flatpickr({
    dateFormat: 'Y-m-d'
  });

  // Add Period Form
  $('#addPeriodForm').on('submit', function(e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();

    // Show loading
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

    // Clear previous errors
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();

    const url = form.data('period-id') ?
      pageData.urls.periodsUpdate.replace('__ID__', form.data('period-id')) :
      pageData.urls.periodsStore;

    const method = form.data('period-id') ? 'PUT' : 'POST';

    $.ajax({
      url: url,
      type: method,
      data: form.serialize(),
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message,
          customClass: {
            confirmButton: 'btn btn-success'
          },
          buttonsStyling: false,
          timer: 1500,
          timerProgressBar: true
        });

        // Reset form and close offcanvas
        form[0].reset();
        form.removeData('period-id');
        $('#addPeriodOffcanvasLabel').text('Add New Fiscal Period');
        $('#addPeriodOffcanvas').offcanvas('hide');

        // Reload table
        if (periodsTable) {
          periodsTable.ajax.reload();
        }
      },
      error: function(xhr) {
        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON.errors;
          Object.keys(errors).forEach(function(field) {
            const input = form.find(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
          });
        } else {
          // Other errors
          let errorMessage = 'An error occurred while saving the fiscal period.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }

          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
          });
        }
      },
      complete: function() {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Edit Period
  $(document).on('click', '.edit-period', function() {
    const periodId = $(this).data('id');

    $.ajax({
      url: pageData.urls.periodsShow.replace('__ID__', periodId),
      type: 'GET',
      success: function(response) {
        const period = response.period;

        // Populate form
        const form = $('#addPeriodForm');
        form.find('[name="name"]').val(period.name);
        form.find('[name="period_type"]').val(period.period_type);
        form.find('[name="start_date"]').val(period.start_date);
        form.find('[name="end_date"]').val(period.end_date);
        form.find('[name="description"]').val(period.description);

        // Set form for editing
        form.data('period-id', periodId);
        $('#addPeriodOffcanvasLabel').text('Edit Fiscal Period');

        // Show offcanvas
        $('#addPeriodOffcanvas').offcanvas('show');
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: 'Failed to load fiscal period data.',
          customClass: {
            confirmButton: 'btn btn-danger'
          },
          buttonsStyling: false
        });
      }
    });
  });

  // Delete Period
  $(document).on('click', '.delete-period', function() {
    const periodId = $(this).data('id');

    Swal.fire({
      title: 'Delete Fiscal Period?',
      text: 'This action cannot be undone. Are you sure you want to delete this fiscal period?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Delete',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.periodsDelete.replace('__ID__', periodId),
          type: 'DELETE',
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false,
              timer: 1500,
              timerProgressBar: true
            });

            if (periodsTable) {
              periodsTable.ajax.reload();
            }
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while deleting the fiscal period.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-danger'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Close Period
  $(document).on('click', '.close-period', function() {
    const periodId = $(this).data('id');

    Swal.fire({
      title: 'Close Fiscal Period?',
      text: 'Closing a fiscal period will prevent further modifications. Continue?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Close',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-warning',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.periodsClose.replace('__ID__', periodId),
          type: 'POST',
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Closed!',
              text: response.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false,
              timer: 1500,
              timerProgressBar: true
            });

            if (periodsTable) {
              periodsTable.ajax.reload();
            }
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while closing the fiscal period.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-danger'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Reopen Period
  $(document).on('click', '.reopen-period', function() {
    const periodId = $(this).data('id');

    Swal.fire({
      title: 'Reopen Fiscal Period?',
      text: 'Are you sure you want to reopen this fiscal period?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Reopen',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.periodsReopen.replace('__ID__', periodId),
          type: 'POST',
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Reopened!',
              text: response.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false,
              timer: 1500,
              timerProgressBar: true
            });

            if (periodsTable) {
              periodsTable.ajax.reload();
            }
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while reopening the fiscal period.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-danger'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Generate Periods Form
  $('#generatePeriodsForm').on('submit', function(e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();

    // Show loading
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Generating...');

    $.ajax({
      url: pageData.urls.periodsGenerate,
      type: 'POST',
      data: form.serialize(),
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message,
          customClass: {
            confirmButton: 'btn btn-success'
          },
          buttonsStyling: false,
          timer: 2000,
          timerProgressBar: true
        });

        // Reset form and close modal
        form[0].reset();
        $('#generatePeriodsModal').modal('hide');

        // Reload table
        if (periodsTable) {
          periodsTable.ajax.reload();
        }
      },
      error: function(xhr) {
        let errorMessage = 'An error occurred while generating fiscal periods.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: errorMessage,
          customClass: {
            confirmButton: 'btn btn-danger'
          },
          buttonsStyling: false
        });
      },
      complete: function() {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Reset offcanvas form when closed
  $('#addPeriodOffcanvas').on('hidden.bs.offcanvas', function() {
    const form = $('#addPeriodForm');
    form[0].reset();
    form.removeData('period-id');
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
    $('#addPeriodOffcanvasLabel').text('Add New Fiscal Period');
  });
});
