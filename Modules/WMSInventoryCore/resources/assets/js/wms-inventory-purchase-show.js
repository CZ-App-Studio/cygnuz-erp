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

function updatePaymentStatus() {
  // Get current payment status from the page
  const currentStatusElement = document.querySelector('.badge');
  let currentStatus = 'unpaid';
  if (currentStatusElement) {
    const statusText = currentStatusElement.textContent.trim().toLowerCase();
    if (statusText.includes('paid') && !statusText.includes('unpaid')) {
      currentStatus = statusText.includes('partially') ? 'partial' : 'paid';
    } else if (statusText.includes('unpaid')) {
      currentStatus = 'unpaid';
    }
  }

  Swal.fire({
    title: pageData.labels.updatePaymentStatus || 'Update Payment Status',
    html: `
      <form id="paymentStatusForm">
        <div class="row">
          <div class="col-md-12 mb-3">
            <label for="payment_status" class="form-label">Payment Status</label>
            <select class="form-select" id="payment_status" name="payment_status" required>
              <option value="unpaid" ${currentStatus === 'unpaid' ? 'selected' : ''}>Unpaid</option>
              <option value="partial" ${currentStatus === 'partial' ? 'selected' : ''}>Partially Paid</option>
              <option value="paid" ${currentStatus === 'paid' ? 'selected' : ''}>Paid</option>
            </select>
          </div>
          <div class="col-md-12 mb-3">
            <label for="paid_amount" class="form-label">Paid Amount</label>
            <input type="number" class="form-control" id="paid_amount" name="paid_amount" step="0.01" min="0" placeholder="0.00">
            <small class="text-muted">Leave empty to calculate automatically based on status</small>
          </div>
          <div class="col-md-12 mb-3">
            <label for="payment_date" class="form-label">Payment Date (Optional)</label>
            <input type="date" class="form-control" id="payment_date" name="payment_date">
          </div>
          <div class="col-md-12 mb-3">
            <label for="payment_notes" class="form-label">Payment Notes (Optional)</label>
            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2" placeholder="Additional payment information..."></textarea>
          </div>
        </div>
      </form>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: pageData.labels.updatePaymentStatus || 'Update Payment Status',
    cancelButtonText: pageData.labels.cancel || 'Cancel',
    customClass: {
      confirmButton: 'btn btn-primary me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false,
    preConfirm: () => {
      const paymentStatus = document.getElementById('payment_status').value;
      const paidAmount = document.getElementById('paid_amount').value;
      const paymentDate = document.getElementById('payment_date').value;
      const paymentNotes = document.getElementById('payment_notes').value;
      
      if (!paymentStatus) {
        Swal.showValidationMessage('Please select a payment status');
        return false;
      }
      
      return {
        payment_status: paymentStatus,
        paid_amount: paidAmount || null,
        payment_date: paymentDate || null,
        payment_notes: paymentNotes || null
      };
    }
  }).then(function (result) {
    if (result.value) {
      $.ajax({
        url: pageData.urls.updatePaymentStatus,
        type: 'POST',
        data: result.value,
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.paymentStatusUpdated || 'Payment Status Updated!',
              text: response.data.message || pageData.labels.paymentStatusUpdatedText || 'Payment status has been updated successfully.',
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
        error: function (xhr) {
          let errorMessage = pageData.labels.error;
          if (xhr.responseJSON && xhr.responseJSON.data) {
            errorMessage = xhr.responseJSON.data;
          } else if (xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = Object.values(xhr.responseJSON.errors).flat();
            errorMessage = errors.join('<br>');
          }
          
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            html: errorMessage,
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
window.updatePaymentStatus = updatePaymentStatus;