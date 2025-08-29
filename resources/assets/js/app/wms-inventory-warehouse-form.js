/**
 * Page: WMS & Inventory Warehouse Form (Create/Edit)
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

  // Initialize Select2 for any dropdown elements
  $('.select2').select2();

  // Determine if we're in create or edit mode
  const isEditMode = pageData.hasOwnProperty('warehouse');
  
  // Handle form submission
  $('#warehouseForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const formData = new FormData(form[0]);
    
    // Set the correct URL and method based on create/edit mode
    let url = isEditMode ? pageData.urls.warehousesUpdate : pageData.urls.warehousesStore;
    let method = isEditMode ? 'POST' : 'POST'; // Still POST for both due to FormData, method is in the form
    
    $.ajax({
      url: url,
      type: method,
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message || (isEditMode ? 'Warehouse has been updated successfully.' : 'Warehouse has been created successfully.'),
          customClass: {
            confirmButton: 'btn btn-success'
          }
        }).then(function() {
          window.location.href = pageData.urls.warehousesIndex;
        });
      },
      error: function(error) {
        let errorMessage = error.responseJSON?.message || (isEditMode ? 'Could not update warehouse.' : 'Could not create warehouse.');
        if (error.responseJSON?.errors) {
          // Clear previous errors
          $('.is-invalid').removeClass('is-invalid');
          $('.invalid-feedback').remove();
          
          // Show validation errors
          const errors = error.responseJSON.errors;
          Object.keys(errors).forEach(function(field) {
            const input = $(`[name="${field}"]`);
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

  // Back to list button
  $('#backToListBtn').on('click', function(e) {
    e.preventDefault();
    window.location.href = pageData.urls.warehousesIndex;
  });

  // Zone management functions
  let zoneIndex = $('#zones-table tbody tr').length;

  // Add new zone row
  $('#add-zone-row').on('click', function() {
    const newRow = `
      <tr class="zone-row">
        <td>
          <input type="text" class="form-control" name="zones[${zoneIndex}][name]" required>
        </td>
        <td>
          <input type="text" class="form-control" name="zones[${zoneIndex}][code]" required>
        </td>
        <td>
          <input type="text" class="form-control" name="zones[${zoneIndex}][description]">
        </td>
        <td>
          <button type="button" class="btn btn-danger btn-sm remove-zone-row">
            <i class="bx bx-trash"></i>
          </button>
        </td>
      </tr>
    `;
    
    $('#zones-table tbody').append(newRow);
    zoneIndex++;
    updateRemoveButtons();
  });

  // Remove zone row
  $(document).on('click', '.remove-zone-row', function() {
    $(this).closest('tr').remove();
    updateRemoveButtons();
    reindexZones();
  });

  // Update remove buttons state
  function updateRemoveButtons() {
    const rowCount = $('#zones-table tbody tr').length;
    $('.remove-zone-row').prop('disabled', rowCount <= 1);
  }

  // Reindex zones after removal
  function reindexZones() {
    $('#zones-table tbody tr').each(function(index) {
      $(this).find('input[name*="[name]"]').attr('name', `zones[${index}][name]`);
      $(this).find('input[name*="[code]"]').attr('name', `zones[${index}][code]`);
      $(this).find('input[name*="[description]"]').attr('name', `zones[${index}][description]`);
      $(this).find('input[name*="[id]"]').attr('name', `zones[${index}][id]`);
    });
    zoneIndex = $('#zones-table tbody tr').length;
  }

  // Initialize remove buttons state
  updateRemoveButtons();
});
