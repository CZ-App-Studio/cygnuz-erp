$(function() {
    'use strict';
    
    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Initialize date picker
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        maxDate: 'today'
    });

    // Initialize select2
    $('.select2').select2({
        placeholder: pageData.labels.selectPlaceholder || 'Select...',
        allowClear: true
    });

    let attachedFiles = [];
    let existingAttachments = pageData.existingAttachments || [];
    let removedAttachments = [];

    // Initialize Dropzone
    const dropzone = new Dropzone('#dropzone-attachments', {
        url: '#', // We'll handle upload manually
        autoProcessQueue: false,
        parallelUploads: 10,
        maxFilesize: 10, // MB
        acceptedFiles: '.jpg,.jpeg,.png,.pdf,.doc,.docx',
        addRemoveLinks: true,
        dictRemoveFile: pageData.labels.removeFile || 'Remove',
        init: function() {
            this.on('addedfile', function(file) {
                attachedFiles.push(file);
            });
            
            this.on('removedfile', function(file) {
                attachedFiles = attachedFiles.filter(f => f !== file);
            });
        }
    });

    // Handle removing existing attachments
    $(document).on('click', '.remove-attachment', function(e) {
        e.preventDefault();
        const index = $(this).data('index');
        const attachmentItem = $(this).closest('.attachment-item');
        
        Swal.fire({
            title: pageData.labels.removeTitle,
            text: pageData.labels.confirmRemove,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.removeFile,
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.isConfirmed) {
                // Add to removed list
                if (existingAttachments[index]) {
                    removedAttachments.push(index);
                }
                // Remove from UI
                attachmentItem.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    });

    // Expense type change handler
    $('#expense_type_id').on('change', function() {
        const option = $(this).find('option:selected');
        const maxAmount = option.data('max-amount');
        const requiresReceipt = option.data('requires-receipt');
        
        // Update help texts
        if (maxAmount) {
            $('#amountHelp').text(pageData.labels.maxAmountPrefix + parseFloat(maxAmount).toFixed(2));
        } else {
            $('#amountHelp').text('');
        }
        
        if (requiresReceipt) {
            $('#required-indicator').show();
            $('#attachmentHelp').html('<strong class="text-danger">' + pageData.labels.receiptRequiredText + '</strong>');
        } else {
            $('#required-indicator').hide();
            $('#attachmentHelp').text(pageData.labels.uploadDocumentsText);
        }
    });

    // Trigger change on load to set initial state
    $('#expense_type_id').trigger('change');

    // Amount validation
    $('#amount').on('input', function() {
        const amount = parseFloat($(this).val());
        const option = $('#expense_type_id').find('option:selected');
        const maxAmount = option.data('max-amount');
        
        if (maxAmount && amount > maxAmount) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">' + pageData.labels.maxAmountExceeded + '</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Form submission
    $('#expenseForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate required attachments
        const option = $('#expense_type_id').find('option:selected');
        const requiresReceipt = option.data('requires-receipt');
        
        // Check if we have any attachments (existing that weren't removed + new ones)
        const remainingExisting = existingAttachments.filter((att, index) => !removedAttachments.includes(index));
        const totalAttachments = remainingExisting.length + attachedFiles.length;
        
        if (requiresReceipt && totalAttachments === 0) {
            Swal.fire({
                title: pageData.labels.error,
                text: pageData.labels.receiptRequired,
                icon: 'error',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
            return;
        }
        
        const formData = new FormData(this);
        
        // Add removed attachments indices
        removedAttachments.forEach(index => {
            formData.append('removed_attachments[]', index);
        });
        
        // Add new attached files
        attachedFiles.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });
        
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.html();
        
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + pageData.labels.submitting);

        $.ajax({
            url: pageData.urls.update,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: pageData.labels.success,
                        text: response.data.message || pageData.labels.updated,
                        icon: 'success',
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        buttonsStyling: false
                    }).then(() => {
                        // Use redirect_url from response if available, otherwise default to index
                        const redirectUrl = response.data.redirect_url || pageData.urls.index;
                        window.location.href = redirectUrl;
                    });
                } else {
                    Swal.fire({
                        title: pageData.labels.error,
                        text: response.data || 'Unknown error',
                        icon: 'error',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = pageData.labels.error;
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage = errors.join('<br>');
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }
                
                Swal.fire({
                    title: pageData.labels.error,
                    html: errorMessage,
                    icon: 'error',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });
});