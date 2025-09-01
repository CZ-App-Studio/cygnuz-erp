'use strict';

$(function () {
  // 1. SETUP & VARIABLE DECLARATIONS
  // =================================================================================================
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  if (typeof pageData === 'undefined' || !pageData.companyId || !pageData.urls || !pageData.labels) {
    console.error('JS pageData object with companyId, URLs, and labels is not defined in Blade.');
    return;
  }

  const currentCompanyId = pageData.companyId;
  const currentCompanyName = pageData.companyName;

  // --- Deal Offcanvas Elements & State ---
  const dealOffcanvasElement = document.getElementById('offcanvasDealForm'); // From deals._form.blade.php
  const dealOffcanvas = dealOffcanvasElement ? new bootstrap.Offcanvas(dealOffcanvasElement) : null;
  const dealForm = document.getElementById('dealForm');
  const saveDealBtn = $('#saveDealBtn'); // In deals._form.blade.php
  const dealIdInput = $('#deal_id');
  const dealFormMethodInput = $(dealForm).find('#formMethod'); // Ensure correct ID
  const dealOffcanvasLabel = $('#offcanvasDealFormLabel');
  const dealPipelineSelectForm = $('#deal_pipeline_id');
  const dealStageSelectForm = $('#deal_stage_id');
  const dealCompanySelectForm = $('#deal_company_id');
  const dealContactSelectForm = $('#deal_contact_id');
  const dealAssignedToUserSelectForm = $('#deal_assigned_to_user_id');
  const dealExpectedCloseDateInput = $('#deal_expected_close_date');
  const dealLostReasonContainer = $('#lost_reason_container');


  // --- Task Offcanvas Elements & State ---
  const taskOffcanvasElement = document.getElementById('offcanvasTaskForm'); // From tasks._form.blade.php
  const taskOffcanvas = taskOffcanvasElement ? new bootstrap.Offcanvas(taskOffcanvasElement) : null;
  const taskForm = document.getElementById('taskForm');
  const saveTaskBtn = $('#saveTaskBtn'); // In tasks._form.blade.php
  const taskIdInput = $('#task_id');
  const taskFormMethodInput = $(taskForm).find('#formMethod'); // Ensure correct ID
  const taskOffcanvasLabel = $('#offcanvasTaskFormLabel');
  const taskTitleInput = $('#task_title');
  const taskDescriptionInput = $('#task_description');
  const taskDueDateInput = $('#task_due_date');
  const taskReminderAtInput = $('#task_reminder_at');
  const taskStatusSelect = $('#task_status_id');
  const taskPrioritySelect = $('#task_priority_id');
  const taskAssignedToUserSelect = $('#task_assigned_to_user_id');
  const taskableTypeSelector = $('#taskable_type_selector');
  const taskableIdSelector = $('#taskable_id_selector');


  // 2. HELPER FUNCTIONS
  // =================================================================================================
  const getUrl = (template, id = null) => {
    if (id === null) return template; // For URLs that don't need an ID (like store URLs)

    // Check for specific placeholders and replace them
    if (template.includes('__DEAL_ID__')) {
      return template.replace('__DEAL_ID__', id);
    } else if (template.includes('__TASK_ID__')) {
      return template.replace('__TASK_ID__', id);
    }
    // Add more else if blocks for other entity placeholders if needed
    // e.g. else if (template.includes('__CONTACT_ID__')) { ... }

    // Fallback if no known placeholder is found but an ID is provided (less ideal)
    // This part might be reached if some URLs use a generic :id or {id} from other contexts
    // For now, we expect specific placeholders from the Blade @php block.
    console.warn("getUrl called with ID but no known placeholder matched in template:", template);
    return template; // Or handle error appropriately
  };

  const resetFormValidation = (form) => {
    if (!form) return;
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
    // Clear specific error divs if they have IDs
    $(form).find('[id$="_error"]').text('');
  };

  const showValidationErrors = (form, errors) => {
    resetFormValidation(form);
    $.each(errors, function (key, value) {
      const inputId = `#${$(form).find(`[name="${key}"]`).attr('id')}`; // Get ID from name
      let input = $(inputId);
      if (key === 'taskable_id_selector' || key === 'taskable_type_selector') { // Specific IDs from task form
        input = $(`#${key}`);
      } else if ($(form).attr('id') === 'dealForm' && !$(`#deal_${key}`).length) {
        input = $(form).find(`[name="${key}"]`); // Fallback for deal form if no prefix
      } else if ($(form).attr('id') === 'taskForm' && !$(`#task_${key}`).length) {
        input = $(form).find(`[name="${key}"]`); // Fallback for task form
      }


      if (input.length) {
        input.addClass('is-invalid');
        let feedbackDiv = input.siblings('.invalid-feedback');
        if(!feedbackDiv.length && input.parent().hasClass('input-group')){ // For inputs in input-groups
          feedbackDiv = input.parent().siblings('.invalid-feedback');
        }
        if(!feedbackDiv.length && input.next().hasClass('select2-container')) { // For Select2
          feedbackDiv = input.next().siblings('.invalid-feedback');
        }
        if(!feedbackDiv.length) { // Create if not exists
          feedbackDiv = $('<div class="invalid-feedback"></div>').insertAfter(input.next('.select2-container').length ? input.next('.select2-container') : input);
        }
        feedbackDiv.text(value[0]);
      } else {
        console.warn(`Validation error for field ${key}, but no matching input found.`);
      }
    });
    $(form).find('.is-invalid:first').focus();
  };


  // 3. DEAL OFFCANVAS & FORM FUNCTIONS
  // =================================================================================================
  const updateDealsTabAfterCreate = () => {
    // Reload only the deals tab content by making an AJAX call to get updated company data
    $.ajax({
      url: pageData.urls.companyDealsAjax,
      type: 'GET',
      success: function(response) {
        if (response.status === 'success' && response.data && response.data.deals) {
          updateDealsTabContent(response.data.deals);
          updateDealsBadgeCount(response.data.deals.length);
        } else {
          // Fallback: reload just the deals section by making a full reload if AJAX endpoint doesn't exist
          // For now, we'll use a simpler approach: reload the page but with a flag to switch to deals tab
          location.reload();
        }
      },
      error: function() {
        // Fallback to full page reload if AJAX fails
        location.reload();
      }
    });
  };

  const updateDealsTabContent = (deals) => {
    const dealsTableBody = $('#navs-deals tbody');
    const noDealsMessage = $('#navs-deals p:contains("No deals")');
    
    if (deals && deals.length > 0) {
      // Hide "no deals" message and show table
      noDealsMessage.hide();
      $('#navs-deals .table-responsive').show();
      
      // Clear existing rows
      dealsTableBody.empty();
      
      // Add new rows
      deals.forEach(deal => {
        const dealShowUrl = window.location.origin + '/deals/' + deal.id;
        const dealValue = parseFloat(deal.value || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
        const stageColor = deal.deal_stage?.color || '#6c757d';
        const stageName = deal.deal_stage?.name || '-';
        const expectedCloseDate = deal.expected_close_date 
          ? new Date(deal.expected_close_date).toLocaleDateString('en-US', {day: 'numeric', month: 'short', year: 'numeric'}) 
          : '-';
        const contactName = deal.contact?.first_name 
          ? deal.contact.first_name + ' ' + (deal.contact.last_name || '')
          : '-';
        const assignedUserName = deal.assigned_to_user?.first_name 
          ? deal.assigned_to_user.first_name + ' ' + (deal.assigned_to_user.last_name || '')
          : '-';
        
        const row = `
          <tr>
            <td><a href="${dealShowUrl}">${deal.title}</a></td>
            <td>$${dealValue}</td>
            <td><span class="badge" style="background-color:${stageColor}; color:#fff;">${stageName}</span></td>
            <td>${expectedCloseDate}</td>
            <td>${contactName}</td>
            <td>${assignedUserName}</td>
            <td><button class="btn btn-xs btn-icon item-edit edit-deal-from-related" data-deal-id="${deal.id}" title="Edit Deal"><i class="bx bx-pencil"></i></button></td>
          </tr>
        `;
        dealsTableBody.append(row);
      });
    } else {
      // Show "no deals" message and hide table
      $('#navs-deals .table-responsive').hide();
      noDealsMessage.show();
    }
  };

  const updateDealsBadgeCount = (count) => {
    // Update the badge count on the deals tab
    $('#navs-deals-tab .badge, button[data-bs-target="#navs-deals"] .badge').text(count);
  };

  const resetDealOffcanvas = () => {
    if (!dealForm) return;
    resetFormValidation(dealForm);
    dealForm.reset();
    dealIdInput.val('');
    dealFormMethodInput.val('POST');
    dealOffcanvasLabel.text(pageData.labels.addNewDeal || 'Add New Deal');
    dealLostReasonContainer.addClass('d-none');

    dealPipelineSelectForm.val(pageData.initialPipelineId || '').trigger('change'); // Triggers stage population
    dealCompanySelectForm.empty().val(null).trigger('change');
    dealContactSelectForm.empty().val(null).trigger('change');
    dealAssignedToUserSelectForm.empty().val(null).trigger('change');
    if (dealExpectedCloseDateInput[0]?._flatpickr) dealExpectedCloseDateInput[0]._flatpickr.clear();
    saveDealBtn.prop('disabled', false).html(pageData.labels.saveDeal || 'Save Deal');
  };

  const initDealFormElements = () => {
    if (!dealForm) return;
    // Static Select2 for Pipeline & Stage
    dealPipelineSelectForm.select2({ dropdownParent: dealOffcanvasElement, placeholder: 'Select Pipeline' });
    dealStageSelectForm.select2({ dropdownParent: dealOffcanvasElement, placeholder: 'Select Stage' });

    // AJAX Select2
    const initAjaxSelect2 = (element, searchUrl, placeholder) => {
      if (!element.length || !searchUrl) return;
      element.select2({
        placeholder: placeholder, dropdownParent: dealOffcanvasElement, allowClear: true,
        ajax: {
          url: searchUrl, dataType: 'json', delay: 250,
          data: (params) => ({ q: params.term, page: params.page || 1, company_id: (element.attr('id') === 'deal_contact_id' ? dealCompanySelectForm.val() : null) }),
          processResults: (data, params) => ({ results: data.results, pagination: { more: data.pagination.more } }),
          cache: true
        }, minimumInputLength: 1
      });
    };
    initAjaxSelect2(dealCompanySelectForm, pageData.urls.companySearch, 'Search Company...');
    initAjaxSelect2(dealContactSelectForm, pageData.urls.contactSearch, 'Search Contact...');
    initAjaxSelect2(dealAssignedToUserSelectForm, pageData.urls.userSearch, 'Search User...');

    // Flatpickr
    if (dealExpectedCloseDateInput.length) {
      dealExpectedCloseDateInput.flatpickr({ dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y' });
    }

    // Populate Pipeline Dropdown
    dealPipelineSelectForm.empty().append($('<option>', { value: '', text: 'Select Pipeline...' }));
    $.each(pageData.allPipelinesForForm, function(id, name) {
      dealPipelineSelectForm.append($('<option>', { value: id, text: name }));
    });

    // Dynamic Stage Dropdown
    dealPipelineSelectForm.on('change', function() {
      const selectedPipelineId = $(this).val();
      dealStageSelectForm.empty().append($('<option>', { value: '', text: 'Select Stage...' })).prop('disabled', true);

      // Use pageData.pipelinesWithStages here
      if (selectedPipelineId && pageData.pipelinesWithStages[selectedPipelineId] && pageData.pipelinesWithStages[selectedPipelineId].stages) {
        $.each(pageData.pipelinesWithStages[selectedPipelineId].stages, function(id, stage) {
          // Only add stages that are not final (won/lost) to the dropdown for active deal management
          if (!stage.is_won_stage && !stage.is_lost_stage) {
            dealStageSelectForm.append($('<option>', { value: id, text: stage.name }));
          }
        });
        dealStageSelectForm.prop('disabled', false);
      }
      dealStageSelectForm.trigger('change.select2'); // Update Select2 display
    });


    // Show/hide lost reason based on stage
    dealStageSelectForm.on('change', function() {
      const stageId = $(this).val();
      const pipelineId = dealPipelineSelectForm.val();
      if (stageId && pipelineId && pageData.pipelinesWithStages && pageData.pipelinesWithStages[pipelineId] && pageData.pipelinesWithStages[pipelineId].stages && pageData.pipelinesWithStages[pipelineId].stages[stageId]) {
        const stage = pageData.pipelinesWithStages[pipelineId].stages[stageId];
        if (stage && stage.is_lost_stage) {
          dealLostReasonContainer.removeClass('d-none');
        } else {
          dealLostReasonContainer.addClass('d-none').find('textarea').val('');
        }
      } else {
        dealLostReasonContainer.addClass('d-none').find('textarea').val('');
      }
    });
  };

  const populateDealOffcanvasForEdit = (deal) => {
    if (!dealForm) return;
    resetDealOffcanvas();
    dealOffcanvasLabel.text(pageData.labels.editDeal || 'Edit Deal');
    dealIdInput.val(deal.id);
    dealFormMethodInput.val('PUT');

    $('#deal_title').val(deal.title);
    $('#deal_description').val(deal.description);
    $('#deal_value').val(deal.value);
    if(deal.expected_close_date && dealExpectedCloseDateInput[0]?._flatpickr) dealExpectedCloseDateInput[0]._flatpickr.setDate(deal.expected_close_date, true);
    $('#deal_probability').val(deal.probability);

    dealPipelineSelectForm.val(deal.pipeline_id).trigger('change');
    setTimeout(() => { dealStageSelectForm.val(deal.deal_stage_id).trigger('change'); }, 250); // Allow stages to populate

    if (deal.company) dealCompanySelectForm.append(new Option(deal.company.name, deal.company.id, true, true)).trigger('change');
    if (deal.contact) dealContactSelectForm.append(new Option(deal.contact.first_name + ' ' + deal.contact.last_name, deal.contact.id, true, true)).trigger('change');
    if (deal.assigned_to_user) dealAssignedToUserSelectForm.append(new Option(deal.assigned_to_user.first_name + ' ' + deal.assigned_to_user.last_name, deal.assigned_to_user_id, true, true)).trigger('change');

    if (deal.deal_stage && deal.deal_stage.is_lost_stage) {
      $('#deal_lost_reason').val(deal.lost_reason);
      dealLostReasonContainer.removeClass('d-none');
    }
    dealOffcanvas.show();
  };

  $('.add-new-deal-for-company').on('click', function() {
    if (dealOffcanvas) {
      resetDealOffcanvas();
      if (currentCompanyId && currentCompanyName) {
        dealCompanySelectForm.append(new Option(currentCompanyName, currentCompanyId, true, true)).trigger('change');
      }
      // Set initial pipeline from pageData if available
      if(pageData.initialPipelineId){
        dealPipelineSelectForm.val(pageData.initialPipelineId).trigger('change');
      }
      dealOffcanvas.show();
    }
  });

  if (dealForm) {
    $(dealForm).on('submit', function(e) {
      e.preventDefault();
      resetFormValidation(this);
      let url = pageData.urls.dealStore || pageData.urls.store; // Fallback if dealStore not defined
      const currentDealId = dealIdInput.val();
      if (currentDealId) {
        url = getUrl(pageData.urls.dealUpdateTemplate || pageData.urls.updateTemplate, currentDealId);
      }
      const formData = new FormData(this);
      if (currentDealId) formData.append('_method', 'PUT');

      const originalButtonText = saveDealBtn.html();
      saveDealBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + pageData.labels.saving);

      $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: function (response) {
          if (response.status === 'success') {
            dealOffcanvas.hide();
            Swal.fire({ 
              icon: 'success', 
              title: pageData.labels.success, 
              text: response.message || (response.data && response.data.message) || pageData.labels.operationFailed, 
              timer: 2000, 
              showConfirmButton: false 
            });
            // Update the deals tab content dynamically instead of full page reload
            updateDealsTabAfterCreate();
          } else {
            Swal.fire(pageData.labels.error, response.message || (response.data && response.data.message) || pageData.labels.operationFailed, 'error');
          }
        },
        error: function (jqXHR) {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(dealForm, jqXHR.responseJSON.errors);
            Swal.fire(pageData.labels.validationError, jqXHR.responseJSON.message || pageData.labels.pleaseCorrectErrors, 'error');
          } else {
            Swal.fire(pageData.labels.error, jqXHR.responseJSON?.message || pageData.labels.unexpectedError, 'error');
          }
        },
        complete: function () { saveDealBtn.prop('disabled', false).html(originalButtonText); }
      });
    });
  }
  if(dealOffcanvasElement) dealOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetDealOffcanvas);


  // 4. TASK OFFCANVAS & FORM FUNCTIONS
  // =================================================================================================
  const updateTasksTabAfterCreate = () => {
    // For simplicity, just reload the page for tasks
    // In a more advanced implementation, we could create a similar AJAX endpoint for tasks
    location.reload();
  };

  const resetTaskOffcanvas = () => {
    if (!taskForm) return;
    resetFormValidation(taskForm);
    taskForm.reset();
    taskIdInput.val('');
    taskFormMethodInput.val('POST');
    taskOffcanvasLabel.text(pageData.labels.addNewTask || 'Add New Task');

    taskStatusSelect.val('').trigger('change'); // Assuming default is handled by controller or form
    taskPrioritySelect.val('').trigger('change');
    taskAssignedToUserSelect.empty().val(null).trigger('change');
    taskableTypeSelector.val('').trigger('change'); // This will also clear/disable taskableIdSelector
    if (taskDueDateInput[0]?._flatpickr) taskDueDateInput[0]._flatpickr.clear();
    if (taskReminderAtInput[0]?._flatpickr) taskReminderAtInput[0]._flatpickr.clear();
    saveTaskBtn.prop('disabled', false).html(pageData.labels.saveTask || 'Save Task');
  };

  const initTaskFormElements = () => {
    if(!taskForm) return;
    // Static Select2s
    taskStatusSelect.empty().append($('<option>', { value: '', text: 'Select Status...' }));
    $.each(pageData.taskStatuses, (id, name) => taskStatusSelect.append($('<option>', { value: id, text: name })));
    taskPrioritySelect.empty().append($('<option>', { value: '', text: 'Select Priority...' }));
    $.each(pageData.taskPriorities, (id, name) => taskPrioritySelect.append($('<option>', { value: id, text: name })));
    $('#task_status_id, #task_priority_id, #taskable_type_selector').select2({ dropdownParent: taskOffcanvasElement, allowClear: true });

    // AJAX Select2 for Assigned User
    taskAssignedToUserSelect.select2({
      placeholder: 'Search User...', dropdownParent: taskOffcanvasElement, allowClear: true,
      ajax: { url: pageData.urls.userSearch, dataType: 'json', delay: 250, data: (p) => ({ q: p.term, page: p.page || 1}), processResults: (d,p) => ({results:d.results, pagination:{more:d.pagination.more}}), cache:true }, minimumInputLength:1
    });

    // AJAX Select2 for Taskable ID (dependent on type)
    taskableIdSelector.select2({ placeholder: 'Select Type First...', dropdownParent: taskOffcanvasElement, allowClear: true, disabled: true });
    taskableTypeSelector.on('change', function() {
      const type = $(this).val();
      const taskableIdSelect = $('#taskable_id_selector'); // Ensure this targets the correct element
      taskableIdSelect.empty().val(null).trigger('change'); // Clear previous options and value

      let searchUrl = null;
      if (type) {
        switch(type.toLowerCase()) {
          case 'contact': searchUrl = pageData.urls.contactSearch; break;
          case 'company': searchUrl = pageData.urls.companySearch; break;
          case 'lead':    searchUrl = pageData.urls.leadSearch;    break;
          case 'deal':    searchUrl = pageData.urls.dealSearch;    break; // Ensure dealSearch is correct URL from pageData
        }
      }

      if (searchUrl) {
        taskableIdSelect.select2('destroy').select2({
          placeholder: `Search ${type}...`,
          dropdownParent: taskOffcanvasElement, // taskOffcanvasElement must be defined
          allowClear: true,
          disabled: false,
          ajax: {
            url: searchUrl,
            dataType: 'json',
            delay: 250,
            data: (params) => ({ q: params.term, page: params.page || 1 }),
            processResults: (data, params) => ({ results: data.results, pagination: { more: data.pagination.more } }),
            cache: true
          },
          minimumInputLength: 1
        });
      } else {
        taskableIdSelect.select2('destroy').select2({
          placeholder: 'Select Type First...',
          dropdownParent: taskOffcanvasElement,
          allowClear: true,
          disabled: true
        });
      }
    });

    // Flatpickr
    if (taskDueDateInput.length) taskDueDateInput.flatpickr({ enableTime: true, dateFormat: "Y-m-d H:i", altInput: true, altFormat: "F j, Y H:i" });
    if (taskReminderAtInput.length) taskReminderAtInput.flatpickr({ enableTime: true, dateFormat: "Y-m-d H:i", altInput: true, altFormat: "F j, Y H:i", minDate: "today" });
  };

  const populateTaskOffcanvasForEdit = (task) => {
    if (!taskForm) return;
    resetTaskOffcanvas();
    taskOffcanvasLabel.text(pageData.labels.editTask || 'Edit Task');
    taskIdInput.val(task.id);
    taskFormMethodInput.val('PUT');

    taskTitleInput.val(task.title);
    taskDescriptionInput.val(task.description);
    if (task.due_date && taskDueDateInput[0]?._flatpickr) taskDueDateInput[0]._flatpickr.setDate(task.due_date, true);
    if (task.reminder_at && taskReminderAtInput[0]?._flatpickr) taskReminderAtInput[0]._flatpickr.setDate(task.reminder_at, true);
    taskStatusSelect.val(task.task_status_id).trigger('change');
    taskPrioritySelect.val(task.task_priority_id).trigger('change');

    if (task.assigned_to_user) taskAssignedToUserSelect.append(new Option(task.assigned_to_user.first_name + ' ' + task.assigned_to_user.last_name, task.assigned_to_user_id, true, true)).trigger('change');

    if (task.taskable_type && task.taskable_id) {
      const typeName = task.taskable_type.split('\\').pop();
      taskableTypeSelector.val(typeName).trigger('change'); // This re-initializes taskableIdSelector

      let taskableText = `Record (${typeName}) ID: ${task.taskable_id}`;
      if(task.taskable) { // task.taskable must be loaded via eager loading from controller
        switch (typeName) {
          case 'Contact': taskableText = task.taskable.first_name + ' ' + task.taskable.last_name; break;
          case 'Company': taskableText = task.taskable.name; break;
          case 'Lead': case 'Deal': taskableText = task.taskable.title; break;
        }
      }
      // Important: Set value *after* the change event has reconfigured the Select2
      setTimeout(() => {
        if (taskableIdSelector.data('select2')) { // Check if select2 is initialized
          taskableIdSelector.append(new Option(taskableText, task.taskable_id, true, true)).trigger('change');
        } else {
          // Fallback if Select2 isn't ready, might need more robust handling
          console.warn("Taskable ID Select2 not ready for pre-population during edit.");
        }
      }, 300); // Adjust delay if necessary
    }
    taskOffcanvas.show();
  };

  $('.add-new-task-for-company').on('click', function() {
    if (taskOffcanvas) {
      resetTaskOffcanvas();
      taskableTypeSelector.val('Company').trigger('change');
      setTimeout(() => {
        taskableIdSelector.append(new Option(currentCompanyName, currentCompanyId, true, true)).trigger('change');
      }, 300);
      taskOffcanvas.show();
    }
  });

  $(document).on('click', '.edit-task-from-related', function() { // For tasks listed on company page
    const taskId = $(this).data('task-id');
    const url = getUrl(pageData.urls.getTaskTemplate, taskId);
    $.get(url, populateTaskOffcanvasForEdit).fail(() => Swal.fire(pageData.labels.error, pageData.labels.couldNotFetch, 'error'));
  });

  if (taskForm) {
    $(taskForm).on('submit', function(e) {
      e.preventDefault();
      resetFormValidation(this);
      let url = pageData.urls.taskStore || pageData.urls.store; // Fallback
      const currentTaskId = taskIdInput.val();
      if (currentTaskId) {
        url = getUrl(pageData.urls.taskUpdateTemplate || pageData.urls.updateTemplate, currentTaskId);
      }
      const formData = new FormData(this);
      if (currentTaskId) formData.append('_method', 'PUT');

      // Ensure taskable_type has the full model namespace if your backend expects it for morphTo
      // The controller provided earlier maps string 'Contact' to Model::class
      // formData.set('taskable_type', mapTaskableTypeToModel(taskableTypeSelector.val()));

      const originalButtonText = saveTaskBtn.html();
      saveTaskBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + pageData.labels.saving);

      $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: function (response) {
          if (response.status === 'success') {
            taskOffcanvas.hide();
            Swal.fire({ 
              icon: 'success', 
              title: pageData.labels.success, 
              text: response.data.message || response.message, 
              timer: 2000, 
              showConfirmButton: false 
            });
            // Update the tasks tab content dynamically instead of full page reload
            updateTasksTabAfterCreate();
          } else {
            Swal.fire(pageData.labels.error, response.data || pageData.labels.operationFailed, 'error');
          }
        },
        error: function (jqXHR) {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(taskForm, jqXHR.responseJSON.errors);
            Swal.fire(pageData.labels.validationError, jqXHR.responseJSON.message || pageData.labels.pleaseCorrectErrors, 'error');
          } else {
            Swal.fire(pageData.labels.error, jqXHR.responseJSON?.message || pageData.labels.unexpectedError, 'error');
          }
        },
        complete: function () { saveTaskBtn.prop('disabled', false).html(originalButtonText); }
      });
    });
  }
  if(taskOffcanvasElement) taskOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetTaskOffcanvas);


  // 5. CONTACT PAGE SPECIFIC: Add New Contact redirect
  // =================================================================================================
  // This was on company-show-interactions.js, should be here if this is a generic show-interactions.js
  // For now, assuming this file is company-show-interactions.js
  $('.add-new-contact-for-company').on('click', function() {
    let createContactUrl = pageData.urls.contactSearch ? pageData.urls.contactSearch.replace('select-search', 'create') : null;
    // A better way: pass a direct createContactUrl in pageData
    if(pageData.urls.contactCreateUrl) createContactUrl = pageData.urls.contactCreateUrl;

    if (createContactUrl) {
      window.location.href = `${createContactUrl}?company_id=${currentCompanyId}&company_name=${encodeURIComponent(currentCompanyName)}`;
    } else {
      console.error("Contact create URL not defined in pageData.");
      // Fallback, ensure you have a general contact create route named 'contacts.create'
      // window.location.href = '/contacts/create?company_id=' + currentCompanyId;
    }
  });


  // 6. INITIALIZATION CALLS (for elements within included offcanvases)
  // =================================================================================================
  if (dealOffcanvasElement) initDealFormElements();
  if (taskOffcanvasElement) initTaskFormElements();

});
