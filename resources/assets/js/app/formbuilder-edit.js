$(function () {
  'use strict';

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  let formFields = [];
  let selectedFieldId = null;
  let fieldCounter = 0;

  // Initialize with existing form data
  if (pageData.formData.fields && pageData.formData.fields.length > 0) {
    formFields = [...pageData.formData.fields];

    // Set field counter to avoid ID conflicts
    const maxId = Math.max(...formFields.map(f => {
      const match = f.id.match(/field_(\d+)/);
      return match ? parseInt(match[1]) : 0;
    }));
    fieldCounter = maxId;

    // Render existing fields
    formFields.forEach(field => {
      renderFormField(field);
    });

    updateMainDropZone();
  }

  // Initialize Flatpickr
  $('.flatpickr-datetime').flatpickr({
    enableTime: true,
    dateFormat: 'Y-m-d H:i',
    time_24hr: true
  });

  // Initialize Sortable for form canvas
  const formCanvas = document.getElementById('formCanvas');
  const sortable = Sortable.create(formCanvas, {
    group: 'formBuilder',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    onAdd: function (evt) {
      const fieldType = evt.item.dataset.fieldType;
      const fieldConfig = JSON.parse(evt.item.dataset.fieldConfig || '{}');

      // Remove the dragged element and create a proper form field
      evt.item.remove();

      addFormField(fieldType, fieldConfig);
    },
    onUpdate: function (evt) {
      updateFieldOrder();
    }
  });

  // Make field palette items draggable
  new Sortable(document.getElementById('fieldPalette'), {
    group: {
      name: 'formBuilder',
      pull: 'clone',
      put: false
    },
    sort: false,
    onStart: function (evt) {
      evt.item.classList.add('dragging');
    },
    onEnd: function (evt) {
      evt.item.classList.remove('dragging');
    }
  });

  function generateFieldId() {
    return 'field_' + (++fieldCounter);
  }

  function addFormField(type, config = {}) {
    const fieldId = generateFieldId();
    const field = {
      id: fieldId,
      type: type,
      label: config.label || getDefaultLabel(type),
      name: config.name || fieldId.toLowerCase(),
      required: config.required || false,
      ...config
    };

    formFields.push(field);
    renderFormField(field);

    // Hide main drop zone if we have fields
    updateMainDropZone();
  }

  function getDefaultLabel(type) {
    const labels = {
      'text': 'Text Input',
      'textarea': 'Text Area',
      'email': 'Email Address',
      'number': 'Number',
      'tel': 'Phone Number',
      'url': 'Website URL',
      'password': 'Password',
      'date': 'Date',
      'time': 'Time',
      'datetime-local': 'Date & Time',
      'select': 'Select Option',
      'radio': 'Radio Choice',
      'checkbox': 'Checkboxes',
      'file': 'File Upload',
      'range': 'Range Slider',
      'color': 'Color Picker',
      'hidden': 'Hidden Field',
      'html': 'HTML Content',
      'heading': 'Heading',
      'divider': 'Divider',
      'rating': 'Rating',
      'step': 'Form Step'
    };
    return labels[type] || 'Field';
  }

  function renderFormField(field) {
    const fieldHtml = generateFieldHtml(field);
    const fieldElement = $(`
      <div class="form-field" data-field-id="${field.id}">
        <div class="field-controls">
          <button type="button" class="btn btn-sm btn-primary edit-field" title="Edit">
            <i class="bx bx-edit"></i>
          </button>
          <button type="button" class="btn btn-sm btn-danger delete-field" title="Delete">
            <i class="bx bx-trash"></i>
          </button>
        </div>
        ${fieldHtml}
      </div>
    `);

    $('#formCanvas').append(fieldElement);
  }

  function generateFieldHtml(field) {
    let html = '';

    switch (field.type) {
      case 'text':
      case 'email':
      case 'tel':
      case 'url':
      case 'password':
      case 'number':
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <input type="${field.type}" class="form-control"
                   placeholder="${field.placeholder || ''}"
                   ${field.required ? 'required' : ''}
                   ${field.minLength ? `minlength="${field.minLength}"` : ''}
                   ${field.maxLength ? `maxlength="${field.maxLength}"` : ''}
                   ${field.min ? `min="${field.min}"` : ''}
                   ${field.max ? `max="${field.max}"` : ''}
                   ${field.step ? `step="${field.step}"` : ''}
                   disabled>
          </div>
        `;
        break;

      case 'textarea':
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <textarea class="form-control"
                      rows="${field.rows || 4}"
                      placeholder="${field.placeholder || ''}"
                      ${field.required ? 'required' : ''}
                      ${field.minLength ? `minlength="${field.minLength}"` : ''}
                      ${field.maxLength ? `maxlength="${field.maxLength}"` : ''}
                      disabled></textarea>
          </div>
        `;
        break;

      case 'date':
      case 'time':
      case 'datetime-local':
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <input type="${field.type}" class="form-control"
                   ${field.required ? 'required' : ''}
                   ${field.min ? `min="${field.min}"` : ''}
                   ${field.max ? `max="${field.max}"` : ''}
                   disabled>
          </div>
        `;
        break;

      case 'select':
        let selectOptions = '';
        if (field.options && field.options.length > 0) {
          field.options.forEach(option => {
            selectOptions += `<option value="${option.value}">${option.label}</option>`;
          });
        }
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <select class="form-select" ${field.required ? 'required' : ''} disabled>
              <option value="">${field.placeholder || 'Select an option...'}</option>
              ${selectOptions}
            </select>
          </div>
        `;
        break;

      case 'radio':
        let radioOptions = '';
        if (field.options && field.options.length > 0) {
          field.options.forEach((option, index) => {
            radioOptions += `
              <div class="form-check">
                <input class="form-check-input" type="radio" name="${field.name}" value="${option.value}" id="${field.id}_${index}" disabled>
                <label class="form-check-label" for="${field.id}_${index}">${option.label}</label>
              </div>
            `;
          });
        }
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            ${radioOptions}
          </div>
        `;
        break;

      case 'checkbox':
        let checkboxOptions = '';
        if (field.options && field.options.length > 0) {
          field.options.forEach((option, index) => {
            checkboxOptions += `
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="${field.name}[]" value="${option.value}" id="${field.id}_${index}" disabled>
                <label class="form-check-label" for="${field.id}_${index}">${option.label}</label>
              </div>
            `;
          });
        }
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            ${checkboxOptions}
          </div>
        `;
        break;

      case 'file':
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <input type="file" class="form-control"
                   ${field.required ? 'required' : ''}
                   ${field.accept ? `accept="${field.accept}"` : ''}
                   ${field.multiple ? 'multiple' : ''}
                   disabled>
            ${field.maxSize ? `<div class="form-text">Maximum file size: ${field.maxSize}KB</div>` : ''}
          </div>
        `;
        break;

      case 'range':
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <input type="range" class="form-range"
                   min="${field.min || 0}"
                   max="${field.max || 100}"
                   step="${field.step || 1}"
                   value="${field.value || 50}"
                   disabled>
            <div class="d-flex justify-content-between">
              <small>${field.min || 0}</small>
              <small>${field.max || 100}</small>
            </div>
          </div>
        `;
        break;

      case 'color':
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <input type="color" class="form-control form-control-color"
                   value="${field.value || '#000000'}"
                   ${field.required ? 'required' : ''}
                   disabled>
          </div>
        `;
        break;

      case 'hidden':
        html = `
          <div class="field-group">
            <div class="alert alert-info">
              <i class="bx bx-info-circle me-1"></i>
              Hidden field: ${field.label} (Value: ${field.value || ''})
            </div>
          </div>
        `;
        break;

      case 'html':
        html = `
          <div class="field-group">
            <div class="border p-3 rounded">
              ${field.content || '<p>HTML content will appear here</p>'}
            </div>
          </div>
        `;
        break;

      case 'heading':
        const headingTag = field.level || 'h3';
        const alignment = field.align || 'left';
        html = `
          <div class="field-group">
            <${headingTag} class="text-${alignment}">${field.text || 'Section Heading'}</${headingTag}>
          </div>
        `;
        break;

      case 'divider':
        html = `
          <div class="field-group">
            <hr style="border-color: ${field.color || '#e0e0e0'}; border-style: ${field.style || 'solid'};">
          </div>
        `;
        break;

      case 'rating':
        let stars = '';
        const maxRating = field.max || 5;
        for (let i = 1; i <= maxRating; i++) {
          stars += `<i class="bx bx-star text-warning me-1" style="font-size: 1.5rem;"></i>`;
        }
        html = `
          <div class="field-group">
            <label class="form-label">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
            <div>${stars}</div>
          </div>
        `;
        break;

      case 'step':
        html = `
          <div class="field-group">
            <div class="card bg-primary text-white">
              <div class="card-body">
                <h5 class="card-title text-white">${field.title || 'Step Title'}</h5>
                <p class="card-text">${field.description || 'Step description...'}</p>
              </div>
            </div>
          </div>
        `;
        break;

      default:
        html = `
          <div class="field-group">
            <div class="alert alert-warning">Unknown field type: ${field.type}</div>
          </div>
        `;
    }

    return html;
  }

  function updateMainDropZone() {
    const mainDropZone = $('#mainDropZone');
    if (formFields.length > 0) {
      mainDropZone.hide();
    } else {
      mainDropZone.show();
    }
  }

  function updateFieldOrder() {
    const newOrder = [];
    $('#formCanvas .form-field').each(function() {
      const fieldId = $(this).data('field-id');
      const field = formFields.find(f => f.id === fieldId);
      if (field) {
        newOrder.push(field);
      }
    });
    formFields = newOrder;
  }

  // Event handlers
  $(document).on('click', '.form-field', function(e) {
    if ($(e.target).closest('.field-controls').length === 0) {
      selectField($(this).data('field-id'));
    }
  });

  $(document).on('click', '.edit-field', function() {
    const fieldId = $(this).closest('.form-field').data('field-id');
    selectField(fieldId);
  });

  $(document).on('click', '.delete-field', function() {
    const fieldId = $(this).closest('.form-field').data('field-id');
    deleteField(fieldId);
  });

  function selectField(fieldId) {
    // Remove previous selection
    $('.form-field').removeClass('selected');

    // Add selection to current field
    $(`.form-field[data-field-id="${fieldId}"]`).addClass('selected');

    selectedFieldId = fieldId;
    const field = formFields.find(f => f.id === fieldId);

    if (field) {
      showFieldProperties(field);
    }
  }

  function deleteField(fieldId) {
    Swal.fire({
      title: pageData.labels.confirmDelete,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yes,
      cancelButtonText: pageData.labels.no,
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        // Remove from array
        formFields = formFields.filter(f => f.id !== fieldId);

        // Remove from DOM
        $(`.form-field[data-field-id="${fieldId}"]`).remove();

        // Clear properties panel if this field was selected
        if (selectedFieldId === fieldId) {
          selectedFieldId = null;
          clearPropertiesPanel();
        }

        updateMainDropZone();
      }
    });
  }

  function showFieldProperties(field) {
    let propertiesHtml = `
      <div class="mb-3">
        <label class="form-label">${pageData.labels.fieldLabel}</label>
        <input type="text" class="form-control" id="prop_label" value="${field.label}">
      </div>
      <div class="mb-3">
        <label class="form-label">${pageData.labels.fieldName}</label>
        <input type="text" class="form-control" id="prop_name" value="${field.name}">
      </div>
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="prop_required" ${field.required ? 'checked' : ''}>
          <label class="form-check-label" for="prop_required">
            ${pageData.labels.required}
          </label>
        </div>
      </div>
    `;

    // Add type-specific properties
    propertiesHtml += getTypeSpecificProperties(field);

    $('#propertiesPanel').html(propertiesHtml);

    // Initialize any special inputs
    initializePropertyInputs();

    // Bind change events
    bindPropertyEvents(field);
  }

  function getTypeSpecificProperties(field) {
    let html = '';

    switch (field.type) {
      case 'text':
      case 'email':
      case 'tel':
      case 'url':
      case 'password':
      case 'textarea':
        html += `
          <div class="mb-3">
            <label class="form-label">${pageData.labels.placeholder}</label>
            <input type="text" class="form-control" id="prop_placeholder" value="${field.placeholder || ''}">
          </div>
          <div class="row">
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label">${pageData.labels.minLength}</label>
                <input type="number" class="form-control" id="prop_minLength" value="${field.minLength || ''}">
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label">${pageData.labels.maxLength}</label>
                <input type="number" class="form-control" id="prop_maxLength" value="${field.maxLength || ''}">
              </div>
            </div>
          </div>
        `;

        if (field.type === 'textarea') {
          html += `
            <div class="mb-3">
              <label class="form-label">${pageData.labels.rows}</label>
              <input type="number" class="form-control" id="prop_rows" value="${field.rows || 4}" min="2" max="20">
            </div>
          `;
        }
        break;

      case 'number':
      case 'range':
        html += `
          <div class="row">
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label">${pageData.labels.minValue}</label>
                <input type="number" class="form-control" id="prop_min" value="${field.min || ''}">
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label">${pageData.labels.maxValue}</label>
                <input type="number" class="form-control" id="prop_max" value="${field.max || ''}">
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">${pageData.labels.step}</label>
            <input type="number" class="form-control" id="prop_step" value="${field.step || 1}" min="0.01" step="0.01">
          </div>
        `;

        if (field.type === 'number') {
          html += `
            <div class="mb-3">
              <label class="form-label">${pageData.labels.placeholder}</label>
              <input type="text" class="form-control" id="prop_placeholder" value="${field.placeholder || ''}">
            </div>
          `;
        }

        if (field.type === 'range') {
          html += `
            <div class="mb-3">
              <label class="form-label">Default Value</label>
              <input type="number" class="form-control" id="prop_value" value="${field.value || 50}">
            </div>
          `;
        }
        break;

      case 'select':
      case 'radio':
      case 'checkbox':
        if (field.type === 'select') {
          html += `
            <div class="mb-3">
              <label class="form-label">${pageData.labels.placeholder}</label>
              <input type="text" class="form-control" id="prop_placeholder" value="${field.placeholder || ''}">
            </div>
          `;
        }

        html += `
          <div class="mb-3">
            <label class="form-label">${pageData.labels.options}</label>
            <div id="optionsContainer">
        `;

        if (field.options && field.options.length > 0) {
          field.options.forEach((option, index) => {
            html += `
              <div class="option-row mb-2">
                <div class="row">
                  <div class="col-5">
                    <input type="text" class="form-control option-label" placeholder="${pageData.labels.optionLabel}" value="${option.label}">
                  </div>
                  <div class="col-5">
                    <input type="text" class="form-control option-value" placeholder="${pageData.labels.optionValue}" value="${option.value}">
                  </div>
                  <div class="col-2">
                    <button type="button" class="btn btn-danger btn-sm remove-option">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            `;
          });
        }

        html += `
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn">
              <i class="bx bx-plus me-1"></i>${pageData.labels.addOption}
            </button>
          </div>
        `;
        break;

      case 'file':
        html += `
          <div class="mb-3">
            <label class="form-label">${pageData.labels.accept}</label>
            <input type="text" class="form-control" id="prop_accept" value="${field.accept || ''}" placeholder=".jpg,.png,.pdf">
          </div>
          <div class="mb-3">
            <label class="form-label">${pageData.labels.maxSize}</label>
            <input type="number" class="form-control" id="prop_maxSize" value="${field.maxSize || ''}">
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="prop_multiple" ${field.multiple ? 'checked' : ''}>
              <label class="form-check-label" for="prop_multiple">
                ${pageData.labels.multiple}
              </label>
            </div>
          </div>
        `;
        break;

      case 'color':
        html += `
          <div class="mb-3">
            <label class="form-label">Default Color</label>
            <input type="color" class="form-control form-control-color" id="prop_value" value="${field.value || '#000000'}">
          </div>
        `;
        break;

      case 'hidden':
        html += `
          <div class="mb-3">
            <label class="form-label">Hidden Value</label>
            <input type="text" class="form-control" id="prop_value" value="${field.value || ''}">
          </div>
        `;
        break;

      case 'html':
        html += `
          <div class="mb-3">
            <label class="form-label">${pageData.labels.content}</label>
            <textarea class="form-control" id="prop_content" rows="6">${field.content || ''}</textarea>
          </div>
        `;
        break;

      case 'heading':
        html += `
          <div class="mb-3">
            <label class="form-label">Heading Text</label>
            <input type="text" class="form-control" id="prop_text" value="${field.text || ''}">
          </div>
          <div class="mb-3">
            <label class="form-label">${pageData.labels.headingLevel}</label>
            <select class="form-select" id="prop_level">
              <option value="h1" ${field.level === 'h1' ? 'selected' : ''}>H1</option>
              <option value="h2" ${field.level === 'h2' ? 'selected' : ''}>H2</option>
              <option value="h3" ${field.level === 'h3' ? 'selected' : ''}>H3</option>
              <option value="h4" ${field.level === 'h4' ? 'selected' : ''}>H4</option>
              <option value="h5" ${field.level === 'h5' ? 'selected' : ''}>H5</option>
              <option value="h6" ${field.level === 'h6' ? 'selected' : ''}>H6</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">${pageData.labels.alignment}</label>
            <select class="form-select" id="prop_align">
              <option value="left" ${field.align === 'left' ? 'selected' : ''}>${pageData.labels.left}</option>
              <option value="center" ${field.align === 'center' ? 'selected' : ''}>${pageData.labels.center}</option>
              <option value="right" ${field.align === 'right' ? 'selected' : ''}>${pageData.labels.right}</option>
            </select>
          </div>
        `;
        break;

      case 'divider':
        html += `
          <div class="mb-3">
            <label class="form-label">Border Style</label>
            <select class="form-select" id="prop_style">
              <option value="solid" ${field.style === 'solid' ? 'selected' : ''}>Solid</option>
              <option value="dashed" ${field.style === 'dashed' ? 'selected' : ''}>Dashed</option>
              <option value="dotted" ${field.style === 'dotted' ? 'selected' : ''}>Dotted</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Border Color</label>
            <input type="color" class="form-control form-control-color" id="prop_color" value="${field.color || '#e0e0e0'}">
          </div>
        `;
        break;

      case 'rating':
        html += `
          <div class="mb-3">
            <label class="form-label">Maximum Rating</label>
            <input type="number" class="form-control" id="prop_max" value="${field.max || 5}" min="1" max="10">
          </div>
        `;
        break;

      case 'step':
        html += `
          <div class="mb-3">
            <label class="form-label">Step Title</label>
            <input type="text" class="form-control" id="prop_title" value="${field.title || ''}">
          </div>
          <div class="mb-3">
            <label class="form-label">Step Description</label>
            <textarea class="form-control" id="prop_description" rows="3">${field.description || ''}</textarea>
          </div>
        `;
        break;
    }

    return html;
  }

  function initializePropertyInputs() {
    // Initialize any special inputs like Select2 if needed
  }

  function bindPropertyEvents(field) {
    // Bind change events for all property inputs
    $('#propertiesPanel input, #propertiesPanel select, #propertiesPanel textarea').on('input change', function() {
      updateFieldProperty(field, $(this).attr('id'), $(this).val(), $(this).attr('type'));
    });

    // Special handling for checkboxes
    $('#propertiesPanel input[type="checkbox"]').on('change', function() {
      updateFieldProperty(field, $(this).attr('id'), $(this).is(':checked'), 'checkbox');
    });

    // Add option button
    $(document).on('click', '#addOptionBtn', function() {
      addOptionRow();
    });

    // Remove option button
    $(document).on('click', '.remove-option', function() {
      $(this).closest('.option-row').remove();
      updateOptionsProperty(field);
    });

    // Options change
    $(document).on('input', '.option-label, .option-value', function() {
      updateOptionsProperty(field);
    });
  }

  function updateFieldProperty(field, propertyId, value, inputType) {
    const property = propertyId.replace('prop_', '');

    if (inputType === 'checkbox') {
      field[property] = value;
    } else if (inputType === 'number') {
      field[property] = value ? Number(value) : null;
    } else {
      field[property] = value;
    }

    // Update the visual representation
    refreshFieldDisplay(field);
  }

  function addOptionRow() {
    const optionHtml = `
      <div class="option-row mb-2">
        <div class="row">
          <div class="col-5">
            <input type="text" class="form-control option-label" placeholder="${pageData.labels.optionLabel}">
          </div>
          <div class="col-5">
            <input type="text" class="form-control option-value" placeholder="${pageData.labels.optionValue}">
          </div>
          <div class="col-2">
            <button type="button" class="btn btn-danger btn-sm remove-option">
              <i class="bx bx-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;
    $('#optionsContainer').append(optionHtml);
  }

  function updateOptionsProperty(field) {
    const options = [];
    $('#optionsContainer .option-row').each(function() {
      const label = $(this).find('.option-label').val();
      const value = $(this).find('.option-value').val();

      if (label && value) {
        options.push({ label: label, value: value });
      }
    });

    field.options = options;
    refreshFieldDisplay(field);
  }

  function refreshFieldDisplay(field) {
    const fieldElement = $(`.form-field[data-field-id="${field.id}"]`);
    const newHtml = generateFieldHtml(field);
    fieldElement.find('.field-group').replaceWith($(newHtml).find('.field-group'));
  }

  function clearPropertiesPanel() {
    $('#propertiesPanel').html(`
      <div class="text-center text-muted">
        <i class="bx bx-settings bx-lg mb-2"></i>
        <div>${pageData.labels.selectFieldToEdit}</div>
      </div>
    `);
  }

  // Copy public URL
  $('#copyPublicUrl').on('click', function() {
    const url = $(this).prev('input').val();
    navigator.clipboard.writeText(url).then(function() {
      Swal.fire({
        title: pageData.labels.success,
        text: pageData.labels.linkCopied,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
    });
  });

  // Clear form
  $('#clearFormBtn').on('click', function() {
    Swal.fire({
      title: pageData.labels.confirmClear,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yes,
      cancelButtonText: pageData.labels.no,
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        formFields = [];
        selectedFieldId = null;
        $('#formCanvas').empty().append(`
          <div class="drop-zone" id="mainDropZone">
            <div class="text-center">
              <i class="bx bx-plus-circle bx-lg mb-2"></i>
              <div>Drag fields from the left panel to build your form</div>
            </div>
          </div>
        `);
        clearPropertiesPanel();
      }
    });
  });

  // Preview form
  $('#previewFormBtn').on('click', function() {
    generateFormPreview();
    $('#previewModal').modal('show');
  });

  function generateFormPreview() {
    let previewHtml = '<form>';

    formFields.forEach(field => {
      previewHtml += generateFieldHtml(field).replace(/disabled/g, '');
    });

    previewHtml += `
      <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary">Submit Form</button>
      </div>
    </form>`;

    $('#formPreview').html(previewHtml);
  }

  // Save form
  $('#saveFormBtn').on('click', function() {
    if (formFields.length === 0) {
      Swal.fire({
        title: pageData.labels.error,
        text: 'Please add at least one field to the form',
        icon: 'error'
      });
      return;
    }

    const formData = {
      name: $('#formName').val(),
      description: $('#formDescription').val(),
      is_public: $('#isPublic').is(':checked') ? 1 : 0,
      expires_at: $('#expiresAt').val(),
      fields: formFields,
      settings: {}
    };

    if (!formData.name) {
      Swal.fire({
        title: pageData.labels.error,
        text: 'Please enter a form name',
        icon: 'error'
      });
      return;
    }

    $.ajax({
      url: pageData.urls.formUpdate,
      type: 'PUT',
      data: formData,
      success: function(response) {
        if (response.status === 'success') {
          Swal.fire({
            title: pageData.labels.success,
            text: pageData.labels.formUpdated,
            icon: 'success'
          });
        }
      },
      error: function(xhr) {
        Swal.fire({
          title: pageData.labels.error,
          text: xhr.responseJSON?.data || pageData.labels.errorOccurred,
          icon: 'error'
        });
      }
    });
  });
});
