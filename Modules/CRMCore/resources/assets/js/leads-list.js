'use strict';

$(function () {
  // CSRF Setup for all AJAX requests
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Data from Blade
  if (typeof pageData === 'undefined') {
    console.error('JS pageData object is not defined. Ensure it is passed from Blade.');
    return;
  }

  // DOM Elements
  const kanbanContainer = $('#kanban-view-container');
  const datatableContainer = $('#datatable-view-container');
  const btnKanbanView = $('#btn-kanban-view');
  const btnListView = $('#btn-list-view');
  const btnKanbanViewTop = $('#btn-kanban-view-top');
  const btnListViewTop = $('#btn-list-view-top');
  const leadsTableElement = $('#leadsTable');
  const leadForm = document.getElementById('leadForm');
  const offcanvasElement = document.getElementById('offcanvasLeadForm');
  const offcanvas = offcanvasElement ? new bootstrap.Offcanvas(offcanvasElement) : null;
  const saveLeadBtn = $('#saveLeadBtn');

  let dtLeadsTable;

  // Helper Functions
  const getUrl = (template, id) => template.replace(':id', id);

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(leadForm);
    leadForm.reset();
    $('#lead_id').val('');
    $('#formMethod').val('POST');
    $('#offcanvasLeadFormLabel').text(pageData.labels.addNewLead || 'Add New Lead');
    $('#assigned_to_user_id').val(null).trigger('change');
    saveLeadBtn.prop('disabled', false).html(pageData.labels.saveLead || 'Save Lead');
  };

  const renderKanbanCard = (lead) => {
    const assignedTo = lead.assigned_to_user ? lead.assigned_to_user.first_name.charAt(0) + lead.assigned_to_user.last_name.charAt(0) : 'NA';
    const assignedToName = lead.assigned_to_user ? `${lead.assigned_to_user.first_name} ${lead.assigned_to_user.last_name}` : 'Unassigned';
    const value = lead.value ? '$' + parseFloat(lead.value).toLocaleString() : 'No Value';

    return `
      <div class="card kanban-card mb-3" data-lead-id="${lead.id}">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="card-title mb-0">${lead.title}</h6>
            <div class="dropdown">
              <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bx bx-dots-vertical-rounded"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item edit-lead" href="#" data-url="${getUrl(pageData.urls.getLeadTemplate, lead.id)}">
                  <i class="bx bx-edit me-1"></i> ${pageData.labels.edit || 'Edit'}
                </a>
                <a class="dropdown-item" href="/leads/${lead.id}">
                  <i class="bx bx-show me-1"></i> ${pageData.labels.viewDetails || 'View Details'}
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger delete-lead" href="#" data-id="${lead.id}" data-url="${getUrl(pageData.urls.destroyTemplate, lead.id)}">
                  <i class="bx bx-trash me-1"></i> ${pageData.labels.delete || 'Delete'}
                </a>
              </div>
            </div>
          </div>
          <p class="card-text small">${lead.contact_name || ''} ${lead.company_name ? `(${lead.company_name})` : ''}</p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="badge bg-label-primary">${value}</span>
            <div class="avatar avatar-xs" data-bs-toggle="tooltip" title="${pageData.labels.assignedTo || 'Assigned to'} ${assignedToName}">
              <span class="avatar-initial rounded-circle bg-label-secondary">${assignedTo}</span>
            </div>
          </div>
        </div>
      </div>
    `;
  };

  const populateOffcanvasForEdit = (lead) => {
    resetOffcanvasForm();
    $('#offcanvasLeadFormLabel').text(pageData.labels.editLead || 'Edit Lead');
    $('#lead_id').val(lead.id);
    $('#formMethod').val('PUT');

    // Populate standard fields
    $('#title').val(lead.title);
    $('#description').val(lead.description);
    $('#contact_name').val(lead.contact_name);
    $('#company_name').val(lead.company_name);
    $('#contact_email').val(lead.contact_email);
    $('#contact_phone').val(lead.contact_phone);
    $('#value').val(lead.value);
    $('#lead_source_id').val(lead.lead_source_id);

    // For Select2, if a value exists, create an option and append it
    if (lead.assigned_to_user) {
      const assignedToSelect = $('#assigned_to_user_id');
      const option = new Option(`${lead.assigned_to_user.first_name} ${lead.assigned_to_user.last_name}`, lead.assigned_to_user_id, true, true);
      assignedToSelect.append(option).trigger('change');
    }

    offcanvas.show();
  };

  const refreshKanbanBoard = () => {
    $('.kanban-stage').empty();
    $.get(pageData.urls.kanbanAjax, function (data) {
      $.each(data, function (statusId, leads) {
        const stage = $(`#kanban-stage-${statusId}`);
        if (stage.length) {
          leads.forEach(lead => {
            stage.append(renderKanbanCard(lead));
          });
        }
      });
      // Re-initialize tooltips for new cards
      $('[data-bs-toggle="tooltip"]').tooltip();
    });
  };

  // Initialize DataTable
  if (leadsTableElement.length) {
    dtLeadsTable = leadsTableElement.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.dataTableAjax,
        type: 'POST'
      },
      columns: [
        { data: 'id', name: 'leads.id' },
        { data: 'title', name: 'title' },
        { data: 'contact_name', name: 'contact_name', defaultContent: '-' },
        { data: 'value', name: 'value', defaultContent: '$0.00' },
        { data: 'status_name', name: 'leadStatus.name', orderable: false },
        { data: 'assigned_to', name: 'assignedToUser.first_name', orderable: false },
        { data: 'created_at', name: 'created_at' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      order: [[6, 'desc']],
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: pageData.labels.searchPlaceholder || 'Search Leads...'
      }
    });
  }

  // Initialize Kanban Board SortableJS
  document.querySelectorAll('.kanban-stage').forEach(stage => {
    new Sortable(stage, {
      group: 'shared',
      animation: 150,
      onEnd: function (evt) {
        const leadId = evt.item.dataset.leadId;
        const newStatusId = evt.to.dataset.statusId;
        const url = getUrl(pageData.urls.updateKanbanStageTemplate, leadId);

        $.ajax({
          url: url,
          type: 'POST',
          data: { lead_status_id: newStatusId },
          success: function(response) {
            if (response.code !== 200) {
              evt.from.appendChild(evt.item);
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error || 'Error',
                text: response.message || pageData.labels.couldNotUpdateStage || 'Could not update stage.'
              });
            }
          },
          error: function() {
            evt.from.appendChild(evt.item);
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error || 'Error',
              text: pageData.labels.unexpectedError || 'An unexpected error occurred.'
            });
          }
        });
      }
    });
  });

  // Initialize Form Elements
  // Populate Lead Source dropdown
  const leadSourceSelect = $('#lead_source_id');
  $.each(pageData.leadSources, function(id, name) {
    leadSourceSelect.append($('<option>', { value: id, text: name }));
  });

  // Initialize User Search Select2
  const userSelect = $('.select2-users');
  if (userSelect.length) {
    userSelect.select2({
      placeholder: userSelect.data('placeholder') || pageData.labels.selectUser || 'Select an option',
      dropdownParent: offcanvasElement,
      allowClear: true,
      ajax: {
        url: pageData.urls.userSearch,
        dataType: 'json',
        delay: 250,
        data: (params) => ({ q: params.term, page: params.page || 1 }),
        processResults: (data) => ({
          results: data.results,
          pagination: { more: data.pagination.more }
        }),
        cache: true
      },
      minimumInputLength: 1
    });
  }

  // Event Listeners

  // View toggle handlers
  function switchToKanbanView() {
    if (btnKanbanView.length) btnKanbanView.addClass('active');
    if (btnListView.length) btnListView.removeClass('active');
    if (btnKanbanViewTop.length) btnKanbanViewTop.addClass('active');
    if (btnListViewTop.length) btnListViewTop.removeClass('active');
    if (datatableContainer.length) datatableContainer.addClass('d-none');
    if (kanbanContainer.length) {
      kanbanContainer.removeClass('d-none');
      refreshKanbanBoard();
    }
  }

  function switchToListView() {
    if (btnListView.length) btnListView.addClass('active');
    if (btnKanbanView.length) btnKanbanView.removeClass('active');
    if (btnListViewTop.length) btnListViewTop.addClass('active');
    if (btnKanbanViewTop.length) btnKanbanViewTop.removeClass('active');
    if (kanbanContainer.length) kanbanContainer.addClass('d-none');
    if (datatableContainer.length) datatableContainer.removeClass('d-none');
  }

  // Bind click handlers to all view toggle buttons
  if (btnKanbanView.length) {
    btnKanbanView.on('click', switchToKanbanView);
  }
  if (btnKanbanViewTop.length) {
    btnKanbanViewTop.on('click', switchToKanbanView);
  }
  if (btnListView.length) {
    btnListView.on('click', switchToListView);
  }
  if (btnListViewTop.length) {
    btnListViewTop.on('click', switchToListView);
  }

  // Trigger initial view state (start with List view)
  switchToListView();

  // "Add New Lead" buttons
  $('#add-new-lead-btn, #add-new-lead-btn-kanban').on('click', function() {
    resetOffcanvasForm();
    offcanvas.show();
  });

  // "Edit Lead" button (delegated)
  $(document).on('click', '.edit-lead', function(e) {
    e.preventDefault();
    const url = $(this).data('url');
    $.get(url, function (response) {
      populateOffcanvasForEdit(response);
    }).fail(function() {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.error || 'Error',
        text: pageData.labels.couldNotFetchLead || 'Could not fetch lead details.'
      });
    });
  });

  // Form Submission (Create and Update)
  $(leadForm).on('submit', function(e) {
    e.preventDefault();
    resetFormValidation(this);

    let url = pageData.urls.store;
    let method = 'POST';
    const leadId = $('#lead_id').val();
    if (leadId) {
      url = getUrl(pageData.urls.updateTemplate, leadId);
      method = 'POST';
    }
    const formData = new FormData(this);

    const originalButtonText = saveLeadBtn.html();
    saveLeadBtn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm"></span> ${pageData.labels.saving || 'Saving...'}`);

    $.ajax({
      url: url,
      type: method,
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.code === 200) {
          offcanvas.hide();
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success || 'Success!',
            text: response.message,
            timer: 1500,
            showConfirmButton: false
          });
          dtLeadsTable.ajax.reload(null, false);
          refreshKanbanBoard();
        }
      },
      error: function (jqXHR) {
        let message = pageData.labels.unexpectedError || 'An unexpected error occurred.';
        if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
          message = jqXHR.responseJSON.message || pageData.labels.pleaseCorrectErrors || 'Please correct the errors below.';
          $.each(jqXHR.responseJSON.errors, function (key, value) {
            const input = $(`#${key}`);
            if (input.length) {
              input.addClass('is-invalid');
              input.siblings('.invalid-feedback').text(value[0]);
            }
          });
        } else {
          message = jqXHR.responseJSON?.message || message;
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error || 'Error',
            html: message
          });
        }
      },
      complete: function () {
        saveLeadBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  // Reset form when offcanvas is hidden
  offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);

  // Delete lead handler
  $(document).on('click', '.delete-lead', function(e) {
    e.preventDefault();
    const url = $(this).data('url');

    Swal.fire({
      title: pageData.labels.confirmDelete || 'Are you sure?',
      text: pageData.labels.deleteWarning || "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.deleteButton || 'Yes, delete it!',
      cancelButtonText: pageData.labels.cancelButton || 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({
          title: pageData.labels.deleting || 'Deleting...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        $.ajax({
          url: url,
          type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.code === 200) {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.deleted || 'Deleted!',
                text: response.message
              });
              dtLeadsTable.ajax.reload(null, false);
              refreshKanbanBoard();
            } else {
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error || 'Error',
                text: response.message
              });
            }
          },
          error: function () {
            Swal.close();
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error || 'Error',
              text: pageData.labels.couldNotDeleteLead || 'Could not delete lead.'
            });
          }
        });
      }
    });
  });

});