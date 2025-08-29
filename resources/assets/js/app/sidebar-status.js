/**
 * Sidebar Footer System Status
 * Updates the system status indicator in the sidebar footer
 */

$(function () {
  // Only run if we're authenticated and system status route exists
  if ($('.status-dot').length && typeof systemStatusUrl !== 'undefined') {
    updateSystemStatusIndicator();
    // Update every 60 seconds
    setInterval(updateSystemStatusIndicator, 60000);
  }
});

/**
 * Update system status indicator in sidebar
 */
function updateSystemStatusIndicator() {
  if (typeof systemStatusUrl === 'undefined') {
    return;
  }

  $.ajax({
    url: systemStatusUrl,
    method: 'GET',
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function(response) {
      const statusDot = $('.status-dot');
      const statusClasses = ['bg-success', 'bg-warning', 'bg-danger'];

      // Remove all status classes
      statusDot.removeClass(statusClasses.join(' '));

      // Add appropriate class based on status
      switch(response.status) {
        case 'healthy':
          statusDot.addClass('bg-success');
          break;
        case 'warning':
          statusDot.addClass('bg-warning');
          break;
        case 'error':
          statusDot.addClass('bg-danger');
          break;
        default:
          statusDot.addClass('bg-secondary');
      }

      // Update tooltip if available
      const statusLink = statusDot.closest('a');
      if (statusLink.length) {
        statusLink.attr('title', `System Status: ${response.message}`);
      }
    },
    error: function() {
      // On error, show warning status
      $('.status-dot')
        .removeClass('bg-success bg-danger')
        .addClass('bg-warning');
    }
  });
}
