/**
 * Page: WMS & Inventory Categories
 * -----------------------------------------------------------------------------
 */

// Global functions for DataTable actions
window.editCategory = function(id, name, description, parentId, status) {
  // Set form values
  $('#edit_id').val(id);
  $('#edit_name').val(name);
  $('#edit_description').val(description);
  $('#edit_parent_id').val(parentId || '');
  $('#edit_status').val(status);
  
  // Show the offcanvas
  const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasEditCategory'));
  offcanvas.show();
};

window.deleteCategory = function(id) {
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
      // Delete the category
      $.ajax({
        url: pageData.urls.categoriesDelete.replace('__CATEGORY_ID__', id),
        type: 'POST',
        data: {
          _method: 'DELETE',
          _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          $('.datatables-categories').DataTable().ajax.reload();
          Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: response.message || 'Category has been deleted.',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        },
        error: function(error) {
          let errorMessage = error.responseJSON?.message || 'Could not delete category.';
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
};

$(function () {
  'use strict';

  // Add CSRF token to all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // DataTable initialization
  let dt_categories_table = $('.datatables-categories');
  
  if (dt_categories_table.length) {
    const dt_categories = dt_categories_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.categoriesData
      },
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { data: 'parent_name' },
        { data: 'products_count' },
        { data: 'status' },
        { data: 'actions', orderable: false, searchable: false }
      ],
      columnDefs: [
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span class="badge bg-label-info">${data || 0}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            const statusColors = {
              'active': 'success',
              'inactive': 'secondary'
            };
            const statusColor = statusColors[full.status] || 'primary';
            return `<span class="badge bg-label-${statusColor}">${full.status.toUpperCase()}</span>`;
          }
        }
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

  // Handle form submission for adding new category
  $('#addCategoryForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(form[0]);
    
    $.ajax({
      url: pageData.urls.categoriesStore,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#offcanvasAddCategory').offcanvas('hide');
        form[0].reset();
        
        // Refresh the DataTable
        $('.datatables-categories').DataTable().ajax.reload();
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message || 'Category has been added successfully.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      error: function(error) {
        let errorMessage = error.responseJSON?.message || 'Could not add category.';
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
    const parent = $(this).data('parent');
    const status = $(this).data('status');
    
    $('#edit_id').val(id);
    $('#edit_name').val(name);
    $('#edit_description').val(description);
    $('#edit_parent_id').val(parent);
    $('#edit_status').val(status);
  });

  // Handle form submission for updating category
  $('#editCategoryForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(form[0]);
    const id = $('#edit_id').val();
    
    // Add the _method field for Laravel method spoofing
    formData.append('_method', 'PUT');
    
    $.ajax({
      url: pageData.urls.categoriesUpdate.replace('__CATEGORY_ID__', id),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#offcanvasEditCategory').offcanvas('hide');
        
        // Refresh the DataTable
        $('.datatables-categories').DataTable().ajax.reload();
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message || 'Category has been updated successfully.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      error: function(error) {
        let errorMessage = error.responseJSON?.message || 'Could not update category.';
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
        // Delete the category
        $.ajax({
          url: pageData.urls.categoriesDelete.replace('__CATEGORY_ID__', id),
          type: 'POST',
          data: {
            _method: 'DELETE',
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function(response) {
            $('.datatables-categories').DataTable().ajax.reload();
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message || 'Category has been deleted.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function(error) {
            let errorMessage = error.responseJSON?.message || 'Could not delete category.';
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