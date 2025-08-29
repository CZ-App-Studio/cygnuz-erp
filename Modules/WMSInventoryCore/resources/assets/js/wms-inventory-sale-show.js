$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });
});

function approveSale() {
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

function rejectSale() {
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

function deleteSale() {
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
              window.location.href = pageData.urls.index || '/inventory/sales';
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

function fulfillSale() {
  Swal.fire({
    title: pageData.labels.confirmFulfill,
    text: pageData.labels.confirmFulfillText,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.confirmFulfill,
    customClass: {
      confirmButton: 'btn btn-primary me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      // Redirect to fulfillment page
      window.location.href = pageData.urls.fulfill;
    }
  });
}

function duplicateSale() {
  Swal.fire({
    title: pageData.labels.duplicateSale || 'Duplicate Sales Order',
    text: pageData.labels.duplicateConfirmText || 'Are you sure you want to duplicate this sales order?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.duplicate || 'Duplicate',
    cancelButtonText: pageData.labels.cancel || 'Cancel',
    customClass: {
      confirmButton: 'btn btn-info me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.duplicate,
        type: 'POST',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.duplicateSuccess || 'Success!',
              text: response.data.message || 'Sales order duplicated successfully',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              // Redirect to the view page of the new duplicated sale order
              if (response.data.sale && response.data.sale.id) {
                window.location.href = pageData.urls.show.replace(':id', response.data.sale.id);
              } else if (response.data.id) {
                window.location.href = pageData.urls.show.replace(':id', response.data.id);
              } else {
                window.location.reload();
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error || 'Error!',
              text: response.message || response.data || 'Failed to duplicate sales order',
              customClass: {
                confirmButton: 'btn btn-danger'
              }
            });
          }
        },
        error: function (xhr) {
          const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.data || 'Failed to duplicate sales order';
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error || 'Error!',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-danger'
            }
          });
        }
      });
    }
  });
}

function deliverSale() {
  Swal.fire({
    title: pageData.labels.confirmDeliver || 'Mark as Delivered',
    text: pageData.labels.deliverConfirmText || 'Are you sure you want to mark this sales order as delivered?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.deliver || 'Mark Delivered',
    cancelButtonText: pageData.labels.cancel || 'Cancel',
    customClass: {
      confirmButton: 'btn btn-success me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.deliver,
        type: 'POST',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deliveredSuccess || 'Delivered!',
              text: response.data.message || response.data || 'Sales order has been marked as delivered successfully',
              confirmButtonText: pageData.labels.ok || 'OK',
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error || 'Error!',
              text: response.message || response.data || 'Failed to mark sales order as delivered',
              customClass: {
                confirmButton: 'btn btn-danger'
              }
            });
          }
        },
        error: function (xhr) {
          const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.data || 'Failed to mark sales order as delivered';
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error || 'Error!',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-danger'
            }
          });
        }
      });
    }
  });
}

function shipSale() {
  Swal.fire({
    title: pageData.labels.confirmShip || 'Ship Order',
    text: pageData.labels.shipConfirmText || 'Are you sure you want to mark this sales order as shipped?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.ship || 'Ship Order',
    cancelButtonText: pageData.labels.cancel || 'Cancel',
    customClass: {
      confirmButton: 'btn btn-primary me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.ship || '/inventory/sales/' + pageData.saleId + '/ship',
        type: 'POST',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.shippedSuccess || 'Shipped!',
              text: response.data.message || response.data || 'Sales order has been marked as shipped successfully',
              confirmButtonText: pageData.labels.ok || 'OK',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error || 'Error!',
              text: response.message || response.data || 'Failed to mark sales order as shipped',
              customClass: {
                confirmButton: 'btn btn-danger'
              }
            });
          }
        },
        error: function (xhr) {
          const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.data || 'Failed to mark sales order as shipped';
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error || 'Error!',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-danger'
            }
          });
        }
      });
    }
  });
}

function fulfillAllSale() {
  Swal.fire({
    title: pageData.labels.confirmFulfillAll,
    text: pageData.labels.confirmFulfillAllText,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.confirmFulfillAll,
    customClass: {
      confirmButton: 'btn btn-success me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.fulfillAll,
        type: 'POST',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.fulfilled,
              text: pageData.labels.fulfilledText,
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


function printSale() {
  // Open print page in new window
  window.open(pageData.urls.print, '_blank');
}

function downloadSalePdf() {
  // Trigger PDF download
  window.location.href = pageData.urls.downloadPdf;
}

// Make functions globally available
window.approveSale = approveSale;
window.rejectSale = rejectSale;
window.deleteSale = deleteSale;
window.fulfillSale = fulfillSale;
window.fulfillAllSale = fulfillAllSale;
window.duplicateSale = duplicateSale;
window.printSale = printSale;
window.downloadSalePdf = downloadSalePdf;