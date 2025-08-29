'use strict';

$(function () {
  // 1. SETUP & VARIABLE DECLARATIONS
  // =================================================================================================
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  if (typeof pageData === 'undefined' || !pageData.leadId || !pageData.urls) {
    console.error('JS pageData object with leadId & URLs is not defined in Blade.');
    return;
  }

  const currentLeadId = pageData.leadId;
  const currentLeadTitle = pageData.leadTitle; // For pre-filling task related to lead

  // --- Lead Offcanvas Elements (from leads/_form.blade.php) ---
  const leadOffcanvasElement = document.getElementById('offcanvasLeadForm'); // Ensure this ID is on your leads/_form.blade.php
  const leadOffcanvas = leadOffcanvasElement ? new bootstrap.Offcanvas(leadOffcanvasElement) : null;
  const leadForm = document.getElementById('leadForm'); // Ensure this ID is on your <form> in leads/_form.blade.php
  const saveLeadBtn = $(leadForm).find('#saveLeadBtn');
  const leadIdInput = $(leadForm).find('#lead_id');
  const leadFormMethodInput = $(leadForm).find('#formMethod');
  const leadOffcanvasLabel = $(leadOffcanvasElement).find('#offcanvasLeadFormLabel');
  // Add specific lead form field selectors here if needed for reset/population, e.g.:
  const leadTitleInput = $(leadForm).find('#title');
  const leadSourceSelectForm = $(leadForm).find('#lead_source_id'); // Assuming ID in leads/_form
  const leadAssignedToUserSelectForm = $(leadForm).find('#assigned_to_user_id'); // Assuming ID in leads/_form

  // --- Task Offcanvas Elements (from tasks/_form.blade.php) ---
  const taskOffcanvasElement = document.getElementById('offcanvasTaskForm');
  const taskOffcanvas = taskOffcanvasElement ? new bootstrap.Offcanvas(taskOffcanvasElement) : null;
  const taskForm = document.getElementById('taskForm');
  const saveTaskBtn = $(taskForm).find('#saveTaskBtn'); // Ensure this ID is unique or scope properly
  const taskIdInput = $(taskForm).find('#task_id');
  const taskFormMethodInput = $(taskForm).find('#formMethod');
  const taskOffcanvasLabel = $(taskOffcanvasElement).find('#offcanvasTaskFormLabel');
  const taskStatusSelect = $(taskForm).find('#task_status_id');
  const taskPrioritySelect = $(taskForm).find('#task_priority_id');
  const taskAssignedToUserSelect = $(taskForm).find('#task_assigned_to_user_id');
  const taskableTypeSelector = $(taskForm).find('#taskable_type_selector');
  const taskableIdSelector = $(taskForm).find('#taskable_id_selector');
  const taskDueDateInput = $(taskForm).find('#task_due_date');
  const taskReminderAtInput = $(taskForm).find('#task_reminder_at');


  // --- Lead Conversion Modal Elements & Logic ---
  const convertLeadModalElement = document.getElementById('convertLeadModal');
  const convertLeadModal = convertLeadModalElement ? new bootstrap.Modal(convertLeadModalElement) : null;
  const convertLeadForm = document.getElementById('convertLeadForm');
  const submitConvertLeadBtn = $('#submitConvertLeadBtn');

  const companyOptionRadios = $('input[name="company_option"]');
  const existingCompanySection = $('#existing_company_section');
  const newCompanySection = $('#new_company_section');
  const dealFieldsSection = $('#deal_fields_section');
  const createDealCheckbox = $('#create_deal');
  const existingCompanySelect = $('#existing_company_id');
  const newCompanyNameInput = $('#new_company_name');
  const newCompanyNameRequiredStar = $('#new_company_name_required_star');

  const convertDealPipelineSelect = $('#convert_deal_pipeline_id');
  const convertDealStageSelect = $('#convert_deal_stage_id');
  const convertDealExpectedCloseDateInput = $('#convert_deal_expected_close_date');

  const resetConvertLeadForm = () => {
    if (!convertLeadForm) return;
    resetFormValidation(convertLeadForm); // Use your global helper
    convertLeadForm.reset();
    $('#convert_lead_id').val(currentLeadId); // currentLeadId from pageData
    $('#convertLeadModalTitleName').text(pageData.leadTitle || '');

    // Pre-fill from leadDataForConversion
    const leadData = pageData.leadDataForConversion || {};
    $('#convert_contact_first_name').val(leadData.contact_first_name || '');
    $('#convert_contact_last_name').val(leadData.contact_last_name || '');
    $('#convert_contact_email').val(leadData.contact_email || '');
    $('#convert_contact_phone').val(leadData.contact_phone || '');
    $('#existing_contact_info').hide().text('');


    // Company section
    $('#company_option_none').prop('checked', true).trigger('change'); // Default to no company
    newCompanyNameInput.val(leadData.company_name || '');
    existingCompanySelect.empty().val(null).trigger('change'); // Clear existing company select

    // Deal section
    createDealCheckbox.prop('checked', true).trigger('change'); // Default to create deal
    $('#convert_deal_title').val(leadData.title || pageData.leadTitle || ''); // Pre-fill deal title
    $('#convert_deal_value').val(leadData.value || '');
    if (convertDealExpectedCloseDateInput[0]?._flatpickr) convertDealExpectedCloseDateInput[0]._flatpickr.clear();

    // Populate and set default pipeline for deal
    convertDealPipelineSelect.val(pageData.initialPipelineId || '').trigger('change');

    submitConvertLeadBtn.prop('disabled', false).html('Convert Lead');
  };

  const initConvertLeadFormElements = () => {
    if (!convertLeadForm || $(convertLeadForm).data('initialized')) return;

    // Company option toggles
    companyOptionRadios.on('change', function() {
      const selectedOption = $(this).val();
      existingCompanySection.hide();
      newCompanySection.hide();
      newCompanyNameInput.prop('required', false);
      newCompanyNameRequiredStar.hide();
      existingCompanySelect.prop('required', false);


      if (selectedOption === 'existing') {
        existingCompanySection.show();
        existingCompanySelect.prop('required', true);
      } else if (selectedOption === 'new') {
        newCompanySection.show();
        newCompanyNameInput.prop('required', true);
        newCompanyNameRequiredStar.show();
      }
    });

    // AJAX Select2 for existing companies in convert modal
    existingCompanySelect.select2({
      placeholder: 'Search & Select Company...',
      dropdownParent: convertLeadModalElement, // Critical for modals
      allowClear: true,
      ajax: {
        url: pageData.urls.companySearch,
        dataType: 'json', delay: 250,
        data: (params) => ({ q: params.term, page: params.page || 1 }),
        processResults: (data, params) => ({ results: data.results, pagination: { more: data.pagination.more } }),
        cache: true
      },
      minimumInputLength: 1
    });
    // Pre-search existing company if lead has company name
    if (pageData.leadDataForConversion.company_name) {
      // This is a bit tricky: Select2 needs an initial search term or you'd need to manually query and append.
      // For now, user has to type if they want to link to existing.
      // Or, upon opening, make an initial AJAX call to search for this company name.
    }


    // Deal creation fields toggle
    createDealCheckbox.on('change', function() {
      if ($(this).is(':checked')) {
        dealFieldsSection.slideDown();
        $('#convert_deal_title').prop('required', true);
        // Add/remove required for other deal fields as necessary
      } else {
        dealFieldsSection.slideUp();
        $('#convert_deal_title').prop('required', false);
      }
    });

    // Deal Pipeline and Stage Dropdowns
    convertDealPipelineSelect.select2({ dropdownParent: convertLeadModalElement, placeholder: 'Select Pipeline' });
    convertDealStageSelect.select2({ dropdownParent: convertLeadModalElement, placeholder: 'Select Stage' });

    convertDealPipelineSelect.empty().append($('<option>', { value: '', text: 'Select Pipeline...' }));
    $.each(pageData.allPipelinesForForm, (id, name) => {
      convertDealPipelineSelect.append($('<option>', { value: id, text: name }));
    });

    convertDealPipelineSelect.on('change.convertLead', function() {
      const pipelineId = $(this).val();
      convertDealStageSelect.empty().append($('<option>', { value: '', text: 'Select Stage...' })).prop('disabled', true);
      if (pipelineId && pageData.pipelinesWithStages[pipelineId] && pageData.pipelinesWithStages[pipelineId].stages) {
        let defaultStageId = null;
        $.each(pageData.pipelinesWithStages[pipelineId].stages, function(id, stage) {
          if (!stage.is_won_stage && !stage.is_lost_stage) { // Only non-final stages
            convertDealStageSelect.append($('<option>', { value: id, text: stage.name }));
            // Find default stage for this pipeline (first non-final one)
            if (!defaultStageId) defaultStageId = id;
          }
        });
        convertDealStageSelect.prop('disabled', false);
        if(defaultStageId) convertDealStageSelect.val(defaultStageId); // Select first available stage
      }
      convertDealStageSelect.trigger('change.select2');
    });

    // Flatpickr for Deal Expected Close Date
    if (convertDealExpectedCloseDateInput.length) {
      convertDealExpectedCloseDateInput.flatpickr({ dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y', minDate: 'today' });
    }
    $(convertLeadForm).data('initialized', true);
  };

  // --- Event Listeners ---
  // ... (existing .edit-lead-btn, .add-new-task-for-lead, .edit-task-from-related, leadForm submit, taskForm submit) ...

  // "Convert Lead" button on the show page
  $('.convert-lead-btn').on('click', function() {
    if (convertLeadModal) {
      initConvertLeadFormElements(); // Initialize elements if not already
      resetConvertLeadForm(); // Pre-fills with lead data
      convertLeadModal.show();
    } else {
      Swal.fire('Error', 'Lead conversion modal not found.', 'error');
    }
  });

  // AJAX Submit for Lead Conversion Form
  if (convertLeadForm) {
    $(convertLeadForm).on('submit', function(e) {
      e.preventDefault();
      resetFormValidation(this);
      const formData = new FormData(this);
      const url = getUrl(pageData.urls.processConversion); // URL already includes leadId

      const originalButtonText = submitConvertLeadBtn.html();
      submitConvertLeadBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Converting...');

      $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: function(response) {
          if (response.code === 200) {
            convertLeadModal.hide();
            Swal.fire({
              icon: 'success',
              title: 'Converted!',
              html: response.message +
                (response.contact_url ? `<br><a href="${response.contact_url}" target="_blank">View Contact</a>` : '') +
                (response.company_url ? `<br><a href="${response.company_url}" target="_blank">View Company</a>` : '') +
                (response.deal_url ? `<br><a href="${response.deal_url}" target="_blank">View Deal</a>` : ''),
              confirmButtonText: 'Okay'
            }).then(() => {
              window.location.reload(); // Reload the lead show page to reflect converted status
            });
          } else {
            Swal.fire('Error', response.message || 'Conversion failed.', 'error');
          }
        },
        error: function(jqXHR) {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(convertLeadForm, jqXHR.responseJSON.errors);
            Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
          } else {
            Swal.fire('Error', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');
          }
        },
        complete: function() {
          submitConvertLeadBtn.prop('disabled', false).html('Convert Lead');
        }
      });
    });
  }
  if(convertLeadModalElement) convertLeadModalElement.addEventListener('hidden.bs.modal', function() {
    // Optional: any specific reset needed when conversion modal just closes without submission
    // resetConvertLeadForm(); // Or just let it be for next open
  });

  // =================================================================================================


  // 2. HELPER FUNCTIONS
  const getUrl = (template, id = null) => {
    if (id === null) return template;
    if (template.includes('__LEAD_ID__')) return template.replace('__LEAD_ID__', id);
    if (template.includes('__TASK_ID__')) return template.replace('__TASK_ID__', id);
    console.warn("getUrl: No known placeholder found in template:", template);
    return template;
  };
  const resetFormValidation = (form) => { $(form).find('.is-invalid').removeClass('is-invalid').siblings('.invalid-feedback').text(''); };
  const showValidationErrors = (form, errors) => { /* ... (same as in company-show-interactions.js) ... */
    resetFormValidation(form);
    $.each(errors, function (key, value) {
      const inputId = `#${$(form).find(`[name="${key}"]`).attr('id')}`;
      let input = $(inputId);
      if (key === 'taskable_id_selector' || key === 'taskable_type_selector') input = $(`#${key}`);
      else if ($(form).attr('id') === 'leadForm' && !$(`#${$(form).find(`[name="${key}"]`).attr('id')}`).length) input = $(form).find(`[name="${key}"]`);
      else if ($(form).attr('id') === 'taskForm' && !$(`#${$(form).find(`[name="${key}"]`).attr('id')}`).length) input = $(form).find(`[name="${key}"]`);

      if (input.length) {
        input.addClass('is-invalid');
        let feedbackDiv = input.siblings('.invalid-feedback');
        if(!feedbackDiv.length && input.parent().hasClass('input-group')) feedbackDiv = input.parent().siblings('.invalid-feedback');
        if(!feedbackDiv.length && input.next().hasClass('select2-container')) feedbackDiv = input.next().siblings('.invalid-feedback');
        if(!feedbackDiv.length) feedbackDiv = $('<div class="invalid-feedback"></div>').insertAfter(input.next('.select2-container').length ? input.next('.select2-container') : input);
        feedbackDiv.text(value[0]);
      }
    });
    $(form).find('.is-invalid:first').focus();
  };

  // 3. LEAD OFFCANVAS & FORM FUNCTIONS
  // =================================================================================================
  const resetLeadOffcanvas = () => {
    if (!leadForm) return;
    resetFormValidation(leadForm);
    leadForm.reset();
    leadIdInput.val('');
    leadFormMethodInput.val('POST'); // For create
    leadOffcanvasLabel.text('Edit Lead'); // Title for editing this lead
    // Reset Select2 fields
    leadSourceSelectForm.val(null).trigger('change');
    leadAssignedToUserSelectForm.empty().val(null).trigger('change');
    $(saveLeadBtn).prop('disabled', false).html('Save Changes'); // Different button text for edit
  };

  const initLeadFormElements = () => {
    if (!leadForm || $(leadForm).data('initialized')) return;
    // Populate Lead Source dropdown (static)
    leadSourceSelectForm.empty().append($('<option>', { value: '', text: 'Select Source...' }));
    $.each(pageData.leadSourcesForForm, (id, name) => {
      leadSourceSelectForm.append($('<option>', { value: id, text: name }));
    });
    leadSourceSelectForm.select2({ dropdownParent: leadOffcanvasElement, placeholder: 'Select Source', allowClear: true });

    // Init User Search Select2
    leadAssignedToUserSelectForm.select2({
      placeholder: 'Search User...', dropdownParent: leadOffcanvasElement, allowClear: true,
      ajax: { url: pageData.urls.userSearch, dataType: 'json', delay: 250, data: (p) => ({ q: p.term, page: p.page || 1}), processResults: (d,p) => ({results:d.results, pagination:{more:d.pagination.more}}), cache:true }, minimumInputLength:1
    });
    $(leadForm).data('initialized', true);
  };

  const populateLeadOffcanvasForEdit = (lead) => {
    if (!leadForm) return;
    initLeadFormElements(); // Ensure elements are initialized before populating
    resetLeadOffcanvas(); // Clears form and sets title for edit
    leadIdInput.val(lead.id);
    leadFormMethodInput.val('PUT');

    // Populate fields from leads/_form.blade.php structure
    $(leadForm).find('#title').val(lead.title);
    $(leadForm).find('#contact_name').val(lead.contact_name);
    $(leadForm).find('#company_name').val(lead.company_name);
    $(leadForm).find('#contact_email').val(lead.contact_email);
    $(leadForm).find('#contact_phone').val(lead.contact_phone);
    $(leadForm).find('#value').val(lead.value);
    leadSourceSelectForm.val(lead.lead_source_id).trigger('change');
    $(leadForm).find('#description').val(lead.description);
    // Assuming lead_status_id is managed on the Kanban/list, not typically in the lead edit form itself,
    // but if it is, add: $(leadForm).find('#lead_status_id').val(lead.lead_status_id).trigger('change');

    if (lead.assigned_to_user) {
      leadAssignedToUserSelectForm.append(new Option(lead.assigned_to_user.first_name + ' ' + lead.assigned_to_user.last_name, lead.assigned_to_user_id, true, true)).trigger('change');
    }
    leadOffcanvas.show();
  };

  $('.edit-lead-btn').on('click', function() { // Button on leads/show.blade.php
    if (leadOffcanvas) {
      const url = getUrl(pageData.urls.getLeadTemplate, currentLeadId); // Get current lead data
      $.get(url, populateLeadOffcanvasForEdit).fail(() => Swal.fire('Error', 'Could not fetch lead details for editing.', 'error'));
    }
  });

  if (leadForm) {
    $(leadForm).on('submit', function(e) { // Handles the lead edit form submission
      e.preventDefault();
      resetFormValidation(this);
      const currentLeadIdForSubmit = leadIdInput.val(); // Should be pre-filled by populateLeadOffcanvasForEdit
      if (!currentLeadIdForSubmit) {
        Swal.fire('Error', 'Lead ID is missing. Cannot update.', 'error');
        return;
      }
      const url = getUrl(pageData.urls.leadUpdateTemplate, currentLeadIdForSubmit);
      const formData = new FormData(this);
      formData.append('_method', 'PUT');

      const originalButtonText = $(saveLeadBtn).html();
      $(saveLeadBtn).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

      $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: (response) => {
          if (response.code === 200) {
            leadOffcanvas.hide();
            Swal.fire({icon:'success', title:'Success!', text:response.message, timer:1500, showConfirmButton:false})
              .then(() => window.location.reload()); // Reload show page to see changes
          } else { Swal.fire('Error', response.message || 'Update failed.', 'error'); }
        },
        error: (jqXHR) => {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(leadForm, jqXHR.responseJSON.errors);
            Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
          } else { Swal.fire('Error', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error');}
        },
        complete: () => { $(saveLeadBtn).prop('disabled', false).html(originalButtonText); }
      });
    });
  }
  if(leadOffcanvasElement) leadOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetLeadOffcanvas);


  // 4. TASK OFFCANVAS & FORM FUNCTIONS (Adapted from company-show-interactions.js)
  // =================================================================================================
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


  $('.add-new-task-for-lead').on('click', function() {
    if (taskOffcanvas) {
      initTaskFormElements(); // Ensure form elements are ready
      resetTaskOffcanvas();
      taskableTypeSelector.val('Lead').trigger('change'); // Pre-select type
      setTimeout(() => { // Pre-select current lead
        taskableIdSelector.append(new Option(currentLeadTitle, currentLeadId, true, true)).trigger('change');
      }, 350);
      taskOffcanvas.show();
    }
  });

  $(document).on('click', '.edit-task-from-related', function() { // For tasks listed on lead show page
    const taskId = $(this).data('task-id');
    const url = getUrl(pageData.urls.getTaskTemplate, taskId);
    initTaskFormElements(); // Ensure form elements are ready
    $.get(url, populateTaskOffcanvasForEdit).fail(() => Swal.fire('Error','Could not fetch task details for editing.','error'));
  });

  if (taskForm) { // Submission logic for tasks created/edited from this page
    $(taskForm).on('submit', function(e) { /* ... AJAX Submit Logic for Tasks (similar to company-show-interactions.js, using pageData.urls.taskStore/taskUpdateTemplate) ... */
      e.preventDefault(); resetFormValidation(this);
      let url = pageData.urls.taskStore;
      const currentTaskId = $(taskForm).find('#task_id').val(); // Get task_id from task form
      if (currentTaskId) url = getUrl(pageData.urls.taskUpdateTemplate, currentTaskId);
      const formData = new FormData(this);
      if (currentTaskId) formData.append('_method', 'PUT');

      const taskSaveButton = $(this).find('#saveTaskBtn');
      const originalButtonText = taskSaveButton.html();
      taskSaveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
      $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: (response) => {
          if (response.code === 200) {
            taskOffcanvas.hide();
            Swal.fire({icon:'success', title:'Success!', text:response.message, timer:1500, showConfirmButton:false})
              .then(() => {
                // Optionally refresh only the tasks tab content here instead of full page reload
                window.location.reload(); // Simple refresh for now
              });
          } else { Swal.fire('Error', response.message || 'Operation failed.', 'error'); }
        },
        error: (jqXHR) => {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(taskForm, jqXHR.responseJSON.errors);
            Swal.fire('Validation Error', jqXHR.responseJSON.message || 'Please correct the errors.', 'error');
          } else { Swal.fire('Error', jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'error'); }
        },
        complete: () => { taskSaveButton.prop('disabled', false).html(originalButtonText); }
      });
    });
  }
  if(taskOffcanvasElement) taskOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetTaskOffcanvas);


/*   // 5. "CONVERT LEAD" BUTTON PLACEHOLDER
  // =================================================================================================
  $('.convert-lead-btn').on('click', function() {
    const leadIdToConvert = $(this).data('lead-id');
    console.log('Convert Lead button clicked for lead ID:', leadIdToConvert);
    Swal.fire({
      title: 'Convert Lead',
      text: 'Lead conversion functionality (e.g., creating Contact, Company, Deal) would be handled here. This is a placeholder.',
      icon: 'info',
      confirmButtonText: 'Got it!'
    });
    // Here you would typically open a new modal dedicated to lead conversion,
    // or redirect to a conversion page, or handle via a more complex AJAX flow.
  }); */

  // 6. INITIALIZATION CALLS
  // =================================================================================================
  if (leadOffcanvasElement) initLeadFormElements(); // Init elements for the lead edit form
  if (taskOffcanvasElement) initTaskFormElements(); // Init elements for the task form

});
