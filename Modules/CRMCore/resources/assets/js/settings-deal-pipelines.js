'use strict';

$(function () {
  // CSRF Setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // DOM Elements
  const offcanvasElement = document.getElementById('offcanvasDealPipelineForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const dealPipelineForm = document.getElementById('dealPipelineForm');
  const savePipelineBtn = $('#savePipelineBtn');

  // Ensure pageData is available from Blade
  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.error('pageData object with URLs is not defined in the Blade view.');
    return;
  }

  const urls = pageData.urls;
  const labels = pageData.labels;

  // Initialize DataTable
  const pipelineTable = $('#pipelineTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: urls.datatable,
      type: 'GET'
    },
    columns: [
      { 
        data: 'order', 
        name: 'order', 
        orderable: false, 
        searchable: false,
        width: '50px'
      },
      { data: 'name', name: 'name' },
      { data: 'description', name: 'description' },
      { 
        data: 'status', 
        name: 'status', 
        orderable: false, 
        searchable: false 
      },
      { 
        data: 'actions', 
        name: 'actions', 
        orderable: false, 
        searchable: false,
        width: '150px'
      }
    ],
    order: [[1, 'asc']],
    pageLength: 25,
    responsive: true,
    language: {
      search: '',
      searchPlaceholder: 'Search pipelines...',
      lengthMenu: '_MENU_',
      info: 'Showing _START_ to _END_ of _TOTAL_ pipelines',
      infoEmpty: 'Showing 0 to 0 of 0 pipelines',
      infoFiltered: '(filtered from _MAX_ total pipelines)'
    },
    drawCallback: function() {
      // Initialize sortable after table is drawn
      initializeSortable();
    }
  });

  // Helper Functions
  const getUrl = (template, id) => template.replace(':id', id);

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(dealPipelineForm);
    dealPipelineForm.reset();
    $('#pipeline_id').val('');
    $('#formMethod').val('POST');
    $('#offcanvasDealPipelineFormLabel').text('Add Deal Pipeline');
    $('#pipeline_is_active').prop('checked', true);
    $('#pipeline_is_default').prop('checked', false);
    savePipelineBtn.prop('disabled', false).html('Save Pipeline');
  };

  const populateOffcanvasForEdit = (pipeline) => {
    resetOffcanvasForm();
    $('#offcanvasDealPipelineFormLabel').text('Edit Deal Pipeline');
    $('#pipeline_id').val(pipeline.id);
    $('#formMethod').val('PUT');

    $('#pipeline_name').val(pipeline.name);
    $('#pipeline_description').val(pipeline.description);
    $('#pipeline_is_active').prop('checked', pipeline.is_active);
    $('#pipeline_is_default').prop('checked', pipeline.is_default);

    offcanvas.show();
  };

  // Initialize sortable functionality for drag and drop reordering
  const initializeSortable = () => {
    const tableBody = document.querySelector('#pipelineTable tbody');
    if (tableBody && typeof Sortable !== 'undefined') {
      new Sortable(tableBody, {
        animation: 150,
        handle: '.bx-grid-vertical',
        onEnd: function (evt) {
          const order = [];
          $('#pipelineTable tbody tr').each(function(index) {
            const id = pipelineTable.row(this).data().id;
            if (id) order.push(id);
          });
          
          if (order.length > 0) {
            updatePipelineOrder(order);
          }
        }
      });
    }
  };

  // Update pipeline order
  const updatePipelineOrder = (order) => {
    $.ajax({
      url: urls.updateOrder,
      type: 'POST',
      data: { order: order },
      success: function(response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: labels.orderUpdated,
            text: response.message || labels.orderUpdated,
            timer: 1500,
            showConfirmButton: false
          });
        } else {
          Swal.fire('Error', response.message || 'Could not update pipeline order.', 'error');
        }
      },
      error: function() {
        Swal.fire('Error', 'Failed to save new pipeline order.', 'error');
        pipelineTable.ajax.reload(null, false);
      }
    });
  };

  // Offcanvas Management
  $('#add-new-pipeline-btn').on('click', function () {
    resetOffcanvasForm();
    offcanvas.show();
  });

  // Global function for edit button (called from DataTable)
  window.editPipeline = function(pipelineId) {
    const url = getUrl(urls.getPipelineTemplate, pipelineId);
    $.get(url, function (response) {
      populateOffcanvasForEdit(response);
    }).fail(function() {
      Swal.fire(labels.error, 'Could not fetch pipeline details.', 'error');
    });
  };

  // Global function for delete button (called from DataTable)
  window.deletePipeline = function(pipelineId) {
    const url = getUrl(urls.destroyTemplate, pipelineId);

    Swal.fire({
      title: labels.confirmDelete,
      text: labels.deleteWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: labels.deleteConfirm,
      cancelButtonText: labels.cancel,
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({
          title: labels.deleting,
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
          url: url,
          type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: labels.deleted,
                text: response.message || labels.orderUpdated,
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                pipelineTable.ajax.reload();
              });
            } else {
              Swal.fire(labels.error, response.message || 'Could not delete pipeline.', 'error');
            }
          },
          error: function (jqXHR) {
            Swal.close();
            const errorMessage = jqXHR.responseJSON?.message || labels.unexpectedError;
            Swal.fire(labels.error, errorMessage, 'error');
          }
        });
      }
    });
  };

  // Toggle Pipeline Status
  window.togglePipelineStatus = function(id) {
    const url = urls.toggleStatusTemplate ? getUrl(urls.toggleStatusTemplate, id) : `/settings/deal-pipelines/${id}/toggle-status`;
    
    $.ajax({
      url: url,
      type: 'POST',
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: labels.updated || 'Updated!',
            text: response.message,
            timer: 1000,
            showConfirmButton: false
          });
          pipelineTable.ajax.reload(null, false);
        } else {
          Swal.fire(labels.error, response.message || 'Could not update status.', 'error');
        }
      },
      error: function (jqXHR) {
        const errorMessage = jqXHR.responseJSON?.message || labels.unexpectedError;
        Swal.fire(labels.error, errorMessage, 'error');
      }
    });
  };

  // Reset form when offcanvas is hidden
  if (offcanvasElement) {
    offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);
  }

  // Form Submission
  $(dealPipelineForm).on('submit', function (e) {
    e.preventDefault();
    resetFormValidation(this);

    const pipelineId = $('#pipeline_id').val();
    let url = urls.store;

    if (pipelineId) {
      url = getUrl(urls.updateTemplate, pipelineId);
    }

    const formData = new FormData(this);
    formData.set('is_active', $('#pipeline_is_active').is(':checked') ? '1' : '0');
    formData.set('is_default', $('#pipeline_is_default').is(':checked') ? '1' : '0');

    const originalButtonText = savePipelineBtn.html();
    savePipelineBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + labels.saving);

    $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          offcanvas.hide();
          Swal.fire({
            icon: 'success',
            title: labels.success,
            text: response.message || labels.operationSuccess,
            timer: 1500,
            showConfirmButton: false
          }).then(() => {
            pipelineTable.ajax.reload();
          });
        } else {
          Swal.fire(labels.error, response.message || 'Operation failed.', 'error');
        }
      },
      error: function (jqXHR) {
        if (jqXHR.status === 422 && jqXHR.responseJSON?.data?.errors) {
          $.each(jqXHR.responseJSON.data.errors, function (key, value) {
            const inputId = `#pipeline_${key}`;
            const input = $(inputId);
            if (input.length) {
              input.addClass('is-invalid');
              input.siblings('.invalid-feedback').text(value[0]);
            }
          });
          Swal.fire(labels.validationError, jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
        } else {
          const errorMessage = jqXHR.responseJSON?.message || labels.unexpectedError;
          Swal.fire(labels.error, errorMessage, 'error');
        }
      },
      complete: function () {
        savePipelineBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });
});