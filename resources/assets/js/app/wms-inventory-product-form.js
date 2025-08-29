/**
 * Page: WMS & Inventory Product Form (Create/Edit)
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

  // Initialize Select2
  $('.select2').select2({
    allowClear: true
  });

  // Initialize Dropzone for product image upload
  if (document.querySelector('#productImageDropzone')) {
    const productImageDropzone = new Dropzone('#productImageDropzone', {
      url: '/upload-temp-image', // You'll need to create this route
      maxFilesize: 2, // MB
      acceptedFiles: 'image/*',
      maxFiles: 1,
      addRemoveLinks: true,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(file, response) {
        // Store the uploaded file path in hidden input
        $('#product_image_path').val(response.path);
      },
      removedfile: function(file) {
        // Clear the hidden input when file is removed
        $('#product_image_path').val('');
        file.previewElement.remove();
      }
    });
  }

  // Auto-generate SKU based on product name and code (optional)
  $('#name, #code').on('input', function() {
    if ($('#sku').val() === '') {
      const name = $('#name').val().slice(0, 3).toUpperCase();
      const code = $('#code').val().slice(0, 3).toUpperCase();
      if (name && code) {
        $('#sku').val(name + '-' + code);
      }
    }
  });

  // Form validation
  $('#productForm').on('submit', function(e) {
    let isValid = true;
    
    // Check required fields
    const requiredFields = ['name', 'code', 'category_id', 'unit_id'];
    requiredFields.forEach(function(field) {
      const input = $('#' + field);
      if (!input.val()) {
        isValid = false;
        input.addClass('is-invalid');
      } else {
        input.removeClass('is-invalid');
      }
    });

    // Validate pricing fields if filled
    const costPrice = parseFloat($('#cost_price').val());
    const sellingPrice = parseFloat($('#selling_price').val());
    
    if (costPrice && sellingPrice && sellingPrice < costPrice) {
      alert('Selling price should not be less than cost price.');
      isValid = false;
    }

    if (!isValid) {
      e.preventDefault();
    }
  });
});
