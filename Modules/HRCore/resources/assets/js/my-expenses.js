$(function () {
    'use strict';

    // Initialize components
    initializeDataTable();
    initializeFilters();
    setupEventHandlers();
});

let table;

function initializeDataTable() {
    table = $('#expensesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function (d) {
                d.status = $('#filterStatus').val();
                d.expense_type_id = $('#filterExpenseType').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        columns: [
            { data: 'expense_date', name: 'expense_date' },
            { data: 'expense_type', name: 'expense_type_id' },
            { data: 'description', name: 'description' },
            { data: 'amount', name: 'amount' },
            { data: 'status', name: 'status' },
            { data: 'attachments', name: 'attachments', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        dom: '<"card-header d-flex flex-wrap pb-2"<"me-5 ms-n2"f><"d-flex justify-content-start justify-content-md-end align-items-baseline"<"dt-action-buttons d-flex justify-content-center flex-md-row mb-3 mb-md-0 ps-1 ms-1 align-items-baseline"lB>>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        displayLength: 10,
        lengthMenu: [10, 20, 50, 100],
        buttons: [],
        language: {
            search: '',
            searchPlaceholder: 'Search...'
        }
    });
}

function initializeFilters() {
    // Initialize Select2
    $('#filterStatus, #filterExpenseType').select2({
        minimumResultsForSearch: -1
    });

    // Initialize Flatpickr for date inputs
    $('#filterDateFrom, #filterDateTo').flatpickr({
        dateFormat: 'Y-m-d',
        allowInput: true
    });

    // Apply filters on change
    $('#filterStatus, #filterExpenseType, #filterDateFrom, #filterDateTo').on('change', function () {
        table.ajax.reload();
    });
}

function setupEventHandlers() {
    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
}

// Create new expense
window.createExpense = function() {
    // Add return_to parameter to redirect back to my-expenses
    const url = new URL(pageData.urls.create, window.location.origin);
    url.searchParams.append('return_to', 'my-expenses');
    window.location.href = url.toString();
};

// Edit expense
window.editExpense = function(id) {
    // Add return_to parameter to redirect back to my-expenses
    const url = new URL(pageData.urls.edit.replace('__ID__', id), window.location.origin);
    url.searchParams.append('return_to', 'my-expenses');
    window.location.href = url.toString();
};

// Delete expense
window.deleteExpense = function(id) {
    Swal.fire({
        title: pageData.labels.deleteTitle,
        text: pageData.labels.confirmDelete,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.deleteButton,
        cancelButtonText: pageData.labels.cancelButton,
        customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.destroy.replace('__ID__', id),
                type: 'DELETE',
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.data.message || pageData.labels.deleted,
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        });
                        table.ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data || pageData.labels.error,
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        });
                    }
                },
                error: function (xhr) {
                    let errorMessage = pageData.labels.error;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
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