$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable for units
  let dt_units = $('.datatables-units');
  if (dt_units.length && typeof pageData !== 'undefined') {
    let dataTable = dt_units.DataTable({
      processing: true,
      serverSide: true,
      ajax: pageData.urls.unitsData,
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'code' },
        { data: 'description' },
        { data: 'products_count' },
        { data: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [],
      responsive: true
    });
  }

  // Handle add form submission
  $(document).on('submit', '#addUnitForm', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    $.ajax({
      url: pageData.urls.unitsStore,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false
    })
      .done(function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data.message || pageData.labels.unitCreatedSuccessfully,
            showConfirmButton: false,
            timer: 2000
          });
          $('#addUnitForm')[0].reset();
          $('.datatables-units').DataTable().ajax.reload();
          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddUnit'));
          offcanvas.hide();
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.data || pageData.labels.failedToCreateUnit
          });
        }
      })
      .fail(function (xhr) {
        let errorMessage = pageData.labels.failedToCreateUnit;
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: errorMessage
        });
      });
  });

  // Handle edit record click
  $(document).on('click', '.edit-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const code = $(this).data('code');
    const description = $(this).data('description');

    $('#edit_id').val(id);
    $('#edit_name').val(name);
    $('#edit_code').val(code);
    $('#edit_description').val(description);
  });

  // Handle edit form submission
  $(document).on('submit', '#editUnitForm', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = $('#edit_id').val();
    const url = pageData.urls.unitsUpdate.replace('__UNIT_ID__', id);
    
    // Add method spoofing for Laravel
    formData.append('_method', 'PUT');

    $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false
    })
      .done(function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.data.message || pageData.labels.unitUpdatedSuccessfully,
            showConfirmButton: false,
            timer: 2000
          });
          $('#editUnitForm')[0].reset();
          $('.datatables-units').DataTable().ajax.reload();
          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditUnit'));
          offcanvas.hide();
        } else {
          Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: response.data || pageData.labels.failedToUpdateUnit
          });
        }
      })
      .fail(function (xhr) {
        let errorMessage = pageData.labels.failedToUpdateUnit;
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: errorMessage
        });
      });
  });

  // Handle delete record
  $(document).on('click', '.delete-record', function () {
    const id = $(this).data('id');
    const url = pageData.urls.unitsDelete.replace('__UNIT_ID__', id);

    Swal.fire({
      title: pageData.labels.areYouSure,
      text: pageData.labels.deleteUnitWarning,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: pageData.labels.yesDelete,
      cancelButtonText: pageData.labels.cancel
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: 'DELETE'
        })
          .done(function (response) {
            if (response.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: pageData.labels.deleted,
                text: response.data.message || pageData.labels.unitDeletedSuccessfully,
                showConfirmButton: false,
                timer: 2000
              });
              $('.datatables-units').DataTable().ajax.reload();
            } else {
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: response.data || pageData.labels.failedToDeleteUnit
              });
            }
          })
          .fail(function (xhr) {
            let errorMessage = pageData.labels.failedToDeleteUnit;
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            Swal.fire({
              icon: 'error',
              title: pageData.labels.error,
              text: errorMessage
            });
          });
      }
    });
  });
});