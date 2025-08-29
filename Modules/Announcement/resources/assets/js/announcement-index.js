/**
 * Announcement Index Page JavaScript
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  (function () {
    const dt_announcements_table = $('.datatables-announcements');
    const filterStatus = document.getElementById('filter-status');
    const filterPriority = document.getElementById('filter-priority');

    // Initialize DataTable without AJAX
    let dt_announcement;
    if (dt_announcements_table.length) {
      dt_announcement = dt_announcements_table.DataTable({
        order: [[5, 'desc']], // Order by publish date
        pageLength: 10,
        lengthMenu: [10, 25, 50, 75, 100],
        responsive: true,
        language: {
          search: 'Search:',
          lengthMenu: '_MENU_',
          paginate: {
            next: '<i class="bx bx-chevron-right bx-xs"></i>',
            previous: '<i class="bx bx-chevron-left bx-xs"></i>'
          }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
              '<"row"<"col-sm-12"tr>>' +
              '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        columnDefs: [
          {
            targets: -1, // Actions column
            orderable: false,
            searchable: false
          }
        ],
        drawCallback: function() {
          // Add custom classes to pagination buttons
          $('.paginate_button').addClass('btn btn-sm');
          $('.paginate_button.current').addClass('btn-primary');
          $('.paginate_button:not(.current)').addClass('btn-outline-primary');
          $('.paginate_button.previous, .paginate_button.next').addClass('btn-icon');
          
          // Fix pagination button container spacing
          $('.dataTables_paginate .pagination').addClass('pagination-sm');
        }
      });
    }

    // Filter event handlers - reload page with filters
    if (filterStatus) {
      filterStatus.addEventListener('change', function () {
        const url = new URL(window.location);
        if (this.value) {
          url.searchParams.set('status', this.value);
        } else {
          url.searchParams.delete('status');
        }
        window.location.href = url.toString();
      });
    }

    if (filterPriority) {
      filterPriority.addEventListener('change', function () {
        const url = new URL(window.location);
        if (this.value) {
          url.searchParams.set('priority', this.value);
        } else {
          url.searchParams.delete('priority');
        }
        window.location.href = url.toString();
      });
    }

    // Set filter values from URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (filterStatus && urlParams.has('status')) {
      filterStatus.value = urlParams.get('status');
    }
    if (filterPriority && urlParams.has('priority')) {
      filterPriority.value = urlParams.get('priority');
    }

    // Toggle Pin functionality
    $(document).on('click', '.toggle-pin', function (e) {
      e.preventDefault();
      const announcementId = $(this).data('id');
      const button = $(this);
      
      $.ajax({
        url: `/announcements/${announcementId}/toggle-pin`,
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
          if (response.success) {
            // Show success message
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: response.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false,
              timer: 2000
            }).then(() => {
              // Reload page to refresh the table
              location.reload();
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to update pin status.',
            customClass: {
              confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
          });
        }
      });
    });

    // Delete form submission with confirmation
    $('.delete-form').on('submit', function (e) {
      e.preventDefault();
      const form = this;
      
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        customClass: {
          confirmButton: 'btn btn-primary me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.value) {
          form.submit();
        }
      });
    });
  })();
});