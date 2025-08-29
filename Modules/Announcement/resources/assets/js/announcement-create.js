/**
 * Announcement Create Page JavaScript
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  (function () {
    // Initialize Select2
    $('.select2').each(function () {
      $(this).select2({
        placeholder: $(this).attr('placeholder') || 'Select an option',
        allowClear: true,
        dropdownParent: $(this).parent()
      });
    });

    // Initialize Quill Editor
    const Font = Quill.import('formats/font');
    Font.whitelist = ['sofia', 'slabo', 'roboto', 'inconsolata', 'ubuntu'];
    Quill.register(Font, true);

    const quillEditor = document.querySelector('#quill-editor');
    if (quillEditor) {
      const editor = new Quill(quillEditor, {
        bounds: '#editor-container',
        placeholder: 'Enter announcement content...',
        theme: 'snow',
        modules: {
          toolbar: [
            [{ font: Font.whitelist }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ color: [] }, { background: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }],
            ['link', 'image', 'video'],
            ['clean']
          ]
        }
      });

      // Update hidden input on form submit
      const form = document.getElementById('announcementForm');
      if (form) {
        form.addEventListener('submit', function () {
          const contentInput = document.getElementById('content');
          contentInput.value = editor.root.innerHTML;
        });
      }
    }

    // Initialize Flatpickr for datetime
    const flatpickrDateTime = document.querySelectorAll('.flatpickr-datetime');
    if (flatpickrDateTime.length) {
      flatpickrDateTime.forEach(element => {
        flatpickr(element, {
          enableTime: true,
          dateFormat: 'Y-m-d H:i',
          minDate: 'today',
          defaultHour: 9
        });
      });
    }

    // Target Audience Selection
    const targetAudience = document.getElementById('target_audience');
    const audienceSelections = document.querySelectorAll('.audience-selection');
    
    if (targetAudience) {
      targetAudience.addEventListener('change', function () {
        // Hide all audience selections
        audienceSelections.forEach(selection => {
          selection.style.display = 'none';
          // Clear selections
          const select = selection.querySelector('select');
          if (select && $(select).data('select2')) {
            $(select).val(null).trigger('change');
          }
        });

        // Show relevant selection
        switch (this.value) {
          case 'departments':
            document.getElementById('departments-selection').style.display = 'block';
            break;
          case 'teams':
            document.getElementById('teams-selection').style.display = 'block';
            break;
          case 'specific_users':
            document.getElementById('users-selection').style.display = 'block';
            break;
        }
      });
    }

    // Status and Publish Date Interaction
    const statusSelect = document.getElementById('status');
    const publishDateInput = document.getElementById('publish_date');
    
    if (statusSelect && publishDateInput) {
      statusSelect.addEventListener('change', function () {
        if (this.value === 'scheduled') {
          publishDateInput.required = true;
          publishDateInput.closest('.mb-3').querySelector('.form-label').innerHTML = 
            'Publish Date <span class="text-danger">*</span>';
        } else {
          publishDateInput.required = false;
          publishDateInput.closest('.mb-3').querySelector('.form-label').innerHTML = 'Publish Date';
        }
      });
    }

    // Form Validation
    const announcementForm = document.getElementById('announcementForm');
    if (announcementForm) {
      announcementForm.addEventListener('submit', function (e) {
        // Validate target audience selections
        const targetValue = targetAudience.value;
        
        if (targetValue === 'departments') {
          const departments = document.getElementById('departments');
          if (!departments.value || departments.value.length === 0) {
            e.preventDefault();
            Swal.fire({
              icon: 'error',
              title: 'Validation Error',
              text: 'Please select at least one department.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
            return false;
          }
        } else if (targetValue === 'teams') {
          const teams = document.getElementById('teams');
          if (!teams.value || teams.value.length === 0) {
            e.preventDefault();
            Swal.fire({
              icon: 'error',
              title: 'Validation Error',
              text: 'Please select at least one team.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
            return false;
          }
        } else if (targetValue === 'specific_users') {
          const users = document.getElementById('users');
          if (!users.value || users.value.length === 0) {
            e.preventDefault();
            Swal.fire({
              icon: 'error',
              title: 'Validation Error',
              text: 'Please select at least one user.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
            return false;
          }
        }

        // Validate dates
        const publishDate = publishDateInput.value;
        const expiryDate = document.getElementById('expiry_date').value;
        
        if (publishDate && expiryDate) {
          if (new Date(expiryDate) <= new Date(publishDate)) {
            e.preventDefault();
            Swal.fire({
              icon: 'error',
              title: 'Validation Error',
              text: 'Expiry date must be after publish date.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
            return false;
          }
        }
      });
    }

    // File Upload Validation
    const attachmentInput = document.getElementById('attachment');
    if (attachmentInput) {
      attachmentInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
          // Check file size (10MB = 10 * 1024 * 1024 bytes)
          if (file.size > 10 * 1024 * 1024) {
            Swal.fire({
              icon: 'error',
              title: 'File Too Large',
              text: 'File size must be less than 10MB.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
            this.value = '';
            return;
          }

          // Check file type
          const allowedTypes = ['application/pdf', 'application/msword', 
                               'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                               'image/jpeg', 'image/png'];
          if (!allowedTypes.includes(file.type)) {
            Swal.fire({
              icon: 'error',
              title: 'Invalid File Type',
              text: 'Only PDF, DOC, DOCX, JPG, and PNG files are allowed.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
            this.value = '';
            return;
          }
        }
      });
    }
  })();
});