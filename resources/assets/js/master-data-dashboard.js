/**
 * Master Data Dashboard
 */

'use strict';

$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  // Initialize dashboard functionality
  initializeDashboard();
});

/**
 * Initialize dashboard functionality
 */
function initializeDashboard() {
  // Add smooth scrolling to section links
  $('a[href^="#"]').on('click', function (e) {
    e.preventDefault();
    const target = $(this.getAttribute('href'));
    if (target.length) {
      $('html, body').animate({
        scrollTop: target.offset().top - 100
      }, 500);
    }
  });

  // Add hover effects to master data cards
  $('.card').hover(
    function () {
      $(this).addClass('shadow-lg').css('transform', 'translateY(-2px)');
    },
    function () {
      $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
    }
  );

  // Initialize tooltips for action buttons
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Add loading states for navigation links
  $('a[href]:not([href^="#"]):not([href^="javascript:"])').on('click', function () {
    const $this = $(this);
    const originalHtml = $this.html();
    
    // Don't add loading state to dropdown items or if already loading
    if ($this.hasClass('dropdown-item') || $this.find('.spinner-border').length) {
      return;
    }
    
    $this.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...');
    
    // Reset after 5 seconds in case navigation fails
    setTimeout(() => {
      $this.html(originalHtml);
    }, 5000);
  });

  // Initialize search functionality if search input exists
  const $searchInput = $('#masterDataSearch');
  if ($searchInput.length) {
    $searchInput.on('input', function () {
      const searchTerm = $(this).val().toLowerCase();
      filterMasterDataItems(searchTerm);
    });
  }

  // Initialize statistics counters animation
  animateCounters();
}

/**
 * Filter master data items based on search term
 */
function filterMasterDataItems(searchTerm) {
  $('.master-data-item').each(function () {
    const $item = $(this);
    const itemText = $item.text().toLowerCase();
    
    if (itemText.includes(searchTerm)) {
      $item.show();
    } else {
      $item.hide();
    }
  });
  
  // Hide/show sections based on visible items
  $('.master-data-section').each(function () {
    const $section = $(this);
    const visibleItems = $section.find('.master-data-item:visible').length;
    
    if (visibleItems > 0) {
      $section.show();
    } else {
      $section.hide();
    }
  });
}

/**
 * Animate statistics counters
 */
function animateCounters() {
  $('.counter').each(function () {
    const $this = $(this);
    const countTo = parseInt($this.text().replace(/,/g, ''));
    
    $({ countNum: 0 }).animate({
      countNum: countTo
    }, {
      duration: 2000,
      easing: 'swing',
      step: function () {
        $this.text(Math.floor(this.countNum).toLocaleString());
      },
      complete: function () {
        $this.text(countTo.toLocaleString());
      }
    });
  });
}

/**
 * Show confirmation dialog for bulk operations
 */
function confirmBulkOperation(operation, count) {
  return Swal.fire({
    title: pageData.labels.confirmBulkOperation || 'Confirm Bulk Operation',
    text: `Are you sure you want to ${operation} ${count} items?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, proceed',
    cancelButtonText: 'Cancel'
  });
}

/**
 * Handle import/export operations
 */
function handleImportExport(type, format) {
  const $button = $(`[data-operation="${type}"]`);
  const originalHtml = $button.html();
  
  // Show loading state
  $button.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
  $button.prop('disabled', true);
  
  // Simulate operation (replace with actual implementation)
  setTimeout(() => {
    $button.html(originalHtml);
    $button.prop('disabled', false);
    
    Swal.fire({
      icon: 'success',
      title: 'Success!',
      text: `${type} operation completed successfully.`,
      timer: 3000,
      showConfirmButton: false
    });
  }, 2000);
}

/**
 * Refresh master data statistics
 */
function refreshStatistics() {
  // Implementation would depend on actual API endpoints
  console.log('Refreshing master data statistics...');
}

// Export functions for use in other scripts
window.confirmBulkOperation = confirmBulkOperation;
window.handleImportExport = handleImportExport;
window.refreshStatistics = refreshStatistics;