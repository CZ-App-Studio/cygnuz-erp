/**
 * HRCore Leave Balance Management
 */

$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize Select2
  $('.select2').select2({
    placeholder: 'Select an option',
    allowClear: true
  });

  // Initialize DataTable
  const dt = $('#leaveBalanceTable').DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.employee_id = $('#employeeFilter').val();
        d.team_id = $('#teamFilter').val();
      },
      dataSrc: 'data'
    },
    columns: [
      { data: 'employee' },
      { data: 'code' },
      { data: 'designation' },
      { data: 'team' },
      // Dynamic columns for leave types
      ...Object.keys(pageData.leaveTypes).map(leaveTypeId => ({
        data: null,
        className: 'text-center',
        render: function (data, type, row) {
          const balance = row.balances[pageData.leaveTypes[leaveTypeId]] || 0;
          return `<span class="badge bg-label-primary">${balance}</span>`;
        }
      })),
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `
            <button class="btn btn-sm btn-label-primary" onclick="viewEmployeeBalance(${row.id})">
              <i class="bx bx-show"></i> ${pageData.labels.viewDetails}
            </button>
          `;
        }
      }
    ],
    order: [[0, 'asc']],
    language: {
      search: 'Search:',
      processing: 'Loading...',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'No entries found',
      emptyTable: 'No data available',
      paginate: {
        first: 'First',
        last: 'Last',
        next: 'Next',
        previous: 'Previous'
      }
    }
  });

  // Filter handlers
  $('#employeeFilter, #teamFilter').on('change', function () {
    dt.ajax.reload();
  });

  // View employee balance
  window.viewEmployeeBalance = function(employeeId) {
    window.location.href = pageData.urls.show.replace(':id', employeeId);
  };

  // Reset filters
  window.resetFilters = function() {
    $('#employeeFilter').val('').trigger('change');
    $('#teamFilter').val('').trigger('change');
    dt.ajax.reload();
  };

  // Show bulk set modal
  window.showBulkSetModal = function() {
    $('#bulkSetForm')[0].reset();
    $('#bulkEmployees').val(null).trigger('change');
    $('#bulkSetModal').modal('show');
  };

  // Submit bulk set
  window.submitBulkSet = function() {
    const defaultDays = $('#defaultDays').val();
    const selectedEmployees = $('#bulkEmployees').val();
    
    if (!defaultDays || !selectedEmployees || selectedEmployees.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Validation Error',
        text: 'Please fill all required fields'
      });
      return;
    }

    const employees = selectedEmployees.map(userId => ({
      user_id: userId,
      entitled_leaves: defaultDays
    }));

    const data = {
      year: $('#bulkYear').val(),
      leave_type_id: $('#bulkLeaveType').val(),
      employees: employees
    };

    $.ajax({
      url: pageData.urls.bulkSet,
      type: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      success: function(response) {
        if (response.status === 'success') {
          $('#bulkSetModal').modal('hide');
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data
          });
          dt.ajax.reload();
        }
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: xhr.responseJSON?.data || 'An error occurred'
        });
      }
    });
  };
});