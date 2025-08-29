$(function() {
    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Profile Picture Upload
    $('#profile_picture').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            reader.readAsDataURL(file);
        }
    });

    $('#uploadProfilePicture').on('click', function() {
        const form = $('#profilePictureForm')[0];
        const formData = new FormData(form);
        
        if (!$('#profile_picture').val()) {
            showErrorToast('Please select an image');
            return;
        }

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Uploading...');

        $.ajax({
            url: '/profile/update-picture',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessToast(response.data.message || 'Profile picture updated');
                    $('#profilePictureModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showErrorToast(response.data || 'Failed to update profile picture');
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.data || 'An error occurred';
                showErrorToast(error);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('#removeProfilePicture').on('click', function() {
        Swal.fire({
            title: 'Remove Profile Picture?',
            text: 'Are you sure you want to remove your profile picture?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/profile/remove-picture',
                    method: 'DELETE',
                    success: function(response) {
                        if (response.status === 'success') {
                            showSuccessToast(response.data.message || 'Profile picture removed');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showErrorToast(response.data || 'Failed to remove profile picture');
                        }
                    },
                    error: function(xhr) {
                        showErrorToast(xhr.responseJSON?.data || 'An error occurred');
                    }
                });
            }
        });
    });

    // Basic Info Form
    $('#basicInfoForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        
        $.ajax({
            url: '/profile/update-basic-info',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessToast(response.data.message || 'Profile updated successfully');
                } else {
                    showErrorToast(response.data || 'Failed to update profile');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON?.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errors.forEach(error => showErrorToast(error));
                } else {
                    showErrorToast(xhr.responseJSON?.data || 'An error occurred');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Change Password Form
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Validate passwords match
        if ($('#new_password').val() !== $('#new_password_confirmation').val()) {
            showErrorToast('New passwords do not match');
            return;
        }
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Changing...');
        
        $.ajax({
            url: '/profile/change-password',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessToast(response.data.message || 'Password changed successfully');
                    form[0].reset();
                } else {
                    showErrorToast(response.data || 'Failed to change password');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON?.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errors.forEach(error => showErrorToast(error));
                } else {
                    showErrorToast(xhr.responseJSON?.data || 'An error occurred');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Notification Preferences Form
    $('#notificationPreferencesForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        
        // Prepare form data with checkbox values
        const formData = new FormData(form[0]);
        
        // Handle checkboxes
        form.find('input[type="checkbox"]').each(function() {
            const name = $(this).attr('name');
            if (name) {
                formData.delete(name);
                formData.append(name, $(this).is(':checked') ? '1' : '0');
            }
        });
        
        $.ajax({
            url: '/profile/update-notifications',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessToast(response.data.message || 'Preferences updated successfully');
                } else {
                    showErrorToast(response.data || 'Failed to update preferences');
                }
            },
            error: function(xhr) {
                showErrorToast(xhr.responseJSON?.data || 'An error occurred');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Terminate Session
    $('.terminate-session').on('click', function() {
        const sessionId = $(this).data('session-id');
        const card = $(this).closest('.session-card');
        
        Swal.fire({
            title: 'Terminate Session?',
            text: 'This will sign out the selected session.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, terminate it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/profile/terminate-session',
                    method: 'POST',
                    data: { session_id: sessionId },
                    success: function(response) {
                        if (response.status === 'success') {
                            card.fadeOut(300, function() {
                                $(this).remove();
                            });
                            showSuccessToast(response.data.message || 'Session terminated');
                        } else {
                            showErrorToast(response.data || 'Failed to terminate session');
                        }
                    },
                    error: function(xhr) {
                        showErrorToast(xhr.responseJSON?.data || 'An error occurred');
                    }
                });
            }
        });
    });

    // Terminate All Sessions
    $('#terminateAllSessions').on('click', function() {
        $('#terminateAllModal').modal('show');
    });

    $('#confirmTerminateAll').on('click', function() {
        const password = $('#terminate_password').val();
        
        if (!password) {
            showErrorToast('Please enter your password');
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
        
        $.ajax({
            url: '/profile/terminate-all-sessions',
            method: 'POST',
            data: { password: password },
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessToast(response.data.message || 'All other sessions terminated');
                    $('#terminateAllModal').modal('hide');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showErrorToast(response.data || 'Failed to terminate sessions');
                }
            },
            error: function(xhr) {
                showErrorToast(xhr.responseJSON?.data || 'Incorrect password');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Password visibility toggle
    window.togglePassword = function(fieldId) {
        const field = $('#' + fieldId);
        const type = field.attr('type');
        field.attr('type', type === 'password' ? 'text' : 'password');
        
        const icon = field.next().find('i');
        icon.toggleClass('bx-show bx-hide');
    };

    // Reset modal on close
    $('#profilePictureModal').on('hidden.bs.modal', function() {
        $('#profilePictureForm')[0].reset();
        $('#imagePreview').hide();
    });

    $('#terminateAllModal').on('hidden.bs.modal', function() {
        $('#terminate_password').val('');
    });
});