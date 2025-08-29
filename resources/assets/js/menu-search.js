/**
 * Menu Search Functionality
 */

document.addEventListener('DOMContentLoaded', function() {
  // Get the search input element
  const searchInput = document.querySelector('.menu-search-input');

  if (searchInput) {
    searchInput.addEventListener('keyup', function(e) {
      const searchTerm = e.target.value.toLowerCase().trim();
      const menuItems = document.querySelectorAll('.menu-inner .menu-item');
      const menuHeaders = document.querySelectorAll('.menu-inner .menu-header');

      // Show all menu headers initially
      menuHeaders.forEach(header => {
        header.style.display = '';
      });

      if (searchTerm === '') {
        // If search is cleared, show all menu items and reset
        menuItems.forEach(item => {
          item.style.display = '';

          // Reset submenu items too
          const submenuItems = item.querySelectorAll('.menu-sub .menu-item');
          submenuItems.forEach(subItem => {
            subItem.style.display = '';
          });
        });
        return;
      }

      // Track which headers have visible items
      const headersWithVisibleItems = new Set();

      // Check each menu item
      menuItems.forEach(item => {
        const menuLink = item.querySelector('.menu-link');
        const menuText = menuLink ? menuLink.textContent.toLowerCase() : '';
        const hasSubmenu = item.querySelector('.menu-sub');
        let isVisible = menuText.includes(searchTerm);
        let hasVisibleSubmenuItems = false;

        // Check submenu items if present
        if (hasSubmenu) {
          const submenuItems = item.querySelectorAll('.menu-sub .menu-item');
          submenuItems.forEach(subItem => {
            const subMenuLink = subItem.querySelector('.menu-link');
            const subMenuText = subMenuLink ? subMenuLink.textContent.toLowerCase() : '';
            const subItemVisible = subMenuText.includes(searchTerm);

            // Show/hide submenu item
            subItem.style.display = subItemVisible ? '' : 'none';

            // If any submenu item is visible, we should show the parent
            if (subItemVisible) {
              hasVisibleSubmenuItems = true;
            }
          });
        }

        // Show item if it matches or has matching submenu items
        item.style.display = (isVisible || hasVisibleSubmenuItems) ? '' : 'none';

        // If this item is visible, find its header
        if (isVisible || hasVisibleSubmenuItems) {
          let headerElement = item.previousElementSibling;
          while (headerElement) {
            if (headerElement.classList.contains('menu-header')) {
              headersWithVisibleItems.add(headerElement);
              break;
            }
            headerElement = headerElement.previousElementSibling;
          }
        }
      });

      // Hide headers that don't have any visible items
      menuHeaders.forEach(header => {
        if (!headersWithVisibleItems.has(header)) {
          header.style.display = 'none';
        }
      });
    });

    // Clear search when ESC key is pressed
    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        e.target.value = '';
        // Trigger the keyup event to reset the menu
        e.target.dispatchEvent(new Event('keyup'));
      }
    });
  }
});
