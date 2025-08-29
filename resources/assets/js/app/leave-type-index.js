'use strict';



$(function () {
  var dt_table = $('.datatables-leaveTypes');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // LeaveTypes datatable
  if (dt_table.length) {
    var dt_leaveType = dt_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatable,
        error: function (xhr, error, code) {
          console.log('Error: ' + error);
          console.log('Code: ' + code);
          console.log('Response: ' + xhr.responseText);
        }
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'code' },
        { data: 'notes' },
        { data: 'is_proof_required' },
        { data: 'status' },
        { data: 'actions' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // id
          targets: 1,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $id = full['id'];

            return '<span class="id">' + $id + '</span>';
          }
        },
        {
          // name
          targets: 2,
          className: 'text-start',
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['name'];

            return '<span class="user-name">' + $name + '</span>';
          }
        },
        {
          // code
          targets: 3,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $code = full['code'];

            return '<span class="user-code">' + $code + '</span>';
          }
        },
        {
          // notes
          targets: 4,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $notes = full['notes'] ?? 'N/A';

            return '<span class="user-notes">' + $notes + '</span>';
          }
        },
        {
          // is_proof_required
          targets: 5,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['is_proof_required'];
          }
        },

        {
          // status
          targets: 6,
          className: 'text-start',
          render: function (data, type, full, meta) {
            return full['status'];
          }
        },

        {
          // Actions
          targets: 7,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return full['actions'];
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [7, 10, 20, 50, 70, 100], //for length of menu
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Leave Type',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      // Buttons with Dropdown
      buttons: pageData.permissions.create ? [{
        text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">' + pageData.labels.addLeaveType + '</span>',
        className: 'btn btn-primary mx-4',
        action: function () {
          resetForm();
          $('#offcanvasLeaveTypeLabel').html(pageData.labels.addLeaveType);
          $('.data-submit').html(pageData.labels.create);
          $('#offcanvasAddOrUpdateLeaveType').offcanvas('show');
        }
      }] : [],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();

              return 'Details of ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }

  var offCanvasForm = $('#offcanvasAddOrUpdateLeaveType');

  // Toggle handlers for all checkboxes
  $('#isProofRequiredToggle').on('change', function () {
    $('#isProofRequired').val(this.checked ? 1 : 0);
  });
  
  $('#isCompOffTypeToggle').on('change', function () {
    $('#isCompOffType').val(this.checked ? 1 : 0);
  });
  
  $('#isAccrualEnabledToggle').on('change', function () {
    $('#isAccrualEnabled').val(this.checked ? 1 : 0);
    $('#accrualSettingsSection').toggle(this.checked);
  });
  
  $('#allowCarryForwardToggle').on('change', function () {
    $('#allowCarryForward').val(this.checked ? 1 : 0);
    $('#carryForwardSettingsSection').toggle(this.checked);
  });
  
  $('#allowEncashmentToggle').on('change', function () {
    $('#allowEncashment').val(this.checked ? 1 : 0);
    $('#encashmentSettingsSection').toggle(this.checked);
  });

  const addLeaveTypeForm = document.getElementById('leaveTypeForm');

  // Edit function called from actions dropdown
  window.editLeaveType = function(id) {
    var dtrModal = $('.dtr-bs-modal.show');

    // hide responsive modal in small screen
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    // changing the title of offcanvas and button text
    $('#offcanvasLeaveTypeLabel').html(pageData.labels.editLeaveType);
    $('.data-submit').html(pageData.labels.update);

    // get data from edit endpoint
    $.get(pageData.urls.edit.replace(':id', id), function (response) {
      if (response.status === 'success') {
        var data = response.data;
        $('#id').val(data.id);
        $('#name').val(data.name);
        $('#code').val(data.code);
        $('#notes').val(data.notes);
        
        // Basic toggles
        $('#isProofRequired').val(data.is_proof_required ? 1 : 0);
        $('#isProofRequiredToggle').prop('checked', data.is_proof_required);
        $('#isCompOffType').val(data.is_comp_off_type ? 1 : 0);
        $('#isCompOffTypeToggle').prop('checked', data.is_comp_off_type);
        
        // Accrual settings
        $('#isAccrualEnabled').val(data.is_accrual_enabled ? 1 : 0);
        $('#isAccrualEnabledToggle').prop('checked', data.is_accrual_enabled);
        $('#accrualSettingsSection').toggle(data.is_accrual_enabled);
        $('#accrualFrequency').val(data.accrual_frequency || 'yearly');
        $('#accrualRate').val(data.accrual_rate || '');
        $('#maxAccrualLimit').val(data.max_accrual_limit || '');
        
        // Carry forward settings
        $('#allowCarryForward').val(data.allow_carry_forward ? 1 : 0);
        $('#allowCarryForwardToggle').prop('checked', data.allow_carry_forward);
        $('#carryForwardSettingsSection').toggle(data.allow_carry_forward);
        $('#maxCarryForward').val(data.max_carry_forward || '');
        $('#carryForwardExpiryMonths').val(data.carry_forward_expiry_months || '');
        
        // Encashment settings
        $('#allowEncashment').val(data.allow_encashment ? 1 : 0);
        $('#allowEncashmentToggle').prop('checked', data.allow_encashment);
        $('#encashmentSettingsSection').toggle(data.allow_encashment);
        $('#maxEncashmentDays').val(data.max_encashment_days || '');
        
        // Show the offcanvas
        $('#offcanvasAddOrUpdateLeaveType').offcanvas('show');
      }
    });
  };

  // Form submission with standard jQuery
  $('#leaveTypeForm').on('submit', function(e) {
    e.preventDefault();
    
    // Clear previous validation errors
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    // Basic validation
    let hasError = false;
    
    // Validate name
    if (!$('#name').val().trim()) {
      $('#name').addClass('is-invalid');
      $('#name').after('<div class="invalid-feedback">' + pageData.labels.nameRequired + '</div>');
      hasError = true;
    }
    
    // Validate code
    if (!$('#code').val().trim()) {
      $('#code').addClass('is-invalid');
      $('#code').after('<div class="invalid-feedback">' + pageData.labels.codeRequired + '</div>');
      hasError = true;
    }
    
    if (hasError) {
      return false;
    }
    
    // Check code availability for new records or changed codes
    const currentCode = $('#code').val().trim();
    const recordId = $('#id').val();
    
    if (currentCode) {
      $.ajax({
        url: pageData.urls.checkCode,
        type: 'GET',
        data: {
          code: currentCode,
          id: recordId
        },
        success: function(response) {
          if (response.valid) {
            submitLeaveTypeForm();
          } else {
            $('#code').addClass('is-invalid');
            $('#code').after('<div class="invalid-feedback">' + pageData.labels.codeTaken + '</div>');
          }
        },
        error: function(xhr) {
          console.log('Code validation error:', xhr);
          submitLeaveTypeForm(); // Proceed if validation check fails
        }
      });
    } else {
      submitLeaveTypeForm();
    }
  });
  
  function submitLeaveTypeForm() {
    const formData = new FormData($('#leaveTypeForm')[0]);
    const leaveTypeId = $('#id').val();
    
    // Fix all checkbox handling
    const checkboxes = [
      'is_proof_required',
      'is_comp_off_type',
      'is_accrual_enabled',
      'allow_carry_forward',
      'allow_encashment'
    ];
    
    checkboxes.forEach(function(field) {
      const toggleId = field.replace(/_/g, '-');
      const capitalizedId = toggleId.split('-').map((word, index) => 
        index === 0 ? word : word.charAt(0).toUpperCase() + word.slice(1)
      ).join('');
      
      const isChecked = $('#' + capitalizedId + 'Toggle').is(':checked');
      formData.delete(field);
      formData.append(field, isChecked ? '1' : '0');
    });
    
    // Add method for update
    if (leaveTypeId) {
      formData.append('_method', 'PUT');
    }
    
    $.ajax({
      data: formData,
      url: leaveTypeId ? pageData.urls.update.replace(':id', leaveTypeId) : pageData.urls.store,
      type: 'POST',
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          offCanvasForm.offcanvas('hide');
          
          Swal.fire({
            icon: 'success',
            title: leaveTypeId ? pageData.labels.updateSuccess : pageData.labels.createSuccess,
            text: response.data.message,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          
          dt_leaveType.draw();
        }
      },
      error: function (xhr) {
        console.log('Error Response:', xhr);
        let errorMessage = pageData.labels.error;
        
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          // Handle validation errors
          const errors = xhr.responseJSON.errors;
          Object.keys(errors).forEach(function(field) {
            const input = $(`#${field}`);
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
          });
          errorMessage = pageData.labels.validationRequired;
        } else if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        }
        
        Swal.fire({
          title: pageData.labels.error || 'Error!',
          text: errorMessage,
          icon: 'error',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  }

  // Function to reset form
  function resetForm() {
    $('#leaveTypeForm')[0].reset();
    $('#id').val('');
    
    // Reset all checkbox fields
    $('#isProofRequired').val('0');
    $('#isProofRequiredToggle').prop('checked', false);
    $('#isCompOffType').val('0');
    $('#isCompOffTypeToggle').prop('checked', false);
    $('#isAccrualEnabled').val('0');
    $('#isAccrualEnabledToggle').prop('checked', false);
    $('#allowCarryForward').val('0');
    $('#allowCarryForwardToggle').prop('checked', false);
    $('#allowEncashment').val('0');
    $('#allowEncashmentToggle').prop('checked', false);
    
    // Hide conditional sections
    $('#accrualSettingsSection').hide();
    $('#carryForwardSettingsSection').hide();
    $('#encashmentSettingsSection').hide();
    
    // Clear validation
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    $('#offcanvasLeaveTypeLabel').html(pageData.labels.addLeaveType);
    $('.data-submit').html(pageData.labels.create);
  }
  
  // clearing form data when offcanvas hidden
  offCanvasForm.on('hidden.bs.offcanvas', function () {
    resetForm();
  });

  // Delete function called from actions dropdown
  window.deleteLeaveType = function(id) {
    var dtrModal = $('.dtr-bs-modal.show');

    // hide responsive modal in small screen
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    // sweetalert for confirmation of delete
    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.wontRevert,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yesDeleteIt,
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // delete the data
        $.ajax({
          type: 'DELETE',
          url: pageData.urls.destroy.replace(':id', id),
          success: function (response) {
            // success sweetalert
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: response.data.message || pageData.labels.deleteSuccess,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });

            dt_leaveType.draw();
          },
          error: function (error) {
            console.log(error);
          }
        });
      }
    });
  };

  // Status toggle function called from actions dropdown
  window.toggleStatus = function(id) {
    $.ajax({
      url: pageData.urls.toggleStatus.replace(':id', id),
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.status === 'success') {
          // Show success notification
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success || 'Success!',
            text: response.data.message || 'Status updated successfully',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          
          dt_leaveType.draw();
        }
      },
      error: function (xhr) {
        console.log('Error:', xhr);
        
        let errorMessage = 'Failed to update status';
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error || 'Error!',
          text: errorMessage,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  };

  // View function called from actions dropdown
  window.viewLeaveType = function(id) {
    var dtrModal = $('.dtr-bs-modal.show');

    // hide responsive modal in small screen
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    // get data
    $.get(pageData.urls.show.replace(':id', id), function (response) {
      if (response.status === 'success') {
        var data = response.data;
        
        // Basic Information
        $('#viewName').text(data.name || '-');
        $('#viewCode').text(data.code || '-');
        $('#viewNotes').text(data.notes || '-');
        
        // Status badge
        if (data.status) {
          var statusClass = data.status_raw === 'active' ? 'bg-label-success' : 'bg-label-secondary';
          $('#viewStatus').html('<span class="badge ' + statusClass + '">' + data.status + '</span>');
        } else {
          $('#viewStatus').text('-');
        }
        
        // Proof Required badge
        if (data.is_proof_required) {
          $('#viewProofRequired').html('<span class="badge bg-label-warning">Yes</span>');
        } else {
          $('#viewProofRequired').html('<span class="badge bg-label-secondary">No</span>');
        }
        
        // Accrual Settings
        if (data.is_accrual_enabled) {
          $('#viewAccrualEnabled').html('<span class="badge bg-label-success">Enabled</span>');
          $('#viewAccrualFrequency').text(capitalizeFirst(data.accrual_frequency || '-'));
          $('#viewAccrualRate').text(data.accrual_rate ? data.accrual_rate + ' days' : '-');
          $('#viewMaxAccrualLimit').text(data.max_accrual_limit ? data.max_accrual_limit + ' days' : 'No limit');
        } else {
          $('#viewAccrualEnabled').html('<span class="badge bg-label-secondary">Disabled</span>');
          $('#viewAccrualFrequency').text('-');
          $('#viewAccrualRate').text('-');
          $('#viewMaxAccrualLimit').text('-');
        }
        
        // Carry Forward Settings
        if (data.allow_carry_forward) {
          $('#viewAllowCarryForward').html('<span class="badge bg-label-success">Allowed</span>');
          $('#viewMaxCarryForward').text(data.max_carry_forward ? data.max_carry_forward + ' days' : 'No limit');
          $('#viewCarryForwardExpiryMonths').text(data.carry_forward_expiry_months ? data.carry_forward_expiry_months + ' months' : 'No expiry');
        } else {
          $('#viewAllowCarryForward').html('<span class="badge bg-label-secondary">Not Allowed</span>');
          $('#viewMaxCarryForward').text('-');
          $('#viewCarryForwardExpiryMonths').text('-');
        }
        
        // Encashment Settings
        if (data.allow_encashment) {
          $('#viewAllowEncashment').html('<span class="badge bg-label-success">Allowed</span>');
          $('#viewMaxEncashmentDays').text(data.max_encashment_days ? data.max_encashment_days + ' days' : 'No limit');
        } else {
          $('#viewAllowEncashment').html('<span class="badge bg-label-secondary">Not Allowed</span>');
          $('#viewMaxEncashmentDays').text('-');
        }
        
        // Special Type
        if (data.is_comp_off_type) {
          $('#viewIsCompOffType').html('<span class="badge bg-label-info">Yes</span>');
        } else {
          $('#viewIsCompOffType').html('<span class="badge bg-label-secondary">No</span>');
        }
        
        // Audit Information
        $('#viewCreatedBy').text(data.created_by_name || '-');
        $('#viewCreatedAt').text(data.created_at_formatted || '-');
        $('#viewUpdatedBy').text(data.updated_by_name || '-');
        $('#viewUpdatedAt').text(data.updated_at_formatted || '-');
        
        // Show the offcanvas
        $('#viewLeaveTypeOffcanvas').offcanvas('show');
      }
    });
  };
  
  // Helper function to capitalize first letter
  function capitalizeFirst(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1);
  }
});
