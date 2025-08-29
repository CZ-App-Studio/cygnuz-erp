$(function () {
  'use strict';

  // CSRF Token Setup
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
  const dtBudgets = $('.datatables-budgets');
  let budgetsTable;

  if (dtBudgets.length) {
    budgetsTable = dtBudgets.DataTable({
      ajax: {
        url: pageData.urls.budgetsData,
        data: function (d) {
          d.fiscal_period_id = $('#filter-fiscal-period').val();
          d.chart_of_account_id = $('#filter-account').val();
          d.status = $('#filter-status').val();
        }
      },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'name', name: 'name' },
        { data: 'fiscal_period', name: 'fiscal_period' },
        { data: 'account', name: 'account' },
        { data: 'budget_amount', name: 'budget_amount' },
        { data: 'actual_amount', name: 'actual_amount' },
        { data: 'variance_amount', name: 'variance_amount' },
        { data: 'variance_percentage', name: 'variance_percentage' },
        { data: 'status', name: 'status' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        }
      }
    });
  }

  // Apply filters
  $('#apply-filters').on('click', function() {
    if (budgetsTable) {
      budgetsTable.ajax.reload();
    }
  });

  // Clear filters
  $('#clear-filters').on('click', function() {
    $('#filter-fiscal-period, #filter-account, #filter-status').val('').trigger('change');
    if (budgetsTable) {
      budgetsTable.ajax.reload();
    }
  });

  // Edit Budget
  $(document).on('click', '.edit-budget', function() {
    const budgetId = $(this).data('id');

    // Get budget data (you would normally fetch this via AJAX)
    const form = $('#budgetForm');
    form.data('budget-id', budgetId);
    $('#budgetOffcanvasLabel').text('Edit Budget');
    $('#budgetOffcanvas').offcanvas('show');

    // Here you would populate the form with budget data
    // For now, we'll just show the form
  });

  // Budget Form Submit
  $('#budgetForm').on('submit', function(e) {
    e.preventDefault();

    const form = $(this);
    const budgetId = form.data('budget-id');
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();

    if (!budgetId) return;

    // Show loading
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');

    // Clear previous errors
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();

    $.ajax({
      url: pageData.urls.budgetsUpdate.replace('__ID__', budgetId),
      type: 'PUT',
      data: form.serialize(),
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: response.message,
          customClass: {
            confirmButton: 'btn btn-success'
          },
          buttonsStyling: false,
          timer: 1500,
          timerProgressBar: true
        });

        // Close offcanvas and reload table
        $('#budgetOffcanvas').offcanvas('hide');
        if (budgetsTable) {
          budgetsTable.ajax.reload();
        }
      },
      error: function(xhr) {
        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON.errors;
          Object.keys(errors).forEach(function(field) {
            const input = form.find(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
          });
        } else {
          // Other errors
          let errorMessage = 'An error occurred while updating the budget.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }

          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
          });
        }
      },
      complete: function() {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Delete Budget
  $(document).on('click', '.delete-budget', function() {
    const budgetId = $(this).data('id');

    Swal.fire({
      title: 'Delete Budget?',
      text: 'This action cannot be undone. Are you sure you want to delete this budget?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Delete',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.budgetsDelete.replace('__ID__', budgetId),
          type: 'DELETE',
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false,
              timer: 1500,
              timerProgressBar: true
            });

            if (budgetsTable) {
              budgetsTable.ajax.reload();
            }
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while deleting the budget.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-danger'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Approve Budget
  $(document).on('click', '.approve-budget', function() {
    const budgetId = $(this).data('id');

    Swal.fire({
      title: 'Approve Budget?',
      text: 'Are you sure you want to approve this budget?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Approve',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.budgetsApprove.replace('__ID__', budgetId),
          type: 'POST',
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Approved!',
              text: response.message,
              customClass: {
                confirmButton: 'btn btn-success'
              },
              buttonsStyling: false,
              timer: 1500,
              timerProgressBar: true
            });

            if (budgetsTable) {
              budgetsTable.ajax.reload();
            }
          },
          error: function(xhr) {
            let errorMessage = 'An error occurred while approving the budget.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-danger'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Variance Report
  $('#variance-report').on('click', function() {
    const fiscalPeriodId = $('#filter-fiscal-period').val();

    if (!fiscalPeriodId) {
      Swal.fire({
        icon: 'warning',
        title: 'Select Fiscal Period',
        text: 'Please select a fiscal period to generate the variance report.',
        customClass: {
          confirmButton: 'btn btn-warning'
        },
        buttonsStyling: false
      });
      return;
    }

    $.ajax({
      url: pageData.urls.varianceReport,
      type: 'GET',
      data: { fiscal_period_id: fiscalPeriodId },
      success: function(response) {
        populateVarianceReport(response);
        $('#varianceReportModal').modal('show');
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: 'Failed to generate variance report.',
          customClass: {
            confirmButton: 'btn btn-danger'
          },
          buttonsStyling: false
        });
      }
    });
  });

  // Populate Variance Report
  function populateVarianceReport(data) {
    const summary = data.summary;
    const budgets = data.budgets;

    // Create summary cards
    const summaryHtml = `
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <h6 class="card-title">Total Budget</h6>
            <h4 class="text-primary">$${Number(summary.total_budget).toLocaleString()}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <h6 class="card-title">Total Actual</h6>
            <h4 class="text-info">$${Number(summary.total_actual).toLocaleString()}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <h6 class="card-title">Total Variance</h6>
            <h4 class="${summary.total_variance >= 0 ? 'text-success' : 'text-danger'}">
              $${Number(summary.total_variance).toLocaleString()}
            </h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <h6 class="card-title">Budget Count</h6>
            <h4 class="text-secondary">${summary.count}</h4>
          </div>
        </div>
      </div>
    `;

    $('#variance-summary').html(summaryHtml);

    // Populate table
    const tbody = $('#variance-table tbody');
    tbody.empty();

    budgets.forEach(function(budget) {
      const variance = budget.variance_amount;
      const varianceClass = variance >= 0 ? 'text-success' : 'text-danger';
      const variancePercentage = budget.variance_percentage;

      const row = `
        <tr>
          <td>${budget.chart_of_account.code} - ${budget.chart_of_account.name}</td>
          <td>$${Number(budget.budget_amount).toLocaleString()}</td>
          <td>$${Number(budget.actual_amount).toLocaleString()}</td>
          <td class="${varianceClass}">$${Number(variance).toLocaleString()}</td>
          <td class="${varianceClass}">${Number(variancePercentage).toFixed(2)}%</td>
          <td><span class="badge bg-label-${budget.status === 'active' ? 'success' : 'info'}">${budget.status}</span></td>
        </tr>
      `;

      tbody.append(row);
    });
  }

  // Reset offcanvas form when closed
  $('#budgetOffcanvas').on('hidden.bs.offcanvas', function() {
    const form = $('#budgetForm');
    form[0].reset();
    form.removeData('budget-id');
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
  });
});
