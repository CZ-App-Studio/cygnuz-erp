/**
 * AccountingCore Transactions JavaScript
 */

// Global variables
let transactionsTable = null;
let formModal = null;
let tagify = null;

// Initialize on DOM ready
$(document).ready(function() {
    console.log('AccountingCore Transactions: Initializing...');
    
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
    const tableEl = $('#transactionsTable');
    if (!tableEl.length) return;
    
    const urls = window.pageData?.urls || {};
    
    transactionsTable = tableEl.DataTable({
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
            { data: 'formatted_date', name: 'transaction_date' },
            { data: 'description', name: 'description' },
            { data: 'category_name', name: 'category.name' },
            { data: 'type_badge', name: 'type' },
            { data: 'formatted_amount', name: 'amount' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']], // Sort by date column
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
    // Initialize Flatpickr for date inputs
    const dateInputs = document.querySelectorAll('.date-picker');
    dateInputs.forEach(input => {
        const options = {
            dateFormat: 'Y-m-d',
            allowInput: true
        };
        
        // Apply date restriction based on settings
        if (!pageData.settings?.allowFutureDates) {
            options.maxDate = 'today';
        }
        
        flatpickr(input, options);
    });
    
    // Handle required attachment setting
    if (pageData.settings?.requireAttachments) {
        const attachmentInput = document.getElementById('attachment');
        if (attachmentInput) {
            attachmentInput.setAttribute('required', 'required');
            const label = attachmentInput.closest('.mb-3')?.querySelector('.form-label');
            if (label && !label.innerHTML.includes('*')) {
                label.innerHTML += ' <span class="text-danger">*</span>';
            }
        }
    }
    
    // Initialize Select2 for category dropdown
    $('.category-select').select2({
        placeholder: pageData.labels.selectCategory,
        allowClear: true,
        width: '100%',
        dropdownParent: $('#transactionFormOffcanvas')
    });
    
    // Initialize Tagify for tags input
    const tagsInput = document.querySelector('#tags');
    if (tagsInput) {
        tagify = new Tagify(tagsInput, {
            whitelist: [], // You can populate this with common tags
            dropdown: {
                maxItems: 20,
                classname: 'tags-look',
                enabled: 0,
                closeOnSelect: false
            },
            duplicates: false
        });
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Create new transaction
    $(document).on('click', '.create-transaction', function() {
        resetForm();
        showForm();
    });
    
    // Edit transaction
    $(document).on('click', '.edit-transaction', function() {
        const id = $(this).data('id');
        editTransaction(id);
    });
    
    // Delete transaction
    $(document).on('click', '.delete-transaction', function() {
        const id = $(this).data('id');
        deleteTransaction(id);
    });
    
    // Form submission
    $('#transactionForm').on('submit', function(e) {
        e.preventDefault();
        saveTransaction();
    });
    
    // Type change
    $('#type').on('change', function() {
        filterCategoriesByType($(this).val());
    });
    
    // File input change
    $('#attachment').on('change', function() {
        const fileName = this.files[0]?.name || '';
        const label = $(this).prev('.form-label');
        if (fileName) {
            label.html(`${pageData.labels.attachment || 'Attachment'} <span class="text-success">(${fileName})</span>`);
        } else {
            label.text(pageData.labels.attachment || 'Attachment');
        }
    });
}

/**
 * Show form offcanvas
 */
function showForm() {
    const offcanvasEl = document.getElementById('transactionFormOffcanvas');
    if (!formModal) {
        formModal = new bootstrap.Offcanvas(offcanvasEl, {
            backdrop: true,
            keyboard: true,
            scroll: false
        });
    }
    formModal.show();
}

/**
 * Hide form offcanvas
 */
function hideForm() {
    if (formModal) {
        formModal.hide();
    }
}

/**
 * Reset form
 */
function resetForm() {
    $('#transactionForm')[0].reset();
    $('#transactionId').val('');
    $('#transactionForm').find('input[name="_method"]').remove();
    $('#transactionForm').attr('action', pageData.urls.store);
    $('#offcanvasLabel').text(pageData.labels.addTransaction);
    $('.category-select').val(null).trigger('change');
    $('#attachment').prev('.form-label').text(pageData.labels.attachment || 'Attachment');
    
    // Reset category filtering - show all categories
    filterCategoriesByType('');
    
    // Clear tagify
    if (tagify) {
        tagify.removeAllTags();
    }
}

/**
 * Edit transaction
 */
function editTransaction(id) {
    // Close view offcanvas if it's open
    const viewOffcanvasEl = document.getElementById('transactionViewOffcanvas');
    const viewOffcanvas = bootstrap.Offcanvas.getInstance(viewOffcanvasEl);
    if (viewOffcanvas) {
        viewOffcanvas.hide();
    }
    
    const url = pageData.urls.show.replace(':id', id);
    
    $.get(url)
        .done(function(response) {
            if (response.status === 'success') {
                const transaction = response.data;
                
                // Reset form first
                resetForm();
                
                // Fill form
                $('#transactionId').val(id);
                $('#transactionForm').attr('action', pageData.urls.update.replace(':id', id));
                $('#transactionForm').append('<input type="hidden" name="_method" value="PUT">');
                $('#offcanvasLabel').text(pageData.labels.editTransaction);
                
                $('#type').val(transaction.type);
                
                // Filter categories by type BEFORE setting the value
                filterCategoriesByType(transaction.type);
                
                $('#amount').val(transaction.amount);
                $('#category_id').val(transaction.category_id).trigger('change');
                $('#description').val(transaction.description);
                
                // Fix date handling to prevent timezone conversion issues
                let transactionDate = transaction.transaction_date;
                if (transactionDate && transactionDate.includes('T')) {
                    // If it's a datetime, extract just the date part
                    transactionDate = transactionDate.split('T')[0];
                }
                $('#transaction_date').val(transactionDate);
                
                $('#reference_number').val(transaction.reference_number);
                $('#payment_method').val(transaction.payment_method);
                
                // Handle tags with Tagify
                if (transaction.tags && tagify) {
                    tagify.removeAllTags();
                    tagify.addTags(transaction.tags);
                }
                
                // Show existing attachment info
                const attachmentLabel = $('#attachment').prev('.form-label');
                if (transaction.files && transaction.files.length > 0) {
                    const fileInfo = transaction.files[0]; // Show first file
                    attachmentLabel.html(
                        `${pageData.labels.attachment || 'Attachment'} <span class="text-success">(${fileInfo.name})</span> <small class="text-muted">${fileInfo.size || ''}</small>`
                    );
                } else if (transaction.attachment_url) {
                    // Legacy attachment
                    const fileName = transaction.attachment_path ? transaction.attachment_path.split('/').pop() : 'Existing file';
                    attachmentLabel.html(
                        `${pageData.labels.attachment || 'Attachment'} <span class="text-success">(${fileName})</span> <small class="text-muted">(legacy)</small>`
                    );
                } else {
                    attachmentLabel.text(pageData.labels.attachment || 'Attachment');
                }
                
                // Wait a bit for the view offcanvas to fully close before opening edit form
                setTimeout(() => {
                    showForm();
                }, 300);
            }
        })
        .fail(function(xhr) {
            showError(xhr.responseJSON?.message || pageData.labels.errorOccurred);
        });
}

/**
 * Save transaction
 */
function saveTransaction() {
    const form = $('#transactionForm')[0];
    const formData = new FormData(form);
    const url = form.action;
    
    // Fix checkbox values
    formData.delete('is_recurring');
    formData.append('is_recurring', $('#is_recurring').is(':checked') ? '1' : '0');
    
    // Handle tags from Tagify
    formData.delete('tags');
    if (tagify) {
        const tags = tagify.value;
        if (tags && tags.length > 0) {
            tags.forEach(tag => {
                formData.append('tags[]', tag.value);
            });
        }
    }
    
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
                transactionsTable.ajax.reload();
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                let errorMessage = '';
                Object.values(errors).forEach(errorArray => {
                    errorArray.forEach(error => {
                        errorMessage += error + '<br>';
                    });
                });
                showError(errorMessage);
            } else {
                showError(xhr.responseJSON?.message || pageData.labels.errorOccurred);
            }
        }
    });
}

/**
 * Delete transaction
 */
function deleteTransaction(id) {
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
                        transactionsTable.ajax.reload();
                    }
                },
                error: function(xhr) {
                    showError(xhr.responseJSON?.message || pageData.labels.errorOccurred);
                }
            });
        }
    });
}

/**
 * Filter categories by type
 */
function filterCategoriesByType(type) {
    const categorySelect = $('#category_id');
    const currentValue = categorySelect.val();
    
    if (!type) {
        // Show all options if no type selected
        categorySelect.find('option').show();
        return;
    }
    
    // First, clear the current selection
    categorySelect.val(null).trigger('change');
    
    // Remove all options except the first (placeholder)
    categorySelect.find('option:not(:first)').remove();
    
    // Re-populate with filtered categories from pageData
    if (window.pageData && window.pageData.categories) {
        const filteredCategories = window.pageData.categories.filter(category => category.type === type);
        
        filteredCategories.forEach(category => {
            const option = new Option(category.name, category.id);
            option.setAttribute('data-type', category.type);
            categorySelect.append(option);
        });
    } else {
        // Fallback to the original method if pageData is not available
        categorySelect.find('option').each(function() {
            const optionType = $(this).data('type');
            if (!optionType || optionType === type) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    // Refresh Select2 if it's initialized
    if (categorySelect.hasClass('select2-hidden-accessible')) {
        categorySelect.trigger('change.select2');
    }
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
 * View transaction details
 */
function viewTransaction(id) {
    const url = pageData.urls.show.replace(':id', id);
    
    // Show offcanvas with loading spinner
    const offcanvasEl = document.getElementById('transactionViewOffcanvas');
    const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
    offcanvas.show();
    
    // Reset content to loading state
    $('#transactionViewContent').html(`
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">${pageData.labels.loading || 'Loading...'}</span>
            </div>
        </div>
    `);
    
    $.get(url)
        .done(function(response) {
            if (response.status === 'success') {
                const transaction = response.data;
                
                // Create offcanvas content
                let content = `
                    <div class="transaction-details">
                        <!-- Transaction Number and Status -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">${transaction.transaction_number || ''}</h6>
                            <span>${transaction.type_badge || transaction.type || ''}</span>
                        </div>
                        
                        <!-- Amount -->
                        <div class="text-center py-3 mb-3 bg-label-${transaction.type === 'income' ? 'success' : 'danger'} rounded">
                            <h3 class="mb-0">${transaction.formatted_amount || transaction.amount || '0'}</h3>
                        </div>
                        
                        <!-- Details Grid -->
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="text-muted small">${pageData.labels.date || 'Date'}</label>
                                <p class="mb-2">${transaction.formatted_date || transaction.transaction_date || '-'}</p>
                            </div>
                            
                            <div class="col-12">
                                <label class="text-muted small">${pageData.labels.category || 'Category'}</label>
                                <p class="mb-2">${transaction.category?.name || '-'}</p>
                            </div>
                            
                            <div class="col-12">
                                <label class="text-muted small">${pageData.labels.paymentMethod || 'Payment Method'}</label>
                                <p class="mb-2">${transaction.payment_method_badge || transaction.payment_method || '-'}</p>
                            </div>
                            
                            <div class="col-12">
                                <label class="text-muted small">${pageData.labels.reference || 'Reference Number'}</label>
                                <p class="mb-2">${transaction.reference_number || '-'}</p>
                            </div>
                            
                            <div class="col-12">
                                <label class="text-muted small">${pageData.labels.description || 'Description'}</label>
                                <p class="mb-2">${transaction.description || '-'}</p>
                            </div>
                        </div>
                `;
                
                // Add tags if present
                if (transaction.tags && transaction.tags.length > 0) {
                    content += `
                        <div class="mt-3">
                            <label class="text-muted small">${pageData.labels.tags || 'Tags'}</label>
                            <div class="mt-1">
                                ${transaction.tags.map(tag => `<span class="badge bg-label-secondary me-1">${tag}</span>`).join('')}
                            </div>
                        </div>
                    `;
                }
                
                // Add attachment links if present
                if (transaction.files && transaction.files.length > 0) {
                    // Display FileManagerCore files
                    content += `
                        <div class="mt-3">
                            <label class="text-muted small">${pageData.labels.attachment || 'Attachments'}</label>
                            <div class="mt-1">
                    `;
                    transaction.files.forEach(file => {
                        content += `
                            <div class="d-flex align-items-center mb-2">
                                <a href="${file.url}" target="_blank" class="btn btn-sm btn-label-primary me-2">
                                    <i class="bx bx-paperclip me-1"></i>${file.name || 'Attachment'}
                                </a>
                                <a href="${file.download_url}" class="btn btn-sm btn-label-secondary">
                                    <i class="bx bx-download"></i>
                                </a>
                                <small class="text-muted ms-2">${file.size || ''}</small>
                            </div>
                        `;
                    });
                    content += `</div></div>`;
                } else if (transaction.attachment_url) {
                    // Fallback to legacy attachment
                    content += `
                        <div class="mt-3">
                            <label class="text-muted small">${pageData.labels.attachment || 'Attachment'}</label>
                            <div class="mt-1">
                                <a href="${transaction.attachment_url}" target="_blank" class="btn btn-sm btn-label-primary">
                                    <i class="bx bx-paperclip me-1"></i>${pageData.labels.viewAttachment || 'View Attachment'}
                                </a>
                            </div>
                        </div>
                    `;
                }
                
                // Add created/updated info
                content += `
                    <hr class="my-3">
                    <div class="small text-muted">
                        ${transaction.created_by ? `<p class="mb-1">${pageData.labels.createdBy || 'Created by'}: ${transaction.created_by.name || '-'}</p>` : ''}
                        ${transaction.updated_by ? `<p class="mb-0">${pageData.labels.updatedBy || 'Updated by'}: ${transaction.updated_by.name || '-'}</p>` : ''}
                    </div>
                </div>
                `;
                
                // Add action buttons
                content += `
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-primary flex-fill" onclick="editTransaction(${transaction.id})">
                            <i class="bx bx-edit me-1"></i>${pageData.labels.edit || 'Edit'}
                        </button>
                        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">
                            ${pageData.labels.close || 'Close'}
                        </button>
                    </div>
                `;
                
                // Update offcanvas content
                $('#transactionViewContent').html(content);
            }
        })
        .fail(function(xhr) {
            $('#transactionViewContent').html(`
                <div class="alert alert-danger">
                    <i class="bx bx-error-circle me-1"></i>
                    ${xhr.responseJSON?.message || pageData.labels.errorOccurred || 'Error loading transaction'}
                </div>
                <button type="button" class="btn btn-label-secondary w-100 mt-3" data-bs-dismiss="offcanvas">
                    ${pageData.labels.close || 'Close'}
                </button>
            `);
        });
}

// Export functions for external use
window.AccountingCoreTransactions = {
    refreshTable: function() {
        if (transactionsTable) {
            transactionsTable.ajax.reload();
        }
    },
    viewTransaction,
    editTransaction,
    deleteTransaction
};