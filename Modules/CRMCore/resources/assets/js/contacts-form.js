$(function () {
    'use strict';

    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Select2 for companies
    $('#company_id').select2({
        placeholder: $('#company_id').data('placeholder'),
        allowClear: true,
        ajax: {
            url: pageData.urls.companiesSearchUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.results || $.map(data.data || [], function (item) {
                        return {
                            text: item.text || item.name,
                            id: item.id
                        };
                    }),
                    pagination: {
                        more: data.pagination?.more || (data.next_page_url !== null)
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });

    // Initialize Select2 for users
    $('#assigned_to_user_id').select2({
        placeholder: $('#assigned_to_user_id').data('placeholder'),
        allowClear: true,
        ajax: {
            url: pageData.urls.usersSearchUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.results || $.map(data.data || [], function (item) {
                        return {
                            text: item.text || item.name,
                            id: item.id
                        };
                    }),
                    pagination: {
                        more: data.pagination?.more || (data.next_page_url !== null)
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });

    // Handle form submission
    $('#createContactForm, #editContactForm').on('submit', function (e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const isEdit = $form.attr('id') === 'editContactForm';
        
        // Disable submit button
        $submitBtn.prop('disabled', true);
        
        // Fix checkbox value
        const isActive = $('#is_active').is(':checked');
        
        // Create FormData
        const formData = new FormData(this);
        formData.delete('is_active');
        formData.append('is_active', isActive ? '1' : '0');
        
        // Add PUT method for edit
        if (isEdit) {
            formData.append('_method', 'PUT');
        }
        
        $.ajax({
            url: isEdit ? pageData.urls.updateUrl : pageData.urls.storeUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: isEdit ? pageData.labels.updateSuccess : pageData.labels.createSuccess,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = pageData.urls.indexUrl;
                    });
                } else {
                    Swal.fire({
                        title: pageData.labels.error,
                        text: response.data || pageData.labels.error,
                        icon: 'error'
                    });
                    $submitBtn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                let errorMessage = pageData.labels.error;
                
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Clear previous errors
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').text('');
                    
                    // Show validation errors
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function (field, messages) {
                        const $field = $(`[name="${field}"]`);
                        $field.addClass('is-invalid');
                        $field.siblings('.invalid-feedback').text(messages[0]);
                    });
                    
                    errorMessage = xhr.responseJSON.message || pageData.labels.error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    title: pageData.labels.error,
                    text: errorMessage,
                    icon: 'error'
                });
                
                $submitBtn.prop('disabled', false);
            }
        });
    });
});