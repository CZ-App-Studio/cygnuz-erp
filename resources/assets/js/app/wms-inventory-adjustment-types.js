/**
 * Page: WMS & Inventory Adjustment Types
 * -----------------------------------------------------------------------------
 */

$(function () {
  'use strict';

  // Add CSRF token to all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // DataTable initialization
  let dt_adjustment_types_table = $('.datatables-adjustment-types');
  
  if (dt_adjustment_types_table.length) {
    const dt_adjustment_types = dt_adjustment_types_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.adjustmentTypesData
      },
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { 
          data: 'effect_type',
          render: function (data, type, full, meta) {
            const effectTypeColors = {
              'increase': 'success',
              'decrease': 'danger'
            };
            const effectTypeColor = effectTypeColors[data] || 'primary';
            
            return `<span class="badge bg-label-${effectTypeColor}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
          }
        },
        { data: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100]
    });

    // Filter form control to default size
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }

  // Handle form submission for adding new adjustment type
  $('#addAdjustmentTypeForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(form[0]);
    
    $.ajax({
      url: pageData.urls.adjustmentTypesStore,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#offcanvasAddAdjustmentType').offcanvas('hide');
        form[0].reset();
        
        // Refresh the DataTable
        $('.datatables-adjustment-types').DataTable().ajax.reload();
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message || 'Adjustment type has been added successfully.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      error: function(error) {
        let errorMessage = error.responseJSON?.message || 'Could not add adjustment type.';
        if (error.responseJSON?.errors) {
          // Clear previous errors
          $('.is-invalid').removeClass('is-invalid');
          $('.invalid-feedback').remove();
          
          // Show validation errors
          const errors = error.responseJSON.errors;
          Object.keys(errors).forEach(function(field) {
            const input = $(`#${field}`);
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
          });
          
          errorMessage = 'Please check the form for errors.';
        }

        Swal.fire({
          title: 'Error!',
          text: errorMessage,
          icon: 'error',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      }
    });
  });

  // Populate edit form when edit button is clicked
  $(document).on('click', '.edit-record', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const description = $(this).data('description');
    const effectType = $(this).data('effect-type');
    
    $('#edit_id').val(id);
    $('#edit_name').val(name);
    $('#edit_description').val(description);
    $('#edit_operation_type').val(effectType);
  });

  // Handle form submission for updating adjustment type
  $('#editAdjustmentTypeForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(form[0]);
    const id = $('#edit_id').val();
    
    // Add the _method field for Laravel method spoofing
    formData.append('_method', 'PUT');
    
    $.ajax({
      url: pageData.urls.adjustmentTypesUpdate.replace('__TYPE_ID__', id),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#offcanvasEditAdjustmentType').offcanvas('hide');
        
        // Refresh the DataTable
        $('.datatables-adjustment-types').DataTable().ajax.reload();
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message || 'Adjustment type has been updated successfully.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      error: function(error) {
        let errorMessage = error.responseJSON?.message || 'Could not update adjustment type.';
        if (error.responseJSON?.errors) {
          // Clear previous errors
          $('.is-invalid').removeClass('is-invalid');
          $('.invalid-feedback').remove();
          
          // Show validation errors
          const errors = error.responseJSON.errors;
          Object.keys(errors).forEach(function(field) {
            const input = $(`#edit_${field}`);
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
          });
          
          errorMessage = 'Please check the form for errors.';
        }

        Swal.fire({
          title: 'Error!',
          text: errorMessage,
          icon: 'error',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      }
    });
  });

  // Delete Record
  $(document).on('click', '.delete-record', function() {
    const id = $(this).data('id');

    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function(result) {
      if (result.value) {
        // Delete the adjustment type
        $.ajax({
          url: pageData.urls.adjustmentTypesDelete.replace('__TYPE_ID__', id),
          type: 'POST',
          data: {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function(response) {
            $('.datatables-adjustment-types').DataTable().ajax.reload();
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message || 'Adjustment type has been deleted.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function(error) {
            let errorMessage = error.responseJSON?.message || 'Could not delete adjustment type.';
            if (error.responseJSON?.errors) {
              errorMessage = Object.values(error.responseJSON.errors).flat().join('<br>');
            }

            Swal.fire({
              title: 'Error!',
              html: errorMessage,
              icon: 'error',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });
});
