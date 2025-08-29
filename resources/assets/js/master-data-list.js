/**
 * Master Data List
 */

'use strict';

$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  // Initialize list functionality
  initializeList();
});

/**
 * Initialize list functionality
 */
function initializeList() {
  // Initialize search functionality
  initializeSearch();
  
  // Initialize filters
  initializeFilters();
  
  // Initialize bulk actions
  initializeBulkActions();
  
  // Initialize export functionality
  initializeExport();
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
  let searchTimeout;
  
  $('#customSearch, .table-search').on('input', function () {
    const searchTerm = $(this).val();
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      if (typeof window.dataTable !== 'undefined') {
        window.dataTable.search(searchTerm).draw();
      }
    }, 300);
  });
  
  // Clear search
  $('.clear-search').on('click', function () {
    $('#customSearch, .table-search').val('');
    if (typeof window.dataTable !== 'undefined') {
      window.dataTable.search('').draw();
    }
  });
}

/**
 * Initialize filters
 */
function initializeFilters() {
  $('.table-filter').on('change', function () {
    const column = $(this).data('column');
    const value = $(this).val();
    
    if (typeof window.dataTable !== 'undefined' && typeof column !== 'undefined') {
      window.dataTable.column(column).search(value).draw();
    }
  });
  
  // Clear all filters
  $('.clear-filters').on('click', function () {
    $('.table-filter').val('').trigger('change');
    if (typeof window.dataTable !== 'undefined') {
      window.dataTable.search('').columns().search('').draw();
    }
  });
}

/**
 * Initialize bulk actions
 */
function initializeBulkActions() {
  // Select all checkbox
  $(document).on('change', '#selectAll', function () {
    const isChecked = $(this).is(':checked');
    $('.row-select').prop('checked', isChecked);
    updateBulkActionsVisibility();
  });
  
  // Individual row selection
  $(document).on('change', '.row-select', function () {
    updateBulkActionsVisibility();
    
    // Update select all checkbox state
    const totalRows = $('.row-select').length;
    const selectedRows = $('.row-select:checked').length;
    
    $('#selectAll').prop('indeterminate', selectedRows > 0 && selectedRows < totalRows);
    $('#selectAll').prop('checked', selectedRows === totalRows);
  });
  
  // Bulk action execution
  $('.bulk-action').on('click', function (e) {
    e.preventDefault();
    
    const action = $(this).data('action');
    const selectedIds = getSelectedIds();
    
    if (selectedIds.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Selection',
        text: 'Please select at least one item to perform bulk action.'
      });
      return;
    }
    
    executeBulkAction(action, selectedIds);
  });
}

/**
 * Update bulk actions visibility
 */
function updateBulkActionsVisibility() {
  const selectedCount = $('.row-select:checked').length;
  
  if (selectedCount > 0) {
    $('#bulkActionsContainer').slideDown();
    $('#selectedCount').text(selectedCount);
  } else {
    $('#bulkActionsContainer').slideUp();
  }
}

/**
 * Get selected record IDs
 */
function getSelectedIds() {
  const selectedIds = [];
  $('.row-select:checked').each(function () {
    selectedIds.push($(this).val());
  });
  return selectedIds;
}

/**
 * Execute bulk action
 */
function executeBulkAction(action, selectedIds) {
  let confirmTitle = 'Confirm Bulk Action';
  let confirmText = `Are you sure you want to ${action} ${selectedIds.length} selected items?`;
  let confirmButtonText = 'Yes, proceed';
  let confirmButtonColor = '#3085d6';
  
  if (action === 'delete') {
    confirmTitle = 'Confirm Bulk Delete';
    confirmText = `Are you sure you want to delete ${selectedIds.length} selected items? This action cannot be undone.`;
    confirmButtonText = 'Yes, delete';
    confirmButtonColor = '#d33';
  }
  
  Swal.fire({
    title: confirmTitle,
    text: confirmText,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: confirmButtonColor,
    cancelButtonColor: '#6c757d',
    confirmButtonText: confirmButtonText,
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      performBulkAction(action, selectedIds);
    }
  });
}

/**
 * Perform bulk action via AJAX
 */
function performBulkAction(action, selectedIds) {
  const $button = $(`.bulk-action[data-action="${action}"]`);
  const originalHtml = $button.html();
  
  // Show loading state
  $button.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
  $button.prop('disabled', true);
  
  $.ajax({
    url: pageData.urls.bulkAction || (pageData.urls.index + '/bulk-action'),
    method: 'POST',
    data: {
      action: action,
      ids: selectedIds,
      _token: $('meta[name="csrf-token"]').attr('content')
    },
    success: function (response) {
      if (response.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message,
          timer: 3000,
          showConfirmButton: false
        });
        
        // Refresh table
        if (typeof window.dataTable !== 'undefined') {
          window.dataTable.ajax.reload(null, false);
        }
        
        // Clear selections
        clearSelections();
      }
    },
    error: function (xhr) {
      const errorMessage = xhr.responseJSON?.message || 'An error occurred while processing the bulk action.';
      
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: errorMessage
      });
    },
    complete: function () {
      // Restore button state
      $button.html(originalHtml);
      $button.prop('disabled', false);
    }
  });
}

/**
 * Clear all selections
 */
function clearSelections() {
  $('.row-select, #selectAll').prop('checked', false);
  $('#selectAll').prop('indeterminate', false);
  updateBulkActionsVisibility();
}

/**
 * Initialize export functionality
 */
function initializeExport() {
  $('.export-data').on('click', function (e) {
    e.preventDefault();
    
    const format = $(this).data('format') || 'excel';
    const url = $(this).attr('href') || (pageData.urls.export || pageData.urls.index + '/export');
    
    // Add format parameter to URL
    const exportUrl = new URL(url, window.location.origin);
    exportUrl.searchParams.set('format', format);
    
    // Add current filters to export
    if (typeof window.dataTable !== 'undefined') {
      const searchValue = window.dataTable.search();
      if (searchValue) {
        exportUrl.searchParams.set('search', searchValue);
      }
    }
    
    // Show loading state
    const $button = $(this);
    const originalHtml = $button.html();
    $button.html('<span class="spinner-border spinner-border-sm me-2"></span>Exporting...');
    $button.prop('disabled', true);
    
    // Create hidden form to submit export request
    const form = $('<form>', {
      method: 'POST',
      action: exportUrl.href,
      style: 'display: none;'
    });
    
    form.append($('<input>', {
      type: 'hidden',
      name: '_token',
      value: $('meta[name="csrf-token"]').attr('content')
    }));
    
    $('body').append(form);
    form.submit();
    
    // Restore button state after a delay
    setTimeout(() => {
      $button.html(originalHtml);
      $button.prop('disabled', false);
      form.remove();
    }, 2000);
  });
}

/**
 * Refresh table data
 */
function refreshTable() {
  if (typeof window.dataTable !== 'undefined') {
    window.dataTable.ajax.reload(null, false);
  }
}

/**
 * Filter table by column value
 */
function filterByColumn(column, value) {
  if (typeof window.dataTable !== 'undefined') {
    window.dataTable.column(column).search(value).draw();
  }
}

/**
 * Reset all table filters
 */
function resetFilters() {
  $('.table-filter').val('').trigger('change');
  $('#customSearch, .table-search').val('');
  
  if (typeof window.dataTable !== 'undefined') {
    window.dataTable.search('').columns().search('').draw();
  }
}

// Export functions for use in other scripts
window.refreshTable = refreshTable;
window.filterByColumn = filterByColumn;
window.resetFilters = resetFilters;
window.getSelectedIds = getSelectedIds;
window.clearSelections = clearSelections;