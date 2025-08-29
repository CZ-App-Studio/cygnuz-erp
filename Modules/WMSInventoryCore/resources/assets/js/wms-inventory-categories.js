/**
 * WMS Inventory Categories JavaScript
 */
$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable for categories
  let dt_categories = $('.datatables-categories');
  if (dt_categories.length && typeof pageData !== 'undefined') {
    let dataTable = dt_categories.DataTable({
      processing: true,
      serverSide: true,
      ajax: pageData.urls.categoriesData,
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { data: 'parent_category' },
        { data: 'products_count' },
        { data: 'status' },
        { data: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [],
      language: {
        processing: pageData.labels.processing || 'Processing...',
        search: pageData.labels.search + ':' || 'Search:',
        lengthMenu: `${pageData.labels.show || 'Show'} _MENU_ ${pageData.labels.entries || 'entries'}`,
        info: `${pageData.labels.showing || 'Showing'} _START_ ${pageData.labels.to || 'to'} _END_ ${pageData.labels.of || 'of'} _TOTAL_ ${pageData.labels.entries || 'entries'}`,
        infoEmpty: `${pageData.labels.showing || 'Showing'} 0 ${pageData.labels.to || 'to'} 0 ${pageData.labels.of || 'of'} 0 ${pageData.labels.entries || 'entries'}`,
        infoFiltered: `(${pageData.labels['entries filtered from'] || 'filtered from'} _MAX_ ${pageData.labels['total entries'] || 'total entries'})`,
        infoPostFix: '',
        loadingRecords: pageData.labels.loading || 'Loading...',
        zeroRecords: pageData.labels['No matching records found'] || 'No matching records found',
        emptyTable: pageData.labels['No data available in table'] || 'No data available in table',
        paginate: {
          first: pageData.labels.first || 'First',
          previous: pageData.labels.previous || 'Previous',
          next: pageData.labels.next || 'Next',
          last: pageData.labels.last || 'Last'
        }
      },
      responsive: true
    });
  }

  // Handle form submissions for add category
  $(document).on('submit', '#addCategoryForm', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    $.post(pageData.urls.categoriesStore, formData)
      .done(function (response) {
        if (response.status === 'success') {
          Swal.fire('Success!', response.data.message || 'Category created successfully.', 'success');
          $('#addCategoryForm')[0].reset();
          $('.datatables-categories').DataTable().ajax.reload();
          const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAddCategory'));
          offcanvas.hide();
        } else {
          Swal.fire('Error!', response.data || 'Failed to create category.', 'error');
        }
      })
      .fail(function () {
        Swal.fire('Error!', 'Failed to create category.', 'error');
      });
  });

  // Handle edit category
  $(document).on('click', '.btn-edit', function () {
    const categoryId = $(this).data('id');
    // Load category data and show edit offcanvas
  });

  // Handle delete category
  $(document).on('click', '.btn-delete', function () {
    const categoryId = $(this).data('id');
    const categoryName = $(this).data('name');

    Swal.fire({
      title: 'Are you sure?',
      text: `You are about to delete "${categoryName}". This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        deleteCategory(categoryId);
      }
    });
  });

  function deleteCategory(categoryId) {
    if (pageData && pageData.urls.categoriesDelete) {
      const url = pageData.urls.categoriesDelete.replace('__CATEGORY_ID__', categoryId);
      
      $.post(url, {
        _method: 'DELETE'
      })
      .done(function (response) {
        if (response.status === 'success') {
          Swal.fire('Deleted!', response.data.message || 'Category deleted successfully.', 'success');
          $('.datatables-categories').DataTable().ajax.reload();
        } else {
          Swal.fire('Error!', response.data || 'Failed to delete category.', 'error');
        }
      })
      .fail(function () {
        Swal.fire('Error!', 'Failed to delete category.', 'error');
      });
    }
  }
});