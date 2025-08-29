/**
 * HRCore Employee Profile
 */

'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize components
    bindActionEvents();
});

/**
 * Bind action button events
 */
function bindActionEvents() {
    // Edit employee button
    $('#editEmployeeBtn').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        // Use the global editEmployee function from the main employees.js
        if (typeof window.editEmployee === 'function') {
            window.editEmployee(id);
        } else {
            // Fallback: redirect to edit page
            window.location.href = pageData.urls.edit.replace(':id', id);
        }
    });
}