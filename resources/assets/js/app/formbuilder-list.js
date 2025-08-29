$(function () {
  'use strict';

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  const dt_forms = $('.datatables-forms');
  if (dt_forms.length) {
    const dt = dt_forms.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.formsData,
        type: 'GET'
      },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'name', name: 'name' },
        { data: 'description', name: 'description', orderable: false },
        { data: 'is_active', name: 'is_active', orderable: false },
        { data: 'is_public', name: 'is_public', orderable: false },
        { data: 'submissions_count', name: 'submissions_count', orderable: false },
        { data: 'created_by', name: 'created_by', orderable: false },
        { data: 'created_at', name: 'created_at' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      responsive: true,
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        }
      }
    });
  }

  // Global functions for DataTable actions
  window.duplicateForm = function(formId) {
    Swal.fire({
      title: pageData.labels.confirmDuplicate,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yes,
      cancelButtonText: pageData.labels.no
    }).then((result) => {
      if (result.isConfirmed) {
        const url = pageData.urls.formDuplicate.replace('__ID__', formId);

        $.ajax({
          url: url,
          type: 'POST',
          success: function(response) {
            if (response.status === 'success') {
              Swal.fire({
                title: pageData.labels.success,
                text: response.data.message,
                icon: 'success'
              });
              if (response.data.redirect) {
                setTimeout(() => {
                  window.location.href = response.data.redirect;
                }, 1500);
              } else {
                dt_forms.DataTable().ajax.reload();
              }
            }
          },
          error: function(xhr) {
            Swal.fire({
              title: pageData.labels.error,
              text: xhr.responseJSON?.data || pageData.labels.errorOccurred,
              icon: 'error'
            });
          }
        });
      }
    });
  };

  window.deleteForm = function(formId) {
    Swal.fire({
      title: pageData.labels.confirmDelete,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yes,
      cancelButtonText: pageData.labels.no,
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        const url = pageData.urls.formDelete.replace('__ID__', formId);

        $.ajax({
          url: url,
          type: 'DELETE',
          success: function(response) {
            if (response.status === 'success') {
              Swal.fire({
                title: pageData.labels.success,
                text: response.data,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              });
              dt_forms.DataTable().ajax.reload();
            }
          },
          error: function(xhr) {
            Swal.fire({
              title: pageData.labels.error,
              text: xhr.responseJSON?.data || pageData.labels.errorOccurred,
              icon: 'error'
            });
          }
        });
      }
    });
  };
});
