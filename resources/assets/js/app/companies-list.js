'use strict';

$(function () {
  // Assume CSRF token is set globally via a meta tag,
  // otherwise $.ajaxSetup can be done here or in a global app.js
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

  // --- URLs from Blade (passed in <script> block in companies/index.blade.php) ---
  // Ensure `urls` object with `ajax`, `destroyTemplate`, `toggleStatusTemplate` is available
  if (typeof urls === 'undefined') {
    console.error('JS URLs object is not defined. Ensure it is passed from Blade.');
    return; // Stop execution if critical URLs are missing
  }

  const dtCompanyTableElement = $('#companiesTable'); // Using the ID we set in Blade

  // --- Helper: Get URL with ID ---
  function getUrl(template, id) {
    if (!template) {
      console.error('URL template is undefined for ID ' + id);
      return '#'; // Return a safe default or handle error appropriately
    }
    return template.replace(':id', id); // Ensure placeholder matches the one in Blade
  }

  // --- DataTables Initialization ---
  let dtCompanyTable;
  if (dtCompanyTableElement.length) {
    dtCompanyTable = dtCompanyTableElement.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: urls.ajax,
        type: 'POST',
        // DataTables automatically sends CSRF token if $.ajaxSetup is configured globally
        // or you can add it here:
        // headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
      },
      columns: [
        { data: 'id', name: 'companies.id' }, // Match 'name' with server-side column name for sorting/searching
        { data: 'name', name: 'name' },
        { data: 'email_office', name: 'email_office', defaultContent: '-' },
        { data: 'phone_office', name: 'phone_office', defaultContent: '-' },
        { data: 'website', name: 'website', defaultContent: '-',
          render: function (data, type, row) {
            if (data) {
              return '<a href="' + data + '" target="_blank">' + data + '</a>';
            }
            return '-';
          }
        },
        { data: 'assigned_to', name: 'assignedToUser.name', defaultContent: 'N/A', orderable: false }, // Example if relationship field
        { data: 'is_active', name: 'is_active', className: 'text-center' }, // Server renders the switch
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      order: [[0, 'desc']], // Default order by ID descending
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: 'Search Companies...',
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      // You can add more DataTables options here as needed (dom, buttons, etc.)
    });
  } else {
    console.error('Company DataTable element not found.');
  }

  // --- Event Handler: Toggle Company Status ---
  dtCompanyTableElement.on('change', '.status-toggle', function () {
    const companyId = $(this).data('id');
    const url = $(this).data('url'); // Get URL from data attribute set in controller
    const isChecked = $(this).is(':checked');
    const checkbox = $(this); // Reference to the checkbox for reverting on error

    $.ajax({
      url: url,
      type: 'POST', // Or 'PUT'/'PATCH' if you prefer, ensure route matches
      data: {
        // Laravel automatically picks up _token from $.ajaxSetup or global meta tag
        is_active: isChecked ? 1 : 0 // Send new status
      },
      success: function (response) {
        if (response.code === 200) {
          Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: response.message,
            timer: 1000,
            showConfirmButton: false,
            customClass: { container: 'swal2-sm' } // smaller swal
          });
          // Optionally, you might want to redraw the row or table if other data changes
          // dtCompanyTable.ajax.reload(null, false); // Use this if server-side data changes beyond just status
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message || 'Could not update status.',
          });
          checkbox.prop('checked', !isChecked); // Revert checkbox on error
        }
      },
      error: function (jqXHR) {
        console.error('Status Toggle Error:', jqXHR);
        Swal.fire({
          icon: 'error',
          title: 'Request Failed',
          text: jqXHR.responseJSON?.message || 'Could not update status. Please try again.',
        });
        checkbox.prop('checked', !isChecked); // Revert checkbox on error
      }
    });
  });

  // --- Event Handler: Edit Company ---
  dtCompanyTableElement.on('click', '.edit-company-btn', function () {
    const url = $(this).data('url');
    if (url) {
      window.location.href = url;
    }
  });

  // --- Event Handler: Delete Company ---
  dtCompanyTableElement.on('click', '.delete-company', function () {
    const companyId = $(this).data('id');
    const url = $(this).data('url'); // Get URL from data attribute set in controller

    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({ // "Processing" modal
          title: 'Deleting...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
          url: url,
          type: 'DELETE', // Ensure route accepts DELETE
          // data: { _token: $('meta[name="csrf-token"]').attr('content') }, // If not using $.ajaxSetup
          success: function (response) {
            Swal.close();
            if (response.code === 200) {
              Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: response.message,
                customClass: { confirmButton: 'btn btn-success' }
              });
              dtCompanyTable.ajax.reload(null, false); // Reload DataTable without resetting pagination
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message || 'Could not delete company.',
                customClass: { confirmButton: 'btn btn-danger' }
              });
            }
          },
          error: function (jqXHR) {
            Swal.close();
            console.error('Delete Company Error:', jqXHR);
            Swal.fire({
              icon: 'error',
              title: 'Request Failed',
              text: jqXHR.responseJSON?.message || 'Could not delete company. Please try again.',
              customClass: { confirmButton: 'btn btn-danger' }
            });
          }
        });
      }
    });
  });

}); // End document ready
