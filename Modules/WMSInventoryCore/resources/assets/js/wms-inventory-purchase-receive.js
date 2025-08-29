$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize date picker
  $('#received_date').flatpickr({
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'F j, Y',
    defaultDate: new Date()
  });

  // Handle receive checkbox changes
  $('.receive-item').on('change', function() {
    const row = $(this).closest('tr');
    const isChecked = $(this).is(':checked');
    const quantityInput = row.find('.quantity-received');
    const statusRadios = row.find('input[type="radio"]');
    const notesInput = row.find('.rejection-notes');

    if (isChecked) {
      quantityInput.prop('disabled', false);
      statusRadios.prop('disabled', false);
      
      // Set default quantity if empty
      if (!quantityInput.val()) {
        const maxQuantity = parseFloat(quantityInput.attr('max'));
        quantityInput.val(maxQuantity);
      }
      
      // Set default status to accepted
      row.find('input[value="accepted"]').prop('checked', true);
    } else {
      quantityInput.prop('disabled', true).val('');
      statusRadios.prop('disabled', true).prop('checked', false);
      notesInput.prop('disabled', true).val('');
    }
    
    updateFormButtons();
  });

  // Handle status radio changes
  $('input[name$="[status]"]').on('change', function() {
    const row = $(this).closest('tr');
    const notesInput = row.find('.rejection-notes');
    const status = $(this).val();

    if (status === 'rejected' || status === 'damaged') {
      notesInput.prop('disabled', false).prop('required', true);
      notesInput.closest('.form-group').show();
    } else {
      notesInput.prop('disabled', true).prop('required', false);
      notesInput.closest('.form-group').hide();
      notesInput.val('');
    }
  });

  // Handle quantity changes
  $('.quantity-received').on('input', function() {
    const max = parseFloat($(this).attr('max'));
    const value = parseFloat($(this).val());
    
    if (value > max) {
      $(this).val(max);
    } else if (value < 0) {
      $(this).val(0);
    }
  });

  // Select/Deselect all functionality
  $('#select-all').on('change', function() {
    const isChecked = $(this).is(':checked');
    $('.receive-item').prop('checked', isChecked).trigger('change');
  });

  // Form submission
  $('#receiveForm').on('submit', function(e) {
    e.preventDefault();

    // Validate at least one item is selected
    if ($('.receive-item:checked').length === 0) {
      Swal.fire({
        icon: 'warning',
        title: pageData.labels.error,
        text: pageData.labels.selectAtLeastOne || 'Please select at least one item to receive.',
        customClass: {
          confirmButton: 'btn btn-warning'
        }
      });
      return;
    }

    // Show confirmation
    Swal.fire({
      title: pageData.labels.confirmReceive,
      text: pageData.labels.confirmReceiveText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: pageData.labels.confirmReceive,
      customClass: {
        confirmButton: 'btn btn-success me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        processReceiving();
      }
    });
  });

  function updateFormButtons() {
    const hasSelectedItems = $('.receive-item:checked').length > 0;
    $('#receive-btn').prop('disabled', !hasSelectedItems);
  }

  function processReceiving() {
    const formData = new FormData(document.getElementById('receiveForm'));

    $.ajax({
      url: pageData.urls.processReceiving,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: pageData.labels.itemsReceived,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            window.location.href = pageData.urls.showPurchase || '/inventory/purchases';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.data || pageData.labels.validationError,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      error: function(xhr) {
        const errors = xhr.responseJSON?.errors || {};
        
        // Clear previous errors
        $('.text-danger').remove();
        $('.is-invalid').removeClass('is-invalid');

        // Display validation errors
        Object.keys(errors).forEach(function(field) {
          const input = $(`[name="${field}"]`);
          input.addClass('is-invalid');
          input.after(`<div class="text-danger">${errors[field][0]}</div>`);
        });

        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.validationError,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  }

  // Initial state
  updateFormButtons();
});