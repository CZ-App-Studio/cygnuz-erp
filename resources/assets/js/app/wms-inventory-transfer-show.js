$(function () {
  
  // Setup CSRF token for AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Ship Transfer functionality
  $('#ship-transfer').on('click', function() {
    const transferId = $(this).data('id');
    
    Swal.fire({
      title: 'Ship Transfer?',
      text: 'Are you sure you want to mark this transfer as shipped? This will deduct stock from the source warehouse.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Ship it!',
      cancelButtonText: 'Cancel',
      confirmButtonClass: 'btn btn-primary',
      cancelButtonClass: 'btn btn-outline-secondary',
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Send AJAX request to ship the transfer
        const shipUrl = typeof pageData !== 'undefined' && pageData.urls && pageData.urls.shipTransfer 
          ? pageData.urls.shipTransfer 
          : `/wmsinventorycore/transfers/${transferId}/ship`;
          
        $.ajax({
          url: shipUrl,
          type: 'POST',
          success: function(response) {
            Swal.fire({
              title: 'Success!',
              text: 'Transfer has been shipped successfully.',
              icon: 'success',
              confirmButtonClass: 'btn btn-success',
              buttonsStyling: false
            }).then(() => {
              location.reload();
            });
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while shipping the transfer.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
              title: 'Error!',
              text: errorMessage,
              icon: 'error',
              confirmButtonClass: 'btn btn-danger',
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Receive Transfer functionality
  $('#receive-transfer').on('click', function() {
    const transferId = $(this).data('id');
    
    Swal.fire({
      title: 'Receive Transfer?',
      text: 'Are you sure you want to receive this transfer? This will add stock to the destination warehouse.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Receive it!',
      cancelButtonText: 'Cancel',
      confirmButtonClass: 'btn btn-success',
      cancelButtonClass: 'btn btn-outline-secondary',
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Send AJAX request to receive the transfer
        const receiveUrl = typeof pageData !== 'undefined' && pageData.urls && pageData.urls.receiveTransfer 
          ? pageData.urls.receiveTransfer 
          : `/wmsinventorycore/transfers/${transferId}/receive`;
          
        $.ajax({
          url: receiveUrl,
          type: 'POST',
          success: function(response) {
            Swal.fire({
              title: 'Success!',
              text: 'Transfer has been received successfully.',
              icon: 'success',
              confirmButtonClass: 'btn btn-success',
              buttonsStyling: false
            }).then(() => {
              location.reload();
            });
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while receiving the transfer.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
              title: 'Error!',
              text: errorMessage,
              icon: 'error',
              confirmButtonClass: 'btn btn-danger',
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Cancel Transfer functionality
  $('#cancel-transfer').on('click', function() {
    const transferId = $(this).data('id');
    
    Swal.fire({
      title: 'Cancel Transfer?',
      text: 'Are you sure you want to cancel this transfer? This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Cancel it!',
      cancelButtonText: 'Close',
      confirmButtonClass: 'btn btn-danger',
      cancelButtonClass: 'btn btn-outline-secondary',
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Send AJAX request to cancel the transfer
        const cancelUrl = typeof pageData !== 'undefined' && pageData.urls && pageData.urls.cancelTransfer 
          ? pageData.urls.cancelTransfer 
          : `/wmsinventorycore/transfers/${transferId}/cancel`;
          
        $.ajax({
          url: cancelUrl,
          type: 'POST',
          data: {
            cancellation_reason: 'Transfer cancelled from UI'
          },
          success: function(response) {
            Swal.fire({
              title: 'Cancelled!',
              text: 'Transfer has been cancelled.',
              icon: 'success',
              confirmButtonClass: 'btn btn-success',
              buttonsStyling: false
            }).then(() => {
              location.reload();
            });
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while cancelling the transfer.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
              title: 'Error!',
              text: errorMessage,
              icon: 'error',
              confirmButtonClass: 'btn btn-danger',
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Print Transfer functionality
  $('#print-transfer').on('click', function() {
    const transferId = $(this).data('id');
    window.open(`/inventory/transfers/${transferId}/print`, '_blank');
  });

});
