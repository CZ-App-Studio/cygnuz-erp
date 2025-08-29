'use strict';

$(function () {
  // Set up CSRF token for all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Check if pageData is defined
  if (typeof pageData === 'undefined') {
    console.error('pageData object is not defined.');
    return;
  }

  const dtContactTableElement = $('#contactsTable');
  let dtContactTable;

  // DataTables Initialization
  if (dtContactTableElement.length) {
    dtContactTable = dtContactTableElement.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatable,
        type: 'POST',
      },
      columns: [
        { data: 'id', name: 'contacts.id' },
        { data: 'full_name', name: 'first_name' },
        { data: 'email_primary', name: 'email_primary', defaultContent: '-' },
        { data: 'phone_primary', name: 'phone_primary', defaultContent: '-' },
        { data: 'company_name', name: 'company.name', defaultContent: '-' },
        { data: 'assigned_to', name: 'assignedToUser.first_name', orderable: false, defaultContent: '-' },
        { data: 'is_active', name: 'is_active', className: 'text-center' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      order: [[0, 'desc']],
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: pageData.labels.searchPlaceholder,
        paginate: {
          next: '<i class="bx bx-chevron-right bx-sm"></i>',
          previous: '<i class="bx bx-chevron-left bx-sm"></i>'
        }
      },
      dom:
        '<"row"' +
        '<"col-sm-12 col-md-6"l>' +
        '<"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>' +
        '>' +
        '<"table-responsive"t>' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      buttons: []
    });
  }

  // Event Handler: Toggle Contact Status
  dtContactTableElement.on('change', '.status-toggle', function () {
    const url = $(this).data('url');
    const isChecked = $(this).is(':checked');
    const checkbox = $(this);

    $.ajax({
      url: url,
      type: 'POST',
      data: { is_active: isChecked ? 1 : 0 },
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success', 
            title: pageData.labels.updated, 
            text: response.data.message, 
            timer: 1500,
            showConfirmButton: false, 
            customClass: { 
              confirmButton: 'btn btn-primary' 
            }
          });
        } else {
          Swal.fire({ 
            icon: 'error', 
            title: pageData.labels.error, 
            text: response.data || 'Could not update status.',
            customClass: { 
              confirmButton: 'btn btn-primary' 
            }
          });
          checkbox.prop('checked', !isChecked);
        }
      },
      error: function (jqXHR) {
        Swal.fire({ 
          icon: 'error', 
          title: pageData.labels.requestFailed, 
          text: jqXHR.responseJSON?.data || 'Could not update status.',
          customClass: { 
            confirmButton: 'btn btn-primary' 
          }
        });
        checkbox.prop('checked', !isChecked);
      }
    });
  });

  // Event Handler: Delete Contact
  dtContactTableElement.on('click', '.delete-contact', function () {
    const contactId = $(this).data('id');
    const url = $(this).data('url');

    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.deleteWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.deleteButton,
      cancelButtonText: pageData.labels.cancelButton,
      customClass: { 
        confirmButton: 'btn btn-primary me-3', 
        cancelButton: 'btn btn-label-secondary' 
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        Swal.fire({ 
          title: pageData.labels.deleting, 
          allowOutsideClick: false, 
          didOpen: () => { 
            Swal.showLoading(); 
          } 
        });

        $.ajax({
          url: url,
          type: 'DELETE',
          success: function (response) {
            Swal.close();
            if (response.status === 'success') {
              Swal.fire({ 
                icon: 'success', 
                title: pageData.labels.deleted, 
                text: response.data.message, 
                customClass: { 
                  confirmButton: 'btn btn-success' 
                } 
              });
              dtContactTable.ajax.reload(null, false);
            } else {
              Swal.fire({ 
                icon: 'error', 
                title: pageData.labels.error, 
                text: response.data || 'Could not delete contact.',
                customClass: { 
                  confirmButton: 'btn btn-primary' 
                }
              });
            }
          },
          error: function (jqXHR) {
            Swal.close();
            Swal.fire({ 
              icon: 'error', 
              title: pageData.labels.requestFailed, 
              text: jqXHR.responseJSON?.data || 'Could not delete contact.',
              customClass: { 
                confirmButton: 'btn btn-primary' 
              }
            });
          }
        });
      }
    });
  });
});