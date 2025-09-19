/**
 * License Management JavaScript
 */
$(function () {
    // CSRF setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Main application license activation
    $('#mainAppLicenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + window.pageData.labels.processing);
        
        $.ajax({
            url: window.pageData.urls.activateMainApp,
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: window.pageData.labels.success,
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: window.pageData.labels.error,
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                Swal.fire({
                    title: window.pageData.labels.error,
                    text: response.message || 'An error occurred during activation',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Validate main application license
    $('#validateMainApp').on('click', function() {
        const customerEmail = $('#customer_email').val();
        
        if (!customerEmail) {
            Swal.fire({
                title: window.pageData.labels.error,
                text: 'Please enter your customer email',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + window.pageData.labels.validating);

        $.ajax({
            url: window.pageData.urls.validateLicense,
            method: 'POST',
            data: { customer_email: customerEmail },
            success: function(response) {
                if (response.success) {
                    const status = response.valid ? 'Valid' : 'Invalid';
                    const icon = response.valid ? 'success' : 'warning';
                    
                    Swal.fire({
                        title: 'License Status',
                        text: `Main application license is ${status}`,
                        icon: icon,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: window.pageData.labels.error,
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: window.pageData.labels.error,
                    text: 'Failed to validate license',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Activate addon license
    $('.activate-addon').on('click', function() {
        const addonName = $(this).data('addon');
        const customerEmail = $('#customer_email').val();
        
        $('#addon_name').val(addonName);
        $('#addon_customer_email').val(customerEmail);
        $('#addonActivationModal .modal-title').text(`Activate ${addonName}`);
        $('#addonActivationModal').modal('show');
    });

    // Handle addon activation form submission
    $('#addonActivationForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + window.pageData.labels.activating);
        
        $.ajax({
            url: window.pageData.urls.activateAddon,
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addonActivationModal').modal('hide');
                    const addonName = $('#addon_name').val();
                    
                    // Update status badge
                    $(`#status-${addonName}`).removeClass('bg-warning bg-danger')
                        .addClass('bg-success').text(window.pageData.labels.activated);
                    
                    Swal.fire({
                        title: window.pageData.labels.success,
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: window.pageData.labels.error,
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                Swal.fire({
                    title: window.pageData.labels.error,
                    text: response.message || 'An error occurred during activation',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Validate addon license
    $('.validate-addon').on('click', function() {
        const addonName = $(this).data('addon');
        const customerEmail = $('#customer_email').val();
        
        if (!customerEmail) {
            Swal.fire({
                title: window.pageData.labels.error,
                text: 'Please enter your customer email first',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + window.pageData.labels.validating);

        $.ajax({
            url: window.pageData.urls.validateLicense,
            method: 'POST',
            data: { 
                customer_email: customerEmail,
                addon_name: addonName
            },
            success: function(response) {
                if (response.success) {
                    const status = response.valid ? 'Valid' : 'Invalid';
                    const icon = response.valid ? 'success' : 'warning';
                    
                    // Update status badge
                    const $statusBadge = $(`#status-${addonName}`);
                    if (response.valid) {
                        $statusBadge.removeClass('bg-warning bg-danger').addClass('bg-success').text(window.pageData.labels.activated);
                    } else {
                        $statusBadge.removeClass('bg-success bg-warning').addClass('bg-danger').text(window.pageData.labels.notActivated);
                    }
                    
                    Swal.fire({
                        title: 'License Status',
                        text: `${addonName} license is ${status}`,
                        icon: icon,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: window.pageData.labels.error,
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: window.pageData.labels.error,
                    text: 'Failed to validate addon license',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Auto-validate addon statuses on page load if customer email is available
    if (window.hasCustomerEmail) {
        setTimeout(function() {
            $('.validate-addon').each(function() {
                $(this).click();
            });
        }, 1000);
    }
});