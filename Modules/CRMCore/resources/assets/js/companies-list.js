/**
 * Companies List - DataTable and Actions Handler
 */

$(function () {
    'use strict';

    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // DataTable initialization
    let dt_companies = $('#companiesTable');
    
    if (dt_companies.length) {
        dt_companies.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: pageData.urls.datatable,
                type: 'GET'
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'email_office', name: 'email_office' },
                { data: 'phone_office', name: 'phone_office' },
                { data: 'website', name: 'website' },
                { data: 'assigned_to', name: 'assigned_to' },
                { 
                    data: 'is_active', 
                    name: 'is_active',
                    searchable: false,
                    orderable: false,
                    className: 'text-center'
                },
                { 
                    data: 'actions', 
                    name: 'actions',
                    searchable: false,
                    orderable: false,
                    className: 'text-center'
                }
            ],
            order: [[1, 'asc']], // Order by name
            displayLength: pageData.settings?.itemsPerPage || 25,
            lengthMenu: [10, 25, 50, 100],
            dom:
                '<"row"' +
                '<"col-sm-12 col-md-6"l>' +
                '<"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>' +
                '>' +
                '<"table-responsive"t>' +
                '<"row"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                '>',
            language: {
                paginate: {
                    next: '<i class="bx bx-chevron-right"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>'
                }
            }
        });
    }

    // Status toggle handler
    $(document).on('change', '.status-toggle', function () {
        const $checkbox = $(this);
        const companyId = $checkbox.data('id');
        const url = $checkbox.data('url');
        
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.statusSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    // Revert checkbox state
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.statusError,
                        text: response.data || pageData.labels.statusError
                    });
                }
            },
            error: function () {
                // Revert checkbox state
                $checkbox.prop('checked', !$checkbox.prop('checked'));
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.statusError
                });
            }
        });
    });

    // Edit button handler (redirects to edit page)
    $(document).on('click', '.edit-company-btn', function () {
        const url = $(this).data('url');
        window.location.href = url;
    });

    // Delete company handler
    window.deleteCompany = function(companyId) {
        const $button = $(`.delete-company[data-id="${companyId}"]`);
        const contactsCount = parseInt($button.data('contacts-count') || 0);
        
        // Check if company has contacts
        if (contactsCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: pageData.labels.hasContacts,
                showConfirmButton: true
            });
            return;
        }
        
        Swal.fire({
            title: pageData.labels.confirmDelete,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            confirmButtonClass: 'btn btn-danger me-2',
            cancelButtonClass: 'btn btn-label-secondary',
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                const url = pageData.urls.destroy.replace(':id', companyId);
                
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.deleteSuccess,
                                showConfirmButton: false,
                                timer: 1500
                            });
                            
                            // Reload DataTable
                            $('#companiesTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: pageData.labels.deleteError,
                                text: response.data || pageData.labels.deleteError
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.deleteError
                        });
                    }
                });
            }
        });
    };
});