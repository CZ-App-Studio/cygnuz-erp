$(function () {
    // Initialize DataTable
    const table = $('#taxRatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: pageData.urls.datatable,
        columns: [
            { data: 'name', name: 'name' },
            { data: 'rate_formatted', name: 'rate' },
            { data: 'type_display', name: 'type' },
            { data: 'tax_authority', name: 'tax_authority', defaultContent: '-' },
            { data: 'is_default_display', name: 'is_default' },
            { data: 'status_display', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']]
    });

    // Handle tax type change
    $('#type').on('change', function() {
        const selectedType = $(this).val();
        const rateInput = $('#rate');
        const rateLabel = $('#rate-label');
        const percentageHelp = $('#percentage-help');
        const fixedHelp = $('#fixed-help');
        
        if (selectedType === 'fixed') {
            rateLabel.text('Fixed Amount');
            rateInput.attr('max', '999999.99');
            percentageHelp.hide();
            fixedHelp.show();
        } else {
            rateLabel.text('Rate (%)');
            rateInput.attr('max', '100');
            percentageHelp.show();
            fixedHelp.hide();
        }
    });

    // Create tax rate
    window.createTaxRate = function() {
        $('#taxRateOffcanvasLabel').text(pageData.labels.addTaxRate);
        $('#taxRateForm')[0].reset();
        $('#taxRateId').val('');
        $('#is_active').prop('checked', true);
        $('#type').val('percentage'); // Set default to percentage
        $('#type').trigger('change'); // Trigger the change event to update UI
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('taxRateOffcanvas'));
        offcanvas.show();
    };

    // Edit tax rate
    window.editTaxRate = function(id) {
        $.get(`${pageData.urls.show}/${id}`, function(taxRate) {
            $('#taxRateOffcanvasLabel').text(pageData.labels.editTaxRate);
            $('#taxRateId').val(taxRate.id);
            $('#name').val(taxRate.name);
            $('#rate').val(taxRate.rate);
            $('#type').val(taxRate.type);
            $('#type').trigger('change'); // Update UI based on tax type
            $('#tax_authority').val(taxRate.tax_authority);
            $('#description').val(taxRate.description);
            $('#is_default').prop('checked', taxRate.is_default);
            $('#is_active').prop('checked', taxRate.is_active);
            $('.invalid-feedback').text('');
            $('.form-control').removeClass('is-invalid');
            
            const offcanvas = new bootstrap.Offcanvas(document.getElementById('taxRateOffcanvas'));
            offcanvas.show();
        });
    };

    // Delete tax rate
    window.deleteTaxRate = function(id) {
        Swal.fire({
            title: pageData.labels.confirmDelete,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesDelete,
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${pageData.urls.destroy}/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.success,
                                text: response.data.message || pageData.labels.deleteSuccess,
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                }
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: pageData.labels.error,
                                text: response.data || pageData.labels.deleteError,
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = pageData.labels.deleteError;
                        if (xhr.responseJSON && xhr.responseJSON.data) {
                            errorMessage = xhr.responseJSON.data;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: errorMessage,
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        });
                    }
                });
            }
        });
    };

    // Handle form submission
    $('#taxRateForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const taxRateId = $('#taxRateId').val();
        const url = taxRateId ? `${pageData.urls.update}/${taxRateId}` : pageData.urls.store;
        
        // Fix checkbox values
        formData.delete('is_default');
        formData.delete('is_active');
        formData.append('is_default', $('#is_default').is(':checked') ? '1' : '0');
        formData.append('is_active', $('#is_active').is(':checked') ? '1' : '0');
        
        if (taxRateId) {
            formData.append('_method', 'PUT');
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success') {
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('taxRateOffcanvas'));
                    offcanvas.hide();
                    
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.success,
                        text: response.data.message,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    
                    table.ajax.reload();
                } else {
                    if (response.data && response.data.errors) {
                        // Display validation errors
                        $.each(response.data.errors, function(field, errors) {
                            $(`#${field}`).addClass('is-invalid');
                            $(`#${field}`).siblings('.invalid-feedback').text(errors[0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data || pageData.labels.genericError,
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        });
                    }
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Display validation errors
                    $.each(xhr.responseJSON.errors, function(field, errors) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(errors[0]);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: pageData.labels.requestError,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                }
            }
        });
    });

    // Clear validation errors when input changes
    $('.form-control').on('input change', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').text('');
    });
});