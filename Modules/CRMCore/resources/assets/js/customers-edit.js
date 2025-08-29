$(function () {
    // Setup AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Select2
    $('.form-select').select2({
        minimumResultsForSearch: -1
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
        
        // Convert checkboxes to proper values
        const formData = new FormData(this);
        
        if (!$('#tax_exempt').is(':checked')) {
            formData.delete('tax_exempt');
            formData.append('tax_exempt', '0');
        }
        
        if (!$('#is_active').is(':checked')) {
            formData.delete('is_active');
            formData.append('is_active', '0');
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
                        text: response.data.message || 'Customer updated successfully',
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
                        text: response.data || 'Failed to update customer'
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