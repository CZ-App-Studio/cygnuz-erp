'use strict';

$(function () {
  // 1. SETUP & VARIABLE DECLARATIONS
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  if (typeof pageData === 'undefined' || !pageData.contactId || !pageData.urls) {
    console.error('JS pageData object with contactId & URLs is not defined in Blade.');
    return;
  }

  const currentContactId = pageData.contactId;
  const currentContactName = pageData.contactName;
  const currentContactCompanyId = pageData.contactCompanyId; // Company ID of the current contact
  const currentContactCompanyName = pageData.contactCompanyName;

  // Deal Offcanvas Elements
  const dealOffcanvasElement = document.getElementById('offcanvasDealForm');
  const dealOffcanvas = dealOffcanvasElement ? new bootstrap.Offcanvas(dealOffcanvasElement) : null;
  const dealForm = document.getElementById('dealForm');
  const saveDealBtn = $(dealForm).find('#saveDealBtn'); // Scoped to dealForm
  const dealIdInput = $(dealForm).find('#deal_id');
  const dealFormMethodInput = $(dealForm).find('#formMethod');
  const dealOffcanvasLabel = $(dealOffcanvasElement).find('#offcanvasDealFormLabel');
  const dealPipelineSelectForm = $(dealForm).find('#deal_pipeline_id');
  const dealStageSelectForm = $(dealForm).find('#deal_stage_id');
  const dealCompanySelectForm = $(dealForm).find('#deal_company_id');
  const dealContactSelectForm = $(dealForm).find('#deal_contact_id');
  const dealAssignedToUserSelectForm = $(dealForm).find('#deal_assigned_to_user_id');
  const dealExpectedCloseDateInput = $(dealForm).find('#deal_expected_close_date');
  const dealLostReasonContainer = $(dealForm).find('#lost_reason_container');


  // Task Offcanvas Elements
  const taskOffcanvasElement = document.getElementById('offcanvasTaskForm');
  const taskOffcanvas = taskOffcanvasElement ? new bootstrap.Offcanvas(taskOffcanvasElement) : null;
  const taskForm = document.getElementById('taskForm');
  const saveTaskBtn = $(taskForm).find('#saveTaskBtn');
  const taskIdInput = $(taskForm).find('#task_id');
  const taskFormMethodInput = $(taskForm).find('#formMethod');
  const taskOffcanvasLabel = $(taskOffcanvasElement).find('#offcanvasTaskFormLabel');
  const taskTitleInput = $(taskForm).find('#task_title');
  const taskDescriptionInput = $(taskForm).find('#task_description');
  const taskDueDateInput = $(taskForm).find('#task_due_date');
  const taskReminderAtInput = $(taskForm).find('#task_reminder_at');
  const taskStatusSelect = $(taskForm).find('#task_status_id');
  const taskPrioritySelect = $(taskForm).find('#task_priority_id');
  const taskAssignedToUserSelect = $(taskForm).find('#task_assigned_to_user_id');
  const taskableTypeSelector = $(taskForm).find('#taskable_type_selector');
  const taskableIdSelector = $(taskForm).find('#taskable_id_selector');

  // 2. HELPER FUNCTIONS (same as company-show-interactions.js)
  const getUrl = (template, id = null) => { /* ... use __DEAL_ID__ and __TASK_ID__ placeholders ... */
    if (id === null) return template;
    if (template.includes('__DEAL_ID__')) return template.replace('__DEAL_ID__', id);
    if (template.includes('__TASK_ID__')) return template.replace('__TASK_ID__', id);
    console.warn("getUrl: No known placeholder found in template:", template);
    return template;
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
  const resetDealOffcanvas = () => {
    if (!dealForm) return;
    resetFormValidation(dealForm);
    dealForm.reset();
    dealIdInput.val(''); dealFormMethodInput.val('POST'); dealOffcanvasLabel.text('Add New Deal');
    dealLostReasonContainer.addClass('d-none');
    dealPipelineSelectForm.val(pageData.initialPipelineId || '').trigger('change');
    dealCompanySelectForm.empty().val(null).trigger('change');
    dealContactSelectForm.empty().val(null).trigger('change');
    dealAssignedToUserSelectForm.empty().val(null).trigger('change');
    if (dealExpectedCloseDateInput.length && dealExpectedCloseDateInput[0]._flatpickr) dealExpectedCloseDateInput[0]._flatpickr.clear();
    saveDealBtn.prop('disabled', false).html('Save Deal');
  };

  const initDealFormElements = () => {
    if (!dealForm || $(dealForm).data('initialized')) return;
    // Init static Select2s for pipeline/stage
    dealPipelineSelectForm.select2({ dropdownParent: dealOffcanvasElement, placeholder: 'Select Pipeline' });
    dealStageSelectForm.select2({ dropdownParent: dealOffcanvasElement, placeholder: 'Select Stage' });
    // Init AJAX Select2s
    const initAjaxSelect2 = (element, searchUrl, placeholder, parentEl = dealOffcanvasElement) => {
      if (!element.length || !searchUrl) return;
      element.select2({
        placeholder: placeholder, dropdownParent: parentEl, allowClear: true,
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
    // Init Flatpickr
    if (dealExpectedCloseDateInput.length) dealExpectedCloseDateInput.flatpickr({ dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y' });
    // Populate Pipeline Dropdown
    dealPipelineSelectForm.empty().append($('<option>', { value: '', text: 'Select Pipeline...' }));
    $.each(pageData.allPipelinesForForm, (id, name) => dealPipelineSelectForm.append($('<option>', { value: id, text: name })));
    // Dynamic Stage Dropdown & Lost Reason
    dealPipelineSelectForm.off('change.dealFormEvents').on('change.dealFormEvents', function() { /* ... same as company-show ... */
      const selectedPipelineId = $(this).val();
      dealStageSelectForm.empty().append($('<option>', { value: '', text: 'Select Stage...' })).prop('disabled', true);
      if (selectedPipelineId && pageData.pipelinesWithStages[selectedPipelineId] && pageData.pipelinesWithStages[selectedPipelineId].stages) {
        $.each(pageData.pipelinesWithStages[selectedPipelineId].stages, function(id, stage) {
          if (!stage.is_won_stage && !stage.is_lost_stage) {
            dealStageSelectForm.append($('<option>', { value: id, text: stage.name }));
          }
        });
        dealStageSelectForm.prop('disabled', false);
      }
      dealStageSelectForm.trigger('change.select2');
    });
    dealStageSelectForm.off('change.dealFormEventsLostReason').on('change.dealFormEventsLostReason', function() { /* Lost Reason Logic */
      const stageId = $(this).val();
      const pipelineId = dealPipelineSelectForm.val();
      if (stageId && pipelineId && pageData.pipelinesWithStages[pipelineId] && pageData.pipelinesWithStages[pipelineId].stages[stageId]) {
        const stage = pageData.pipelinesWithStages[pipelineId].stages[stageId];
        if (stage.is_lost_stage) {
          dealLostReasonContainer.removeClass('d-none');
        } else {
          dealLostReasonContainer.addClass('d-none').find('textarea').val('');
        }
      } else {
        dealLostReasonContainer.addClass('d-none').find('textarea').val('');
      }
    });
    $(dealForm).data('initialized', true);
  };

  const populateDealOffcanvasForEdit = (deal) => {
    if (!dealForm) return;
    resetDealOffcanvas();
    dealOffcanvasLabel.text('Edit Deal');
    dealIdInput.val(deal.id);
    dealFormMethodInput.val('PUT');
    $(dealForm).find('#deal_title').val(deal.title);
    $(dealForm).find('#deal_description').val(deal.description);
    $(dealForm).find('#deal_value').val(deal.value);
    if(deal.expected_close_date && dealExpectedCloseDateInput.length && dealExpectedCloseDateInput[0]._flatpickr) dealExpectedCloseDateInput[0]._flatpickr.setDate(deal.expected_close_date, true);
    $(dealForm).find('#deal_probability').val(deal.probability);

    dealPipelineSelectForm.val(deal.pipeline_id).trigger('change');
    setTimeout(() => { dealStageSelectForm.val(deal.deal_stage_id).trigger('change'); }, 300);

    if (deal.company) dealCompanySelectForm.append(new Option(deal.company.name, deal.company.id, true, true)).trigger('change');
    if (deal.contact) dealContactSelectForm.append(new Option(deal.contact.first_name + ' ' + deal.contact.last_name, deal.contact.id, true, true)).trigger('change');
    if (deal.assigned_to_user) dealAssignedToUserSelectForm.append(new Option(deal.assigned_to_user.first_name + ' ' + deal.assigned_to_user.last_name, deal.assigned_to_user_id, true, true)).trigger('change');

    if (deal.deal_stage && deal.deal_stage.is_lost_stage) {
      $(dealForm).find('#deal_lost_reason').val(deal.lost_reason);
      dealLostReasonContainer.removeClass('d-none');
    }
    dealOffcanvas.show();
  };

  $('.add-new-deal-for-contact').on('click', function() {
    if (dealOffcanvas) {
      initDealFormElements();
      resetDealOffcanvas();
      // Pre-fill Contact
      if (currentContactId && currentContactName) {
        dealContactSelectForm.append(new Option(currentContactName, currentContactId, true, true)).trigger('change');
      }
      // Pre-fill Company if this contact has one
      if (currentContactCompanyId && currentContactCompanyName) {
        dealCompanySelectForm.append(new Option(currentContactCompanyName, currentContactCompanyId, true, true)).trigger('change');
      }
      if(pageData.initialPipelineId) dealPipelineSelectForm.val(pageData.initialPipelineId).trigger('change');
      dealOffcanvas.show();
    }
  });
  $(document).on('click', '.edit-deal-from-related', function() { // For deals listed on contact page
    const dealId = $(this).data('deal-id');
    const url = getUrl(pageData.urls.getDealTemplate, dealId);
    initDealFormElements();
    $.get(url, populateDealOffcanvasForEdit).fail(() => Swal.fire('Error','Could not fetch deal details.','error'));
  });

  if (dealForm) {
    $(dealForm).on('submit', function(e) { /* AJAX Submit Logic for Deals */
      e.preventDefault(); resetFormValidation(this);
      let url = pageData.urls.dealStore;
      const currentDealId = dealIdInput.val();
      if (currentDealId) url = getUrl(pageData.urls.dealUpdateTemplate, currentDealId);
      const formData = new FormData(this);
      if (currentDealId) formData.append('_method', 'PUT');

      const originalButtonText = saveDealBtn.html();
      saveDealBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
      $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: (response) => {
          if (response.code === 200) {
            dealOffcanvas.hide();
            Swal.fire({icon:'success', title:'Success!', text:response.message, timer:1500, showConfirmButton:false});
            // Optionally refresh deals tab: $(document).trigger('dealSavedFromContactShow');
            // For now, user may need to manually refresh tab or page to see updated list
            setTimeout(() => { window.location.reload(); }, 1600); // Simple refresh
          } else { Swal.fire('Error', response.message || 'Operation failed.', 'error'); }
        },
        error: (jqXHR) => {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(dealForm, jqXHR.responseJSON.errors);
            Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
          } else { Swal.fire('Error', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');}
        },
        complete: () => { saveDealBtn.prop('disabled', false).html(originalButtonText); }
      });
    });
  }
  if(dealOffcanvasElement) dealOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetDealOffcanvas);


  // 4. TASK OFFCANVAS & FORM FUNCTIONS
  const resetTaskOffcanvas = () => {
    if (!taskForm) return;
    resetFormValidation(taskForm);
    taskForm.reset();
    taskIdInput.val('');
    taskFormMethodInput.val('POST');
    taskOffcanvasLabel.text('Add New Task');

    taskStatusSelect.val('').trigger('change'); // Assuming default is handled by controller or form
    taskPrioritySelect.val('').trigger('change');
    taskAssignedToUserSelect.empty().val(null).trigger('change');
    taskableTypeSelector.val('').trigger('change'); // This will also clear/disable taskableIdSelector
    if (taskDueDateInput[0]?._flatpickr) taskDueDateInput[0]._flatpickr.clear();
    if (taskReminderAtInput[0]?._flatpickr) taskReminderAtInput[0]._flatpickr.clear();
    saveTaskBtn.prop('disabled', false).html('Save Task');
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
    taskOffcanvasLabel.text('Edit Task');
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

  $('.add-new-task-for-contact').on('click', function() {
    if (taskOffcanvas) {
      initTaskFormElements();
      resetTaskOffcanvas();
      taskableTypeSelector.val('Contact').trigger('change');
      setTimeout(() => {
        taskableIdSelector.append(new Option(currentContactName, currentContactId, true, true)).trigger('change');
      }, 350);
      taskOffcanvas.show();
    }
  });
  $(document).on('click', '.edit-task-from-related', function() { // For tasks listed on contact page
    const taskId = $(this).data('task-id');
    const url = getUrl(pageData.urls.getTaskTemplate, taskId);
    initTaskFormElements();
    $.get(url, populateTaskOffcanvasForEdit).fail(() => Swal.fire('Error','Could not fetch task details.','error'));
  });

  if (taskForm) {
    $(taskForm).on('submit', function(e) { /* AJAX Submit Logic for Tasks */
      e.preventDefault(); resetFormValidation(this);
      let url = pageData.urls.taskStore;
      const currentTaskId = taskIdInput.val();
      if (currentTaskId) url = getUrl(pageData.urls.taskUpdateTemplate, currentTaskId);
      const formData = new FormData(this);
      if (currentTaskId) formData.append('_method', 'PUT');

      const originalButtonText = $(this).find('#saveTaskBtn').html(); // Scope button to current form
      $(this).find('#saveTaskBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

      $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: (response) => {
          if (response.code === 200) {
            taskOffcanvas.hide();
            Swal.fire({icon:'success', title:'Success!', text:response.message, timer:1500, showConfirmButton:false});
            // Optionally refresh tasks tab: $(document).trigger('taskSavedFromContactShow');
            setTimeout(() => { window.location.reload(); }, 1600); // Simple refresh
          } else { Swal.fire('Error', response.message || 'Operation failed.', 'error'); }
        },
        error: (jqXHR) => {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(taskForm, jqXHR.responseJSON.errors);
            Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
          } else { Swal.fire('Error', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error'); }
        },
        complete: () => { $(this).find('#saveTaskBtn').prop('disabled', false).html(originalButtonText); }
      });
    });
  }
  if(taskOffcanvasElement) taskOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetTaskOffcanvas);

  // 5. INITIALIZATION CALLS
  if (dealOffcanvasElement) initDealFormElements();
  if (taskOffcanvasElement) initTaskFormElements();
});
