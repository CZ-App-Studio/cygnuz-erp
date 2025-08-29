'use strict';

$(function () {
  // 1. SETUP & VARIABLE DECLARATIONS
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  if (typeof pageData === 'undefined' || !pageData.urls) {
    console.error('AccountingCore Tax Rates: pageData object with URLs is not defined.');
    return;
  }

  // DOM Elements
  const offcanvasElement = document.getElementById('offcanvasTaxRateForm');
  const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
  const taxRateForm = document.getElementById('taxRateForm');
  const saveTaxRateBtn = $('#saveTaxRateBtn');
  const dtTaxRatesTableElement = $('.datatables-tax-rates');
  let dtTaxRatesTable;

  // 2. HELPER FUNCTIONS
  const getUrl = (template, id) => template.replace('__ID__', id);

  const resetFormValidation = (form) => {
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').text('');
  };

  const resetOffcanvasForm = () => {
    resetFormValidation(taxRateForm);
    taxRateForm.reset();
    $('#tax_rate_id').val('');
    $('#taxRateFormMethod').val('POST');
    $('#offcanvasTaxRateFormLabel').text('{{ __("Add Tax Rate") }}');
    $('#tax_rate_is_active').prop('checked', true);
    $('#tax_rate_is_default').prop('checked', false);
    $('#tax_rate_type').val('percentage');
    saveTaxRateBtn.prop('disabled', false).html('{{ __("Save") }}');
  };

  const populateOffcanvasForEdit = (taxRate) => {
    resetOffcanvasForm();
    $('#offcanvasTaxRateFormLabel').text('{{ __("Edit Tax Rate") }}');
    $('#tax_rate_id').val(taxRate.id);
    $('#taxRateFormMethod').val('PUT');
    $('#tax_rate_name').val(taxRate.name);
    $('#tax_rate_description').val(taxRate.description || '');
    $('#tax_rate_rate').val(taxRate.rate);
    $('#tax_rate_type').val(taxRate.type || 'percentage');
    $('#tax_rate_country').val(taxRate.country || '');
    $('#tax_rate_state').val(taxRate.state || '');
    $('#tax_rate_is_active').prop('checked', taxRate.is_active);
    $('#tax_rate_is_default').prop('checked', taxRate.is_default);
    offcanvas.show();
  };

  const showValidationErrors = (errors) => {
    resetFormValidation(taxRateForm);
    $.each(errors, function (key, value) {
      const input = $(taxRateForm).find(`[name="${key}"]`);
      if (input.length) {
        input.addClass('is-invalid');
        input.siblings('.invalid-feedback').text(value[0]);
      }
    });
    $(taxRateForm).find('.is-invalid:first').focus();
  };

  // 3. DATATABLES INITIALIZATION
  if (dtTaxRatesTableElement.length) {
    dtTaxRatesTable = dtTaxRatesTableElement.DataTable({
      processing: true,
      serverSide: true,
      ajax: { url: pageData.urls.ajax, type: 'GET' },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'name', name: 'name' },
        { data: 'description', name: 'description', defaultContent: '-' },
        { data: 'rate_formatted', name: 'rate', className: 'text-center' },
        { data: 'type_display', name: 'type', className: 'text-center' },
        { data: 'status_display', name: 'is_active', className: 'text-center' },
        { data: 'is_default_display', name: 'is_default', className: 'text-center' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      order: [[3, 'asc']], // Order by rate ascending
      language: { search: '', searchPlaceholder: 'Search Tax Rates...' },
      pageLength: 25,
      responsive: true
    });
  }

  // 4. OFFCANVAS MANAGEMENT
  $('#add-new-tax-rate-btn').on('click', function () {
    resetOffcanvasForm();
    offcanvas.show();
  });

  dtTaxRatesTableElement.on('click', '.edit-tax-rate', function () {
    const url = $(this).data('url');
    $.get(url, populateOffcanvasForEdit)
      .fail(() => Swal.fire('Error', 'Could not fetch tax rate details.', 'error'));
  });

  if (offcanvasElement) {
    offcanvasElement.addEventListener('hidden.bs.offcanvas', resetOffcanvasForm);
  }

  // 5. FORM SUBMISSION (AJAX)
  $(taxRateForm).on('submit', function (e) {
    e.preventDefault();
    resetFormValidation(this);

    const taxRateId = $('#tax_rate_id').val();
    let url = pageData.urls.store;
    const formData = new FormData(this);

    // Handle checkboxes
    formData.set('is_active', $('#tax_rate_is_active').is(':checked') ? '1' : '0');
    formData.set('is_default', $('#tax_rate_is_default').is(':checked') ? '1' : '0');

    if (taxRateId) {
      url = getUrl(pageData.urls.updateTemplate, taxRateId);
    }

    const originalButtonText = saveTaxRateBtn.html();
    saveTaxRateBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ __("Saving...") }}');

    $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          offcanvas.hide();
          Swal.fire({
            icon: 'success',
            title: '{{ __("Success!") }}',
            text: response.message,
            timer: 1500,
            showConfirmButton: false
          });
          dtTaxRatesTable.ajax.reload(null, false);
        } else {
          Swal.fire('{{ __("Error") }}', response.message || '{{ __("Operation failed.") }}', 'error');
        }
      },
      error: function (jqXHR) {
        if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
          showValidationErrors(jqXHR.responseJSON.errors);
        } else {
          Swal.fire('{{ __("Error") }}', jqXHR.responseJSON?.message || '{{ __("An unexpected error occurred.") }}', 'error');
        }
      },
      complete: function () {
        saveTaxRateBtn.prop('disabled', false).html(originalButtonText);
      }
    });
  });

  // 6. DELETE TAX RATE (AJAX)
  dtTaxRatesTableElement.on('click', '.delete-tax-rate', function () {
    const url = $(this).data('url');

    Swal.fire({
      title: '{{ __("Are you sure?") }}',
      text: '{{ __("This tax rate will be permanently deleted. Make sure no invoices or transactions are using this tax rate.") }}',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '{{ __("Yes, delete it!") }}',
      cancelButtonText: '{{ __("Cancel") }}',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({
          title: '{{ __("Deleting...") }}',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
          url: url,
          type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.success) {
              Swal.fire('{{ __("Deleted!") }}', response.message, 'success');
              dtTaxRatesTable.ajax.reload(null, false);
            } else {
              Swal.fire('{{ __("Error!") }}', response.message || '{{ __("Could not delete tax rate.") }}', 'error');
            }
          },
          error: function (jqXHR) {
            Swal.close();
            Swal.fire('{{ __("Error!") }}', jqXHR.responseJSON?.message || '{{ __("An unexpected error occurred.") }}', 'error');
          }
        });
      }
    });
  });
});
