/**
 * Billing Settings Management
 */

'use strict';

$(function () {
  let currentSettings = {};
  let settingsStructure = {};

  // Initialize
  loadSettings();
  
  // Load settings from server
  function loadSettings() {
    $.ajax({
      url: pageData.urls.getSettings,
      type: 'GET',
      success: function (response) {
        if (response.status === 'success') {
          currentSettings = response.data.settings;
          settingsStructure = response.data.structure;
          renderSettings();
          $('#settingsLoader').hide();
          $('#settingsContent').show();
        }
      },
      error: function () {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.saveFailed,
          text: 'Unable to load settings'
        });
      }
    });
  }

  // Render settings form
  function renderSettings() {
    const accordion = $('#settingsAccordion');
    accordion.empty();

    let groupIndex = 0;
    for (const [groupKey, groupSettings] of Object.entries(settingsStructure)) {
      const groupId = `group-${groupKey}`;
      const isExpanded = groupIndex === 0; // Expand first group by default
      
      const groupHtml = createAccordionGroup(groupId, groupKey, groupSettings, isExpanded);
      accordion.append(groupHtml);
      
      groupIndex++;
    }

    // Initialize Select2
    $('.select2').select2();
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  // Create accordion group
  function createAccordionGroup(groupId, groupKey, groupSettings, isExpanded) {
    const groupTitle = formatGroupTitle(groupKey);
    
    const accordionItem = $('<div class="accordion-item">');
    
    // Header
    const header = $(`
      <h2 class="accordion-header" id="heading-${groupId}">
        <button class="accordion-button ${!isExpanded ? 'collapsed' : ''}" type="button" 
                data-bs-toggle="collapse" data-bs-target="#collapse-${groupId}" 
                aria-expanded="${isExpanded}" aria-controls="collapse-${groupId}">
          ${groupTitle}
        </button>
      </h2>
    `);
    
    // Body
    const body = $(`
      <div id="collapse-${groupId}" class="accordion-collapse collapse ${isExpanded ? 'show' : ''}" 
           aria-labelledby="heading-${groupId}" data-bs-parent="#settingsAccordion">
        <div class="accordion-body">
          <div class="row">
          </div>
        </div>
      </div>
    `);
    
    const row = body.find('.row');
    
    // Add settings to group
    for (const [settingKey, settingConfig] of Object.entries(groupSettings)) {
      const settingElement = createSettingElement(settingKey, settingConfig);
      row.append(settingElement);
    }
    
    accordionItem.append(header);
    accordionItem.append(body);
    
    return accordionItem;
  }

  // Create individual setting element
  function createSettingElement(key, config) {
    const col = $('<div class="col-md-6 mb-3">');
    
    // Label
    const label = $(`
      <label class="form-label" for="${key}">
        ${config.label}
        ${config.help ? `<i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="${config.help}"></i>` : ''}
      </label>
    `);
    
    // Input based on type
    let input;
    const currentValue = currentSettings[key] !== undefined ? currentSettings[key] : config.default;
    
    switch (config.type) {
      case 'toggle':
        input = $(`
          <div class="form-check form-switch">
            <input class="form-check-input setting-input" type="checkbox" id="${key}" name="${key}" 
                   ${currentValue == '1' || currentValue === true ? 'checked' : ''}>
            <label class="form-check-label" for="${key}"></label>
          </div>
        `);
        break;
        
      case 'select':
        input = $(`<select class="form-select select2 setting-input" id="${key}" name="${key}">`);
        for (const [optionValue, optionLabel] of Object.entries(config.options || {})) {
          const option = $(`<option value="${optionValue}">${optionLabel}</option>`);
          if (currentValue == optionValue) {
            option.prop('selected', true);
          }
          input.append(option);
        }
        break;
        
      case 'number':
        input = $(`
          <input type="number" class="form-control setting-input" id="${key}" name="${key}" 
                 value="${currentValue}" 
                 ${config.step ? `step="${config.step}"` : ''}>
        `);
        break;
        
      case 'text':
      default:
        input = $(`
          <input type="text" class="form-control setting-input" id="${key}" name="${key}" 
                 value="${currentValue}">
        `);
        break;
    }
    
    col.append(label);
    col.append(input);
    
    // Help text
    if (config.help) {
      col.append(`<div class="form-text">${config.help}</div>`);
    }
    
    return col;
  }

  // Format group title
  function formatGroupTitle(key) {
    return key.replace(/_/g, ' ')
      .split(' ')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  }

  // Save settings
  $('#saveSettingsBtn').on('click', function () {
    const btn = $(this);
    const originalHtml = btn.html();
    
    // Show loading state
    btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>' + 'Saving...');
    
    // Collect form data
    const formData = {};
    $('.setting-input').each(function () {
      const input = $(this);
      const key = input.attr('name');
      
      if (input.attr('type') === 'checkbox') {
        formData[key] = input.is(':checked');
      } else {
        formData[key] = input.val();
      }
    });
    
    // Send update request
    $.ajax({
      url: pageData.urls.updateSettings,
      type: 'POST',
      data: formData,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.saveSuccess,
            timer: 2000,
            showConfirmButton: false
          });
          
          // Update current settings
          currentSettings = response.data.settings;
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.saveFailed,
            text: response.data || 'Unknown error'
          });
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.saveFailed;
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage
        });
      },
      complete: function () {
        btn.prop('disabled', false).html(originalHtml);
      }
    });
  });

  // Reset settings
  $('#resetSettingsBtn').on('click', function () {
    Swal.fire({
      title: pageData.labels.resetConfirm,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yes,
      cancelButtonText: pageData.labels.cancel,
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.resetSettings,
          type: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.resetSuccess,
                timer: 2000,
                showConfirmButton: false
              });
              
              // Reload settings
              currentSettings = response.data.settings;
              renderSettings();
            }
          },
          error: function () {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: pageData.labels.resetFailed
            });
          }
        });
      }
    });
  });

  // Handle setting change for auto-save (optional)
  $(document).on('change', '.setting-input', function () {
    const input = $(this);
    const key = input.attr('name');
    let value = input.val();
    
    if (input.attr('type') === 'checkbox') {
      value = input.is(':checked');
    }
    
    // You can implement auto-save here if needed
    // updateSingleSetting(key, value);
  });

  // Update single setting (for auto-save)
  function updateSingleSetting(key, value) {
    $.ajax({
      url: pageData.urls.updateSingleSetting,
      type: 'POST',
      data: {
        key: key,
        value: value,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.status === 'success') {
          // Show small toast notification
          showToast('Setting updated', 'success');
        }
      },
      error: function () {
        // Revert the change
        renderSettings();
        showToast('Failed to update setting', 'error');
      }
    });
  }

  // Small toast notification
  function showToast(message, type) {
    // You can implement a toast notification here
    console.log(`${type}: ${message}`);
  }
});