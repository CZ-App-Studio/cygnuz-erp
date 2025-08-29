/**
 * Announcement Show Page JavaScript
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  (function () {
    // Acknowledge Button Handler
    const acknowledgeBtn = document.getElementById('acknowledge-btn');
    
    if (acknowledgeBtn) {
      acknowledgeBtn.addEventListener('click', function () {
        const announcementId = this.getAttribute('data-id');
        const button = this;
        
        // Disable button to prevent multiple clicks
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
        
        // Send acknowledgment request
        fetch(`/announcements/${announcementId}/acknowledge`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Show success message
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: data.message || 'Announcement acknowledged successfully.',
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false
            }).then(() => {
              // Reload page to update UI
              location.reload();
            });
          } else {
            // Show error message
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: data.message || 'Failed to acknowledge announcement.',
              customClass: {
                confirmButton: 'btn btn-danger'
              },
              buttonsStyling: false
            });
            
            // Re-enable button
            button.disabled = false;
            button.innerHTML = '<i class="bx bx-check me-1"></i> Acknowledge';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while processing your request.',
            customClass: {
              confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
          });
          
          // Re-enable button
          button.disabled = false;
          button.innerHTML = '<i class="bx bx-check me-1"></i> Acknowledge';
        });
      });
    }

    // Print functionality
    const printBtn = document.getElementById('print-announcement');
    if (printBtn) {
      printBtn.addEventListener('click', function () {
        window.print();
      });
    }

    // Copy link functionality
    const copyLinkBtn = document.getElementById('copy-link');
    if (copyLinkBtn) {
      copyLinkBtn.addEventListener('click', function () {
        const url = window.location.href;
        
        // Create temporary input element
        const tempInput = document.createElement('input');
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        
        try {
          document.execCommand('copy');
          
          // Show success toast
          Swal.fire({
            icon: 'success',
            title: 'Link Copied!',
            text: 'Announcement link has been copied to clipboard.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
          });
        } catch (err) {
          console.error('Failed to copy:', err);
          
          // Show error message
          Swal.fire({
            icon: 'error',
            title: 'Copy Failed',
            text: 'Failed to copy link to clipboard.',
            customClass: {
              confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
          });
        }
        
        // Remove temporary input
        document.body.removeChild(tempInput);
      });
    }

    // Image viewer for attachments
    const attachmentImages = document.querySelectorAll('.announcement-content img');
    attachmentImages.forEach(img => {
      img.style.cursor = 'pointer';
      img.addEventListener('click', function () {
        // Create modal for image viewing
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body text-center">
                <img src="${this.src}" class="img-fluid" alt="Attachment">
              </div>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Remove modal from DOM when hidden
        modal.addEventListener('hidden.bs.modal', function () {
          document.body.removeChild(modal);
        });
      });
    });

    // Auto-refresh read statistics
    const refreshStats = document.getElementById('auto-refresh-stats');
    if (refreshStats && refreshStats.checked) {
      setInterval(function () {
        // This would typically make an AJAX call to get updated stats
        // For now, we'll just reload the stats section
        const statsCard = document.querySelector('.read-statistics-card');
        if (statsCard) {
          // Implement AJAX call here to update statistics
          console.log('Refreshing statistics...');
        }
      }, 30000); // Refresh every 30 seconds
    }

    // Format relative time
    const timeElements = document.querySelectorAll('[data-time]');
    timeElements.forEach(element => {
      const time = element.getAttribute('data-time');
      if (time) {
        const date = new Date(time);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // Difference in seconds
        
        let relativeTime;
        if (diff < 60) {
          relativeTime = 'just now';
        } else if (diff < 3600) {
          const minutes = Math.floor(diff / 60);
          relativeTime = `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diff < 86400) {
          const hours = Math.floor(diff / 3600);
          relativeTime = `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (diff < 604800) {
          const days = Math.floor(diff / 86400);
          relativeTime = `${days} day${days > 1 ? 's' : ''} ago`;
        } else {
          relativeTime = date.toLocaleDateString();
        }
        
        element.textContent = relativeTime;
        element.title = date.toLocaleString();
      }
    });
  })();
});