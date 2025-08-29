$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });
});

function approvePurchase() {
  Swal.fire({
    title: pageData.labels.confirmApprove,
    text: pageData.labels.confirmApproveText,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.confirmApprove,
    customClass: {
      confirmButton: 'btn btn-success me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.approve,
        type: 'POST',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.approved,
              text: pageData.labels.approvedText,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: response.data || pageData.labels.error,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: pageData.labels.error,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      });
    }
  });
}

function rejectPurchase() {
  Swal.fire({
    title: pageData.labels.confirmReject,
    text: pageData.labels.confirmRejectText,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: pageData.labels.confirmReject,
    customClass: {
      confirmButton: 'btn btn-danger me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.reject,
        type: 'POST',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.rejected,
              text: pageData.labels.rejectedText,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: response.data || pageData.labels.error,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: pageData.labels.error,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      });
    }
  });
}

function deletePurchase() {
  Swal.fire({
    title: pageData.labels.confirmDelete,
    text: pageData.labels.confirmDeleteText,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: pageData.labels.confirmDelete,
    customClass: {
      confirmButton: 'btn btn-danger me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.delete,
        type: 'DELETE',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: pageData.labels.deletedText,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(() => {
              window.location.href = pageData.urls.index || '/inventory/purchases';
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: response.data || pageData.labels.error,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: pageData.labels.error,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      });
    }
  });
}

// Make functions globally available
window.approvePurchase = approvePurchase;
window.rejectPurchase = rejectPurchase;
window.deletePurchase = deletePurchase;