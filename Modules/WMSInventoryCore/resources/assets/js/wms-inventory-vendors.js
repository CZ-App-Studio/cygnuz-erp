$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Initialize status filter
  $('#status-filter').select2({
    placeholder: pageData.labels.selectStatus,
    allowClear: true
  });

  // Initialize DataTable
  const table = $('#vendors-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      data: function (d) {
        d.status = $('#status-filter').val();
      }
    },
    columns: [
      { data: 'id', name: 'id' },
      { data: 'name', name: 'name' },
      { data: 'company_name', name: 'company_name' },
      { data: 'email', name: 'email' },
      { data: 'phone_number', name: 'phone_number' },
      { data: 'status', name: 'status', orderable: false },
      { data: 'payment_terms', name: 'payment_terms' },
      { data: 'lead_time_days', name: 'lead_time_days' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[0, 'desc']],
    responsive: true,
    language: {
      paginate: {
        previous: '&nbsp;',
        next: '&nbsp;'
      }
    },
    drawCallback: function() {
      // Initialize tooltips after table draw
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });
    }
  });

  // Status filter change
  $('#status-filter').on('change', function () {
    table.draw();
  });

  // Reset filters button
  $('#reset-filters').on('click', function() {
    $('#status-filter').val('').trigger('change');
    table.draw();
  });

  // Initialize form offcanvas if exists
  const formOffcanvasEl = document.getElementById('vendorFormOffcanvas');
  if (formOffcanvasEl) {
    window.vendorFormOffcanvas = new bootstrap.Offcanvas(formOffcanvasEl);
  }

  // Handle form submission
  $('#vendorForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const url = form.attr('action');
    const method = form.find('input[name="_method"]').val() || 'POST';
    const formData = new FormData(this);

    // Disable submit button
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + pageData.labels.saving);

    $.ajax({
      url: url,
      type: method === 'PUT' ? 'POST' : method,
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.status === 'success') {
          // Show success message
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data.message || pageData.labels.vendorSaved,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            // Hide offcanvas
            if (window.vendorFormOffcanvas) {
              window.vendorFormOffcanvas.hide();
            }
            // Reload table
            table.ajax.reload();
            // Reset form
            form[0].reset();
          });
        }
      },
      error: function(xhr) {
        let errorMessage = pageData.labels.errorOccurred;
        
        if (xhr.responseJSON) {
          if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.responseJSON.errors) {
            // Validation errors
            const errors = xhr.responseJSON.errors;
            errorMessage = Object.values(errors).flat().join('<br>');
          }
        }
        
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          html: errorMessage,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      },
      complete: function() {
        // Re-enable submit button
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });
});

// Edit vendor function - Make it globally accessible
window.editVendor = function(id) {
  const url = pageData.urls.vendorsEdit.replace('__VENDOR_ID__', id);
  
  // If using offcanvas form
  if (window.vendorFormOffcanvas) {
    // Load vendor data
    $.get(url, function(response) {
      if (response.status === 'success') {
        const vendor = response.data.vendor;
        const form = $('#vendorForm');
        
        // Set form action for update
        form.attr('action', pageData.urls.vendorsUpdate.replace('__VENDOR_ID__', id));
        
        // Add method field for PUT
        if (!form.find('input[name="_method"]').length) {
          form.append('<input type="hidden" name="_method" value="PUT">');
        }
        
        // Populate form fields
        form.find('#vendor_id').val(vendor.id);
        form.find('#name').val(vendor.name);
        form.find('#company_name').val(vendor.company_name);
        form.find('#email').val(vendor.email);
        form.find('#phone_number').val(vendor.phone_number);
        form.find('#address').val(vendor.address);
        form.find('#city').val(vendor.city);
        form.find('#state').val(vendor.state);
        form.find('#country').val(vendor.country);
        form.find('#postal_code').val(vendor.postal_code);
        form.find('#tax_number').val(vendor.tax_number);
        form.find('#website').val(vendor.website);
        form.find('#status').val(vendor.status);
        form.find('#payment_terms').val(vendor.payment_terms);
        form.find('#lead_time_days').val(vendor.lead_time_days);
        form.find('#minimum_order_value').val(vendor.minimum_order_value);
        form.find('#notes').val(vendor.notes);
        
        // Update offcanvas title
        $('#vendorFormOffcanvasLabel').text(pageData.labels.editVendor);
        
        // Show offcanvas
        window.vendorFormOffcanvas.show();
      }
    });
  } else {
    // Redirect to edit page
    window.location.href = url;
  }
}

// View vendor function - Make it globally accessible
window.viewVendor = function(id) {
  const url = pageData.urls.vendorsShow.replace('__VENDOR_ID__', id);
  window.location.href = url;
}

// Delete vendor function - Make it globally accessible
window.deleteVendor = function(id) {
  Swal.fire({
    title: pageData.labels.confirmDelete,
    text: pageData.labels.confirmDeleteText,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: pageData.labels.confirmDeleteButton,
    cancelButtonText: pageData.labels.cancel,
    customClass: {
      confirmButton: 'btn btn-primary me-3',
      cancelButton: 'btn btn-label-secondary'
    },
    buttonsStyling: false
  }).then(function (result) {
    if (result.value) {
      const url = pageData.urls.vendorsDelete.replace('__VENDOR_ID__', id);
      
      $.ajax({
        url: url,
        type: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: pageData.labels.deleted,
              text: response.data.message || pageData.labels.deletedText,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(() => {
              $('#vendors-table').DataTable().ajax.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: response.data || pageData.labels.couldNotDelete,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        },
        error: function (xhr) {
          let errorMessage = pageData.labels.couldNotDelete;
          
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }
          
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      });
    }
  });
}

// Add new vendor function - Make it globally accessible
window.addVendor = function() {
  if (window.vendorFormOffcanvas) {
    const form = $('#vendorForm');
    
    // Reset form
    form[0].reset();
    
    // Set form action for store
    form.attr('action', pageData.urls.vendorsStore);
    
    // Remove method field
    form.find('input[name="_method"]').remove();
    
    // Update offcanvas title
    $('#vendorFormOffcanvasLabel').text(pageData.labels.addVendor);
    
    // Show offcanvas
    window.vendorFormOffcanvas.show();
  } else {
    // Redirect to create page
    window.location.href = pageData.urls.vendorsCreate;
  }
}