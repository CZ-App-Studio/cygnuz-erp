$(function () {
  'use strict';

  // CSRF token setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize variables
  let dt_chart_of_accounts = $('.datatables-chart-of-accounts'),
    addNewAccountForm = $('#addNewAccountForm'),
    offcanvasAddAccount = $('#offcanvasAddAccount'),
    isEdit = false;

  // Initialize DataTable
  if (dt_chart_of_accounts.length) {
    var dt_accounts = dt_chart_of_accounts.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.accountData,
        data: function (d) {
          d.account_type = $('#filter-account-type').val();
          d.is_active = $('#filter-status').val();
          d.parent_id = $('#filter-parent').val();
        }
      },
      columns: [
        { data: 'code_name', name: 'code', orderable: true },
        { data: 'account_type_display', name: 'account_type', orderable: true },
        { data: 'sub_type_display', name: 'sub_type', orderable: true },
        { data: 'parent_display', name: 'parent.name', orderable: true },
        { data: 'opening_balance_formatted', name: 'opening_balance', orderable: true, className: 'text-end' },
        { data: 'current_balance_formatted', name: 'current_balance', orderable: true, className: 'text-end' },
        { data: 'is_active_display', name: 'is_active', orderable: true },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'asc']],
      dom: '<"row mx-1"<"col-sm-12 col-md-3" l><"col-sm-12 col-md-9"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-md-end justify-content-center flex-wrap me-1"<"me-3"f>>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Accounts...'
      },
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['code_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }

  // Filter functionality
  $('#filter-account-type, #filter-status, #filter-parent').on('change', function () {
    dt_accounts.ajax.reload();
  });

  // Reset filters
  $('#btn-reset-filters').on('click', function () {
    $('#filter-account-type, #filter-status, #filter-parent').val('').trigger('change');
    dt_accounts.ajax.reload();
  });

  // Account Type change handler
  $('#account_type').on('change', function () {
    const accountType = $(this).val();

    if (accountType) {
      // Load sub-types
      loadSubTypes(accountType);

      // Load parent accounts
      loadParentAccounts(accountType);

      // Auto-generate code if creating new account
      if (!isEdit && !$('#code').val()) {
        generateAccountCode();
      }
    } else {
      // Clear dependent dropdowns
      $('#sub_type').empty().append('<option value="">' + (pageData.text?.selectSubType || 'Select Sub Type') + '</option>');
      $('#parent_id').empty().append('<option value="">' + (pageData.text?.noParent || 'No Parent (Main Account)') + '</option>');
    }
  });

  // Parent account change handler
  $('#parent_id').on('change', function () {
    // Auto-generate code when parent changes
    if (!isEdit && $('#account_type').val()) {
      generateAccountCode();
    }
  });

  // Generate code button
  $('#btn-generate-code').on('click', function () {
    if ($('#account_type').val()) {
      generateAccountCode();
    } else {
      Swal.fire({
        icon: 'warning',
        title: 'Warning',
        text: pageData.text?.selectAccountType || 'Please select an account type first'
      });
    }
  });

  // Load sub-types for account type
  function loadSubTypes(accountType) {
    $.get(pageData.urls.getSubTypes, { account_type: accountType })
      .done(function (response) {
        const subTypeSelect = $('#sub_type');
        subTypeSelect.empty().append('<option value="">Select Sub Type</option>');

        if (response.success && response.sub_types) {
          $.each(response.sub_types, function (value, label) {
            subTypeSelect.append('<option value="' + value + '">' + label + '</option>');
          });
        }
      })
      .fail(function () {
        console.error('Failed to load sub-types');
      });
  }

  // Load parent accounts for account type
  function loadParentAccounts(accountType) {
    $.get(pageData.urls.getParentAccounts, { account_type: accountType })
      .done(function (response) {
        const parentSelect = $('#parent_id');
        parentSelect.empty().append('<option value="">No Parent (Main Account)</option>');

        if (response.success && response.parent_accounts) {
          $.each(response.parent_accounts, function (index, account) {
            parentSelect.append('<option value="' + account.id + '">' + account.text + '</option>');
          });
        }
      })
      .fail(function () {
        console.error('Failed to load parent accounts');
      });
  }

  // Generate account code
  function generateAccountCode() {
    const accountType = $('#account_type').val();
    const parentId = $('#parent_id').val() || null;

    if (!accountType) return;

    $.get(pageData.urls.generateCode, {
      account_type: accountType,
      parent_id: parentId
    })
      .done(function (response) {
        if (response.success) {
          $('#code').val(response.code);
        }
      })
      .fail(function () {
        console.error('Failed to generate account code');
      });
  }

  // Form validation and submission
  addNewAccountForm.on('submit', function (e) {
    e.preventDefault();

    // Clear previous validation errors
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    const formData = new FormData(this);
    const submitBtn = $(this).find('[type="submit"]');
    const originalBtnText = submitBtn.text();

    // Handle checkbox value properly
    formData.set('is_active', $('#is_active').is(':checked') ? '1' : '0');

    // Show loading state
    submitBtn.prop('disabled', true).text(pageData.text?.processing || 'Processing...');

    const url = isEdit
      ? pageData.urls.accountUpdate.replace('__ID__', $('#account_id').val())
      : pageData.urls.accountStore;

    const method = isEdit ? 'PUT' : 'POST';

    if (isEdit) {
      formData.append('_method', 'PUT');
    }

    $.ajax({
      url: url,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log('AJAX Success Response:', response); // Debug log
        if (response.status === 'success' || response.statusCode === 200) {
          // Show success message
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.data || pageData.text?.saved || 'Account saved successfully!',
            timer: 2000,
            showConfirmButton: false,
            customClass: {
              confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
          });

          // Reload DataTable
          dt_accounts.ajax.reload();

          // Close offcanvas and reset form
          offcanvasAddAccount.offcanvas('hide');
          resetForm();
        } else {
          // Handle non-success response
          console.log('Non-success response:', response); // Debug log
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: response.data || response.message || pageData.text?.error || 'An error occurred. Please try again.'
          });
        }
      },
      error: function (xhr) {
        console.log('AJAX Error Response:', xhr); // Debug log
        if (xhr.status === 422) {
          // Validation errors
          let errorData = xhr.responseJSON;
          console.log('Validation error data:', errorData); // Debug log
          
          if (errorData.errors) {
            // Laravel validation format - display field-specific errors
            $.each(errorData.errors, function (field, messages) {
              const input = $('[name="' + field + '"]');
              input.addClass('is-invalid');
              // Handle both array and string message formats
              const message = Array.isArray(messages) ? messages[0] : messages;
              input.next('.invalid-feedback').text(message);
            });
            
            // Show general SweetAlert for validation errors
            console.log('About to show validation error SweetAlert'); // Debug log
            setTimeout(() => {
              Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: 'Please check the highlighted fields and try again.',
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              }).then(() => {
                console.log('Validation error SweetAlert shown'); // Debug log
              });
            }, 100);
          } else {
            // Show general validation error message
            const errorMessage = errorData.message || 'Validation failed. Please check your input.';
            Swal.fire({
              icon: 'error',
              title: 'Validation Error!',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        } else {
          // Other errors (500, 404, etc.)
          let errorMessage = pageData.text?.error || 'An error occurred. Please try again.';
          if (xhr.responseJSON) {
            // Handle Error class format
            if (xhr.responseJSON.data) {
              errorMessage = xhr.responseJSON.data;
            } else if (xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
          } else if (xhr.responseText) {
            // Fallback to response text
            errorMessage = xhr.responseText;
          }
          
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        }
      },
      complete: function () {
        // Restore button state
        submitBtn.prop('disabled', false).text(originalBtnText);
      }
    });
  });

  // Edit account
  $(document).on('click', '.btn-edit-account', function () {
    const accountId = $(this).data('id');
    isEdit = true;

    // Change offcanvas title
    $('#offcanvasAddAccountLabel').text(pageData.text?.editTitle || 'Edit Account');

    // Load account data
    $.get(pageData.urls.accountShow.replace('__ID__', accountId))
      .done(function (response) {
        if (response.success || response.status === 'success') {
          // Handle both old format (response.data) and new format (response.data contains the actual data)
          const data = response.data;

          // Fill form fields
          $('#account_id').val(data.id);
          $('#account_type').val(data.account_type).trigger('change');
          $('#code').val(data.code);
          $('#name').val(data.name);
          $('#description').val(data.description);
          $('#opening_balance').val(data.opening_balance);
          $('#is_active').prop('checked', data.is_active);

          // Wait for account type change to load options, then set values
          setTimeout(function () {
            if (data.parent_id) {
              $('#parent_id').val(data.parent_id);
            }
            if (data.sub_type) {
              $('#sub_type').val(data.sub_type);
            }
          }, 500);

          // Show offcanvas
          offcanvasAddAccount.offcanvas('show');
        }
      })
      .fail(function () {
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: pageData.text?.error || 'An error occurred. Please try again.'
        });
      });
  });

  // Delete account
  $(document).on('click', '.btn-delete-account', function () {
    const accountId = $(this).data('id');

    Swal.fire({
      title: 'Are you sure?',
      text: pageData.text?.confirmDelete || 'Are you sure you want to delete this account?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: pageData.urls.accountDelete.replace('__ID__', accountId),
          method: 'DELETE',
          success: function (response) {
            if (response.success || response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: response.data || pageData.text?.deleted || 'Account deleted successfully!',
                timer: 2000,
                showConfirmButton: false,
                customClass: {
                  confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
              });

              dt_accounts.ajax.reload();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response.data || response.message || pageData.text?.error || 'An error occurred. Please try again.'
              });
            }
          },
          error: function (xhr) {
            let errorMessage = pageData.text?.error || 'An error occurred. Please try again.';
            if (xhr.responseJSON) {
              errorMessage = xhr.responseJSON.data || xhr.responseJSON.message || errorMessage;
            }
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: errorMessage,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  // Reset form when offcanvas is hidden
  offcanvasAddAccount.on('hidden.bs.offcanvas', function () {
    resetForm();
  });

  // Reset form function
  function resetForm() {
    isEdit = false;
    addNewAccountForm[0].reset();
    $('#account_id').val('');
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    $('#offcanvasAddAccountLabel').text(pageData.text?.addTitle || 'Add New Account');

    // Reset dropdowns
    $('#sub_type').empty().append('<option value="">' + (pageData.text?.selectSubType || 'Select Sub Type') + '</option>');
    $('#parent_id').empty().append('<option value="">' + (pageData.text?.noParent || 'No Parent (Main Account)') + '</option>');
  }
});
