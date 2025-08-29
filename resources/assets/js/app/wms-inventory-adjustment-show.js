/**
 * Page: WMS & Inventory Adjustment Details (Show)
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

  // Approve Adjustment
  $('#approve-adjustment').on('click', function() {
    const adjustmentId = $(this).data('id');
    
    Swal.fire({
      title: 'Are you sure?',
      text: "This will approve the adjustment and update inventory levels. This action cannot be undone!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, approve it!',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Create a form and submit it
        const form = $('<form>', {
          'method': 'POST',
          'action': pageData.urls.adjustmentApprove.replace('__ADJUSTMENT_ID__', adjustmentId)
        });
        
        form.append($('<input>', {
          'type': 'hidden',
          'name': '_token',
          'value': $('meta[name="csrf-token"]').attr('content')
        }));
        
        form.append($('<input>', {
          'type': 'hidden',
          'name': '_method',
          'value': 'PATCH'
        }));
        
        $('body').append(form);
        form.submit();
      }
    });
  });

  // Delete Adjustment
  $('.delete-adjustment').on('click', function() {
    const adjustmentId = $(this).data('id');
    
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this action!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Delete the adjustment
        $.ajax({
          url: pageData.urls.adjustmentDelete.replace('__ADJUSTMENT_ID__', adjustmentId),
          type: 'DELETE',
          success: function (response) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message || 'Adjustment has been deleted successfully.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(function() {
              // Redirect to adjustments index
              window.location.href = pageData.urls.adjustmentsIndex;
            });
          },
          error: function (error) {
            let errorMessage = error.responseJSON?.message || 'Could not delete adjustment.';
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

  // Print Adjustment
  $('#print-adjustment').on('click', function() {
    const adjustmentId = $(this).data('id');
    // For now, just show a message. Can be implemented later with a proper print view
    Swal.fire({
      icon: 'info',
      title: 'Print Feature',
      text: 'Print functionality will be implemented soon.',
      customClass: {
        confirmButton: 'btn btn-primary'
      }
    });
  });
});
