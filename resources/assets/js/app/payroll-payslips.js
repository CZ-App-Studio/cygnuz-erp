/**
 * Payroll Payslips Management
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  // Initialize DataTable if present and has data rows
  const payslipsTable = document.getElementById('payslipsTable');
  if (payslipsTable && $.fn.DataTable) {
    // Check if there are actual data rows (not just the empty message row)
    const tbody = payslipsTable.querySelector('tbody');
    const rows = tbody ? tbody.querySelectorAll('tr') : [];
    const hasDataRows = rows.length > 0 && !tbody.querySelector('td[colspan]');
    
    // Only initialize DataTable if there are actual data rows
    if (hasDataRows) {
      $(payslipsTable).DataTable({
        order: [[1, 'desc']], // Sort by payment date
        pageLength: 10,
        responsive: true,
        language: {
          search: 'Search payslips:',
          lengthMenu: 'Show _MENU_ payslips',
          info: 'Showing _START_ to _END_ of _TOTAL_ payslips',
          emptyTable: 'No payslips found for the selected period',
          paginate: {
            first: 'First',
            last: 'Last',
            next: 'Next',
            previous: 'Previous'
          }
        }
      });
    }
  }

  // Handle view payslip details
  document.querySelectorAll('.view-payslip').forEach(button => {
    button.addEventListener('click', function() {
      const payslipId = this.dataset.id;
      showPayslipDetails(payslipId);
    });
  });

  // Show payslip details in modal
  function showPayslipDetails(payslipId) {
    const modal = new bootstrap.Modal(document.getElementById('payslipModal'));
    const modalBody = document.getElementById('payslipModalBody');
    const downloadBtn = document.getElementById('downloadPayslipBtn');
    
    // Show loading spinner
    modalBody.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    `;
    
    modal.show();
    
    // Fetch payslip details
    fetch(`/payroll/my-payslips/${payslipId}/data`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const payslip = data.payslip;
          
          // Update download button
          downloadBtn.href = `/payroll/my-payslips/${payslipId}/download`;
          
          // Build earnings table
          let earningsHtml = '';
          let totalEarnings = 0;
          if (payslip.earnings && Object.keys(payslip.earnings).length > 0) {
            for (const [key, value] of Object.entries(payslip.earnings)) {
              earningsHtml += `
                <tr>
                  <td>${formatLabel(key)}</td>
                  <td class="text-end">${formatCurrency(value)}</td>
                </tr>
              `;
              totalEarnings += parseFloat(value) || 0;
            }
          }
          
          // Build deductions table
          let deductionsHtml = '';
          let totalDeductions = 0;
          if (payslip.deductions && Object.keys(payslip.deductions).length > 0) {
            for (const [key, value] of Object.entries(payslip.deductions)) {
              deductionsHtml += `
                <tr>
                  <td>${formatLabel(key)}</td>
                  <td class="text-end text-danger">-${formatCurrency(value)}</td>
                </tr>
              `;
              totalDeductions += parseFloat(value) || 0;
            }
          }
          
          // Build modal content
          modalBody.innerHTML = `
            <!-- Employee Information -->
            <div class="mb-4">
              <h6 class="text-muted mb-3">Employee Information</h6>
              <div class="row">
                <div class="col-md-6">
                  <p class="mb-1"><strong>Name:</strong> ${payslip.employee.name}</p>
                  <p class="mb-1"><strong>Employee ID:</strong> ${payslip.employee.employee_id}</p>
                </div>
                <div class="col-md-6">
                  <p class="mb-1"><strong>Designation:</strong> ${payslip.employee.designation}</p>
                  <p class="mb-1"><strong>Department:</strong> ${payslip.employee.department}</p>
                </div>
              </div>
            </div>
            
            <!-- Payslip Period -->
            <div class="mb-4">
              <h6 class="text-muted mb-3">Payslip Period</h6>
              <div class="row">
                <div class="col-md-6">
                  <p class="mb-1"><strong>Period:</strong> ${payslip.period}</p>
                  <p class="mb-1"><strong>From:</strong> ${payslip.period_start}</p>
                </div>
                <div class="col-md-6">
                  <p class="mb-1"><strong>To:</strong> ${payslip.period_end}</p>
                  <p class="mb-1"><strong>Payment Date:</strong> ${payslip.payment_date}</p>
                </div>
              </div>
            </div>
            
            <!-- Earnings and Deductions -->
            <div class="row">
              <!-- Earnings -->
              <div class="col-md-6">
                <div class="card bg-light">
                  <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Earnings</h6>
                  </div>
                  <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                      <tbody>
                        ${earningsHtml || '<tr><td colspan="2" class="text-center text-muted">No earnings</td></tr>'}
                      </tbody>
                      <tfoot>
                        <tr class="border-top">
                          <th>Total Earnings</th>
                          <th class="text-end text-success">${formatCurrency(totalEarnings)}</th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
              
              <!-- Deductions -->
              <div class="col-md-6">
                <div class="card bg-light">
                  <div class="card-header bg-warning text-white">
                    <h6 class="mb-0">Deductions</h6>
                  </div>
                  <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                      <tbody>
                        ${deductionsHtml || '<tr><td colspan="2" class="text-center text-muted">No deductions</td></tr>'}
                      </tbody>
                      <tfoot>
                        <tr class="border-top">
                          <th>Total Deductions</th>
                          <th class="text-end text-danger">-${formatCurrency(totalDeductions)}</th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Net Salary -->
            <div class="mt-4">
              <div class="card bg-primary text-white">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col">
                      <h5 class="mb-0">Net Salary</h5>
                    </div>
                    <div class="col-auto">
                      <h3 class="mb-0">${formatCurrency(payslip.net_salary)}</h3>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Status Badge -->
            <div class="mt-3 text-center">
              ${getStatusBadge(payslip.status)}
            </div>
          `;
        } else {
          modalBody.innerHTML = `
            <div class="alert alert-danger">
              <i class="bx bx-error-circle me-2"></i>
              Failed to load payslip details. Please try again.
            </div>
          `;
        }
      })
      .catch(error => {
        console.error('Error loading payslip:', error);
        modalBody.innerHTML = `
          <div class="alert alert-danger">
            <i class="bx bx-error-circle me-2"></i>
            An error occurred while loading payslip details.
          </div>
        `;
      });
  }

  // Format currency
  function formatCurrency(amount) {
    const symbol = window.appConfig?.currencySymbol || '$';
    return symbol + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  // Format label (convert snake_case to Title Case)
  function formatLabel(label) {
    return label
      .replace(/_/g, ' ')
      .replace(/\b\w/g, char => char.toUpperCase());
  }

  // Get status badge HTML
  function getStatusBadge(status) {
    const statusConfig = {
      'draft': { class: 'bg-label-secondary', icon: 'bx-edit' },
      'pending': { class: 'bg-label-warning', icon: 'bx-time' },
      'approved': { class: 'bg-label-info', icon: 'bx-check-circle' },
      'paid': { class: 'bg-label-success', icon: 'bx-check-double' },
      'cancelled': { class: 'bg-label-danger', icon: 'bx-x-circle' }
    };
    
    const config = statusConfig[status] || statusConfig['draft'];
    const statusText = status.charAt(0).toUpperCase() + status.slice(1);
    
    return `
      <span class="badge ${config.class}">
        <i class="bx ${config.icon} me-1"></i>
        ${statusText}
      </span>
    `;
  }

  // Handle filter form submission
  const filterForm = document.querySelector('form[action*="my-payslips"]');
  if (filterForm) {
    // Auto-submit on change
    filterForm.querySelectorAll('select').forEach(select => {
      select.addEventListener('change', function() {
        // Optional: Show loading indicator
        const submitBtn = filterForm.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        }
      });
    });
  }

  // Initialize Select2 for filters if available
  if ($.fn.select2) {
    $('#yearFilter, #monthFilter').select2({
      minimumResultsForSearch: -1 // Disable search box for these dropdowns
    });
  }

  // Print payslip functionality
  window.printPayslip = function(payslipId) {
    const printWindow = window.open(`/payroll/my-payslips/${payslipId}?print=true`, '_blank');
    if (printWindow) {
      printWindow.addEventListener('load', function() {
        printWindow.print();
      });
    }
  };
});