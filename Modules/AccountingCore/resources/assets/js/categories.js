/**
 * AccountingCore Categories JavaScript
 */

// Global variables
let categoriesTable = null;
let formOffcanvas = null;

// Initialize on DOM ready
$(document).ready(function() {
    console.log('AccountingCore Categories: Initializing...');
    
    // Initialize DataTable
    initializeDataTable();
    
    // Initialize form elements
    initializeFormElements();
    
    // Setup event listeners
    setupEventListeners();
});

/**
 * Initialize DataTable
 */
function initializeDataTable() {
    const tableEl = $('#categoriesTable');
    if (!tableEl.length) return;
    
    const urls = window.pageData?.urls || {};
    
    categoriesTable = tableEl.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: urls.datatable,
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTable error:', error, thrown);
            }
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'type_badge', name: 'type' },
            { data: 'parent_name', name: 'parent_id' },
            { data: 'icon_display', name: 'icon' },
            { data: 'color_display', name: 'color' },
            { data: 'transaction_count', name: 'transaction_count' },
            { data: 'status', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        drawCallback: function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
}

/**
 * Initialize form elements
 */
function initializeFormElements() {
    // Initialize Select2 for parent category dropdown
    $('#parent_id').select2({
        placeholder: pageData.labels.selectParentCategory || 'Select Parent Category',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#categoryFormOffcanvas')
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Create new category
    $(document).on('click', '.create-category', function() {
        resetForm();
        showForm();
    });
    
    // Edit category
    $(document).on('click', '.edit-category', function() {
        const id = $(this).data('id');
        editCategory(id);
    });
    
    // Delete category
    $(document).on('click', '.delete-category', function() {
        const id = $(this).data('id');
        deleteCategory(id);
    });
    
    // Form submission
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        saveCategory();
    });
    
    // Type change - filter parent categories
    $('#type').on('change', function() {
        filterParentCategories($(this).val());
    });
}

/**
 * Show form offcanvas
 */
function showForm() {
    const offcanvasEl = document.getElementById('categoryFormOffcanvas');
    if (!formOffcanvas) {
        formOffcanvas = new bootstrap.Offcanvas(offcanvasEl, {
            backdrop: true,
            keyboard: true,
            scroll: false
        });
    }
    formOffcanvas.show();
}

/**
 * Hide form offcanvas
 */
function hideForm() {
    if (formOffcanvas) {
        formOffcanvas.hide();
    }
}

/**
 * Reset form
 */
function resetForm() {
    $('#categoryForm')[0].reset();
    $('#categoryId').val('');
    $('#categoryForm').find('input[name="_method"]').remove();
    $('#categoryForm').attr('action', pageData.urls.store);
    $('#offcanvasLabel').text(pageData.labels.addCategory);
    $('#is_active').prop('checked', true);
    $('#parent_id').val('').trigger('change');
}

/**
 * Edit category
 */
function editCategory(id) {
    const url = pageData.urls.show.replace(':id', id);
    
    $.get(url)
        .done(function(response) {
            if (response.status === 'success') {
                const category = response.data.category;
                
                // Reset form first
                resetForm();
                
                // Fill form
                $('#categoryId').val(id);
                $('#categoryForm').attr('action', pageData.urls.update.replace(':id', id));
                $('#categoryForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#offcanvasLabel').text(pageData.labels.editCategory);
                
                $('#name').val(category.name);
                $('#type').val(category.type);
                $('#parent_id').val(category.parent_id);
                $('#icon').val(category.icon);
                $('#color').val(category.color);
                $('#is_active').prop('checked', category.is_active);
                
                // Filter parent categories based on type
                filterParentCategories(category.type);
                
                showForm();
            }
        })
        .fail(function(xhr) {
            showError(extractErrorMessage(xhr));
        });
}

/**
 * Save category
 */
function saveCategory() {
    const form = $('#categoryForm')[0];
    const formData = new FormData(form);
    const url = form.action;
    
    // Fix checkbox values
    formData.delete('is_active');
    formData.append('is_active', $('#is_active').is(':checked') ? '1' : '0');
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status === 'success') {
                showSuccess(response.data.message || pageData.labels.savedSuccessfully);
                hideForm();
                categoriesTable.ajax.reload();
            }
        },
        error: function(xhr) {
            showError(extractErrorMessage(xhr));
        }
    });
}

/**
 * Delete category
 */
function deleteCategory(id) {
    Swal.fire({
        title: pageData.labels.areYouSure,
        text: pageData.labels.cannotRevert,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: pageData.labels.yesDelete,
        cancelButtonText: pageData.labels.cancel
    }).then((result) => {
        if (result.isConfirmed) {
            const url = pageData.urls.destroy.replace(':id', id);
            
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccess(response.data.message || pageData.labels.deletedSuccessfully);
                        categoriesTable.ajax.reload();
                    }
                },
                error: function(xhr) {
                    showError(extractErrorMessage(xhr));
                }
            });
        }
    });
}

/**
 * Extract error message from response
 */
function extractErrorMessage(xhr) {
    const response = xhr.responseJSON;
    let errorMessage = pageData.labels.errorOccurred;
    
    if (response) {
        // Handle validation errors (422)
        if (response.errors) {
            errorMessage = '';
            Object.values(response.errors).forEach(errorArray => {
                errorArray.forEach(error => {
                    errorMessage += error + '<br>';
                });
            });
        }
        // Handle general error response with data field
        else if (response.data && typeof response.data === 'string') {
            errorMessage = response.data;
        }
        // Handle general error response with message field
        else if (response.message) {
            errorMessage = response.message;
        }
    }
    
    return errorMessage;
}

/**
 * Show success message
 */
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: pageData.labels.success,
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

/**
 * Show error message
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        html: message
    });
}

/**
 * Filter parent categories by type
 */
function filterParentCategories(type) {
    const parentSelect = $('#parent_id');
    
    // Show/hide options based on type
    parentSelect.find('option').each(function() {
        const optionType = $(this).data('type');
        if (!optionType || optionType === type) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    
    // Clear selection if current parent doesn't match type
    const selectedOption = parentSelect.find('option:selected');
    if (selectedOption.data('type') && selectedOption.data('type') !== type) {
        parentSelect.val('').trigger('change');
    }
}

// Export functions for external use
window.AccountingCoreCategories = {
    refreshTable: function() {
        if (categoriesTable) {
            categoriesTable.ajax.reload();
        }
    },
    editCategory,
    deleteCategory
};

// Export global functions for inline onclick handlers
window.editCategory = editCategory;
window.deleteCategory = deleteCategory;