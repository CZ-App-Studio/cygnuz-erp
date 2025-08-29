/**
 * Employee Dashboard Scripts
 */

'use strict';

(function () {
  // Initialize tooltips
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

  // Task priority colors
  const priorityColors = {
    high: 'danger',
    medium: 'warning',
    low: 'secondary',
    normal: 'secondary'
  };

  // Format dates
  function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric' 
    });
  }

  // Initialize any charts if needed
  function initializeCharts() {
    // Placeholder for future chart implementations
    // e.g., attendance chart, leave balance chart, etc.
  }

  // Quick action handlers
  function initializeQuickActions() {
    // Add click tracking for quick actions
    document.querySelectorAll('.btn').forEach(button => {
      if (button.href && button.href.includes('web-attendance')) {
        button.addEventListener('click', function(e) {
          // Could add confirmation or tracking here
          console.log('Navigating to web check-in');
        });
      }
    });
  }

  // Task list interactions
  function initializeTaskList() {
    document.querySelectorAll('.list-group-item-action').forEach(item => {
      item.addEventListener('click', function() {
        // Could open task details in offcanvas or redirect
        console.log('Task clicked:', this.querySelector('h6').textContent);
      });
    });
  }

  // Announcement interactions
  function initializeAnnouncements() {
    document.querySelectorAll('.list-group-item').forEach(item => {
      const title = item.querySelector('h6');
      if (title && title.textContent) {
        item.style.cursor = 'pointer';
        item.addEventListener('click', function() {
          // Could open announcement details
          console.log('Announcement clicked:', title.textContent);
        });
      }
    });
  }

  // Refresh dashboard data
  function refreshDashboardData() {
    // Placeholder for AJAX refresh functionality
    console.log('Refreshing dashboard data...');
  }

  // Auto-refresh every 5 minutes
  let refreshInterval;
  function startAutoRefresh() {
    refreshInterval = setInterval(refreshDashboardData, 5 * 60 * 1000);
  }

  function stopAutoRefresh() {
    if (refreshInterval) {
      clearInterval(refreshInterval);
    }
  }

  // Page visibility handling
  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      stopAutoRefresh();
    } else {
      startAutoRefresh();
    }
  });

  // Initialize everything when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    initializeQuickActions();
    initializeTaskList();
    initializeAnnouncements();
    startAutoRefresh();
  });

  // Cleanup on page unload
  window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
  });

})();