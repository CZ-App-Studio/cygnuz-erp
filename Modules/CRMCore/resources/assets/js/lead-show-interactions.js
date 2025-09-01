'use strict';

$(function () {
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  if (typeof pageData === 'undefined' || !pageData.leadId || !pageData.urls) {
    console.error('pageData object with leadId & URLs is not defined in Blade.');
    return;
  }

  const currentLeadId = pageData.leadId;
  const currentLeadTitle = pageData.leadTitle;

  // Lead Offcanvas Elements
  const leadOffcanvasElement = document.getElementById('offcanvasLeadForm');
  const leadOffcanvas = leadOffcanvasElement ? new bootstrap.Offcanvas(leadOffcanvasElement) : null;
  const leadForm = document.getElementById('leadForm');
  const saveLeadBtn = $(leadForm).find('#saveLeadBtn');
  const leadIdInput = $(leadForm).find('#lead_id');
  const leadFormMethodInput = $(leadForm).find('#formMethod');
  const leadOffcanvasLabel = $(leadOffcanvasElement).find('#offcanvasLeadFormLabel');
  const leadSourceSelectForm = $(leadForm).find('#lead_source_id');
  const leadAssignedToUserSelectForm = $(leadForm).find('#assigned_to_user_id');

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
  const taskStatusSelect = $(taskForm).find('#task_status_id');
  const taskPrioritySelect = $(taskForm).find('#task_priority_id');
  const taskAssignedToUserSelect = $(taskForm).find('#task_assigned_to_user_id');
  const taskableTypeSelector = $(taskForm).find('#taskable_type_selector');
  const taskableIdSelector = $(taskForm).find('#taskable_id_selector');
  const taskDueDateInput = $(taskForm).find('#task_due_date');
  const taskReminderAtInput = $(taskForm).find('#task_reminder_at');

  // Lead Conversion Offcanvas Elements
  const convertLeadOffcanvasElement = document.getElementById('convertLeadOffcanvas');
  const convertLeadOffcanvas = convertLeadOffcanvasElement ? new bootstrap.Offcanvas(convertLeadOffcanvasElement) : null;
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

  // Helper Functions
  const getUrl = (template, id = null) => {
    if (id === null) return template;
    if (template.includes('__LEAD_ID__')) return template.replace('__LEAD_ID__', id);
    if (template.includes('__TASK_ID__')) return template.replace('__TASK_ID__', id);
    console.warn("getUrl: No known placeholder found in template:", template);
    return template;
  };

  const resetFormValidation = (form) => { 
    $(form).find('.is-invalid').removeClass('is-invalid').siblings('.invalid-feedback').text(''); 
  };

  const showValidationErrors = (form, errors) => {
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
    const firstInvalid = $(form).find('.is-invalid:first');
    if (firstInvalid.length) firstInvalid.trigger('focus');
  };

  // Lead Offcanvas Functions
  const resetLeadOffcanvas = () => {
    if (!leadForm) return;
    resetFormValidation(leadForm);
    leadForm.reset();
    leadIdInput.val('');
    leadFormMethodInput.val('POST');
    leadOffcanvasLabel.text(__('Edit Lead'));
    leadSourceSelectForm.val(null).trigger('change');
    leadAssignedToUserSelectForm.empty().val(null).trigger('change');
    $(saveLeadBtn).prop('disabled', false).html(__('Save Changes'));
  };

  const initLeadFormElements = () => {
    if (!leadForm || $(leadForm).data('initialized')) return;
    
    leadSourceSelectForm.empty().append($('<option>', { value: '', text: __('Select Source...') }));
    $.each(pageData.leadSourcesForForm, (id, name) => {
      leadSourceSelectForm.append($('<option>', { value: id, text: name }));
    });
    leadSourceSelectForm.select2({ dropdownParent: leadOffcanvasElement, placeholder: __('Select Source'), allowClear: true });

    leadAssignedToUserSelectForm.select2({
      placeholder: __('Search User...'), 
      dropdownParent: leadOffcanvasElement, 
      allowClear: true,
      ajax: { 
        url: pageData.urls.userSearch, 
        dataType: 'json', 
        delay: 250, 
        data: (p) => ({ q: p.term, page: p.page || 1}), 
        processResults: (d) => ({results:d.results, pagination:{more:d.pagination.more}}), 
        cache:true 
      }, 
      minimumInputLength:1
    });
    $(leadForm).data('initialized', true);
  };

  const populateLeadOffcanvasForEdit = (lead) => {
    if (!leadForm) return;
    initLeadFormElements();
    resetLeadOffcanvas();
    leadIdInput.val(lead.id);
    leadFormMethodInput.val('PUT');

    $(leadForm).find('#title').val(lead.title);
    $(leadForm).find('#contact_name').val(lead.contact_name);
    $(leadForm).find('#company_name').val(lead.company_name);
    $(leadForm).find('#contact_email').val(lead.contact_email);
    $(leadForm).find('#contact_phone').val(lead.contact_phone);
    $(leadForm).find('#value').val(lead.value);
    leadSourceSelectForm.val(lead.lead_source_id).trigger('change');
    $(leadForm).find('#description').val(lead.description);

    if (lead.assigned_to_user) {
      leadAssignedToUserSelectForm.append(new Option(lead.assigned_to_user.first_name + ' ' + lead.assigned_to_user.last_name, lead.assigned_to_user_id, true, true)).trigger('change');
    }
    leadOffcanvas.show();
  };

  // Task Offcanvas Functions
  const resetTaskOffcanvas = () => {
    if (!taskForm) return;
    resetFormValidation(taskForm);
    taskForm.reset();
    taskIdInput.val('');
    taskFormMethodInput.val('POST');
    taskOffcanvasLabel.text(__('Add New Task'));

    taskStatusSelect.val('').trigger('change');
    taskPrioritySelect.val('').trigger('change');
    taskAssignedToUserSelect.empty().val(null).trigger('change');
    taskableTypeSelector.val('').trigger('change');
    if (taskDueDateInput[0]?._flatpickr) taskDueDateInput[0]._flatpickr.clear();
    if (taskReminderAtInput[0]?._flatpickr) taskReminderAtInput[0]._flatpickr.clear();
    saveTaskBtn.prop('disabled', false).html(__('Save Task'));
  };

  const initTaskFormElements = () => {
    if(!taskForm) return;
    
    taskStatusSelect.empty().append($('<option>', { value: '', text: __('Select Status...') }));
    $.each(pageData.taskStatuses, (id, name) => taskStatusSelect.append($('<option>', { value: id, text: name })));
    
    taskPrioritySelect.empty().append($('<option>', { value: '', text: __('Select Priority...') }));
    $.each(pageData.taskPriorities, (id, name) => taskPrioritySelect.append($('<option>', { value: id, text: name })));
    
    $('#task_status_id, #task_priority_id, #taskable_type_selector').select2({ dropdownParent: taskOffcanvasElement, allowClear: true });

    taskAssignedToUserSelect.select2({
      placeholder: __('Search User...'), 
      dropdownParent: taskOffcanvasElement, 
      allowClear: true,
      ajax: { 
        url: pageData.urls.userSearch, 
        dataType: 'json', 
        delay: 250, 
        data: (p) => ({ q: p.term, page: p.page || 1}), 
        processResults: (d) => ({results:d.results, pagination:{more:d.pagination.more}}), 
        cache:true 
      }, 
      minimumInputLength:1
    });

    taskableIdSelector.select2({ placeholder: __('Select Type First...'), dropdownParent: taskOffcanvasElement, allowClear: true, disabled: true });
    
    taskableTypeSelector.on('change', function() {
      const type = $(this).val();
      const taskableIdSelect = $('#taskable_id_selector');
      taskableIdSelect.empty().val(null).trigger('change');

      let searchUrl = null;
      if (type) {
        switch(type.toLowerCase()) {
          case 'contact': searchUrl = pageData.urls.contactSearch; break;
          case 'company': searchUrl = pageData.urls.companySearch; break;
          case 'lead':    searchUrl = pageData.urls.leadSearch;    break;
          case 'deal':    searchUrl = pageData.urls.dealSearch;    break;
        }
      }

      if (searchUrl) {
        taskableIdSelect.select2('destroy').select2({
          placeholder: `${__('Search')} ${type}...`,
          dropdownParent: taskOffcanvasElement,
          allowClear: true,
          disabled: false,
          ajax: {
            url: searchUrl,
            dataType: 'json',
            delay: 250,
            data: (params) => ({ q: params.term, page: params.page || 1 }),
            processResults: (data) => ({ results: data.results, pagination: { more: data.pagination.more } }),
            cache: true
          },
          minimumInputLength: 1
        });
      } else {
        taskableIdSelect.select2('destroy').select2({
          placeholder: __('Select Type First...'),
          dropdownParent: taskOffcanvasElement,
          allowClear: true,
          disabled: true
        });
      }
    });

    if (taskDueDateInput.length) taskDueDateInput.flatpickr({ enableTime: true, dateFormat: "Y-m-d H:i", altInput: true, altFormat: "F j, Y H:i" });
    if (taskReminderAtInput.length) taskReminderAtInput.flatpickr({ enableTime: true, dateFormat: "Y-m-d H:i", altInput: true, altFormat: "F j, Y H:i", minDate: "today" });
  };

  const populateTaskOffcanvasForEdit = (task) => {
    if (!taskForm) return;
    resetTaskOffcanvas();
    taskOffcanvasLabel.text(__('Edit Task'));
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
      taskableTypeSelector.val(typeName).trigger('change');

      let taskableText = `Record (${typeName}) ID: ${task.taskable_id}`;
      if(task.taskable) {
        switch (typeName) {
          case 'Contact': taskableText = task.taskable.first_name + ' ' + task.taskable.last_name; break;
          case 'Company': taskableText = task.taskable.name; break;
          case 'Lead': case 'Deal': taskableText = task.taskable.title; break;
        }
      }
      
      setTimeout(() => {
        if (taskableIdSelector.data('select2')) {
          taskableIdSelector.append(new Option(taskableText, task.taskable_id, true, true)).trigger('change');
        } else {
          console.warn("Taskable ID Select2 not ready for pre-population during edit.");
        }
      }, 300);
    }
    taskOffcanvas.show();
  };

  // Lead Conversion Functions
  const resetConvertLeadForm = () => {
    if (!convertLeadForm) return;
    resetFormValidation(convertLeadForm);
    convertLeadForm.reset();
    $('#convert_lead_id').val(currentLeadId);
    $('#convertLeadOffcanvasTitleName').text(pageData.leadTitle || '');

    const leadData = pageData.leadDataForConversion || {};
    $('#convert_contact_first_name').val(leadData.contact_first_name || '');
    $('#convert_contact_last_name').val(leadData.contact_last_name || '');
    $('#convert_contact_email').val(leadData.contact_email || '');
    $('#convert_contact_phone').val(leadData.contact_phone || '');
    $('#existing_contact_info').hide().text('');

    $('#company_option_none').prop('checked', true).trigger('change');
    newCompanyNameInput.val(leadData.company_name || '');
    existingCompanySelect.empty().val(null).trigger('change');

    createDealCheckbox.prop('checked', true).trigger('change');
    $('#convert_deal_title').val(leadData.title || pageData.leadTitle || '');
    $('#convert_deal_value').val(leadData.value || '');
    if (convertDealExpectedCloseDateInput[0]?._flatpickr) convertDealExpectedCloseDateInput[0]._flatpickr.clear();

    convertDealPipelineSelect.val(pageData.initialPipelineId || '').trigger('change');

    submitConvertLeadBtn.prop('disabled', false).html(__('Convert Lead'));
  };

  const initConvertLeadFormElements = () => {
    if (!convertLeadForm || $(convertLeadForm).data('initialized')) return;

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

    existingCompanySelect.select2({
      placeholder: __('Search & Select Company...'),
      dropdownParent: convertLeadOffcanvasElement,
      allowClear: true,
      ajax: {
        url: pageData.urls.companySearch,
        dataType: 'json', 
        delay: 250,
        data: (params) => ({ q: params.term, page: params.page || 1 }),
        processResults: (data, params) => ({ results: data.results, pagination: { more: data.pagination.more } }),
        cache: true
      },
      minimumInputLength: 1
    });

    createDealCheckbox.on('change', function() {
      if ($(this).is(':checked')) {
        dealFieldsSection.slideDown();
        $('#convert_deal_title').prop('required', true);
      } else {
        dealFieldsSection.slideUp();
        $('#convert_deal_title').prop('required', false);
      }
    });

    convertDealPipelineSelect.select2({ dropdownParent: convertLeadOffcanvasElement, placeholder: __('Select Pipeline') });
    convertDealStageSelect.select2({ dropdownParent: convertLeadOffcanvasElement, placeholder: __('Select Stage') });

    convertDealPipelineSelect.empty().append($('<option>', { value: '', text: __('Select Pipeline...') }));
    $.each(pageData.allPipelinesForForm, (id, name) => {
      convertDealPipelineSelect.append($('<option>', { value: id, text: name }));
    });

    convertDealPipelineSelect.on('change.convertLead', function() {
      const pipelineId = $(this).val();
      convertDealStageSelect.empty().append($('<option>', { value: '', text: __('Select Stage...') })).prop('disabled', true);
      if (pipelineId && pageData.pipelinesWithStages[pipelineId] && pageData.pipelinesWithStages[pipelineId].stages) {
        let defaultStageId = null;
        $.each(pageData.pipelinesWithStages[pipelineId].stages, function(id, stage) {
          if (!stage.is_won_stage && !stage.is_lost_stage) {
            convertDealStageSelect.append($('<option>', { value: id, text: stage.name }));
            if (!defaultStageId) defaultStageId = id;
          }
        });
        convertDealStageSelect.prop('disabled', false);
        if(defaultStageId) convertDealStageSelect.val(defaultStageId);
      }
      convertDealStageSelect.trigger('change.select2');
    });

    if (convertDealExpectedCloseDateInput.length) {
      convertDealExpectedCloseDateInput.flatpickr({ dateFormat: 'Y-m-d', altInput: true, altFormat: 'F j, Y', minDate: 'today' });
    }
    $(convertLeadForm).data('initialized', true);
  };

  // Event Listeners
  $('.edit-lead-btn').on('click', function() {
    if (leadOffcanvas) {
      const url = getUrl(pageData.urls.getLeadTemplate, currentLeadId);
      $.get(url, populateLeadOffcanvasForEdit).fail(() => Swal.fire(__('Error'), __('Could not fetch lead details for editing.'), 'error'));
    }
  });

  $('.add-new-task-for-lead').on('click', function() {
    if (taskOffcanvas) {
      initTaskFormElements();
      resetTaskOffcanvas();
      taskableTypeSelector.val('Lead').trigger('change');
      setTimeout(() => {
        taskableIdSelector.append(new Option(currentLeadTitle, currentLeadId, true, true)).trigger('change');
      }, 350);
      taskOffcanvas.show();
    }
  });

  $(document).on('click', '.edit-task-from-related', function() {
    const taskId = $(this).data('task-id');
    const url = getUrl(pageData.urls.getTaskTemplate, taskId);
    initTaskFormElements();
    $.get(url, populateTaskOffcanvasForEdit).fail(() => Swal.fire(__('Error'), __('Could not fetch task details for editing.'), 'error'));
  });

  $('.convert-lead-btn').on('click', function() {
    if (convertLeadOffcanvas) {
      initConvertLeadFormElements();
      resetConvertLeadForm();
      convertLeadOffcanvas.show();
    } else {
      Swal.fire(pageData.labels.error, pageData.labels.leadConversionOffcanvasNotFound, 'error');
    }
  });

  // Form Submissions
  if (leadForm) {
    $(leadForm).on('submit', function(e) {
      e.preventDefault();
      resetFormValidation(this);
      const currentLeadIdForSubmit = leadIdInput.val();
      if (!currentLeadIdForSubmit) {
        Swal.fire(__('Error'), __('Lead ID is missing. Cannot update.'), 'error');
        return;
      }
      const url = getUrl(pageData.urls.leadUpdateTemplate, currentLeadIdForSubmit);
      const formData = new FormData(this);
      formData.append('_method', 'PUT');

      const originalButtonText = $(saveLeadBtn).html();
      $(saveLeadBtn).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + __('Saving...'));

      $.ajax({
        url: url, 
        type: 'POST', 
        data: formData, 
        processData: false, 
        contentType: false,
        success: (response) => {
          if (response.status === 'success') {
            leadOffcanvas.hide();
            Swal.fire({
              icon:'success', 
              title: __('Success!'), 
              text: response.data.message || response.message, 
              timer:1500, 
              showConfirmButton:false
            }).then(() => window.location.reload());
          } else { 
            Swal.fire(__('Error'), response.data || response.message || __('Update failed.'), 'error'); 
          }
        },
        error: (jqXHR) => {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(leadForm, jqXHR.responseJSON.errors);
            Swal.fire(__('Validation Error'), jqXHR.responseJSON.message || __('Please correct the errors.'), 'error');
          } else { 
            Swal.fire(__('Error'), jqXHR.responseJSON?.message || __('An unexpected error occurred.'), 'error');
          }
        },
        complete: () => { 
          $(saveLeadBtn).prop('disabled', false).html(originalButtonText); 
        }
      });
    });
  }

  if (taskForm) {
    $(taskForm).on('submit', function(e) {
      e.preventDefault(); 
      resetFormValidation(this);
      let url = pageData.urls.taskStore;
      const currentTaskId = $(taskForm).find('#task_id').val();
      if (currentTaskId) url = getUrl(pageData.urls.taskUpdateTemplate, currentTaskId);
      const formData = new FormData(this);
      if (currentTaskId) formData.append('_method', 'PUT');

      const taskSaveButton = $(this).find('#saveTaskBtn');
      const originalButtonText = taskSaveButton.html();
      taskSaveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + __('Saving...'));
      
      $.ajax({
        url: url, 
        type: 'POST', 
        data: formData, 
        processData: false, 
        contentType: false,
        success: (response) => {
          if (response.status === 'success') {
            taskOffcanvas.hide();
            Swal.fire({
              icon:'success', 
              title: __('Success!'), 
              text: response.data.message || response.message, 
              timer:1500, 
              showConfirmButton:false
            }).then(() => window.location.reload());
          } else { 
            Swal.fire(__('Error'), response.data || response.message || __('Operation failed.'), 'error'); 
          }
        },
        error: (jqXHR) => {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(taskForm, jqXHR.responseJSON.errors);
            Swal.fire(__('Validation Error'), jqXHR.responseJSON.message || __('Please correct the errors.'), 'error');
          } else { 
            Swal.fire(__('Error'), jqXHR.responseJSON?.message || __('An unexpected error occurred.'), 'error'); 
          }
        },
        complete: () => { 
          taskSaveButton.prop('disabled', false).html(originalButtonText); 
        }
      });
    });
  }

  if (convertLeadForm) {
    $(convertLeadForm).on('submit', function(e) {
      e.preventDefault();
      resetFormValidation(this);
      const formData = new FormData(this);
      const url = getUrl(pageData.urls.processConversion);

      const originalButtonText = submitConvertLeadBtn.html();
      submitConvertLeadBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + __('Converting...'));

      $.ajax({
        url: url, 
        type: 'POST', 
        data: formData, 
        processData: false, 
        contentType: false,
        success: function(response) {
          if (response.status === 'success') {
            convertLeadOffcanvas.hide();
            Swal.fire({
              icon: 'success',
              title: __('Converted!'),
              html: (response.data.message || response.message) +
                (response.data.contact_url ? `<br><a href="${response.data.contact_url}" target="_blank">${__('View Contact')}</a>` : '') +
                (response.data.company_url ? `<br><a href="${response.data.company_url}" target="_blank">${__('View Company')}</a>` : '') +
                (response.data.deal_url ? `<br><a href="${response.data.deal_url}" target="_blank">${__('View Deal')}</a>` : ''),
              confirmButtonText: __('Okay')
            }).then(() => {
              window.location.reload();
            });
          } else {
            Swal.fire(__('Error'), response.data || response.message || __('Conversion failed.'), 'error');
          }
        },
        error: function(jqXHR) {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            showValidationErrors(convertLeadForm, jqXHR.responseJSON.errors);
            Swal.fire(__('Validation Error'), jqXHR.responseJSON.message || __('Please correct the errors.'), 'error');
          } else {
            Swal.fire(__('Error'), jqXHR.responseJSON?.message || __('An unexpected error occurred.'), 'error');
          }
        },
        complete: function() {
          submitConvertLeadBtn.prop('disabled', false).html(originalButtonText);
        }
      });
    });
  }

  // Modal/Offcanvas reset handlers
  if(leadOffcanvasElement) leadOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetLeadOffcanvas);
  if(taskOffcanvasElement) taskOffcanvasElement.addEventListener('hidden.bs.offcanvas', resetTaskOffcanvas);
  if(convertLeadOffcanvasElement) convertLeadOffcanvasElement.addEventListener('hidden.bs.offcanvas', function() {
    // Optional: any specific reset needed when conversion offcanvas just closes without submission
  });

  // Translation helper function
  function __(key) {
    return pageData.labels && pageData.labels[key] ? pageData.labels[key] : key;
  }

  // Initialization
  if (leadOffcanvasElement) initLeadFormElements();
  if (taskOffcanvasElement) initTaskFormElements();
});