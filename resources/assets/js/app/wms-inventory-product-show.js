/**
 * Page: WMS & Inventory Product Show
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

  // Delete Product
  $(document).on('click', '.delete-product', function () {
    const id = $(this).data('id');

    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.confirmDeleteText,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.confirmDeleteButton,
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          url: pageData.urls.productsDelete.replace('__PRODUCT_ID__', id),
          type: 'DELETE',
          success: function () {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: pageData.labels.deletedText,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(function() {
              window.location.href = pageData.urls.productsIndex;
            });
          },
          error: function (error) {
            Swal.fire({
              title: pageData.labels.error,
              text: error.responseJSON.message || pageData.labels.couldNotDelete,
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
