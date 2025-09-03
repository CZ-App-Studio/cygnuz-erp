/**
 * HRCore Leave Management
 */

$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  const dt = $('#leaveRequestsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.employeeFilter = $('#employeeFilter').val();
        d.dateFilter = $('#dateFilter').val();
        d.leaveTypeFilter = $('#leaveTypeFilter').val();
        d.statusFilter = $('#statusFilter').val();
      }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'user', orderable: false, searchable: false },
      { data: 'leave_type', searchable: false },
      { data: 'leave_dates', orderable: false, searchable: false },
      { data: 'status', orderable: false, searchable: false },
      { data: 'document', orderable: false, searchable: false },
      { data: 'actions', orderable: false, searchable: false }
    ],
    order: [[0, 'desc']],
    language: {
      search: pageData.labels.search,
      processing: pageData.labels.processing,
      lengthMenu: pageData.labels.lengthMenu,
      info: pageData.labels.info,
      infoEmpty: pageData.labels.infoEmpty,
      emptyTable: pageData.labels.emptyTable,
      paginate: pageData.labels.paginate
    },
    dom:
      '<"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
      't' +
      '<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
  });

  // Initialize Select2 for static dropdowns
  $('#leaveTypeFilter, #statusFilter').select2({
    placeholder: 'Select Option',
    allowClear: true
  });

  // Initialize Select2 with AJAX for employee filter
  $('#employeeFilter').select2({
    placeholder: 'All Employees',
    allowClear: true,
    ajax: {
      url: '/hrcore/employees/search',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          q: params.term,
          page: params.page || 1
        };
      },
      processResults: function (data, params) {
        params.page = params.page || 1;
        return {
          results: data.data ? data.data.map(function(employee) {
            return {
              id: employee.id,
              text: employee.name + ' (' + employee.code + ')'
            };
          }) : [],
          pagination: {
            more: data.has_more || false
          }
        };
      },
      cache: true
    },
    minimumInputLength: 0,
    width: '100%'
  });

  // Initialize Select2 with AJAX for employee selection in the form
  $('#user_id').select2({
    placeholder: pageData.labels.selectEmployee,
    allowClear: true,
    dropdownParent: $('#offcanvasAddLeave'),
    ajax: {
      url: '/hrcore/employees/search',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          q: params.term,
          page: params.page || 1
        };
      },
      processResults: function (data, params) {
        params.page = params.page || 1;
        return {
          results: data.data ? data.data.map(function(employee) {
            return {
              id: employee.id,
              text: employee.name + ' (' + employee.code + ')'
            };
          }) : [],
          pagination: {
            more: data.has_more || false
          }
        };
      },
      cache: true
    },
    minimumInputLength: 0,
    width: '100%'
  });

  // Initialize Flatpickr for date filter
  flatpickr('#dateFilter', {
    dateFormat: 'Y-m-d',
    allowClear: true
  });

  // Filter handlers
  $('#employeeFilter, #dateFilter, #leaveTypeFilter, #statusFilter').on('change', function () {
    dt.ajax.reload();
  });

  // Initialize GLightbox for document preview
  if (typeof GLightbox !== 'undefined') {
    GLightbox({
      selector: '.glightbox'
    });
  }

  // Refresh GLightbox after DataTable draw
  dt.on('draw', function () {
    if (typeof GLightbox !== 'undefined') {
      GLightbox({
        selector: '.glightbox'
      });
    }
  });
});

// View leave request details - redirect to show page
window.viewLeaveDetails = function(id) {
  // Redirect to the show page
  window.location.href = pageData.urls.show.replace(':id', id);
}

// Legacy function for backward compatibility
window.viewLeaveDetailsAjax = function(id) {
  const url = pageData.urls.show.replace(':id', id);
  
  $.ajax({
    url: url,
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    success: function (response) {
      if (response.status === 'success') {
        const data = response.data;
        
        // Populate details
        $('#leaveDetailsContent').html(`
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.employee}</h6>
            <p class="mb-0">${data.userName} (${data.userCode})</p>
          </div>
          
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.leaveType}</h6>
            <p class="mb-0">${data.leaveType}</p>
          </div>
          
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.dates}</h6>
            <p class="mb-0">${data.fromDate} - ${data.toDate}</p>
          </div>
          
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.status}</h6>
            ${data.statusBadge}
          </div>
          
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.reason}</h6>
            <p class="mb-0">${data.userNotes || 'N/A'}</p>
          </div>
          
          ${data.approvalNotes ? `
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.approvalNotes}</h6>
            <p class="mb-0">${data.approvalNotes}</p>
          </div>
          ` : ''}
          
          ${data.document ? `
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.document}</h6>
            <a href="${data.document}" class="glightbox">
              <img src="${data.document}" alt="Document" class="img-thumbnail" style="max-width: 200px;">
            </a>
          </div>
          ` : ''}
          
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.requestedOn}</h6>
            <p class="mb-0">${data.createdAt}</p>
          </div>
          
          ${data.approvedBy ? `
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.approvedBy}</h6>
            <p class="mb-0">${data.approvedBy}</p>
          </div>
          ` : ''}
          
          ${data.rejectedBy ? `
          <div class="mb-4">
            <h6 class="text-muted mb-2">${pageData.labels.rejectedBy}</h6>
            <p class="mb-0">${data.rejectedBy}</p>
          </div>
          ` : ''}
        `);
        
        // Show/hide action buttons based on status
        const statusValue = data.statusValue || data.status;
        const status = typeof statusValue === 'string' ? statusValue.toLowerCase() : statusValue.value?.toLowerCase() || '';
        
        console.log('Leave status:', status, 'Raw status value:', data.status, 'Status value:', data.statusValue);
        
        // Hide all action buttons first using important to override any other styles
        $('.leave-action-btn').each(function() {
          $(this).attr('style', 'display: none !important');
          console.log('Hiding button:', $(this).attr('id'));
        });
        
        // Show appropriate buttons based on status
        switch(status) {
          case 'pending':
            console.log('Status is pending - showing all buttons');
            $('#approveBtn').attr('style', '');
            $('#rejectBtn').attr('style', '');
            $('#cancelBtn').attr('style', '');
            break;
            
          case 'approved':
            console.log('Status is approved - showing only cancel button');
            $('#cancelBtn').attr('style', '');
            break;
            
          case 'rejected':
          case 'cancelled':
          case 'cancelled_by_admin':
            console.log('Status is ' + status + ' - hiding all buttons');
            // All buttons remain hidden
            break;
            
          default:
            console.warn('Unknown status:', status);
        }
        
        // Debug: Check final button states
        setTimeout(() => {
          $('.leave-action-btn').each(function() {
            console.log('Button', $(this).attr('id'), 'display:', $(this).css('display'));
          });
        }, 100);
        
        // Store the leave request ID
        $('#approveBtn, #rejectBtn, #cancelBtn').data('id', id);
        
        // Show offcanvas
        const offcanvasEl = document.getElementById('offcanvasLeaveDetails');
        const offcanvas = new bootstrap.Offcanvas(offcanvasEl);
        
        // Store the status for use in shown event
        offcanvasEl.dataset.leaveStatus = status;
        
        // Ensure buttons remain in correct state after offcanvas is shown
        offcanvasEl.addEventListener('shown.bs.offcanvas', function () {
          const currentStatus = this.dataset.leaveStatus;
          console.log('Offcanvas shown - reapplying button visibility for status:', currentStatus);
          
          // Reapply button visibility with !important
          $('.leave-action-btn').attr('style', 'display: none !important');
          
          if (currentStatus === 'pending') {
            $('#approveBtn').attr('style', '');
            $('#rejectBtn').attr('style', '');
            $('#cancelBtn').attr('style', '');
          } else if (currentStatus === 'approved') {
            $('#cancelBtn').attr('style', '');
          }
          
          // Final check
          console.log('After reapplying:');
          $('.leave-action-btn').each(function() {
            console.log('Button', $(this).attr('id'), 'style:', $(this).attr('style'));
          });
        }, { once: true });
        
        offcanvas.show();
        
        // Re-initialize GLightbox for the modal content
        if (typeof GLightbox !== 'undefined') {
          GLightbox({
            selector: '.glightbox'
          });
        }
      }
    },
    error: function () {
      Swal.fire({
        icon: 'error',
        title: pageData.labels.error,
        text: pageData.labels.error
      });
    }
  });
}

// Handle leave actions (approve/reject/cancel) - make it globally accessible
window.handleLeaveAction = function(id, status) {
  // Close the offcanvas first to avoid focus issues
  const offcanvasEl = document.getElementById('offcanvasLeaveDetails');
  const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
  if (offcanvas) {
    offcanvas.hide();
  }
  
  // Wait a bit for offcanvas to close
  setTimeout(() => {
    Swal.fire({
      title: pageData.labels.confirmAction,
      text: `Are you sure you want to ${status} this leave request?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      input: 'textarea',
      inputLabel: 'Notes (optional)',
      inputPlaceholder: 'Enter any notes...',
      didOpen: () => {
        // Ensure proper focus handling
        const textarea = Swal.getInput();
        if (textarea) {
          textarea.focus();
        }
      }
    }).then((result) => {
    if (result.isConfirmed) {
      const url = pageData.urls.action.replace(':id', id);
      
      $.ajax({
        url: url,
        method: 'POST',
        data: {
          id: id,
          status: status,
          adminNotes: result.value || ''
        },
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.success,
              text: response.message
            });
            
            // Reload table
            $('#leaveRequestsTable').DataTable().ajax.reload();
          }
        },
        error: function () {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: pageData.labels.error
          });
        }
      });
    }
  });
  }, 300); // Wait 300ms for offcanvas to close
}

// Attach action button handlers
$(document).on('click', '#approveBtn', function () {
  handleLeaveAction($(this).data('id'), 'approved');
});

$(document).on('click', '#rejectBtn', function () {
  handleLeaveAction($(this).data('id'), 'rejected');
});

$(document).on('click', '#cancelBtn', function () {
  handleLeaveAction($(this).data('id'), 'cancelled');
});