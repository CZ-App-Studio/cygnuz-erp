$(function () {
    // Setup AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Select2
    $('#contact_id').select2({
        placeholder: pageData.labels.searchContact,
        allowClear: true,
        ajax: {
            url: pageData.urls.searchContacts,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });
    
    $('.form-select').not('#contact_id').select2({
        minimumResultsForSearch: -1
    });
    
    // Contact selection change
    $('#contact_id').on('change', function() {
        const contactId = $(this).val();
        if (contactId && pageData.contactsData[contactId]) {
            const contact = pageData.contactsData[contactId];
            $('#contact-name').text(contact.name);
            $('#contact-email').text(contact.email);
            $('#contact-phone').text(contact.phone);
            $('#contact-company').text(contact.company);
            $('#contact-job-title').text(contact.job_title);
            $('#contact-address').text(contact.address);
            $('#contact-info-display').slideDown();
        } else {
            $('#contact-info-display').slideUp();
        }
    });
    
    // Tax exempt toggle
    $('#tax_exempt').on('change', function() {
        if ($(this).is(':checked')) {
            $('#tax-number-group').slideDown();
        } else {
            $('#tax-number-group').slideUp();
            $('input[name="tax_number"]').val('');
        }
    });
    
    // Form submission
    $('#customerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Convert checkbox to proper value
        const formData = new FormData(this);
        if (!$('#tax_exempt').is(':checked')) {
            formData.delete('tax_exempt');
            formData.append('tax_exempt', '0');
        }
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.data.message);
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response.errors) {
                    Object.keys(response.errors).forEach(function(key) {
                        toastr.error(response.errors[key][0]);
                    });
                } else {
                    toastr.error(response.data || pageData.labels.errorOccurred);
                }
            }
        });
    });
});