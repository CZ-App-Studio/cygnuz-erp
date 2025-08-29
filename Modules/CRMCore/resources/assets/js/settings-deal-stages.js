'use strict';

$(function () {
  // CSRF Setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // DOM Elements
  const offcanvasElement = document.getElementById('offcanvasDealStageForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const dealStageForm = document.getElementById('dealStageForm');
  const saveStageBtn = $('#saveStageBtn');
  const stageListElement = document.getElementById('stage-list'); // Assuming your list has this ID

  // Ensure pageData is available from Blade
  if (typeof pageData === 'undefined' || !pageData.urls || !pageData.pipeline || !pageData.pipeline.id) {
    console.error('pageData object with URLs and pipeline.id is not defined in the Blade view.');
    Swal.fire('Error', 'Page configuration data is missing. Please contact support.', 'error');
    return;
  }

  const currentPipelineId = pageData.pipeline.id;

  // --- Helper Functions ---
  const getUrl = (template, stageId = null) => {
    if (stageId !== null && template.includes('__DEAL_STAGE_ID__')) {
      return template.replace('__DEAL_STAGE_ID__', stageId);
    }
    return template;
  };

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(dealStageForm);
    dealStageForm.reset();
    $('#stage_id').val('');
    $('#formMethod').val('POST');
    $('#offcanvasDealStageFormLabel').text('Add New Stage');
    $('#stage_is_default_for_pipeline').prop('checked', false);
    $('#stage_is_won_stage').prop('checked', false);
    $('#stage_is_lost_stage').prop('checked', false);
    saveStageBtn.prop('disabled', false).html('Save Stage');
  };

  const populateOffcanvasForEdit = (stage) => {
    resetOffcanvasForm();
    $('#offcanvasDealStageFormLabel').text('Edit Stage');
    $('#stage_id').val(stage.id);
    $('#formMethod').val('PUT');

    $('#stage_name').val(stage.name);
    $('#stage_color').val(stage.color || '#6c757d');
    $('#stage_is_default_for_pipeline').prop('checked', stage.is_default_for_pipeline);
    $('#stage_is_won_stage').prop('checked', stage.is_won_stage);
    $('#stage_is_lost_stage').prop('checked', stage.is_lost_stage);

    offcanvas.show();
  };

  const refreshStageList = () => {
    // Simplest way is to reload the page to get the updated list and order
    window.location.reload();
    // A more complex solution would involve re-fetching and re-rendering the list via AJAX.
  };

  // --- Offcanvas Management ---
  $('#add-new-stage-btn').on('click', function () {
    resetOffcanvasForm();
    offcanvas.show();
  });

  $(document).on('click', '.edit-deal-stage', function () {
    const stageId = $(this).data('id');
    const url = getUrl(pageData.urls.getStageTemplate, stageId);
    $.get(url, function (response) {
      populateOffcanvasForEdit(response);
    }).fail(function() {
      Swal.fire('Error', 'Could not fetch stage details.', 'error');
    });
  });

  if(offcanvasElement){
    offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);
  }

  // --- Form Submission (AJAX) ---
  $(dealStageForm).on('submit', function (e) {
    e.preventDefault();
    resetFormValidation(this);

    const stageId = $('#stage_id').val();
    let url = getUrl(pageData.urls.store); // URL will include pipeline_id from getUrl helper
    if (stageId) {
      url = getUrl(pageData.urls.updateTemplate, stageId);
    }

    const formData = new FormData(this);
    formData.set('is_default_for_pipeline', $('#stage_is_default_for_pipeline').is(':checked') ? '1' : '0');
    formData.set('is_won_stage', $('#stage_is_won_stage').is(':checked') ? '1' : '0');
    formData.set('is_lost_stage', $('#stage_is_lost_stage').is(':checked') ? '1' : '0');
    // pipeline_id is part of the URL, not usually sent in form for nested resources,
    // but ensure your controller can access it from the route or add if needed.

    const originalButtonText = saveStageBtn.html();
    saveStageBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

    $.ajax({
      url: url, type: 'POST', data: formData, processData: false, contentType: false,
      success: function (response) {
        if (response.code === 200) {
          offcanvas.hide();
          Swal.fire({icon: 'success', title: 'Success!', text: response.message, timer: 1500, showConfirmButton: false})
            .then(() => { refreshStageList(); });
        } else {
          Swal.fire('Error', response.message || 'Operation failed.', 'error');
        }
      },
      error: function (jqXHR) {
        if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
          $.each(jqXHR.responseJSON.errors, function (key, value) {
            const input = $(`#stage_${key}`);
            if (input.length) {
              input.addClass('is-invalid');
              input.siblings('.invalid-feedback').text(value[0]);
            }
          });
          Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
        } else {
          Swal.fire('Error', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');
        }
      },
      complete: function () {
        saveStageBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  // --- Delete Stage (AJAX) ---
  $(document).on('click', '.delete-deal-stage', function () {
    const stageId = $(this).data('id');
    const url = getUrl(pageData.urls.destroyTemplate, stageId);

    Swal.fire({
      title: 'Are you sure?', text: "Associated deals might be affected or prevent deletion!", icon: 'warning',
      showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel',
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        $.ajax({
          url: url, type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.code === 200) {
              Swal.fire('Deleted!', response.message, 'success').then(() => { refreshStageList(); });
            } else {
              Swal.fire('Error!', response.message || 'Could not delete stage.', 'error');
            }
          },
          error: function (jqXHR) {
            Swal.close();
            Swal.fire('Error!', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');
          }
        });
      }
    });
  });

  // --- SortableJS for Reordering Stages ---
  if (stageListElement) {
    new Sortable(stageListElement, {
      animation: 150,
      handle: '.bx-grid-vertical',
      onEnd: function (evt) {
        const order = Array.from(stageListElement.children).map(item => $(item).data('id'));
        const url = getUrl(pageData.urls.updateOrder); // URL already includes pipeline_id

        $.ajax({
          url: url, type: 'POST', data: { order: order },
          success: function(response) {
            if (response.code === 200) {
              Swal.fire({ icon: 'success', title: 'Order Updated!', text: response.message, timer: 1000, showConfirmButton: false, customClass: { container: 'swal2-sm' } });
            } else {
              Swal.fire('Error', response.message || 'Could not update stage order.', 'error');
            }
          },
          error: function() {
            Swal.fire('Error', 'Failed to save new stage order.', 'error');
          }
        });
      }
    });
  }
});
