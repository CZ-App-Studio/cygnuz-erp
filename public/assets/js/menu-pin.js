/**
 * Menu Pinning and Reordering Functionality
 */

'use strict';

// DOM elements
let pinnedMenuContainer;

// Initialize once DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
  pinnedMenuContainer = document.getElementById('pinned-menu-container');

  // Initialize Sortable.js for drag-and-drop reordering
  if (pinnedMenuContainer) {
    initSortable();
  }

  // Add event listeners to all pin icons
  initPinClickHandlers();
});

/**
 * Initialize Sortable.js for drag-and-drop reordering of pinned items
 */
function initSortable() {
  if (typeof Sortable !== 'undefined') {
    new Sortable(pinnedMenuContainer, {
      animation: 150,
      ghostClass: 'menu-item-ghost',
      chosenClass: 'menu-item-chosen',
      dragClass: 'menu-item-drag',
      handle: '.menu-item',
      onEnd: function(evt) {
        saveMenuOrder();
      }
    });
  } else {
    console.warn('Sortable.js is not loaded. Menu items cannot be reordered.');
  }
}

/**
 * Add click handlers to all pin icons
 */
function initPinClickHandlers() {
  // Use event delegation for better performance
  document.addEventListener('click', function(e) {
    // First, check if we clicked on the pin icon itself or its container
    const pinIcon = e.target.closest('.menu-pin-icon');
    if (pinIcon) {
      // Stop event propagation to prevent menu link click
      e.preventDefault();
      e.stopPropagation();

      const menuSlug = pinIcon.getAttribute('data-menu-slug');
      togglePinMenu(menuSlug, pinIcon);

      // Return false to ensure the event doesn't bubble up
      return false;
    }
  });
}

/**
 * Toggle pin status for a menu item
 * @param {string} menuSlug - The slug of the menu item
 * @param {HTMLElement} pinIconElement - The pin icon element that was clicked
 */
function togglePinMenu(menuSlug, pinIconElement) {
  console.log('Toggling pin for:', menuSlug); // Debugging

  if (!menuSlug) {
    console.error('No menu slug provided for pinning');
    return;
  }

  // Send AJAX request to toggle pin status
  fetch('/menu-preferences/toggle-pin', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ menu_slug: menuSlug })
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok: ' + response.statusText);
    }
    return response.json();
  })
  .then(data => {
    console.log('Toggle response:', data); // Debugging
    if (data.success) {
      // Update UI based on the new pin status
      updatePinUI(menuSlug, data.is_pinned);

      // Reload the page to show/hide the item in the pinned section
      window.location.reload();
    }
  })
  .catch(error => {
    console.error('Error toggling pin status:', error);
  });
}

/**
 * Update the UI to reflect the new pin status
 * @param {string} menuSlug - The slug of the menu item
 * @param {boolean} isPinned - Whether the item is pinned or not
 */
function updatePinUI(menuSlug, isPinned) {
  // Update all instances of this menu slug's pin icon
  document.querySelectorAll(`.menu-pin-icon[data-menu-slug="${menuSlug}"] .pin-icon`).forEach(icon => {
    if (isPinned) {
      icon.classList.remove('bx-pin');
      icon.classList.add('bxs-pin', 'text-primary');
    } else {
      icon.classList.remove('bxs-pin', 'text-primary');
      icon.classList.add('bx-pin');
    }
  });
}

/**
 * Save the current order of pinned menu items
 */
function saveMenuOrder() {
  // Get all pinned menu items
  const pinnedItems = pinnedMenuContainer.querySelectorAll('.menu-item');

  // Create an array of menu slugs in their current order
  const orders = Array.from(pinnedItems).map((item, index) => {
    return {
      menu_slug: item.getAttribute('data-menu-slug'),
      display_order: index
    };
  });

  // Send AJAX request to save the new order
  fetch('/menu-preferences/update-order', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ orders: orders })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show a success notification if you have a notification system
      console.log('Menu order saved successfully');
    }
  })
  .catch(error => {
    console.error('Error saving menu order:', error);
  });
}
