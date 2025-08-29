$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize Select2 for payment terms
  $('#payment_terms').select2({
    placeholder: 'Select Payment Terms',
    allowClear: true,
    width: '100%'
  });

  // Form validation
  $('#vendorForm').on('submit', function(e) {
    // Client-side validation
    const form = $(this);
    let isValid = true;
    
    // Check required fields
    const requiredFields = form.find('[required]');
    requiredFields.each(function() {
      const field = $(this);
      if (!field.val() || field.val().trim() === '') {
        field.addClass('is-invalid');
        isValid = false;
      } else {
        field.removeClass('is-invalid');
      }
    });
    
    // Validate email format if provided
    const emailField = $('#email');
    if (emailField.val()) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailField.val())) {
        emailField.addClass('is-invalid');
        isValid = false;
      }
    }
    
    // Validate website URL if provided
    const websiteField = $('#website');
    if (websiteField.val()) {
      try {
        new URL(websiteField.val());
        websiteField.removeClass('is-invalid');
      } catch (e) {
        websiteField.addClass('is-invalid');
        isValid = false;
      }
    }
    
    // Validate minimum order value if provided
    const minOrderField = $('#minimum_order_value');
    if (minOrderField.val() && parseFloat(minOrderField.val()) < 0) {
      minOrderField.addClass('is-invalid');
      isValid = false;
    }
    
    // Validate lead time if provided
    const leadTimeField = $('#lead_time_days');
    if (leadTimeField.val() && parseInt(leadTimeField.val()) < 0) {
      leadTimeField.addClass('is-invalid');
      isValid = false;
    }
    
    if (!isValid) {
      e.preventDefault();
      
      // Show error message
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please fill in all required fields correctly.',
        customClass: {
          confirmButton: 'btn btn-success'
        }
      });
      
      // Scroll to first error field
      const firstError = form.find('.is-invalid').first();
      if (firstError.length) {
        $('html, body').animate({
          scrollTop: firstError.offset().top - 100
        }, 500);
      }
      
      return false;
    }
    
    // Show loading state on submit button
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
    
    // Form will submit normally
  });
  
  // Remove validation error on field change
  $('#vendorForm').find('input, select, textarea').on('change keyup', function() {
    $(this).removeClass('is-invalid');
  });
  
  // Auto-format phone number (optional enhancement)
  $('#phone_number').on('input', function() {
    let value = $(this).val().replace(/\D/g, '');
    if (value.length > 10) {
      value = value.substring(0, 15); // Limit to reasonable phone number length
    }
    $(this).val(value);
  });
  
  // Auto-format postal code (optional enhancement)
  $('#postal_code').on('input', function() {
    let value = $(this).val().replace(/[^a-zA-Z0-9\s-]/g, '');
    $(this).val(value.toUpperCase());
  });
  
  // Handle cancel button with confirmation if form has changes
  let formChanged = false;
  
  $('#vendorForm').find('input, select, textarea').on('change', function() {
    formChanged = true;
  });
  
  $('a[href*="vendors"]').on('click', function(e) {
    if (formChanged && $(this).hasClass('btn-label-secondary')) {
      e.preventDefault();
      const href = $(this).attr('href');
      
      Swal.fire({
        title: 'Unsaved Changes',
        text: 'You have unsaved changes. Are you sure you want to leave?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, leave',
        cancelButtonText: 'Stay',
        customClass: {
          confirmButton: 'btn btn-primary me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          window.location.href = href;
        }
      });
    }
  });
  
  // Initialize tooltips if any
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
  
  // Focus on first input field
  $('#name').focus();
});