/**
 * Page: WMS & Inventory Units
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
  let dt_units_table = $('.datatables-units');
  
  if (dt_units_table.length) {
    const dt_units = dt_units_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.unitsData
      },
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'code' },
        { data: 'description' },
        { data: 'products_count' },
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

  // Handle form submission for adding new unit
  $('#addUnitForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(form[0]);
    
    $.ajax({
      url: pageData.urls.unitsStore,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#offcanvasAddUnit').offcanvas('hide');
        form[0].reset();
        
        // Refresh the DataTable
        $('.datatables-units').DataTable().ajax.reload();
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message || 'Unit has been added successfully.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      error: function(error) {
        let errorMessage = error.responseJSON?.message || 'Could not add unit.';
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
    const code = $(this).data('code');
    const description = $(this).data('description');
    
    $('#edit_id').val(id);
    $('#edit_name').val(name);
    $('#edit_code').val(code);
    $('#edit_description').val(description);
  });

  // Handle form submission for updating unit
  $('#editUnitForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(form[0]);
    const id = $('#edit_id').val();
    
    // Add the _method field for Laravel method spoofing
    formData.append('_method', 'PUT');
    
    $.ajax({
      url: pageData.urls.unitsUpdate.replace('__UNIT_ID__', id),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#offcanvasEditUnit').offcanvas('hide');
        
        // Refresh the DataTable
        $('.datatables-units').DataTable().ajax.reload();
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message || 'Unit has been updated successfully.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      error: function(error) {
        let errorMessage = error.responseJSON?.message || 'Could not update unit.';
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
        // Delete the unit
        $.ajax({
          url: pageData.urls.unitsDelete.replace('__UNIT_ID__', id),
          type: 'POST',
          data: {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function(response) {
            $('.datatables-units').DataTable().ajax.reload();
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message || 'Unit has been deleted.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function(error) {
            let errorMessage = error.responseJSON?.message || 'Could not delete unit.';
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
