/**
 * SearchPlus Compatibility
 * This file ensures compatibility with the default search functionality when SearchPlus is enabled
 */

document.addEventListener('DOMContentLoaded', function() {
    // Override default search behavior when SearchPlus is enabled
    const searchPlusEnabled = document.querySelector('[data-searchplus="true"]');
    
    if (searchPlusEnabled) {
        // Prevent default search functionality
        // Be more specific - only remove the navbar search wrapper, not the modal search
        const navbarSearchWrapper = document.querySelector('.navbar-search-wrapper .search-input-wrapper');
        
        // Remove navbar search elements to prevent errors
        if (navbarSearchWrapper && !navbarSearchWrapper.closest('#searchPlusModal')) {
            navbarSearchWrapper.remove();
        }
        
        // Override keyboard shortcut for default search
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }
});