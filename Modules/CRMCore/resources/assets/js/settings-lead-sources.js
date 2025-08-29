'use strict';

$(function () {
  // CSRF Setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // DOM Elements
  const offcanvasElement = document.getElementById('offcanvasLeadSourceForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const leadSourceForm = document.getElementById('leadSourceForm');
  const saveSourceBtn = $('#saveSourceBtn');
  const dtLeadSourcesTableElement = $('#leadSourcesTable');
  let dtLeadSourcesTable;

  // Ensure pageData is available
  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.error('pageData object with URLs is not defined in the Blade view.');
    return;
  }
  
  // Use pageData labels
  const labels = pageData.labels || {};

  // --- Helper Functions ---
  const getUrl = (template, id) => template.replace(':id', id);

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(leadSourceForm);
    leadSourceForm.reset();
    $('#source_id').val('');
    $('#formMethod').val('POST');
    $('#offcanvasLeadSourceFormLabel').text(labels.addLeadSource || 'Add Lead Source');
    $('#source_is_active').prop('checked', true);
    saveSourceBtn.prop('disabled', false).html(labels.save || 'Save');
  };

  const populateOffcanvasForEdit = (source) => {
    resetOffcanvasForm();
    $('#offcanvasLeadSourceFormLabel').text(labels.editLeadSource || 'Edit Lead Source');
    $('#source_id').val(source.id);
    $('#formMethod').val('PUT');

    $('#source_name').val(source.name);
    $('#source_is_active').prop('checked', source.is_active);
    offcanvas.show();
  };

  // --- DataTables Initialization ---
  if (dtLeadSourcesTableElement.length) {
    dtLeadSourcesTable = dtLeadSourcesTableElement.DataTable({
      processing: true, serverSide: true,
      ajax: { url: pageData.urls.ajax, type: 'POST' },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'name', name: 'name' },
        { data: 'is_active', name: 'is_active', className: 'text-center' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      order: [[0, 'desc']],
      language: { search: '', searchPlaceholder: labels.searchSources || 'Search Sources...' }
    });
  }

  // --- Offcanvas Management ---
  $('#add-new-source-btn').on('click', function () {
    resetOffcanvasForm();
    offcanvas.show();
  });

  dtLeadSourcesTableElement.on('click', '.edit-lead-source', function () {
    const url = $(this).data('url');
    $.get(url, function (response) {
      populateOffcanvasForEdit(response);
    }).fail(function() {
      Swal.fire(labels.error || 'Error', labels.fetchError || 'Could not fetch source details.', 'error');
    });
  });

  offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);

  // --- Form Submission (AJAX) ---
  $(leadSourceForm).on('submit', function(e) {
    e.preventDefault();
    resetFormValidation(this);

    const sourceId = $('#source_id').val();
    let url = pageData.urls.store;
    let method = 'POST';

    if (sourceId) {
      url = getUrl(pageData.urls.updateTemplate, sourceId);
    }
    const formData = new FormData(this);
    formData.set('is_active', $('#source_is_active').is(':checked') ? '1' : '0');


    const originalButtonText = saveSourceBtn.html();
    saveSourceBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + (labels.saving || 'Saving...'));

    $.ajax({
      url: url, type: 'POST', data: formData, processData: false, contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          offcanvas.hide();
          Swal.fire(labels.success || 'Success!', response.message, 'success');
          dtLeadSourcesTable.ajax.reload(null, false);
        } else {
          Swal.fire(labels.error || 'Error', response.message || labels.operationFailed || 'Operation failed.', 'error');
        }
      },
      error: function (jqXHR) {
        if (jqXHR.status === 422 && jqXHR.responseJSON?.data?.errors) {
          $.each(jqXHR.responseJSON.data.errors, function (key, value) {
            const input = $(`#source_${key}`);
            if (input.length) {
              input.addClass('is-invalid');
              input.siblings('.invalid-feedback').text(value[0]);
            }
          });
          Swal.fire(labels.validationError || 'Validation Error', jqXHR.responseJSON.message || labels.correctErrors || 'Please correct the errors.', 'error');
        } else {
          Swal.fire(labels.error || 'Error', jqXHR.responseJSON?.message || labels.unexpectedError || 'An unexpected error occurred.', 'error');
        }
      },
      complete: function () {
        saveSourceBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  // --- Delete Source (AJAX) ---
  dtLeadSourcesTableElement.on('click', '.delete-lead-source', function () {
    const url = $(this).data('url');
    Swal.fire({
      title: labels.confirmDelete || 'Are you sure?', 
      text: labels.deleteWarning || "This cannot be undone!", 
      icon: 'warning',
      showCancelButton: true, 
      confirmButtonText: labels.confirmDeleteButton || 'Yes, delete it!',
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({ title: labels.deleting || 'Deleting...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        $.ajax({
          url: url, type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.status === 'success') {
              Swal.fire(labels.deleted || 'Deleted!', response.message, 'success');
              dtLeadSourcesTable.ajax.reload(null, false);
            } else {
              Swal.fire(labels.error || 'Error!', response.message || labels.deleteError || 'Could not delete source.', 'error');
            }
          },
          error: function (jqXHR) {
            Swal.close();
            Swal.fire(labels.error || 'Error!', jqXHR.responseJSON?.message || labels.unexpectedError || 'An unexpected error occurred.', 'error');
          }
        });
      }
    });
  });

  // --- Toggle Source Status from Dropdown (AJAX) ---
  dtLeadSourcesTableElement.on('click', '.toggle-status', function () {
    const url = $(this).data('url');
    const $button = $(this);
    
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
            showConfirmButton: false, 
            customClass: { container: 'swal2-sm' } 
          });
          dtLeadSourcesTable.ajax.reload(null, false);
        } else {
          Swal.fire(labels.error || 'Error', response.message || labels.statusUpdateError || 'Could not update status.', 'error');
        }
      },
      error: function (jqXHR) {
        Swal.fire(labels.error || 'Error', jqXHR.responseJSON?.message || labels.statusUpdateError || 'Failed to update status.', 'error');
      }
    });
  });
});
