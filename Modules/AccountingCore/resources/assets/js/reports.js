/**
 * AccountingCore Reports JavaScript
 */

// Global variables
let currentReportData = null;

// Initialize on DOM ready
$(document).ready(function() {
    console.log('AccountingCore Reports: Initializing...');
    
    // Initialize form elements
    initializeFormElements();
    
    // Setup event listeners
    setupEventListeners();
    
    // Initialize with default date range
    setDefaultDateRange();
});

/**
 * Initialize form elements
 */
function initializeFormElements() {
    // Initialize date range picker
    flatpickr('#dateRange', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        maxDate: 'today',
        locale: {
            rangeSeparator: ' to '
        }
    });
    
    // Initialize category select
    $('#categoryFilter').select2({
        placeholder: pageData.labels.selectCategory || 'Select Category',
        allowClear: true,
        width: '100%'
    });
}

/**
 * Set default date range (current month)
 */
function setDefaultDateRange() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    const dateRangePicker = document.querySelector('#dateRange')._flatpickr;
    if (dateRangePicker) {
        dateRangePicker.setDate([firstDay, lastDay]);
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Handle report generation
    $('#reportFilters').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
    
    // Handle report type change
    $('#reportType').on('change', function() {
        const reportType = $(this).val();
        if (reportType === 'category') {
            $('#categoryFilter').closest('.col-md-3').show();
        } else {
            $('#categoryFilter').closest('.col-md-3').hide();
            $('#categoryFilter').val('').trigger('change');
        }
    });
    
    // Print button
    $(document).on('click', '.print-report', function() {
        printReport();
    });
    
    // Export button
    $(document).on('click', '.export-report', function() {
        exportReport();
    });
}

/**
 * Generate report
 */
function generateReport() {
    const dateRange = $('#dateRange').val();
    if (!dateRange) {
        showError(pageData.labels.selectDateRange || 'Please select a date range');
        return;
    }
    
    const formData = {
        dateRange: dateRange,
        reportType: $('#reportType').val(),
        categoryFilter: $('#categoryFilter').val()
    };
    
    // Show loading state
    showLoading();
    
    $.ajax({
        url: pageData.urls.generate,
        type: 'POST',
        data: {
            ...formData,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                currentReportData = response.data;
                displayReport(response.data);
            }
        },
        error: function(xhr) {
            hideLoading();
            showError(extractErrorMessage(xhr));
        }
    });
}

/**
 * Display report
 */
function displayReport(data) {
    // Hide loading
    hideLoading();
    
    // Show cards and table
    $('#summaryCards').fadeIn();
    $('#reportCard').fadeIn();
    
    // Update summary cards
    $('#totalIncome').text(formatCurrency(data.summary.income));
    $('#totalExpenses').text(formatCurrency(data.summary.expenses));
    $('#netBalance').text(formatCurrency(data.summary.balance));
    
    // Update balance color
    const $netBalance = $('#netBalance');
    $netBalance.removeClass('text-success text-danger text-muted');
    if (data.summary.balance > 0) {
        $netBalance.addClass('text-success');
    } else if (data.summary.balance < 0) {
        $netBalance.addClass('text-danger');
    } else {
        $netBalance.addClass('text-muted');
    }
    
    // Update report title
    $('#reportTitle').text(data.title);
    
    // Build table
    buildReportTable(data);
}

/**
 * Build report table
 */
function buildReportTable(data) {
    const $thead = $('#reportTableHead');
    const $tbody = $('#reportTableBody');
    const $tfoot = $('#reportTableFoot');
    
    // Clear existing content
    $thead.empty();
    $tbody.empty();
    $tfoot.empty();
    
    // Build header
    let headerRow = '<tr>';
    data.columns.forEach(col => {
        headerRow += `<th>${col}</th>`;
    });
    headerRow += '</tr>';
    $thead.html(headerRow);
    
    // Build body
    if (data.rows && data.rows.length > 0) {
        data.rows.forEach(row => {
            let rowHtml = '<tr>';
            row.forEach((cell, index) => {
                if (index === row.length - 1 && typeof cell === 'number') {
                    // Format amount columns
                    const cssClass = cell >= 0 ? 'text-success' : 'text-danger';
                    rowHtml += `<td class="${cssClass} text-end">${formatCurrency(Math.abs(cell))}</td>`;
                } else if (typeof cell === 'number') {
                    // Format other numeric columns
                    rowHtml += `<td class="text-end">${formatCurrency(cell)}</td>`;
                } else {
                    rowHtml += `<td>${cell}</td>`;
                }
            });
            rowHtml += '</tr>';
            $tbody.append(rowHtml);
        });
    } else {
        $tbody.html(`<tr><td colspan="${data.columns.length}" class="text-center text-muted">${pageData.labels.noData || 'No data found'}</td></tr>`);
    }
    
    // Build footer if totals exist
    if (data.totals && data.totals.length > 0) {
        let footerRow = '<tr class="fw-bold table-active">';
        data.totals.forEach((total, index) => {
            if (index === data.totals.length - 1 && typeof total === 'number') {
                const cssClass = total >= 0 ? 'text-success' : 'text-danger';
                footerRow += `<td class="${cssClass} text-end">${formatCurrency(Math.abs(total))}</td>`;
            } else if (typeof total === 'number') {
                footerRow += `<td class="text-end">${formatCurrency(total)}</td>`;
            } else {
                footerRow += `<td>${total}</td>`;
            }
        });
        footerRow += '</tr>';
        $tfoot.html(footerRow);
    }
}

/**
 * Show loading state
 */
function showLoading() {
    // Hide results
    $('#summaryCards').hide();
    $('#reportCard').hide();
    
    // Show loading spinner
    Swal.fire({
        title: pageData.labels.generatingReport || 'Generating Report...',
        html: pageData.labels.pleaseWait || 'Please wait while we generate your report',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Hide loading state
 */
function hideLoading() {
    Swal.close();
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    // You can customize this based on your currency settings
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

/**
 * Print report
 */
function printReport() {
    if (!currentReportData) {
        showError(pageData.labels.noReportToExport || 'Please generate a report first');
        return;
    }
    
    window.print();
}

/**
 * Export report
 */
function exportReport() {
    if (!currentReportData) {
        showError(pageData.labels.noReportToExport || 'Please generate a report first');
        return;
    }
    
    const formData = {
        dateRange: $('#dateRange').val(),
        reportType: $('#reportType').val(),
        categoryFilter: $('#categoryFilter').val()
    };
    
    const params = new URLSearchParams(formData);
    window.location.href = pageData.urls.export + '?' + params.toString();
}

/**
 * Extract error message from response
 */
function extractErrorMessage(xhr) {
    const response = xhr.responseJSON;
    let errorMessage = pageData.labels.errorOccurred || 'Something went wrong';
    
    if (response) {
        if (response.errors) {
            errorMessage = '';
            Object.values(response.errors).forEach(errorArray => {
                errorArray.forEach(error => {
                    errorMessage += error + '<br>';
                });
            });
        } else if (response.data && typeof response.data === 'string') {
            errorMessage = response.data;
        } else if (response.message) {
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
        title: pageData.labels.success || 'Success',
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
        title: pageData.labels.error || 'Error',
        html: message
    });
}

// Export functions for external use
window.AccountingCoreReports = {
    generateReport,
    printReport,
    exportReport
};