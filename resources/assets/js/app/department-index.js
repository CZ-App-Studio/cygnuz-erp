'use strict';

$(function () {
  var dt_table = $('.datatables-departments');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // department datatable
  if (dt_table.length) {
    var dt_department = dt_table.DataTable({
      initComplete: function () {
        $('#loader').attr('style', 'display:none');
        $('.card-datatable').show();
      },
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
        { data: 'parent_id' },
        { data: 'notes' },
        { data: 'status' },
        { data: 'action' }
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
          // Name
          targets: 2,
          className: 'text-start',
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['name'];
            return `<span class="department-name">${$name}</span>`;
          }
        },
        {
          // Code
          targets: 3,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $code = full['code'];
            return `<span class="department-code">${$code}</span>`;
          }
        },
        {
          // Parent Department
          targets: 4,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $parentName = full.parent_id ? full.parent_id : 'No Parent';
            return `<span class="department-parent">${$parentName}</span>`;
          }
        },
        {
          // Notes
          targets: 5,
          className: 'text-start',
          render: function (data, type, full, meta) {
            var $notes = full['notes'] || '';
            return `<span class="department-notes">${$notes}</span>`;
          }
        },

        {
          // Status
          targets: 6,
          className: 'text-start'
        },
        {
          // Actions
          targets: 7,
          searchable: false,
          orderable: false
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
      lengthMenu: [7, 10, 20, 50, 70, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Department',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      // Buttons
      buttons: pageData.permissions.create ? [
        {
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">' + pageData.labels.addDepartment + '</span>',
          className: 'btn btn-primary mx-4 add-new-department',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddDepartment'
          }
        }
      ] : [],
      // For Responsive Popup
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
              return col.title !== ''
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

  var offCanvasForm = $('#offcanvasAddDepartment');
  
  // changing the title
  $('.add-new-department').on('click', function () {
    $('#departmentId').val('');
    $('#offcanvasAddDepartmentLabel').html(pageData.labels.addDepartment);
    loadDepartmentList();
    $('#parent_department').val(null).trigger('change');
  });


  // Form submission using standard jQuery validation
  $('#addNewDepartmentForm').on('submit', function (e) {
    e.preventDefault();
    
    // Basic validation
    var name = $('#name').val().trim();
    var code = $('#code').val().trim();
    
    if (!name) {
      showErrorMessage(pageData.labels.nameRequired);
      return;
    }
    
    if (!code) {
      showErrorMessage(pageData.labels.codeRequired);
      return;
    }
    
    if (code.length < 3 || code.length > 10) {
      showErrorMessage(pageData.labels.codeLength);
      return;
    }
    
    addOrUpdateDepartment();
  });

  // Clearing form data when offcanvas is hidden
  offCanvasForm.on('hidden.bs.offcanvas', function () {
    $('#addNewDepartmentForm')[0].reset();
    $('#departmentId').val('');
    $('#parent_department').val(null).trigger('change');
  });



  function loadDepartmentList() {
    $.ajax({
      url: pageData.urls.parentList,
      type: 'GET',
      success: function (response) {
        if (response.status === 'success') {
          let parentDropdown = $('#parent_department');
          parentDropdown.empty();
          parentDropdown.append('<option value="">' + pageData.labels.selectParent + '</option>');
          
          // Handle the data array from Success response
          let departments = response.data || [];
          departments.forEach(function (department) {
            parentDropdown.append(`<option value="${department.id}">${department.name}</option>`);
          });
        } else {
          showErrorMessage(response.data || pageData.labels.error);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error fetching parent departments:', error);
        showErrorMessage(pageData.labels.error);
      }
    });
  }

  function addOrUpdateDepartment() {
    $.ajax({
      data: $('#addNewDepartmentForm').serialize(),
      url: pageData.urls.store,
      type: 'POST',
      success: function (response) {
        if (response.status === 'success') {
          offCanvasForm.offcanvas('hide');
          
          var isEdit = $('#departmentId').val() !== '';
          var message = isEdit ? pageData.labels.updateSuccess : pageData.labels.createSuccess;
          
          showSuccessMessage(message);
          dt_department.draw();
        } else {
          showErrorMessage(response.data || pageData.labels.error);
        }
      },
      error: function (xhr) {
        var message = pageData.labels.error;
        if (xhr.responseJSON && xhr.responseJSON.errors) {
          // Handle validation errors
          var errors = xhr.responseJSON.errors;
          if (errors.code) {
            message = pageData.labels.codeUnique;
          }
        }
        showErrorMessage(message);
      }
    });
  }

  function deleteDepartmentAjax(departmentId) {
    $.ajax({
      type: 'DELETE',
      url: pageData.urls.delete.replace(':id', departmentId),
      success: function (response) {
        if (response.status === 'success') {
          showSuccessMessage(pageData.labels.deleteSuccess);
          dt_department.draw();
        } else {
          showErrorMessage(response.data || pageData.labels.error);
        }
      },
      error: function (xhr) {
        var message = pageData.labels.error;
        if (xhr.responseJSON && xhr.responseJSON.data) {
          message = xhr.responseJSON.data;
        }
        showErrorMessage(message);
      }
    });
  }

  function setDepartmentData(departmentId) {
    $.get(pageData.urls.edit.replace(':id', departmentId), function (response) {
      if (response.status === 'success') {
        let data = response.data;
        $('#departmentId').val(data.id);
        $('#name').val(data.name);
        $('#code').val(data.code);
        $('#notes').val(data.notes);
        if (data.parent_id) {
          $('#parent_department').val(data.parent_id).trigger('change');
        } else {
          $('#parent_department').val('');
        }
      } else {
        showErrorMessage(response.data || pageData.labels.error);
      }
    }).fail(function() {
      showErrorMessage(pageData.labels.error);
    });
  }

  // Helper functions for consistent messaging
  function showSuccessMessage(message) {
    Swal.fire({
      icon: 'success',
      title: pageData.labels.success,
      text: message,
      customClass: {
        confirmButton: 'btn btn-success'
      }
    });
  }

  function showErrorMessage(message) {
    Swal.fire({
      icon: 'error',
      title: pageData.labels.error,
      text: message,
      customClass: {
        confirmButton: 'btn btn-success'
      }
    });
  }

  // Global functions for actions
  window.editDepartment = function(id) {
    var dtrModal = $('.dtr-bs-modal.show');
    
    // hide responsive modal in small screen
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    loadDepartmentList();

    // changing the title of offcanvas
    $('#offcanvasAddDepartmentLabel').html(pageData.labels.editDepartment);

    // set department data
    setDepartmentData(id);
    
    // Show the offcanvas
    offCanvasForm.offcanvas('show');
  };

  window.deleteDepartment = function(id) {
    var dtrModal = $('.dtr-bs-modal.show');

    // Hide responsive modal on small screens
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    // SweetAlert for delete confirmation
    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.deleteWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yesDelete,
      cancelButtonText: pageData.labels.cancel,
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        deleteDepartmentAjax(id);
      }
    });
  };

  window.toggleDepartmentStatus = function(id) {
    $.ajax({
      url: pageData.urls.changeStatus.replace(':id', id),
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.status === 'success') {
          dt_department.draw();
          showSuccessMessage(pageData.labels.statusUpdated);
        } else {
          showErrorMessage(response.data || pageData.labels.error);
        }
      },
      error: function (response) {
        showErrorMessage(pageData.labels.error);
      }
    });
  };
});
