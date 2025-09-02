'use strict';

$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  if (typeof pageData === 'undefined' || !pageData.urls || !pageData.taskStatuses || !pageData.taskPriorities) {
    console.error('pageData object is not defined or incomplete.');
    return;
  }

  // DOM Elements
  const offcanvasElement = document.getElementById('offcanvasTaskForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const taskForm = document.getElementById('taskForm');
  const saveTaskBtn = $('#saveTaskBtn');
  const tasksTableElement = $('#tasksTable');

  // Form Elements
  const taskIdInput = $('#task_id');
  const formMethodInput = $('#formMethod');
  const offcanvasLabel = $('#offcanvasTaskFormLabel');
  const taskTitleInput = $('#task_title');
  const taskDescriptionInput = $('#task_description');
  const taskDueDateInput = $('#task_due_date');
  const taskReminderAtInput = $('#task_reminder_at');
  const taskStatusSelect = $('#task_status_id');
  const taskPrioritySelect = $('#task_priority_id');
  const taskAssignedToUserSelect = $('#task_assigned_to_user_id');
  const taskableTypeSelector = $('#taskable_type_selector');
  const taskableIdSelector = $('#taskable_id_selector');

  // Filter Elements
  const filterStatusSelect = $('#filter_task_status_id');
  const filterPrioritySelect = $('#filter_task_priority_id');
  const filterAssignedToUserSelect = $('#filter_assigned_to_user_id');
  const filterDueDateRangeInput = $('#filter_due_date_range');

  // View Toggle Elements
  const kanbanViewContainer = $('#kanban-view-container');
  const datatableViewContainer = $('#datatable-view-container');
  const btnKanbanView = $('#btn-kanban-view');
  const btnListView = $('#btn-list-view');
  const btnKanbanViewTop = $('#btn-kanban-view-top');
  const btnListViewTop = $('#btn-list-view-top');

  let dtTasksTable;
  let sortableInstances = [];
  let currentView = localStorage.getItem('tasks-view-preference') || 'list';

  // Helper Functions
  const getUrl = (template, id) => template.replace(':id', id);

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(taskForm);
    taskForm.reset();
    taskIdInput.val('');
    formMethodInput.val('POST');
    offcanvasLabel.text(pageData.labels.addNewTask);

    taskStatusSelect.val(null).trigger('change');
    taskPrioritySelect.val(null).trigger('change');
    taskAssignedToUserSelect.val(null).trigger('change');
    taskableTypeSelector.val(null).trigger('change');
    
    if (taskDueDateInput[0]?._flatpickr) taskDueDateInput[0]._flatpickr.clear();
    if (taskReminderAtInput[0]?._flatpickr) taskReminderAtInput[0]._flatpickr.clear();

    saveTaskBtn.prop('disabled', false).html(pageData.labels.saveTask);
  };

  const showValidationErrors = (errors) => {
    $.each(errors, function (key, value) {
      let inputId = `#task_${key.replace(/\./g, '_')}`;
      if (key === 'taskable_id_selector' || key === 'taskable_type_selector') {
        inputId = `#${key}`;
      }
      const input = $(inputId);
      if (input.length) {
        input.addClass('is-invalid');
        input.siblings('.invalid-feedback').text(value[0]);
      }
    });
    $('.is-invalid:first').focus();
  };

  // View Management
  const switchToKanbanView = () => {
    currentView = 'kanban';
    localStorage.setItem('tasks-view-preference', 'kanban');
    
    btnKanbanView.addClass('active');
    btnListView.removeClass('active');
    btnKanbanViewTop.addClass('active');
    btnListViewTop.removeClass('active');
    
    datatableViewContainer.addClass('d-none');
    kanbanViewContainer.removeClass('d-none');
    
    loadKanbanData();
  };

  const switchToListView = () => {
    currentView = 'list';
    localStorage.setItem('tasks-view-preference', 'list');
    
    btnListView.addClass('active');
    btnKanbanView.removeClass('active');
    btnListViewTop.addClass('active');
    btnKanbanViewTop.removeClass('active');
    
    kanbanViewContainer.addClass('d-none');
    datatableViewContainer.removeClass('d-none');
    
    if (dtTasksTable) {
      dtTasksTable.ajax.reload();
    }
  };

  // Kanban Functions
  const loadKanbanData = () => {
    $.ajax({
      url: pageData.urls.kanbanAjax,
      type: 'GET',
      success: function (response) {
        updateKanbanBoard(response.tasks);
        updateTaskCounts(response.tasks);
      },
      error: function () {
        Swal.fire(pageData.labels.error, pageData.labels.unexpectedError, 'error');
      }
    });
  };

  const updateKanbanBoard = (tasksByStatus) => {
    // Clear existing sortables
    sortableInstances.forEach(instance => instance.destroy());
    sortableInstances = [];

    // Update each status column
    Object.keys(pageData.taskStatuses).forEach(statusId => {
      const container = document.getElementById(`kanban-stage-${statusId}`);
      if (!container) return;

      container.innerHTML = '';

      const tasks = tasksByStatus[statusId] || [];
      tasks.forEach(task => {
        container.appendChild(createTaskCard(task));
      });

      // Initialize sortable
      const sortable = Sortable.create(container, {
        group: 'tasks',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function (evt) {
          const taskId = evt.item.dataset.taskId;
          const newStatusId = evt.to.dataset.statusId;
          
          updateTaskStatus(taskId, newStatusId);
        }
      });
      
      sortableInstances.push(sortable);
    });
  };

  const createTaskCard = (task) => {
    const card = document.createElement('div');
    card.className = 'card kanban-task mb-2';
    card.dataset.taskId = task.id;

    const isOverdue = task.is_overdue;
    const dueDateClass = isOverdue ? 'text-danger' : 'text-muted';
    const dueDateIcon = isOverdue ? '<i class="bx bx-time-five"></i> ' : '';

    let assignedHtml = '';
    if (task.assigned_to) {
      let avatarContent = '';
      if (task.assigned_to.avatar) {
        avatarContent = `<img src="${task.assigned_to.avatar}" alt="${task.assigned_to.name}" class="rounded-circle w-100 h-100">`;
      } else {
        // Get initials from name
        const nameParts = task.assigned_to.name.split(' ');
        const initials = nameParts.length >= 2 
          ? (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
          : nameParts[0].substring(0, 2).toUpperCase();
        avatarContent = `<span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>`;
      }
      
      assignedHtml = `
        <div class="d-flex align-items-center mb-2">
          <div class="avatar avatar-xs me-2">
            ${avatarContent}
          </div>
          <small class="text-muted">${task.assigned_to.name}</small>
        </div>
      `;
    }

    let relatedHtml = '';
    if (task.related_to) {
      relatedHtml = `
        <div class="mb-2">
          <small class="text-muted">
            <i class="bx bx-link"></i> ${task.related_to.type}: ${task.related_to.name}
          </small>
        </div>
      `;
    }

    card.innerHTML = `
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <h6 class="card-title mb-0 text-truncate flex-grow-1">${task.title}</h6>
          <div class="dropdown">
            <button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown">
              <i class="bx bx-dots-vertical-rounded"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="javascript:void(0)" onclick="markTaskCompleted(${task.id})">
                <i class="bx bx-check-circle me-1"></i> ${pageData.labels.markCompleted || 'Mark as Completed'}
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="javascript:void(0)" onclick="editTask(${task.id})">
                <i class="bx bx-edit me-1"></i> ${pageData.labels.edit}
              </a></li>
              <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteTask(${task.id})">
                <i class="bx bx-trash me-1"></i> ${pageData.labels.delete}
              </a></li>
            </ul>
          </div>
        </div>
        
        ${assignedHtml}
        ${relatedHtml}
        
        <div class="d-flex justify-content-between align-items-center">
          <span class="badge" style="background-color: ${task.priority.color}; color: #fff;">
            ${task.priority.name}
          </span>
          ${task.due_date ? `<small class="${dueDateClass}">${dueDateIcon}${task.due_date}</small>` : ''}
        </div>
      </div>
    `;

    return card;
  };

  const updateTaskCounts = (tasksByStatus) => {
    Object.keys(pageData.taskStatuses).forEach(statusId => {
      const count = (tasksByStatus[statusId] || []).length;
      $(`.task-count[data-status="${statusId}"]`).text(count);
    });
  };

  const updateTaskStatus = (taskId, newStatusId) => {
    $.ajax({
      url: getUrl(pageData.urls.updateKanbanStatusTemplate, taskId),
      type: 'POST',
      data: { task_status_id: newStatusId },
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.message,
            timer: 1500,
            showConfirmButton: false
          });
          
          if (pageData.taskStatuses[newStatusId].is_completed) {
            loadKanbanData();
          }
        } else {
          Swal.fire(pageData.labels.error, response.message || pageData.labels.couldNotUpdateStatus, 'error');
          loadKanbanData();
        }
      },
      error: function () {
        Swal.fire(pageData.labels.error, pageData.labels.unexpectedError, 'error');
        loadKanbanData();
      }
    });
  };

  // Filter Initialization
  const initFilters = () => {
    filterStatusSelect.select2({ placeholder: pageData.labels.allStatuses, allowClear: true });
    filterPrioritySelect.select2({ placeholder: pageData.labels.allPriorities, allowClear: true });

    filterAssignedToUserSelect.select2({
      placeholder: filterAssignedToUserSelect.data('placeholder') || pageData.labels.anyUser,
      allowClear: true,
      ajax: {
        url: pageData.urls.userSearch,
        dataType: 'json',
        delay: 250,
        data: (params) => ({ q: params.term, page: params.page || 1 }),
        processResults: (data, params) => ({ 
          results: data.results, 
          pagination: { more: data.pagination.more } 
        }),
        cache: true
      }
    });

    if (filterDueDateRangeInput.length) {
      filterDueDateRangeInput.flatpickr({ mode: "range", dateFormat: "Y-m-d" });
    }

    $('.select2-filter, #filter_due_date_range').on('change', function () {
      if (dtTasksTable) {
        dtTasksTable.ajax.reload();
      }
    });
  };

  // DataTable Initialization
  const initDataTable = () => {
    if (tasksTableElement.length) {
      dtTasksTable = tasksTableElement.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: pageData.urls.dataTableAjax,
          type: 'POST',
          data: function (d) {
            d.status_id = filterStatusSelect.val();
            d.priority_id = filterPrioritySelect.val();
            d.assigned_to_user_id = filterAssignedToUserSelect.val();
            d.due_date_range = filterDueDateRangeInput.val();
          }
        },
        columns: [
          { data: 'id', name: 'crm_tasks.id', width: '5%' },
          { data: 'title', name: 'title' },
          { data: 'status_formatted', name: 'status.name', orderable: false },
          { data: 'priority_formatted', name: 'priority.name', orderable: false },
          { data: 'related_to', name: 'taskable_type', orderable: false },
          { data: 'assigned_to', name: 'assignedToUser.first_name', orderable: false },
          { data: 'due_date', name: 'due_date' },
          { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[6, 'asc'], [0, 'desc']],
        responsive: true,
        language: { 
          search: '', 
          searchPlaceholder: pageData.labels.searchPlaceholder 
        },
        drawCallback: function () {
          $('[data-bs-toggle="tooltip"]').tooltip();
        }
      });
    }
  };

  // Form Functions
  const populateStaticSelects = () => {
    taskStatusSelect.empty().append($('<option>', { value: '', text: pageData.labels.selectStatus }));
    $.each(pageData.taskStatuses, function (id, status) {
      taskStatusSelect.append($('<option>', { value: id, text: status.name }));
    });
    
    taskPrioritySelect.empty().append($('<option>', { value: '', text: pageData.labels.selectPriority }));
    $.each(pageData.taskPriorities, function (id, name) {
      taskPrioritySelect.append($('<option>', { value: id, text: name }));
    });
  };

  const initializeFormSelect2 = () => {
    taskStatusSelect.select2({ 
      dropdownParent: offcanvasElement, 
      placeholder: pageData.labels.selectStatus 
    });
    
    taskPrioritySelect.select2({ 
      dropdownParent: offcanvasElement, 
      placeholder: pageData.labels.selectPriority, 
      allowClear: true 
    });

    taskAssignedToUserSelect.select2({
      placeholder: pageData.labels.selectUser,
      dropdownParent: offcanvasElement,
      allowClear: true,
      ajax: {
        url: pageData.urls.userSearch,
        dataType: 'json',
        delay: 250,
        data: (params) => ({ q: params.term, page: params.page || 1 }),
        processResults: (data, params) => ({ 
          results: data.results, 
          pagination: { more: data.pagination.more } 
        }),
        cache: true
      },
      minimumInputLength: 1
    });

    taskableTypeSelector.select2({ 
      dropdownParent: offcanvasElement, 
      placeholder: pageData.labels.selectType, 
      allowClear: true 
    });
    
    taskableIdSelector.select2({ 
      dropdownParent: offcanvasElement, 
      placeholder: pageData.labels.selectRecord, 
      allowClear: true 
    });
  };

  const initializeFormFlatpickr = () => {
    if (taskDueDateInput.length) {
      taskDueDateInput.flatpickr({ 
        enableTime: true, 
        dateFormat: "Y-m-d H:i", 
        altInput: true, 
        altFormat: "F j, Y H:i" 
      });
    }
    
    if (taskReminderAtInput.length) {
      taskReminderAtInput.flatpickr({ 
        enableTime: true, 
        dateFormat: "Y-m-d H:i", 
        altInput: true, 
        altFormat: "F j, Y H:i", 
        minDate: "today" 
      });
    }
  };

  const updateTaskableIdSelect2 = (type) => {
    taskableIdSelector.val(null).trigger('change').empty();

    // Map singular types to their plural URL keys
    const typeToUrlKey = {
      'contact': 'contacts',
      'company': 'companies',
      'lead': 'leads',
      'deal': 'deals'
    };

    const urlKey = typeToUrlKey[type.toLowerCase()];
    
    if (!type || !urlKey || !pageData.urls.relatedTo[urlKey]) {
      taskableIdSelector.select2({
        dropdownParent: offcanvasElement,
        placeholder: pageData.labels.selectTypeFirst,
        disabled: true,
        allowClear: true
      });
      return;
    }

    const searchUrl = pageData.urls.relatedTo[urlKey];
    taskableIdSelector.select2({
      placeholder: pageData.labels.searchAndSelect + ' ' + type,
      dropdownParent: offcanvasElement,
      allowClear: true,
      disabled: false,
      ajax: {
        url: searchUrl,
        dataType: 'json',
        delay: 250,
        data: (params) => ({ q: params.term, page: params.page || 1 }),
        processResults: (data, params) => ({ 
          results: data.results, 
          pagination: { more: data.pagination.more } 
        }),
        cache: true
      },
      minimumInputLength: 1
    });
  };

  const populateOffcanvasForEdit = (task) => {
    resetOffcanvasForm();
    offcanvasLabel.text(pageData.labels.editTask);
    taskIdInput.val(task.id);
    formMethodInput.val('PUT');

    taskTitleInput.val(task.title);
    taskDescriptionInput.val(task.description);
    
    if (task.due_date && taskDueDateInput[0]?._flatpickr) {
      taskDueDateInput[0]._flatpickr.setDate(task.due_date, true);
    }
    
    if (task.reminder_at && taskReminderAtInput[0]?._flatpickr) {
      taskReminderAtInput[0]._flatpickr.setDate(task.reminder_at, true);
    }
    
    taskStatusSelect.val(task.task_status_id).trigger('change');
    taskPrioritySelect.val(task.task_priority_id).trigger('change');

    if (task.assigned_to_user) {
      const option = new Option(
        task.assigned_to_user.first_name + ' ' + task.assigned_to_user.last_name,
        task.assigned_to_user_id,
        true,
        true
      );
      taskAssignedToUserSelect.append(option).trigger('change');
    }

    if (task.taskable_type && task.taskable_id) {
      const typeName = task.taskable_type.split('\\').pop();
      taskableTypeSelector.val(typeName).trigger('change');

      let taskableText = `Record ID: ${task.taskable_id}`;
      if (task.taskable) {
        switch (typeName) {
          case 'Contact':
            taskableText = task.taskable.first_name + ' ' + task.taskable.last_name;
            break;
          case 'Company':
            taskableText = task.taskable.name;
            break;
          case 'Lead':
          case 'Deal':
            taskableText = task.taskable.title;
            break;
        }
      }
      const taskableOption = new Option(taskableText, task.taskable_id, true, true);
      taskableIdSelector.append(taskableOption).trigger('change');
    }
    
    offcanvas.show();
  };

  // Event Listeners
  $('#add-new-task-btn, #add-new-task-btn-kanban').on('click', function () {
    resetOffcanvasForm();
    offcanvas.show();
  });

  tasksTableElement.on('click', '.mark-task-complete', function () {
    const url = $(this).data('url');
    const completeStatusId = $(this).data('status-id');
    const button = $(this);

    button.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);

    $.ajax({
      url: url,
      type: 'PUT',
      data: { task_status_id_quick: completeStatusId },
      success: function (response) {
        if (response.code === 200) {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.completed,
            text: response.message,
            timer: 1000,
            showConfirmButton: false
          });
          if (dtTasksTable) dtTasksTable.ajax.reload(null, false);
        } else {
          Swal.fire(pageData.labels.error, response.message || pageData.labels.couldNotUpdateStatus, 'error');
          button.html('<i class="bx bx-check-square text-success"></i>').prop('disabled', false);
        }
      },
      error: function () {
        Swal.fire(pageData.labels.error, pageData.labels.unexpectedError, 'error');
        button.html('<i class="bx bx-check-square text-success"></i>').prop('disabled', false);
      }
    });
  });

  if (offcanvasElement) {
    offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);
  }

  taskableTypeSelector.on('change', function () {
    updateTaskableIdSelect2($(this).val());
  });

  $(taskForm).on('submit', function (e) {
    e.preventDefault();
    resetFormValidation(this);

    let url = pageData.urls.store;
    const currentTaskId = taskIdInput.val();
    if (currentTaskId) {
      url = getUrl(pageData.urls.updateTemplate, currentTaskId);
    }
    
    const formData = new FormData(this);
    if (currentTaskId) formData.append('_method', 'PUT');

    const originalButtonText = saveTaskBtn.html();
    saveTaskBtn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm"></span> ${pageData.labels.saving}`);

    $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.code === 200) {
          offcanvas.hide();
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.message,
            timer: 1500,
            showConfirmButton: false
          });
          
          if (currentView === 'kanban') {
            loadKanbanData();
          } else if (dtTasksTable) {
            dtTasksTable.ajax.reload(null, false);
          }
        } else {
          Swal.fire(pageData.labels.error, response.message || pageData.labels.unexpectedError, 'error');
        }
      },
      error: function (jqXHR) {
        if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
          showValidationErrors(jqXHR.responseJSON.errors);
          Swal.fire(pageData.labels.error, jqXHR.responseJSON.message || pageData.labels.pleaseCorrectErrors, 'error');
        } else {
          Swal.fire(pageData.labels.error, jqXHR.responseJSON?.message || pageData.labels.unexpectedError, 'error');
        }
      },
      complete: function () {
        saveTaskBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  // View Toggle Event Listeners
  btnKanbanView.add(btnKanbanViewTop).on('click', switchToKanbanView);
  btnListView.add(btnListViewTop).on('click', switchToListView);

  // Global Functions
  window.editTask = function (taskId) {
    const url = getUrl(pageData.urls.getTaskTemplate, taskId);
    $.get(url, populateOffcanvasForEdit).fail(() => {
      Swal.fire(pageData.labels.error, pageData.labels.couldNotFetchTask, 'error');
    });
  };

  window.markTaskCompleted = function (taskId) {
    // Find the completed status ID from task statuses
    let completedStatusId = null;
    for (const [statusId, status] of Object.entries(pageData.taskStatuses)) {
      if (status.is_completed) {
        completedStatusId = statusId;
        break;
      }
    }
    
    if (!completedStatusId) {
      Swal.fire(pageData.labels.error, 'No completed status found in the system.', 'error');
      return;
    }
    
    Swal.fire({
      title: pageData.labels.confirmComplete || 'Mark as Completed?',
      text: pageData.labels.completeWarning || 'Are you sure you want to mark this task as completed?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yesComplete || 'Yes, Complete it!',
      cancelButtonText: pageData.labels.cancel || 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.value) {
        const url = getUrl(pageData.urls.updateTemplate, taskId);
        $.ajax({
          url: url,
          type: 'PUT',
          data: { 
            task_status_id_quick: completedStatusId,
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.code === 200) {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.completed || 'Completed!',
                text: pageData.labels.taskCompleted || 'Task has been marked as completed.',
                customClass: { confirmButton: 'btn btn-success' }
              });
              // Reload kanban or datatable based on current view
              if (currentView === 'kanban') {
                loadKanbanData();
              } else {
                dtTasksTable.ajax.reload();
              }
            }
          },
          error: function () {
            Swal.fire(pageData.labels.error, pageData.labels.errorCompletingTask || 'Could not complete the task.', 'error');
          }
        });
      }
    });
  };

  window.deleteTask = function (taskId) {
    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.deleteWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.deleteButton,
      cancelButtonText: pageData.labels.cancelButton,
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({
          title: pageData.labels.deleting,
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        $.ajax({
          url: getUrl(pageData.urls.destroyTemplate, taskId),
          type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.code === 200) {
              Swal.fire(pageData.labels.deleted, response.message, 'success');
              
              if (currentView === 'kanban') {
                loadKanbanData();
              } else if (dtTasksTable) {
                dtTasksTable.ajax.reload(null, false);
              }
            } else {
              Swal.fire(pageData.labels.error, response.message || pageData.labels.couldNotDeleteTask, 'error');
            }
          },
          error: function () {
            Swal.close();
            Swal.fire(pageData.labels.error, pageData.labels.unexpectedError, 'error');
          }
        });
      }
    });
  };

  // Initialization
  initFilters();
  initDataTable();
  populateStaticSelects();
  initializeFormSelect2();
  initializeFormFlatpickr();

  // Fix dropdown z-index issues in kanban view
  $(document).on('shown.bs.dropdown', '.kanban-task .dropdown', function() {
    const $dropdown = $(this);
    const $task = $dropdown.closest('.kanban-task');
    $task.css('z-index', '100');
    $dropdown.find('.dropdown-menu').css('z-index', '9999');
  });
  
  $(document).on('hidden.bs.dropdown', '.kanban-task .dropdown', function() {
    $(this).closest('.kanban-task').css('z-index', '');
  });
  updateTaskableIdSelect2('');

  // Set initial view
  if (currentView === 'kanban') {
    switchToKanbanView();
  } else {
    switchToListView();
  }
});