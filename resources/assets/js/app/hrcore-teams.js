'use strict';

$(function () {
  var dt_table = $('.datatables-teams');
  var offCanvasForm = $('#offcanvasAddOrUpdateTeam');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // DataTable initialization
  if (dt_table.length) {
    var dt_team = dt_table.DataTable({
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
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'code' },
        { data: 'team_head' },
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
          // ID
          targets: 1,
          className: 'text-start'
        },
        {
          // Name
          targets: 2,
          className: 'text-start',
          responsivePriority: 1
        },
        {
          // Code
          targets: 3,
          className: 'text-start'
        },
        {
          // Team Head
          targets: 4,
          className: 'text-start'
        },
        {
          // Status
          targets: 5,
          className: 'text-start'
        },
        {
          // Actions
          targets: 6,
          searchable: false,
          orderable: false
        }
      ],
      order: [[1, 'desc']],
      dom: '<"row"<"col-md-2"<"ms-n2"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      lengthMenu: [7, 10, 20, 50, 70, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Teams',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      buttons: pageData.permissions.create ? [
        {
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">' + pageData.labels.addTeam + '</span>',
          className: 'btn btn-primary mx-4',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddOrUpdateTeam'
          },
          action: function () {
            resetForm();
          }
        }
      ] : [],
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

  // Form submission handler
  $('#teamForm').on('submit', function(e) {
    e.preventDefault();
    
    // Basic validation
    const name = $('#name').val().trim();
    const code = $('#code').val().trim();
    
    if (!name) {
      showValidationError('name', 'The name is required');
      return;
    }
    
    if (!code) {
      showValidationError('code', 'The code is required');
      return;
    }
    
    // Clear any previous errors
    clearValidationErrors();
    
    // Submit form
    submitForm();
  });
  
  // Helper function to show validation error
  function showValidationError(field, message) {
    const fieldElement = $('#' + field);
    const errorDiv = '<div class="invalid-feedback d-block">' + message + '</div>';
    
    fieldElement.addClass('is-invalid');
    fieldElement.parent().find('.invalid-feedback').remove();
    fieldElement.parent().append(errorDiv);
  }
  
  // Helper function to clear validation errors
  function clearValidationErrors() {
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
  }
  
  // Code validation on blur
  $('#code').on('blur', function() {
    const code = $(this).val().trim();
    const id = $('#id').val();
    
    if (code) {
      $.get(pageData.urls.checkCode, { code: code, id: id }, function(response) {
        if (!response.valid) {
          showValidationError('code', 'The code is already taken');
        } else {
          $('#code').removeClass('is-invalid');
          $('#code').parent().find('.invalid-feedback').remove();
        }
      });
    }
  });

  // Form submission
  function submitForm() {
    const formData = new FormData($('#teamForm')[0]);
    const id = $('#id').val();
    

    const url = id ? pageData.urls.update.replace(':id', id) : pageData.urls.store;
    const method = id ? 'PUT' : 'POST';
    
    // Convert FormData to regular object for PUT requests
    let data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });

    $.ajax({
      url: url,
      type: method,
      data: data,
      success: function (response) {
        if (response.status === 'success') {
          offCanvasForm.offcanvas('hide');
          
          Swal.fire({
            icon: 'success',
            title: id ? pageData.labels.updateSuccess : pageData.labels.createSuccess,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });

          dt_team.draw();
        }
      },
      error: function (xhr) {
        let message = pageData.labels.error;
        if (xhr.responseJSON && xhr.responseJSON.data) {
          message = xhr.responseJSON.data;
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: message,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  }

  // Reset form
  function resetForm() {
    $('#id').val('');
    $('#teamForm')[0].reset();
    $('#offcanvasTeamLabel').html(pageData.labels.createTeam);
    clearValidationErrors();
  }

  // Edit team
  window.editTeam = function(id) {
    $('#offcanvasTeamLabel').html(pageData.labels.editTeam);
    
    $.get(pageData.urls.show.replace(':id', id), function (response) {
      if (response.status === 'success') {
        const team = response.data;
        $('#id').val(team.id);
        $('#name').val(team.name);
        $('#code').val(team.code);
        $('#team_head_id').val(team.team_head_id);
        $('#notes').val(team.notes);
        
        offCanvasForm.offcanvas('show');
      }
    });
  };

  // Delete team
  window.deleteTeam = function(id) {
    Swal.fire({
      title: pageData.labels.confirmDelete,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.deleteTeam,
      cancelButtonText: pageData.labels.cancel,
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          type: 'DELETE',
          url: pageData.urls.destroy.replace(':id', id),
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.deleteSuccess,
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
              
              dt_team.draw();
            }
          },
          error: function (xhr) {
            let message = pageData.labels.error;
            if (xhr.responseJSON && xhr.responseJSON.data) {
              message = xhr.responseJSON.data;
            }
            
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: message,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        });
      }
    });
  };

  // Toggle status
  window.toggleStatus = function(id) {
    $.ajax({
      url: pageData.urls.toggleStatus.replace(':id', id),
      type: 'POST',
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.statusUpdated,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          
          dt_team.draw();
        }
      },
      error: function (xhr) {
        let message = pageData.labels.error;
        if (xhr.responseJSON && xhr.responseJSON.data) {
          message = xhr.responseJSON.data;
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: message,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  };

  // Clear form on offcanvas close
  offCanvasForm.on('hidden.bs.offcanvas', function () {
    resetForm();
  });
});