'use strict';

$(function () {
  // Setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  if (typeof pageData === 'undefined' || !pageData.urls || !pageData.pipelines) {
    console.error('JS pageData object (with urls, pipelines, initialPipelineId) is not defined in Blade.');
    return;
  }

  // DOM Elements
  const kanbanViewContainer = $('#kanban-view-container');
  const datatableViewContainer = $('#datatable-view-container');
  const kanbanStagesWrapper = $('#kanban-stages-wrapper');
  const btnKanbanView = $('#btn-kanban-view');
  const btnListView = $('#btn-list-view');
  const btnKanbanViewTop = $('#btn-kanban-view-top');
  const btnListViewTop = $('#btn-list-view-top');
  const dealsTableElement = $('#dealsTable');
  const dealForm = document.getElementById('dealForm');
  const offcanvasElement = document.getElementById('offcanvasDealForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const saveDealBtn = $('#saveDealBtn');
  const pipelineFilterSelect = $('#pipeline-filter-select');

  // Form Elements
  const dealIdInput = $('#deal_id');
  const formMethodInput = $('#formMethod');
  const offcanvasLabel = $('#offcanvasDealFormLabel');
  const pipelineSelectForm = $('#deal_pipeline_id');
  const stageSelectForm = $('#deal_stage_id');
  const companySelectForm = $('#deal_company_id');
  const contactSelectForm = $('#deal_contact_id');
  const assignedToUserSelectForm = $('#deal_assigned_to_user_id');
  const expectedCloseDateInput = $('#deal_expected_close_date');
  const lostReasonContainer = $('#lost_reason_container');

  let dtDealsTable;
  let currentPipelineId = pageData.initialPipelineId;
  let sortableInstances = [];

  // Helper Functions
  const getUrl = (template, id) => template.replace(':id', id);

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(dealForm);
    dealForm.reset();
    dealIdInput.val('');
    formMethodInput.val('POST');
    offcanvasLabel.text(pageData.labels.addNewDeal || 'Add New Deal');
    lostReasonContainer.addClass('d-none');

    // Reset Select2 fields
    pipelineSelectForm.val(currentPipelineId || '').trigger('change');
    companySelectForm.val(null).trigger('change');
    contactSelectForm.val(null).trigger('change');
    assignedToUserSelectForm.val(null).trigger('change');

    saveDealBtn.prop('disabled', false).html('Save Deal');
  };

  const showValidationErrors = (errors) => {
    $.each(errors, function (key, value) {
      const input = $(`#deal_${key}`);
      if (input.length) {
        input.addClass('is-invalid');
        input.siblings('.invalid-feedback').text(value[0]);
      } else {
        console.warn(`Validation error for unknown field or general error: ${key}`);
      }
    });
    $('.is-invalid:first').focus();
  };

  // Kanban Board Functions
  const renderKanbanCard = (deal) => {
    const assignedToInitials = deal.assigned_to_user ? (deal.assigned_to_user.first_name.charAt(0) + (deal.assigned_to_user.last_name ? deal.assigned_to_user.last_name.charAt(0) : '')).toUpperCase() : 'NA';
    const assignedToName = deal.assigned_to_user ? `${deal.assigned_to_user.first_name} ${deal.assigned_to_user.last_name}` : 'Unassigned';
    const dealValue = deal.value ? '$' + parseFloat(deal.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'No Value';
    const companyName = deal.company ? deal.company.name : '';
    const contactName = deal.contact ? `${deal.contact.first_name} ${deal.contact.last_name}` : '';
    const associatedParty = [contactName, companyName].filter(Boolean).join(companyName && contactName ? ' @ ' : '');

    return `
            <div class="card kanban-card mb-3" data-deal-id="${deal.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0" title="${deal.title}">${deal.title.length > 30 ? deal.title.substring(0, 27) + '...' : deal.title}</h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item edit-deal" href="#" data-url="${getUrl(pageData.urls.getDealTemplate, deal.id)}"><i class="bx bx-edit me-1"></i>Edit</a>
                                <a class="dropdown-item text-danger delete-deal" href="#" data-id="${deal.id}" data-url="${getUrl(pageData.urls.destroyTemplate, deal.id)}"><i class="bx bx-trash me-1"></i>Delete</a>
                                <a class="dropdown-item" href="/deals/${deal.id}"><i class="bx bx-show me-1"></i>View Details</a>
                            </div>
                        </div>
                    </div>
                    ${associatedParty ? `<p class="card-text small text-muted">${associatedParty}</p>` : ''}
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="badge bg-label-primary">${dealValue}</span>
                        <div class="avatar avatar-xs" data-bs-toggle="tooltip" title="Assigned to: ${assignedToName}">
                            <span class="avatar-initial rounded-circle bg-label-secondary">${assignedToInitials}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
  };

  const updateKanbanStageTotals = () => {
    $('.kanban-stage').each(function() {
      const stageId = $(this).data('stage-id');
      const cards = $(this).find('.kanban-card');
      $(`#stage-total-${stageId}`).text(`(${cards.length} Deals)`);
    });
  };

  const loadKanbanData = (pipelineId) => {
    sortableInstances.forEach(sortable => sortable.destroy());
    sortableInstances = [];
    kanbanStagesWrapper.empty();

    if (!pageData.pipelines[pipelineId]) {
      kanbanStagesWrapper.html(`<div class="col-12"><div class="alert alert-warning">${pageData.labels.selectedPipelineNotFound}</div></div>`);
      return;
    }

    const currentPipelineStages = pageData.pipelines[pipelineId].stages;
    let stagesRendered = false;
    $.each(currentPipelineStages, (stageId, stage) => {
      if (stage.is_won_stage || stage.is_lost_stage) return true;
      stagesRendered = true;
      const stageHtml = `
        <div class="kanban-column">
          <div class="kanban-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <span class="badge" style="background-color: ${stage.color || '#6c757d'};">${stage.name}</span>
            </h6>
            <span class="text-muted small" id="stage-total-${stageId}">$0 (0)</span>
          </div>
          <div id="kanban-stage-${stageId}" class="kanban-stage" data-stage-id="${stageId}" data-pipeline-id="${pipelineId}">
            <!-- Deals will be populated here -->
          </div>
        </div>`;
      kanbanStagesWrapper.append(stageHtml);
    });

    if (!stagesRendered) {
      kanbanStagesWrapper.html(`<div class="col-12"><div class="alert alert-info">${pageData.labels.thisPipelineHasNoStages}</div></div>`);
    }

    $.get(pageData.urls.kanbanAjax, { pipeline_id: pipelineId }, function (data) {
      $('.kanban-stage').each(function() { $(this).empty(); });

      $.each(data, function (stageId, dealsInStage) {
        const stageElement = $(`#kanban-stage-${stageId}`);
        if (stageElement.length) {
          dealsInStage.forEach(deal => {
            stageElement.append(renderKanbanCard(deal));
          });
        }
      });
      updateKanbanStageTotals();
      $('[data-bs-toggle="tooltip"]').tooltip();

      // Initialize SortableJS for the newly rendered stages
      document.querySelectorAll('.kanban-stage').forEach(stageEl => {
        const sortable = new Sortable(stageEl, {
          group: 'deals-kanban', animation: 150,
          onEnd: function (evt) {
            const dealId = evt.item.dataset.dealId;
            const newStageId = evt.to.dataset.stageId;
            const originalStageId = evt.from.dataset.stageId;
            const url = getUrl(pageData.urls.updateKanbanStageTemplate, dealId);

            $.ajax({
              url: url, type: 'POST', data: { deal_stage_id: newStageId, _token: $('meta[name="csrf-token"]').attr('content') },
              success: function(response) {
                if (response.code === 200) {
                  Swal.fire({ icon: 'success', title: 'Stage Updated', timer: 1000, showConfirmButton: false, customClass: { container: 'swal2-sm' } });
                  updateKanbanStageTotals();
                } else {
                  evt.from.appendChild(evt.item);
                  Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Could not update stage.' });
                }
              },
              error: function() {
                evt.from.appendChild(evt.item);
                Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred while updating stage.' });
              }
            });
          }
        });
        sortableInstances.push(sortable);
      });
    });
  };

  // DataTable Functions
  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(dealsTableElement)) {
      dtDealsTable.ajax.reload();
      return;
    }
    if (dealsTableElement.length) {
      dtDealsTable = dealsTableElement.DataTable({
        processing: true, serverSide: true,
        ajax: { url: pageData.urls.dataTableAjax, type: 'POST', data: { pipeline_id: currentPipelineId } },
        columns: [
          { data: 'id', name: 'deals.id' },
          { data: 'title', name: 'title' },
          { data: 'value', name: 'value' },
          { data: 'stage_name', name: 'dealStage.name', orderable: false },
          { data: 'company_name', name: 'company.name', orderable: false },
          { data: 'contact_name', name: 'contact.first_name', orderable: false },
          { data: 'assigned_to', name: 'assignedToUser.first_name', orderable: false },
          { data: 'expected_close_date', name: 'expected_close_date' },
          { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-start' }
        ],
        order: [[0, 'desc']], responsive: true,
        language: { search: '', searchPlaceholder: 'Search Deals...', paginate: { next: '<i class="bx bx-chevron-right"></i>', previous: '<i class="bx bx-chevron-left"></i>' } },
      });
    }
  };

  // Offcanvas & Form Functions
  const populatePipelineDropdown = () => {
    pipelineSelectForm.empty().append($('<option>', { value: '', text: 'Select Pipeline...' }));
    $.each(pageData.allPipelinesForForm, function(id, name) {
      pipelineSelectForm.append($('<option>', { value: id, text: name }));
    });
    pipelineSelectForm.val(currentPipelineId).trigger('change');
  };

  const populateStageDropdown = (pipelineId, selectedStageId = null) => {
    stageSelectForm.empty().append($('<option>', { value: '', text: 'Select Stage...' })).prop('disabled', true);
    if (pipelineId && pageData.pipelines[pipelineId] && pageData.pipelines[pipelineId].stages) {
      $.each(pageData.pipelines[pipelineId].stages, function(id, stage) {
        stageSelectForm.append($('<option>', { value: id, text: stage.name }));
      });
      stageSelectForm.prop('disabled', false);
      if (selectedStageId) {
        stageSelectForm.val(selectedStageId);
      }
    }
    stageSelectForm.trigger('change.select2');
  };

  const initializeFormElements = () => {
    if (expectedCloseDateInput.length) {
      expectedCloseDateInput.flatpickr({ dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y' });
    }
    pipelineSelectForm.select2({ dropdownParent: offcanvasElement, placeholder: 'Select Pipeline' });
    stageSelectForm.select2({ dropdownParent: offcanvasElement, placeholder: 'Select Stage' });

    const initAjaxSelect2 = (element, searchUrl, placeholder, parentEl = offcanvasElement) => {
      if (!element.length) return;
      element.select2({
        placeholder: placeholder, dropdownParent: parentEl, allowClear: true,
        ajax: {
          url: searchUrl, dataType: 'json', delay: 250,
          data: (params) => ({ q: params.term, page: params.page || 1, company_id: (element.attr('id') === 'deal_contact_id' ? companySelectForm.val() : null) }),
          processResults: (data, params) => ({ results: data.results, pagination: { more: data.pagination.more } }),
          cache: true
        },
        minimumInputLength: 1
      });
    };
    initAjaxSelect2(companySelectForm, pageData.urls.companySearch, 'Search Company...');
    initAjaxSelect2(contactSelectForm, pageData.urls.contactSearch, 'Search Contact...');
    initAjaxSelect2(assignedToUserSelectForm, pageData.urls.userSearch, 'Search User...');
  };

  const populateOffcanvasForEdit = (deal) => {
    resetOffcanvasForm();
    offcanvasLabel.text('Edit Deal');
    dealIdInput.val(deal.id);
    formMethodInput.val('PUT');

    $('#deal_title').val(deal.title);
    $('#deal_description').val(deal.description);
    $('#deal_value').val(deal.value);
    if(deal.expected_close_date) expectedCloseDateInput[0]._flatpickr.setDate(deal.expected_close_date, true);
    $('#deal_probability').val(deal.probability);

    pipelineSelectForm.val(deal.pipeline_id).trigger('change');
    setTimeout(() => { stageSelectForm.val(deal.deal_stage_id).trigger('change'); }, 200);

    if (deal.company) {
      companySelectForm.append(new Option(deal.company.name, deal.company.id, true, true)).trigger('change');
    }
    if (deal.contact) {
      contactSelectForm.append(new Option(`${deal.contact.first_name} ${deal.contact.last_name}`, deal.contact.id, true, true)).trigger('change');
    }
    if (deal.assigned_to_user) {
      assignedToUserSelectForm.append(new Option(`${deal.assigned_to_user.first_name} ${deal.assigned_to_user.last_name}`, deal.assigned_to_user_id, true, true)).trigger('change');
    }

    if (deal.deal_stage && deal.deal_stage.is_lost_stage) {
      $('#deal_lost_reason').val(deal.lost_reason);
      lostReasonContainer.removeClass('d-none');
    } else {
      lostReasonContainer.addClass('d-none');
    }
    offcanvas.show();
  };

  // Dynamic stage dropdown based on pipeline
  pipelineSelectForm.on('change', function() {
    const selectedPipelineId = $(this).val();
    populateStageDropdown(selectedPipelineId);
  });

  // Show/hide lost reason based on stage
  stageSelectForm.on('change', function() {
    const stageId = $(this).val();
    const pipelineId = pipelineSelectForm.val();
    if (stageId && pipelineId && pageData.pipelines[pipelineId] && pageData.pipelines[pipelineId].stages[stageId]) {
      const stage = pageData.pipelines[pipelineId].stages[stageId];
      if (stage.is_lost_stage) {
        lostReasonContainer.removeClass('d-none');
      } else {
        lostReasonContainer.addClass('d-none').find('textarea').val('');
      }
    } else {
      lostReasonContainer.addClass('d-none').find('textarea').val('');
    }
  });

  // Event Listeners
  // View toggle handlers
  function switchToKanbanView() {
    if (btnKanbanView.length) btnKanbanView.addClass('active');
    if (btnListView.length) btnListView.removeClass('active');
    if (btnKanbanViewTop.length) btnKanbanViewTop.addClass('active');
    if (btnListViewTop.length) btnListViewTop.removeClass('active');
    if (datatableViewContainer.length) datatableViewContainer.addClass('d-none');
    if (kanbanViewContainer.length) {
      kanbanViewContainer.removeClass('d-none');
      loadKanbanData(currentPipelineId);
    }
  }

  function switchToListView() {
    if (btnListView.length) btnListView.addClass('active');
    if (btnKanbanView.length) btnKanbanView.removeClass('active');
    if (btnListViewTop.length) btnListViewTop.addClass('active');
    if (btnKanbanViewTop.length) btnKanbanViewTop.removeClass('active');
    if (kanbanViewContainer.length) kanbanViewContainer.addClass('d-none');
    if (datatableViewContainer.length) {
      datatableViewContainer.removeClass('d-none');
      if (!$.fn.DataTable.isDataTable(dealsTableElement)) {
        initDataTable();
      } else {
        dtDealsTable.ajax.url(pageData.urls.dataTableAjax + '?pipeline_id=' + currentPipelineId).load();
      }
    }
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

  pipelineFilterSelect.select2({
    minimumResultsForSearch: Infinity
  }).on('change', function() {
    currentPipelineId = $(this).val();
    if (kanbanViewContainer.is(':visible')) {
      loadKanbanData(currentPipelineId);
    }
    if (datatableViewContainer.is(':visible') && dtDealsTable) {
      dtDealsTable.ajax.url(pageData.urls.dataTableAjax + '?pipeline_id=' + currentPipelineId).load();
    }
    if (pipelineSelectForm.length) {
      pipelineSelectForm.val(currentPipelineId).trigger('change');
    }
  });

  $('#add-new-deal-btn, #add-new-deal-btn-kanban').on('click', function() {
    resetOffcanvasForm();
    pipelineSelectForm.val(currentPipelineId).trigger('change');
    if (offcanvas) offcanvas.show();
  });

  $(document).on('click', '.edit-deal', function(e) {
    e.preventDefault();
    const url = $(this).data('url');
    $.get(url, function (response) {
      populateOffcanvasForEdit(response);
    }).fail(function() {
      Swal.fire({ icon: 'error', title: 'Error', text: 'Could not fetch deal details.' });
    });
  });

  $(dealForm).on('submit', function(e) {
    e.preventDefault();
    resetFormValidation(this);
    let url = pageData.urls.store;
    const dealId = dealIdInput.val();
    if (dealId) {
      url = getUrl(pageData.urls.updateTemplate, dealId);
    }
    const formData = new FormData(this);
    if (dealId) {
      formData.append('_method', 'PUT');
    }

    const originalButtonText = saveDealBtn.html();
    saveDealBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

    $.ajax({
      url: url, type: 'POST', data: formData, processData: false, contentType: false,
      success: function (response) {
        if (response.code === 200) {
          offcanvas.hide();
          Swal.fire({ icon: 'success', title: 'Success!', text: response.message, timer: 1500, showConfirmButton: false });
          if (kanbanViewContainer.is(':visible')) loadKanbanData(currentPipelineId);
          if (datatableViewContainer.is(':visible') && dtDealsTable) dtDealsTable.ajax.reload(null, false);
        }
      },
      error: function (jqXHR) {
        if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
          showValidationErrors(jqXHR.responseJSON.errors);
          Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
        } else {
          Swal.fire('Error', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');
        }
      },
      complete: function () {
        saveDealBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);

  $(document).on('click', '.delete-deal', function(e) {
    e.preventDefault();
    const url = $(this).data('url');
    Swal.fire({
      title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning',
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
              Swal.fire({ icon: 'success', title: 'Deleted!', text: response.message });
              if (kanbanViewContainer.is(':visible')) loadKanbanData(currentPipelineId);
              if (datatableViewContainer.is(':visible') && dtDealsTable) dtDealsTable.ajax.reload(null, false);
            } else { Swal.fire({ icon: 'error', title: 'Error', text: response.message }); }
          },
          error: function () { Swal.close(); Swal.fire({ icon: 'error', title: 'Error', text: 'Could not delete deal.' });}
        });
      }
    });
  });

  // Check for ?edit_deal=ID on page load
  const urlParams = new URLSearchParams(window.location.search);
  const editDealId = urlParams.get('edit_deal');
  if (editDealId) {
    const editUrl = getUrl(pageData.urls.getDealTemplate, editDealId);
    $.get(editUrl, function (response) {
      populateOffcanvasForEdit(response);
    }).fail(function() {
      console.error('Failed to fetch deal for pre-edit on page load.');
    });
  }

  // Fix dropdown z-index issues in kanban view
  $(document).on('shown.bs.dropdown', '.kanban-card .dropdown', function() {
    const $dropdown = $(this);
    const $menu = $dropdown.find('.dropdown-menu');
    $dropdown.closest('.kanban-card').css('z-index', '100');
    $menu.css('z-index', '9999');
  });
  
  $(document).on('hidden.bs.dropdown', '.kanban-card .dropdown', function() {
    $(this).closest('.kanban-card').css('z-index', '');
  });

  // Initialization
  populatePipelineDropdown();
  initializeFormElements();
  btnKanbanView.trigger('click');
});