/**
 * WMS Inventory - Sales Orders Management
 * Handles sales order listing, creation, editing, approval, and fulfillment
 */

$(function () {
    'use strict';

    // CSRF setup for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize DataTable if exists
    if ($('#salesTable').length) {
        initializeSalesDataTable();
    }

    // Initialize form handlers
    initializeSaleForm();
    initializeActionButtons();
});

/**
 * Initialize sales DataTable
 */
function initializeSalesDataTable() {
    const table = $('#salesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            type: 'GET',
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.customer_id = $('#customerFilter').val();
                d.warehouse_id = $('#warehouseFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'customer', name: 'customer.name', orderable: false },
            { data: 'warehouse', name: 'warehouse.name' },
            { data: 'sale_date', name: 'sale_date' },
            { data: 'expected_delivery_date', name: 'expected_delivery_date' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'payment_status', name: 'payment_status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            search: pageData.labels.search,
            lengthMenu: pageData.labels.lengthMenu,
            info: pageData.labels.info,
            paginate: {
                previous: pageData.labels.previous,
                next: pageData.labels.next
            }
        }
    });

    // Initialize Select2 for filters
    $('#statusFilter, #customerFilter, #warehouseFilter').select2({
        allowClear: true,
        width: '100%'
    });

    // Filter handlers
    $('#statusFilter, #customerFilter, #warehouseFilter').on('change', function () {
        table.draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function () {
        $('#statusFilter, #customerFilter, #warehouseFilter').val('').trigger('change');
        table.draw();
    });

    return table;
}

/**
 * Initialize sale order form handlers
 */
function initializeSaleForm() {
    // Customer selection handler
    $('#customer_id').on('change', function () {
        const customerId = $(this).val();
        updateCustomerDetails(customerId);
        updateProductOptions(customerId);
    });

    // Product repeater initialization
    if ($('.sale-products-repeater').length) {
        initializeProductRepeater();
    }

    // Form submission handler
    $('#saleForm').on('submit', function (e) {
        e.preventDefault();
        
        if (validateSaleForm()) {
            submitSaleForm();
        }
    });

    // Calculate totals when product data changes
    $(document).on('input change', '.product-quantity, .product-price, .product-tax, .product-discount, #tax_amount, #discount_amount, #shipping_cost', function () {
        calculateTotals();
    });

    // Date picker initialization
    if ($('.flatpickr-date').length) {
        $('.flatpickr-date').flatpickr({
            dateFormat: 'Y-m-d'
        });
    }
}

/**
 * Initialize product repeater for sale form
 */
function initializeProductRepeater() {
    $('.sale-products-repeater').repeater({
        initEmpty: false,
        defaultValues: {
            'product_id': '',
            'quantity': 1,
            'unit_price': 0,
            'tax_rate': 0,
            'discount_rate': 0,
            'batch_number': '',
            'expiry_date': ''
        },
        show: function () {
            $(this).slideDown();
            
            // Initialize product select for new row
            const productSelect = $(this).find('.product-select');
            initializeProductSelect(productSelect);
            
            // Initialize date picker for expiry date
            const expiryInput = $(this).find('.expiry-date');
            if (expiryInput.length) {
                expiryInput.flatpickr({
                    dateFormat: 'Y-m-d',
                    minDate: 'today'
                });
            }
            
            calculateTotals();
        },
        hide: function (deleteElement) {
            if (confirm(pageData.labels.confirmRemoveProduct)) {
                $(this).slideUp(deleteElement);
                setTimeout(calculateTotals, 500);
            }
        }
    });

    // Initialize existing product selects
    $('.product-select').each(function () {
        initializeProductSelect($(this));
    });
}

/**
 * Initialize product select dropdown
 */
function initializeProductSelect(selectElement) {
    selectElement.select2({
        placeholder: pageData.labels.selectProduct,
        allowClear: true,
        ajax: {
            url: pageData.urls.customerProducts,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    customer_id: $('#customer_id').val(),
                    warehouse_id: $('#warehouse_id').val(),
                    limit: 20
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        templateResult: function (product) {
            if (product.loading) {
                return product.text;
            }
            
            return $(`
                <div class="product-option">
                    <div class="fw-semibold">${product.name}</div>
                    <small class="text-muted">${product.code} | ${pageData.labels.unit}: ${product.unit || 'N/A'} | ${pageData.labels.stock}: ${product.current_stock || 0}</small>
                </div>
            `);
        },
        templateSelection: function (product) {
            return product.name || product.text;
        }
    });

    // Handle product selection
    selectElement.on('select2:select', function (e) {
        const product = e.params.data;
        const row = $(this).closest('.repeater-item');
        
        // Set default unit price
        if (product.selling_price) {
            row.find('.product-price').val(product.selling_price);
        }
        
        // Show available quantity
        if (product.current_stock !== undefined) {
            row.find('.available-qty').text(pageData.labels.available + ': ' + product.current_stock);
        }
        
        // Show/hide batch and expiry fields
        const batchField = row.find('.batch-field');
        const expiryField = row.find('.expiry-field');
        
        if (product.track_batch) {
            batchField.show();
        } else {
            batchField.hide();
        }
        
        if (product.track_expiry) {
            expiryField.show();
        } else {
            expiryField.hide();
        }
        
        calculateTotals();
    });
}

/**
 * Update customer details in the form
 */
function updateCustomerDetails(customerId) {
    if (!customerId) {
        $('#customer-details').hide();
        return;
    }

    // This would typically fetch customer details via AJAX
    // For now, we'll just show the customer details section
    $('#customer-details').show();
}

/**
 * Update product options based on selected customer
 */
function updateProductOptions(customerId) {
    // Clear existing product selections
    $('.product-select').val(null).trigger('change');
    
    // Product select will automatically filter by customer when user searches
    // due to the AJAX configuration in initializeProductSelect
}

/**
 * Calculate totals for sale order
 */
function calculateTotals() {
    let subtotal = 0;
    
    // Calculate line totals
    $('.repeater-item').each(function () {
        const row = $(this);
        const quantity = parseFloat(row.find('.product-quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.product-price').val()) || 0;
        const taxRate = parseFloat(row.find('.product-tax').val()) || 0;
        const discountRate = parseFloat(row.find('.product-discount').val()) || 0;
        
        // Calculate line subtotal
        let lineTotal = quantity * unitPrice;
        const discountAmount = lineTotal * (discountRate / 100);
        lineTotal -= discountAmount;
        const taxAmount = lineTotal * (taxRate / 100);
        lineTotal += taxAmount;
        
        // Display line total
        row.find('.line-total').text(formatCurrency(lineTotal));
        
        subtotal += lineTotal;
    });
    
    // Apply global adjustments
    const globalTax = parseFloat($('#tax_amount').val()) || 0;
    const globalDiscount = parseFloat($('#discount_amount').val()) || 0;
    const shippingCost = parseFloat($('#shipping_cost').val()) || 0;
    
    const total = subtotal + globalTax + shippingCost - globalDiscount;
    
    // Update totals display
    $('#subtotal-display').text(formatCurrency(subtotal));
    $('#total-display').text(formatCurrency(total));
    
    // Update hidden fields if they exist
    $('#subtotal_calculated').val(subtotal.toFixed(2));
    $('#total_calculated').val(total.toFixed(2));
}

/**
 * Validate sale order form
 */
function validateSaleForm() {
    let isValid = true;
    const errors = [];
    
    // Check required fields
    if (!$('#customer_id').val()) {
        errors.push(pageData.labels.customerRequired);
        isValid = false;
    }
    
    if (!$('#warehouse_id').val()) {
        errors.push(pageData.labels.warehouseRequired);
        isValid = false;
    }
    
    if (!$('#date').val()) {
        errors.push(pageData.labels.dateRequired);
        isValid = false;
    }
    
    // Check if at least one product is added
    const productRows = $('.repeater-item').length;
    if (productRows === 0) {
        errors.push(pageData.labels.productsRequired);
        isValid = false;
    }
    
    // Validate product rows
    let hasValidProducts = false;
    $('.repeater-item').each(function () {
        const row = $(this);
        const productId = row.find('.product-select').val();
        const quantity = parseFloat(row.find('.product-quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.product-price').val()) || 0;
        
        if (productId && quantity > 0 && unitPrice >= 0) {
            hasValidProducts = true;
        }
    });
    
    if (!hasValidProducts) {
        errors.push(pageData.labels.validProductsRequired);
        isValid = false;
    }
    
    // Show errors if any
    if (!isValid) {
        showAlert('error', errors.join('<br>'));
    }
    
    return isValid;
}

/**
 * Submit sale order form
 */
function submitSaleForm() {
    const formData = new FormData($('#saleForm')[0]);
    const submitBtn = $('#saleForm button[type="submit"]');
    
    // Show loading state
    submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');
    
    $.ajax({
        url: $('#saleForm').attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            showAlert('success', pageData.labels.saleCreatedSuccess);
            // Redirect will be handled by the backend
        },
        error: function (xhr) {
            let errorMessage = pageData.labels.saleCreateError;
            
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat();
                errorMessage = errors.join('<br>');
            }
            
            showAlert('error', errorMessage);
        },
        complete: function () {
            submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
        }
    });
}

/**
 * Initialize action buttons for sale management
 */
function initializeActionButtons() {
    // Global action buttons
    window.approveRecord = function (id) {
        Swal.fire({
            title: pageData.labels.confirmApprove,
            text: pageData.labels.approveConfirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: pageData.labels.approve,
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-success me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                executeApproval(id);
            }
        });
    };

    window.rejectRecord = function (id) {
        Swal.fire({
            title: pageData.labels.rejectSale,
            text: pageData.labels.rejectConfirmText,
            input: 'textarea',
            inputLabel: pageData.labels.rejectionReason,
            inputPlaceholder: pageData.labels.enterRejectionReason,
            inputValidator: (value) => {
                if (!value) {
                    return pageData.labels.rejectionReasonRequired;
                }
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.reject,
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                executeRejection(id, result.value);
            }
        });
    };

    window.fulfillAllRecord = function (id) {
        Swal.fire({
            title: pageData.labels.fulfillAll,
            text: pageData.labels.fulfillAllConfirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: pageData.labels.fulfillAll,
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-success me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                executeFulfillAll(id);
            }
        });
    };

    window.fulfillPartialRecord = function (id) {
        // This would open a modal or redirect to partial fulfillment page
        window.location.href = pageData.urls.partialFulfill.replace(':id', id);
    };

    window.duplicateRecord = function (id) {
        Swal.fire({
            title: pageData.labels.duplicateSale,
            text: pageData.labels.duplicateConfirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: pageData.labels.duplicate,
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-info me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                executeDuplication(id);
            }
        });
    };

    window.deleteRecord = function (id) {
        Swal.fire({
            title: pageData.labels.deleteSale,
            text: pageData.labels.deleteConfirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.delete,
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                executeDeletion(id);
            }
        });
    };
    
    window.deliverRecord = function (id) {
        Swal.fire({
            title: pageData.labels.confirmDeliver || 'Mark as Delivered',
            text: pageData.labels.deliverConfirmText || 'Are you sure you want to mark this sales order as delivered?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: pageData.labels.deliver || 'Mark Delivered',
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-success me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                executeDelivery(id);
            }
        });
    };
    
    window.fulfillRecord = function (id) {
        // Redirect to the fulfillment page
        window.location.href = pageData.urls.fulfillAll.replace(':id', id);
    };
    
    window.shipRecord = function (id) {
        Swal.fire({
            title: pageData.labels.confirmShip || 'Ship Order',
            text: pageData.labels.shipConfirmText || 'Are you sure you want to mark this sales order as shipped?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: pageData.labels.ship || 'Ship Order',
            cancelButtonText: pageData.labels.cancel,
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                executeShipping(id);
            }
        });
    };
}

/**
 * Execute sale order approval
 */
function executeApproval(id) {
    $.ajax({
        url: pageData.urls.approve.replace(':id', id),
        type: 'POST',
        success: function (response) {
            if (response.status === 'success') {
                showAlert('success', response.data.message);
                
                // Refresh DataTable if exists
                if ($.fn.DataTable.isDataTable('#salesTable')) {
                    $('#salesTable').DataTable().ajax.reload(null, false);
                } else {
                    // Refresh page
                    window.location.reload();
                }
            } else {
                showAlert('error', response.data);
            }
        },
        error: function (xhr) {
            const errorMessage = xhr.responseJSON?.data || pageData.labels.approveError;
            showAlert('error', errorMessage);
        }
    });
}

/**
 * Execute sale order rejection
 */
function executeRejection(id, reason) {
    $.ajax({
        url: pageData.urls.reject.replace(':id', id),
        type: 'POST',
        data: {
            rejection_reason: reason
        },
        success: function (response) {
            if (response.status === 'success') {
                showAlert('success', response.data.message);
                
                // Refresh DataTable if exists
                if ($.fn.DataTable.isDataTable('#salesTable')) {
                    $('#salesTable').DataTable().ajax.reload(null, false);
                } else {
                    window.location.reload();
                }
            } else {
                showAlert('error', response.data);
            }
        },
        error: function (xhr) {
            const errorMessage = xhr.responseJSON?.data || pageData.labels.rejectError;
            showAlert('error', errorMessage);
        }
    });
}

/**
 * Execute fulfill all items
 */
function executeFulfillAll(id) {
    $.ajax({
        url: pageData.urls.fulfillAll.replace(':id', id),
        type: 'POST',
        success: function (response) {
            if (response.status === 'success') {
                showAlert('success', response.data.message);
                
                // Refresh DataTable if exists
                if ($.fn.DataTable.isDataTable('#salesTable')) {
                    $('#salesTable').DataTable().ajax.reload(null, false);
                } else {
                    window.location.reload();
                }
            } else {
                showAlert('error', response.data);
            }
        },
        error: function (xhr) {
            const errorMessage = xhr.responseJSON?.data || pageData.labels.fulfillError;
            showAlert('error', errorMessage);
        }
    });
}

/**
 * Execute sale order duplication
 */
function executeDuplication(id) {
    $.ajax({
        url: pageData.urls.duplicate.replace(':id', id),
        type: 'POST',
        success: function (response) {
            if (response.status === 'success') {
                // Show success message with Swal
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.duplicateSuccess || 'Success!',
                    text: response.data.message || 'Sales order duplicated successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Redirect to the view page of the new duplicated sale order
                    if (response.data.sale && response.data.sale.id) {
                        window.location.href = pageData.urls.show.replace(':id', response.data.sale.id);
                    } else if (response.data.id) {
                        window.location.href = pageData.urls.show.replace(':id', response.data.id);
                    } else {
                        // Refresh DataTable if exists
                        if ($('#salesTable').length) {
                            $('#salesTable').DataTable().ajax.reload();
                        }
                    }
                });
            } else {
                showAlert('error', response.message || response.data || 'Failed to duplicate sales order');
            }
        },
        error: function (xhr) {
            handleError(xhr, 'Failed to duplicate sales order');
        }
    });
}

/**
 * Execute sale order deletion
 */
function executeDeletion(id) {
    $.ajax({
        url: pageData.urls.destroy.replace(':id', id),
        type: 'DELETE',
        success: function (response) {
            if (response.status === 'success') {
                showAlert('success', response.data);
                
                // Refresh DataTable if exists
                if ($.fn.DataTable.isDataTable('#salesTable')) {
                    $('#salesTable').DataTable().ajax.reload(null, false);
                } else {
                    window.location.reload();
                }
            } else {
                showAlert('error', response.data);
            }
        },
        error: function (xhr) {
            const errorMessage = xhr.responseJSON?.data || pageData.labels.deleteError;
            showAlert('error', errorMessage);
        }
    });
}

/**
 * Execute sale order delivery
 */
function executeDelivery(id) {
    $.ajax({
        url: pageData.urls.deliver ? pageData.urls.deliver.replace(':id', id) : '/inventory/sales/' + id + '/deliver',
        type: 'POST',
        success: function (response) {
            if (response.status === 'success') {
                // Show success message with Swal
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.deliveredSuccess || 'Delivered!',
                    text: response.data.message || response.data || 'Sales order has been marked as delivered successfully',
                    confirmButtonText: pageData.labels.ok || 'OK',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    },
                    buttonsStyling: false
                }).then(() => {
                    // Refresh DataTable if exists
                    if ($.fn.DataTable.isDataTable('#salesTable')) {
                        $('#salesTable').DataTable().ajax.reload(null, false);
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error || 'Error!',
                    text: response.message || response.data || 'Failed to mark sales order as delivered',
                    confirmButtonText: pageData.labels.ok || 'OK',
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                });
            }
        },
        error: function (xhr) {
            const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.data || 'Failed to mark sales order as delivered';
            Swal.fire({
                icon: 'error',
                title: pageData.labels.error || 'Error!',
                text: errorMessage,
                confirmButtonText: pageData.labels.ok || 'OK',
                customClass: {
                    confirmButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });
        }
    });
}

/**
 * Execute sale order shipping
 */
function executeShipping(id) {
    $.ajax({
        url: pageData.urls.ship ? pageData.urls.ship.replace(':id', id) : '/inventory/sales/' + id + '/ship',
        type: 'POST',
        success: function (response) {
            if (response.status === 'success') {
                // Show success message with Swal
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.shippedSuccess || 'Shipped!',
                    text: response.data.message || response.data || 'Sales order has been marked as shipped successfully',
                    confirmButtonText: pageData.labels.ok || 'OK',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                }).then(() => {
                    // Refresh DataTable if exists
                    if ($.fn.DataTable.isDataTable('#salesTable')) {
                        $('#salesTable').DataTable().ajax.reload(null, false);
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error || 'Error!',
                    text: response.message || response.data || 'Failed to mark sales order as shipped',
                    confirmButtonText: pageData.labels.ok || 'OK',
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                });
            }
        },
        error: function (xhr) {
            const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.data || 'Failed to mark sales order as shipped';
            Swal.fire({
                icon: 'error',
                title: pageData.labels.error || 'Error!',
                text: errorMessage,
                confirmButtonText: pageData.labels.ok || 'OK',
                customClass: {
                    confirmButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });
        }
    });
}

/**
 * Utility function to format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(amount);
}

/**
 * Utility function to show alerts
 */
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="bx ${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the page
    $('main .container-fluid, main .container-xxl').first().prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
    
    // Scroll to top to show alert
    $('html, body').animate({ scrollTop: 0 }, 500);
}