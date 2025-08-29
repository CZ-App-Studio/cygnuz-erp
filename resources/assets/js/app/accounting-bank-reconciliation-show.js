$(function () {
  'use strict';

  // CSRF Token Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable for reconciliation items
  const dtItems = $('.datatables-reconciliation-items');
  let itemsTable;

  if (dtItems.length) {
    itemsTable = dtItems.DataTable({
      ajax: {
        url: pageData.urls.itemsData,
        type: 'GET'
      },
      columns: [
        { data: 'transaction_date', name: 'transaction_date' },
        { data: 'description', name: 'description' },
        { data: 'item_type', name: 'item_type' },
        { data: 'statement_amount', name: 'statement_amount' },
        { data: 'book_amount', name: 'book_amount' },
        { data: 'difference_amount', name: 'difference_amount' },
        { data: 'reconciliation_status', name: 'is_reconciled' },
        ...(pageData.canEdit ? [{ data: 'actions', name: 'actions', orderable: false, searchable: false }] : [])
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

  // Complete reconciliation
  $('#complete-reconciliation').on('click', function() {
    Swal.fire({
      title: 'Complete Reconciliation?',
      text: 'This action cannot be undone. Are you sure you want to complete this reconciliation?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Complete',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-warning',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.complete,
          type: 'POST',
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: response.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              location.reload();
            });
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while completing the reconciliation.';
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

  // Refresh items
  $('#refresh-items').on('click', function() {
    if (itemsTable) {
      itemsTable.ajax.reload();
    }
  });

  // Toggle reconciled status
  $(document).on('click', '.toggle-reconciled', function() {
    const itemId = $(this).data('item-id');
    const isReconciled = $(this).data('reconciled') === 1;
    const actionText = isReconciled ? 'unreconcile' : 'reconcile';

    Swal.fire({
      title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Item?`,
      text: `Are you sure you want to ${actionText} this item?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: `Yes, ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        const url = pageData.urls.toggleReconciled.replace('__ITEM_ID__', itemId);

        $.ajax({
          url: url,
          type: 'POST',
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

            // Reload the table
            if (itemsTable) {
              itemsTable.ajax.reload();
            }

            // Refresh page to update totals
            setTimeout(() => {
              location.reload();
            }, 1600);
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while updating the item.';
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

  // Delete reconciliation item
  $(document).on('click', '.delete-item', function() {
    const itemId = $(this).data('item-id');

    Swal.fire({
      title: 'Delete Item?',
      text: 'This action cannot be undone. Are you sure you want to delete this reconciliation item?',
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
        const url = pageData.urls.deleteItem.replace('__ITEM_ID__', itemId);

        $.ajax({
          url: url,
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

            // Reload the table
            if (itemsTable) {
              itemsTable.ajax.reload();
            }

            // Refresh page to update totals
            setTimeout(() => {
              location.reload();
            }, 1600);
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while deleting the item.';
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

  // Add statement item form
  $('#addStatementItemForm').on('submit', function(e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();

    // Disable submit button and show loading
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Adding...');

    // Clear previous errors
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();

    $.ajax({
      url: pageData.urls.addStatementItem,
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
          timer: 1500,
          timerProgressBar: true
        });

        // Reset form and close modal
        form[0].reset();
        $('#addStatementItemModal').modal('hide');

        // Reload the table
        if (itemsTable) {
          itemsTable.ajax.reload();
        }

        // Refresh page to update totals
        setTimeout(() => {
          location.reload();
        }, 1600);
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
          let errorMessage = 'An error occurred while adding the statement item.';
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
        // Re-enable submit button
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Reset modal form when modal is closed
  $('#addStatementItemModal').on('hidden.bs.modal', function() {
    const form = $('#addStatementItemForm');
    form[0].reset();
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
  });
});
