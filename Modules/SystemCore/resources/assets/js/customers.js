$(function () {
    let dt_table = $('#customers-table');
    
    // Setup AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Initialize Select2
    $('.form-select').select2({
        allowClear: true
    });
    
    // DataTable
    let dt = dt_table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function (d) {
                d.customer_type = $('#filter-customer-type').val();
                d.customer_group_id = $('#filter-customer-group').val();
                d.is_active = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'customer_name' },
            { data: 'contact_info' },
            { data: 'customer_type_badge' },
            { 
                data: 'customer_group.name',
                defaultContent: '<span class="text-muted">-</span>'
            },
            { data: 'lifetime_value_formatted' },
            { 
                data: 'last_purchase_date',
                render: function(data) {
                    if (!data) {
                        return '<span class="text-muted">' + pageData.labels.never + '</span>';
                    }
                    // Format date without moment.js
                    const date = new Date(data);
                    const options = { day: '2-digit', month: 'short', year: 'numeric' };
                    return date.toLocaleDateString('en-US', options);
                }
            },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        drawCallback: function() {
            loadStatistics();
        }
    });
    
    // Filter change
    $('#filter-customer-type, #filter-customer-group, #filter-status').on('change', function() {
        dt.ajax.reload();
    });
    
    // Load statistics
    function loadStatistics() {
        $.get(pageData.urls.statistics, function(response) {
            if (response.status === 'success') {
                $('#stat-total').text(response.data.total_customers.toLocaleString());
                $('#stat-active').text(response.data.active_customers.toLocaleString());
                $('#stat-new').text(response.data.new_customers_this_month.toLocaleString());
                $('#stat-vip').text(response.data.vip_customers.toLocaleString());
                $('#stat-value').text('$' + response.data.total_lifetime_value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#stat-blacklisted').text(response.data.blacklisted_customers.toLocaleString());
            }
        });
    }
    
    // View customer
    window.viewCustomer = function(id) {
        window.location.href = pageData.urls.show.replace(':id', id);
    }
    
    // Edit customer
    window.editCustomer = function(id) {
        window.location.href = pageData.urls.edit.replace(':id', id);
    }
    
    // Toggle blacklist with SweetAlert2
    window.toggleBlacklist = function(id, blacklist) {
        const title = blacklist ? pageData.labels.addToBlacklist : pageData.labels.removeFromBlacklist;
        const confirmButtonText = blacklist ? pageData.labels.addToBlacklist : pageData.labels.removeFromBlacklist;
        const confirmButtonClass = blacklist ? 'btn btn-danger' : 'btn btn-success';
        
        let inputOptions = {};
        if (blacklist) {
            inputOptions = {
                input: 'textarea',
                inputLabel: pageData.labels.reasonForBlacklisting,
                inputPlaceholder: pageData.labels.enterReason,
                inputValidator: (value) => {
                    if (!value) {
                        return pageData.labels.reasonRequired;
                    }
                }
            };
        }
        
        Swal.fire({
            title: title,
            text: blacklist ? pageData.labels.blacklistWarning : pageData.labels.removeBlacklistWarning,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: confirmButtonClass
            },
            ...inputOptions
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value || '';
                
                $.post(pageData.urls.blacklist.replace(':id', id), {
                    blacklist: blacklist,
                    reason: reason
                }).done(function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: pageData.labels.success,
                            text: response.data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        dt.ajax.reload();
                    }
                }).fail(function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        title: pageData.labels.error,
                        text: response.data || pageData.labels.errorOccurred,
                        icon: 'error'
                    });
                });
            }
        });
    }
    
    // Reset filters
    window.resetFilters = function() {
        $('#filter-customer-type').val('').trigger('change');
        $('#filter-customer-group').val('').trigger('change');
        $('#filter-status').val('').trigger('change');
        dt.ajax.reload();
    }
    
    // Initial load
    loadStatistics();
});