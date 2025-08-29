'use strict';

$(function () {
  // 1. SETUP & VARIABLE DECLARATIONS
  // =================================================================================================
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // DOM Elements
  const offcanvasElement = document.getElementById('offcanvasTaskStatusForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const taskStatusForm = document.getElementById('taskStatusForm');
  const saveStatusBtn = $('#saveTaskStatusBtn');
  const statusListElement = document.getElementById('task-status-list'); // Ensure this ID matches your Blade ul

  // Page data from Blade
  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.error('pageData object with URLs is not defined in the Blade view.');
    Swal.fire(pageData.labels?.error || 'Error', 'Page configuration data is missing.', 'error');
    return;
  }

  const urls = pageData.urls;
  const labels = pageData.labels;

  // 2. HELPER FUNCTIONS
  // =================================================================================================
  const getUrl = (template, id = null) => {
    if (id !== null && template.includes(':id')) { // Check if placeholder exists
      return template.replace(':id', id);
    }
    return template; // For URLs like store or updateOrder
  };

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(taskStatusForm);
    taskStatusForm.reset();
    $('#task_status_id_input').val('');
    $('#taskStatusFormMethod').val('POST');
    $('#offcanvasTaskStatusFormLabel').text(labels.addNewStatus);
    $('#task_status_is_default').prop('checked', false);
    $('#task_status_is_completed_status').prop('checked', false);
    $('#task_status_color').val('#6c757d');
    saveStatusBtn.prop('disabled', false).html(labels.saveStatus);
  };

  const populateOffcanvasForEdit = (status) => {
    resetOffcanvasForm();
    $('#offcanvasTaskStatusFormLabel').text(labels.editStatus);
    $('#task_status_id_input').val(status.id);
    $('#taskStatusFormMethod').val('PUT');

    $('#task_status_name').val(status.name);
    $('#task_status_color').val(status.color || '#6c757d');
    $('#task_status_is_default').prop('checked', status.is_default);
    $('#task_status_is_completed_status').prop('checked', status.is_completed_status);

    offcanvas.show();
  };

  const refreshStatusList = () => {
    // Simple page reload to reflect changes and new order
    window.location.reload();
    // For a more dynamic update, you would re-fetch and re-render the list here.
  };

  // 3. OFFCANVAS MANAGEMENT
  // =================================================================================================
  $('#add-new-task-status-btn').on('click', function () {
    resetOffcanvasForm();
    offcanvas.show();
  });

  $(document).on('click', '.edit-task-status', function () {
    const statusId = $(this).data('id');
    const url = getUrl(urls.getTaskStatusTemplate, statusId);
    $.get(url, function (response) {
      populateOffcanvasForEdit(response);
    }).fail(function() {
      Swal.fire(labels.error, 'Could not fetch status details.', 'error');
    });
  });

  if (offcanvasElement) {
    offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);
  }

  // 4. FORM SUBMISSION (AJAX)
  // =================================================================================================
  $(taskStatusForm).on('submit', function (e) {
    e.preventDefault();
    resetFormValidation(this);

    const statusId = $('#task_status_id_input').val();
    let url = getUrl(urls.store);
    const formData = new FormData(this);
    formData.set('is_default', $('#task_status_is_default').is(':checked') ? '1' : '0');
    formData.set('is_completed_status', $('#task_status_is_completed_status').is(':checked') ? '1' : '0');

    if (statusId) {
      url = getUrl(urls.updateTemplate, statusId);
    }

    const originalButtonText = saveStatusBtn.html();
    saveStatusBtn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-2"></span>${labels.saving}`);

    $.ajax({
      url: url,
      type: 'POST', // Form method is always POST, _method handles PUT for Laravel
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          offcanvas.hide();
          Swal.fire({
            icon: 'success',
            title: labels.success,
            text: response.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => { refreshStatusList(); });
        } else {
          Swal.fire(labels.error, response.message || 'Operation failed.', 'error');
        }
      },
      error: function (jqXHR) {
        if (jqXHR.status === 422 && jqXHR.responseJSON?.data?.errors) {
          $.each(jqXHR.responseJSON.data.errors, function (key, value) {
            const input = $(`#task_status_${key}`);
            if (input.length) {
              input.addClass('is-invalid');
              input.siblings('.invalid-feedback').text(value[0]);
            }
          });
          Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
        } else {
          Swal.fire(labels.error, jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');
        }
      },
      complete: function () {
        saveStatusBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  // 5. DELETE STATUS (AJAX)
  // =================================================================================================
  $(document).on('click', '.delete-task-status', function () {
    const statusId = $(this).data('id');
    const url = getUrl(urls.destroyTemplate, statusId);

    Swal.fire({
      title: labels.confirmDelete,
      text: labels.deleteWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: labels.confirmButtonText,
      cancelButtonText: labels.cancelButtonText,
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({ title: labels.deleting, allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        $.ajax({
          url: url, type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.status === 'success') {
              Swal.fire(labels.deleted, response.message, 'success').then(() => { refreshStatusList(); });
            } else {
              Swal.fire(labels.error, response.message || 'Could not delete status.', 'error');
            }
          },
          error: function (jqXHR) {
            Swal.close();
            Swal.fire(labels.error, jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');
          }
        });
      }
    });
  });

  // 6. SORTABLEJS FOR REORDERING STATUSES
  // =================================================================================================
  if (statusListElement) {
    new Sortable(statusListElement, {
      animation: 150,
      handle: '.bx-grid-vertical', // Drag handle icon
      onEnd: function (evt) {
        const order = Array.from(statusListElement.children).map(item => $(item).data('id'));
        const url = getUrl(urls.updateOrder);

        $.ajax({
          url: url, type: 'POST', data: { order: order },
          success: function(response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: labels.orderUpdated,
                text: response.message,
                timer: 1000,
                showConfirmButton: false,
                customClass: { container: 'swal2-sm' }
              });
            } else {
              Swal.fire(labels.error, response.message || 'Could not update status order.', 'error');
              refreshStatusList();
            }
          },
          error: function() {
            Swal.fire(labels.error, 'Failed to save new status order.', 'error');
            refreshStatusList();
          }
        });
      }
    });
  }
});
