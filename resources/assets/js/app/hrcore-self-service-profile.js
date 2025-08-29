/**
 * Employee Self-Service Profile
 */

'use strict';

$(document).ready(function() {
  // Initialize Flatpickr for date of birth
  const dobInput = document.getElementById('date_of_birth');
  if (dobInput) {
    flatpickr(dobInput, {
      dateFormat: 'Y-m-d',
      maxDate: 'today',
      yearRange: [1950, new Date().getFullYear()]
    });
  }

  // Photo preview
  $('#photo').on('change', function() {
    const file = this.files[0];
    if (file) {
      // Validate file size (2MB)
      if (file.size > 2 * 1024 * 1024) {
        Swal.fire({
          icon: 'error',
          title: 'File Too Large',
          text: 'The photo size must not exceed 2MB.',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
        this.value = '';
        return;
      }

      // Validate file type
      if (!file.type.match('image.*')) {
        Swal.fire({
          icon: 'error',
          title: 'Invalid File',
          text: 'Please select a valid image file.',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
        this.value = '';
        return;
      }

      // Show preview
      const reader = new FileReader();
      reader.onload = function(e) {
        $('#previewImage').attr('src', e.target.result);
        $('#photoPreview').show();
      };
      reader.readAsDataURL(file);
    } else {
      $('#photoPreview').hide();
    }
  });

  // Upload photo form submission
  $('#uploadPhotoForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $(this).find('button[type="submit"]');
    
    // Disable submit button
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Uploading...');
    
    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          // Update profile image or replace initials with image
          const profileElement = $('#profileImage');
          if (profileElement.is('div')) {
            // Replace initials div with image
            const newImg = $('<img>')
              .attr('src', response.photo_url)
              .attr('alt', 'user image')
              .attr('id', 'profileImage')
              .addClass('d-block h-auto ms-0 ms-sm-4 rounded user-profile-img');
            profileElement.replaceWith(newImg);
          } else {
            // Update existing image
            profileElement.attr('src', response.photo_url);
          }
          
          // Close modal
          $('#uploadPhotoModal').modal('hide');
          
          // Reset form
          $('#uploadPhotoForm')[0].reset();
          $('#photoPreview').hide();
          
          // Show success message
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message,
            customClass: {
              confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
          });
        }
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: 'Upload Failed',
          text: xhr.responseJSON?.message || 'Failed to upload photo. Please try again.',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      },
      complete: function() {
        // Re-enable submit button
        submitBtn.prop('disabled', false).html('Upload Photo');
      }
    });
  });

  // Password validation
  const newPasswordInput = document.getElementById('new_password');
  const confirmPasswordInput = document.getElementById('new_password_confirmation');
  
  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', function() {
      if (this.value !== newPasswordInput.value) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
      } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
    });
  }

  if (newPasswordInput) {
    newPasswordInput.addEventListener('input', function() {
      if (this.value.length < 8) {
        this.setCustomValidity('Password must be at least 8 characters');
        this.classList.add('is-invalid');
      } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
      
      // Re-validate confirm password if it has a value
      if (confirmPasswordInput && confirmPasswordInput.value) {
        confirmPasswordInput.dispatchEvent(new Event('input'));
      }
    });
  }

  // Show success/error messages from session
  // Note: Session messages should be handled in the Blade template
  // This is just a placeholder for any JavaScript-based notifications

  // Form validation for personal info
  const personalInfoForm = document.querySelector('#navs-pills-personal form');
  if (personalInfoForm) {
    personalInfoForm.addEventListener('submit', function(e) {
      // Email validation
      const personalEmail = document.getElementById('personal_email');
      if (personalEmail.value && !validateEmail(personalEmail.value)) {
        e.preventDefault();
        Swal.fire({
          icon: 'error',
          title: 'Invalid Email',
          text: 'Please enter a valid email address.',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
        return false;
      }
    });
  }

  // Email validation helper
  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  // Phone number formatting
  $('#phone, #mobile, #emergency_contact_phone').on('input', function() {
    // Remove non-numeric characters except + and -
    let value = this.value.replace(/[^\d+-]/g, '');
    this.value = value;
  });

  // Tab persistence (remember last active tab)
  $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
    localStorage.setItem('activeProfileTab', $(e.target).attr('data-bs-target'));
  });

  // Restore last active tab
  const activeTab = localStorage.getItem('activeProfileTab');
  if (activeTab) {
    $(`button[data-bs-target="${activeTab}"]`).tab('show');
  }
});