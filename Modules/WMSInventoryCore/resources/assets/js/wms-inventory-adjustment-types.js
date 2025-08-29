/**
 * WMS Inventory Adjustment Types JavaScript
 */
$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable for adjustment types
  let dt_adjustmentTypes = $('.datatables-adjustment-types');
  if (dt_adjustmentTypes.length && typeof pageData !== 'undefined') {
    let dataTable = dt_adjustmentTypes.DataTable({
      processing: true,
      serverSide: true,
      ajax: pageData.urls.datatable,
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { data: 'effect_type' },
        { data: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [],
      language: {
        processing: pageData.labels.processing,
        search: pageData.labels.search + ':',
        lengthMenu: `${pageData.labels.show} _MENU_ ${pageData.labels.entries}`,
        info: `${pageData.labels.showing} _START_ ${pageData.labels.to} _END_ ${pageData.labels.of} _TOTAL_ ${pageData.labels.entries}`,
        infoEmpty: `${pageData.labels.showing} 0 ${pageData.labels.to} 0 ${pageData.labels.of} 0 ${pageData.labels.entries}`,
        infoFiltered: `(filtered from _MAX_ total entries)`,
        infoPostFix: '',
        loadingRecords: pageData.labels.loading,
        zeroRecords: pageData.labels.noMatchingRecords,
        emptyTable: pageData.labels.noDataAvailable,
        paginate: {
          first: pageData.labels.first,
          previous: pageData.labels.previous,
          next: pageData.labels.next,
          last: pageData.labels.last
        }
      },
      responsive: true
    });
  }

  // Handle Add form submission
  $(document).on('submit', '#addAdjustmentTypeForm', function (e) {
    e.preventDefault();
    const form = $(this)[0];
    const formData = new FormData(form);
    
    $.ajax({
      url: pageData.urls.store,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            title: pageData.labels.success,
            text: response.data.message || pageData.labels.adjustmentTypeCreated,
            icon: 'success',
            showConfirmButton: false,
            timer: 2000
          });
          form.reset();
          $('.datatables-adjustment-types').DataTable().ajax.reload();
          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddAdjustmentType'));
          offcanvas.hide();
        } else {
          Swal.fire({
            title: pageData.labels.error,
            text: response.data || pageData.labels.failedToCreate,
            icon: 'error'
          });
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.failedToCreate;
        
        if (xhr.responseJSON && xhr.responseJSON.errors) {
          const errors = Object.values(xhr.responseJSON.errors).flat();
          errorMessage = errors.join('<br>');
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        
        Swal.fire({
          title: pageData.labels.error,
          html: errorMessage,
          icon: 'error'
        });
      }
    });
  });

  // Handle Edit button click
  $(document).on('click', '.edit-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const description = $(this).data('description');
    const effectType = $(this).data('effect-type');

    $('#edit_id').val(id);
    $('#edit_name').val(name);
    $('#edit_description').val(description);
    $('#edit_operation_type').val(effectType);
  });

  // Handle Edit form submission
  $(document).on('submit', '#editAdjustmentTypeForm', function (e) {
    e.preventDefault();
    const form = $(this)[0];
    const formData = new FormData(form);
    const id = $('#edit_id').val();
    
    // Add method spoofing for PUT request
    formData.append('_method', 'PUT');
    
    $.ajax({
      url: pageData.urls.update.replace('__TYPE_ID__', id),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            title: pageData.labels.success,
            text: response.data.message || pageData.labels.adjustmentTypeUpdated,
            icon: 'success',
            showConfirmButton: false,
            timer: 2000
          });
          $('.datatables-adjustment-types').DataTable().ajax.reload();
          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditAdjustmentType'));
          offcanvas.hide();
        } else {
          Swal.fire({
            title: pageData.labels.error,
            text: response.data || pageData.labels.failedToUpdate,
            icon: 'error'
          });
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.failedToUpdate;
        
        if (xhr.responseJSON && xhr.responseJSON.errors) {
          const errors = Object.values(xhr.responseJSON.errors).flat();
          errorMessage = errors.join('<br>');
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        
        Swal.fire({
          title: pageData.labels.error,
          html: errorMessage,
          icon: 'error'
        });
      }
    });
  });

  // Handle Delete button click
  $(document).on('click', '.delete-record', function () {
    const id = $(this).data('id');
    
    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.confirmDeleteText,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: pageData.labels.yesDelete,
      cancelButtonText: pageData.labels.cancel,
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        
        $.ajax({
          url: pageData.urls.destroy.replace('__TYPE_ID__', id),
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                title: pageData.labels.success,
                text: response.data.message || pageData.labels.adjustmentTypeDeleted,
                icon: 'success',
                showConfirmButton: false,
                timer: 2000
              });
              $('.datatables-adjustment-types').DataTable().ajax.reload();
            } else {
              Swal.fire({
                title: pageData.labels.error,
                text: response.data || pageData.labels.failedToDelete,
                icon: 'error'
              });
            }
          },
          error: function (xhr) {
            let errorMessage = pageData.labels.failedToDelete;
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
              title: pageData.labels.error,
              text: errorMessage,
              icon: 'error'
            });
          }
        });
      }
    });
  });

  // Reset forms when offcanvas is hidden
  $('#offcanvasAddAdjustmentType').on('hidden.bs.offcanvas', function () {
    $('#addAdjustmentTypeForm')[0].reset();
    $('#addAdjustmentTypeForm').removeClass('was-validated');
  });

  $('#offcanvasEditAdjustmentType').on('hidden.bs.offcanvas', function () {
    $('#editAdjustmentTypeForm')[0].reset();
    $('#editAdjustmentTypeForm').removeClass('was-validated');
  });
});