$(function () {
    let dt_table = $('#customer-groups-table');
    let offcanvas = null;
    let editingGroupId = null;
    
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
                d.is_active = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'name_with_code' },
            { data: 'discount_info' },
            { data: 'priority_badge' },
            { data: 'customers_count_badge' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']], // Sort by priority
        drawCallback: function() {
            loadStatistics();
        }
    });
    
    // Filter change (if filter is enabled)
    if ($('#filter-status').length) {
        $('#filter-status').on('change', function() {
            dt.ajax.reload();
        });
    }
    
    // Load statistics
    function loadStatistics() {
        $.get(pageData.urls.statistics, function(response) {
            if (response.status === 'success') {
                $('#stat-total').text(response.data.total_groups.toLocaleString());
                $('#stat-active').text(response.data.active_groups.toLocaleString());
                $('#stat-discounts').text(response.data.groups_with_discounts.toLocaleString());
                $('#stat-customers').text(response.data.total_customers_in_groups.toLocaleString());
            }
        });
    }
    
    // Show create form
    window.showCreateForm = function() {
        editingGroupId = null;
        $('#offcanvasCustomerGroupLabel').text(pageData.labels.addGroup);
        $('#customerGroupForm')[0].reset();
        $('#group_id').val('');
        $('#is_active').prop('checked', true);
        
        if (!offcanvas) {
            offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCustomerGroup'));
        }
        offcanvas.show();
    }
    
    // Edit customer group
    window.editCustomerGroup = function(id) {
        editingGroupId = id;
        $('#offcanvasCustomerGroupLabel').text(pageData.labels.editGroup);
        
        // Load group data
        $.get(pageData.urls.show.replace(':id', id), function(response) {
            if (response.status === 'success') {
                const group = response.data;
                $('#group_id').val(group.id);
                $('#name').val(group.name);
                $('#code').val(group.code);
                $('#description').val(group.description || '');
                $('#discount_percentage').val(group.discount_percentage || 0);
                $('#priority').val(group.priority);
                $('#is_active').prop('checked', group.is_active);
                
                if (!offcanvas) {
                    offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCustomerGroup'));
                }
                offcanvas.show();
            }
        });
    }
    
    // Form submit
    $('#customerGroupForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Fix checkbox value
        const isActive = $('#is_active').is(':checked');
        formData.delete('is_active');
        formData.append('is_active', isActive ? '1' : '0');
        
        const url = editingGroupId 
            ? pageData.urls.update.replace(':id', editingGroupId)
            : pageData.urls.store;
            
        const method = editingGroupId ? 'PUT' : 'POST';
        
        // Convert FormData to JSON for PUT request
        let data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        $.ajax({
            url: url,
            type: method,
            data: method === 'PUT' ? JSON.stringify(data) : formData,
            contentType: method === 'PUT' ? 'application/json' : false,
            processData: method === 'PUT' ? false : false,
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.data.message);
                    dt.ajax.reload();
                    offcanvas.hide();
                    $('#customerGroupForm')[0].reset();
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                console.error('Error response:', response);
                
                // Handle validation errors
                if (response.errors) {
                    let errorMsg = '';
                    Object.keys(response.errors).forEach(function(key) {
                        errorMsg += response.errors[key].join(', ') + '\n';
                    });
                    toastr.error(errorMsg || pageData.labels.errorOccurred);
                } else {
                    toastr.error(response.data || response.message || pageData.labels.errorOccurred);
                }
            }
        });
    });
    
    // Delete customer group
    window.deleteCustomerGroup = function(id) {
        Swal.fire({
            title: pageData.labels.confirmDelete,
            text: pageData.labels.deleteWarning,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.deleteButton,
            cancelButtonText: pageData.labels.cancelButton,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-label-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.urls.destroy.replace(':id', id),
                    type: 'DELETE',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: pageData.labels.success,
                                text: pageData.labels.groupDeleted,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            dt.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire({
                            title: pageData.labels.error,
                            text: response.data || pageData.labels.errorOccurred,
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }
    
    // Code field uppercase
    $('#code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Initial load
    loadStatistics();
});