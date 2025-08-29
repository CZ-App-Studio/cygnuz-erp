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
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message || 'Customer created successfully',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    });
                } else if (response.status === 'failed') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data || 'Failed to create customer'
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                let errorMessage = pageData.labels.errorOccurred || 'An error occurred';
                
                if (response) {
                    if (response.errors) {
                        errorMessage = Object.values(response.errors).flat().join('<br>');
                    } else if (response.data) {
                        errorMessage = response.data;
                    } else if (response.message) {
                        errorMessage = response.message;
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            }
        });
    });
});