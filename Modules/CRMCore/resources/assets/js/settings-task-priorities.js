'use strict';

$(function () {
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  const offcanvasElement = document.getElementById('offcanvasTaskPriorityForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const taskPriorityForm = document.getElementById('taskPriorityForm');
  const savePriorityBtn = $('#saveTaskPriorityBtn');
  const priorityListElement = document.getElementById('task-priority-list');

  if (typeof pageData === 'undefined' || !pageData.urls || !pageData.labels) {
    console.error('pageData object with URLs and labels is not defined in the Blade view.');
    Swal.fire('Error', 'Page configuration data is missing.', 'error');
    return;
  }

  const urls = pageData.urls;
  const labels = pageData.labels;

  const getUrl = (template, id = null) => {
    if (id !== null && template.includes(':id')) {
      return template.replace(':id', id);
    }
    return template;
  };

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(taskPriorityForm);
    taskPriorityForm.reset();
    $('#task_priority_id_input').val('');
    $('#taskPriorityFormMethod').val('POST');
    $('#offcanvasTaskPriorityFormLabel').text(labels.addPriority || 'Add Task Priority');
    $('#task_priority_is_default').prop('checked', false);
    $('#task_priority_color').val('#6c757d');
    savePriorityBtn.prop('disabled', false).html(labels.savePriority);
  };

  const populateOffcanvasForEdit = (priority) => {
    resetOffcanvasForm();
    $('#offcanvasTaskPriorityFormLabel').text(labels.editPriority || 'Edit Task Priority');
    $('#task_priority_id_input').val(priority.id);
    $('#taskPriorityFormMethod').val('PUT');

    // API returns camelCase due to TransformApiResponse middleware
    $('#task_priority_name').val(priority.name);
    $('#task_priority_color').val(priority.color || '#6c757d');
    $('#task_priority_is_default').prop('checked', priority.isDefault === 1 || priority.isDefault === true);
  };

  const refreshPriorityList = () => {
    window.location.reload();
  };

  // ADD NEW PRIORITY BUTTON
  $('#add-new-task-priority-btn').on('click', function () {
    resetOffcanvasForm();
    offcanvas.show();
  });

  // EDIT PRIORITY BUTTON
  $(document).on('click', '.edit-task-priority', function () {
    const priorityId = $(this).data('id');
    const url = getUrl(urls.getPriorityTemplate, priorityId);

    $.ajax({
      url: url,
      type: 'GET',
      success: function (response) {
        console.log('Edit response:', response); // Debug log
        if (response.status === 'success' && response.data) {
          populateOffcanvasForEdit(response.data);
          offcanvas.show();
        } else {
          Swal.fire(labels.error, response.message || 'Could not load priority data.', 'error');
        }
      },
      error: function () {
        Swal.fire(labels.error, labels.unexpectedError, 'error');
      }
    });
  });

  // FORM SUBMISSION
  $('#taskPriorityForm').on('submit', function (e) {
    e.preventDefault();
    resetFormValidation(this);

    const formData = new FormData(this);
    const isDefault = $('#task_priority_is_default').is(':checked');
    formData.delete('is_default');
    formData.append('is_default', isDefault ? '1' : '0');

    const priorityId = $('#task_priority_id_input').val();
    const method = $('#taskPriorityFormMethod').val();
    let url = urls.store;

    if (method === 'PUT' && priorityId) {
      formData.append('_method', 'PUT');
      url = getUrl(urls.updateTemplate, priorityId);
    }

    savePriorityBtn.prop('disabled', true).html(labels.saving);

    $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: labels.success,
            text: response.message || (method === 'PUT' ? labels.priorityUpdated : labels.priorityCreated),
            timer: 1500,
            showConfirmButton: false
          });
          offcanvas.hide();
          setTimeout(() => refreshPriorityList(), 1500);
        } else {
          Swal.fire(labels.error, response.message || labels.unexpectedError, 'error');
        }
        savePriorityBtn.prop('disabled', false).html(labels.savePriority);
      },
      error: function (jqXHR) {
        savePriorityBtn.prop('disabled', false).html(labels.savePriority);
        if (jqXHR.status === 422 && jqXHR.responseJSON?.data?.errors) {
          $.each(jqXHR.responseJSON.data.errors, function (key, value) {
            const input = $(`#task_priority_${key}`);
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(value[0]);
          });
        } else {
          Swal.fire(labels.error, jqXHR.responseJSON?.message || labels.unexpectedError, 'error');
        }
      }
    });
  });

  // DELETE PRIORITY
  $(document).on('click', '.delete-task-priority', function () {
    const priorityId = $(this).data('id');
    const url = getUrl(urls.destroyTemplate, priorityId);

    Swal.fire({
      title: labels.confirmDelete,
      text: labels.deleteWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: labels.confirmDeleteButton,
      cancelButtonText: labels.cancel
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({ title: labels.deleting, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
          url: url,
          type: 'DELETE',
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: labels.deleted,
                text: response.message,
                timer: 1500,
                showConfirmButton: false
              });
              setTimeout(() => refreshPriorityList(), 1500);
            } else {
              Swal.fire(labels.error, response.message || 'Could not delete priority.', 'error');
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

  // SORTABLEJS FOR REORDERING PRIORITIES
  if (priorityListElement) {
    new Sortable(priorityListElement, {
      animation: 150,
      handle: '.bx-grid-vertical',
      onEnd: function (evt) {
        const order = Array.from(priorityListElement.children).map(item => $(item).data('id'));
        const url = getUrl(urls.updateOrder);

        $.ajax({
          url: url,
          type: 'POST',
          data: { order: order },
          success: function(response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: labels.orderUpdated || 'Order Updated',
                text: response.message,
                timer: 1000,
                showConfirmButton: false,
                customClass: { container: 'swal2-sm' }
              });
            } else {
              Swal.fire(labels.error, response.message || 'Could not update priority order.', 'error');
              refreshPriorityList();
            }
          },
          error: function() {
            Swal.fire(labels.error, 'Failed to save new priority order.', 'error');
            refreshPriorityList();
          }
        });
      }
    });
  }
});