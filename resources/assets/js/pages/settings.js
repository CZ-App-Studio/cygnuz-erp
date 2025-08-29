// Settings Page JavaScript
console.log('Settings.js loaded successfully!');

// Current active item
let currentType = null;
let currentKey = null;

// Initialize
$(function() {
    console.log('Settings.js jQuery ready!');
    // Search functionality
    $('#settings-search').on('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const menuItems = document.querySelectorAll('.settings-menu-item');
        
        menuItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? 'block' : 'none';
        });
    });
    
    // Handle import form
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = $(this).find('button[type="submit"]');
        const originalText = button.html();
        
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> ' + pageData.labels.importing);
        
        $.ajax({
            url: pageData.urls.import,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.success,
                        text: response.message,
                        confirmButtonText: pageData.labels.ok
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: response.message || pageData.labels.importFailed
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: pageData.labels.importError
                });
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
                bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
            }
        });
    });
    
    // Load first settings category by default
    const firstCategory = $('.settings-menu-item[data-category]').first();
    if (firstCategory.length) {
        const category = firstCategory.data('category');
        loadSettingsContent('system', category);
    }
});

// Load settings content - Make it globally available
window.loadSettingsContent = function(type, key = null) {
    // Update active state
    $('.settings-menu-item').removeClass('active');
    if (key) {
        $(`.settings-menu-item[data-${type === 'system' ? 'category' : 'module'}="${key}"]`).addClass('active');
    } else if (type === 'history') {
        // Find the history menu item by checking the onclick attribute
        $('.settings-menu-item[onclick*="history"]').addClass('active');
    }
    
    // Store current state
    currentType = type;
    currentKey = key;
    
    // Show loading
    $('#settings-content').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">${pageData.labels.loading}</span>
            </div>
        </div>
    `);
    
    // Load content based on type
    let url;
    if (type === 'system' && key) {
        url = pageData.urls.systemSettings.replace(':category', key);
    } else if (type === 'module' && key) {
        url = pageData.urls.moduleForm.replace(':module', key);
    } else if (type === 'history') {
        url = pageData.urls.history;
    }
    
    if (url) {
        fetch(url)
            .then(response => response.text())
            .then(html => {
                $('#settings-content').html(html);
                
                // Remove any script tags from the loaded content to prevent conflicts
                $('#settings-content').find('script').remove();
                
                // Initialize select2 if needed
                if ($('#settings-content').find('.select2').length) {
                    $('#settings-content').find('.select2').select2({
                        width: '100%'
                    });
                }
                
                // Bind form submission if it's a settings form
                if (type !== 'history') {
                    bindSettingsForm();
                }
                
                // Special handling for email settings
                if (type === 'system' && key === 'email') {
                    console.log('Email settings loaded, binding test button...');
                    bindEmailTestButton();
                }
            })
            .catch(error => {
                $('#settings-content').html(`
                    <div class="alert alert-danger">
                        <i class="bx bx-error me-1"></i>
                        ${pageData.labels.loadError}
                    </div>
                `);
            });
    }
};

// Bind settings form submission
function bindSettingsForm() {
    const form = $('#settings-content').find('form');
    if (form.length) {
        // Unbind any existing submit handlers to prevent duplicates
        form.off('submit');
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const button = $form.find('button[type="submit"]');
            const originalText = button.html();
            
            // Fix checkbox values
            $form.find('input[type="checkbox"]').each(function() {
                const name = $(this).attr('name');
                const value = $(this).is(':checked') ? '1' : '0';
                
                // Remove existing hidden input if any
                $form.find(`input[type="hidden"][name="${name}"]`).remove();
                
                // Add hidden input with correct value
                $form.append(`<input type="hidden" name="${name}" value="${value}">`);
            });
            
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> ' + pageData.labels.saving);
            
            let url;
            if (currentType === 'system') {
                url = pageData.urls.updateSystem.replace(':category', currentKey);
            } else if (currentType === 'module') {
                url = pageData.urls.updateModule.replace(':module', currentKey);
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.message || pageData.labels.saveError
                        });
                    }
                },
                error: function(xhr) {
                    let message = pageData.labels.errorOccurred;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: message
                    });
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        });
    }
}

// Export settings - Make it globally available
window.exportSettings = function() {
    Swal.fire({
        title: pageData.labels.exportTitle || 'Export Settings?',
        text: pageData.labels.exportText || 'This will download all current settings as a JSON file. Do you want to continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: pageData.labels.yesExport || 'Yes, Export',
        cancelButtonText: pageData.labels.cancel || 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: pageData.labels.exporting || 'Exporting...',
                text: pageData.labels.exportingText || 'Please wait while we prepare your settings file.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Trigger download
            window.location.href = pageData.urls.export;
            
            // Close loading after a short delay
            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.exportSuccess || 'Export Completed',
                    text: pageData.labels.exportSuccessText || 'Your settings file has been downloaded.',
                    timer: 3000,
                    showConfirmButton: false
                });
            }, 2000);
        }
    });
};

// Import settings - Make it globally available
window.importSettings = function() {
    const modal = new bootstrap.Modal(document.getElementById('importModal'));
    modal.show();
};

// Global function for rollback (used in history view)
window.rollbackSetting = function(historyId) {
    Swal.fire({
        title: pageData.labels.rollbackTitle,
        text: pageData.labels.rollbackText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.yesRollback,
        cancelButtonText: pageData.labels.cancel
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.rollback.replace(':id', historyId),
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            loadSettingsContent('history');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: pageData.labels.rollbackError
                    });
                }
            });
        }
    });
};

// Bind email test button
function bindEmailTestButton() {
    console.log('bindEmailTestButton called');
    const testBtn = $('#testEmailBtn');
    console.log('Test button found:', testBtn.length > 0);
    console.log('Test button element:', testBtn[0]);
    
    if (testBtn.length) {
        // Remove any existing handlers first
        testBtn.off('click');
        
        // Try direct DOM event listener as well
        testBtn[0].addEventListener('click', function(e) {
            console.log('Test email button clicked via addEventListener!');
            e.preventDefault();
            showTestEmailDialog();
        });
        
        // Also bind with jQuery
        testBtn.on('click', function(e) {
            console.log('Test email button clicked via jQuery!');
            e.preventDefault();
            showTestEmailDialog();
        });
    }
}

// Separate function for showing the dialog
function showTestEmailDialog() {
    console.log('showTestEmailDialog called');
    
    // Get user email from button data attribute
    const testBtn = $('#testEmailBtn');
    const userEmail = testBtn.data('user-email') || '';
    
    Swal.fire({
        title: 'Send Test Email',
        input: 'email',
        inputLabel: 'Enter email address to send test to',
        inputValue: userEmail,
        showCancelButton: true,
        confirmButtonText: 'Send',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter an email address';
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                return 'Please enter a valid email address';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/settings/test-email',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    test_email: result.value
                },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Sending...',
                        text: 'Please wait while we send the test email',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    let message = 'Failed to send test email';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON.error) {
                            message += '\n\nError Details: ' + xhr.responseJSON.error;
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message
                    });
                }
            });
        }
    });
}

// Global function for resetting module settings
window.resetModuleSettings = function(module) {
    Swal.fire({
        title: 'Reset Settings',
        text: 'Are you sure you want to reset all settings to their default values?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reset',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/settings/module/${module}/reset`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Settings reset successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload the module form
                            loadSettingsContent('module', module);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to reset settings'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to reset settings'
                    });
                }
            });
        }
    });
};