@extends('master-data.base')

@push('page-script')
@vite(['resources/assets/js/master-data-list.js'])
@endpush

@section('page-actions')
@if($permissions['can_create'] ?? true)
<a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary">
  <i class="bx bx-plus me-1"></i>
  {{ __('Add New') }}
</a>
@endif
@endsection

@section('main-content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ $tableTitle ?? $pageTitle }}</h5>
    <div class="d-flex gap-2">
      @if(isset($customActions))
        @foreach($customActions as $action)
          <button type="button" class="btn btn-{{ $action['variant'] ?? 'outline-secondary' }}" 
                  onclick="{{ $action['onclick'] ?? '' }}" 
                  {!! isset($action['attributes']) ? $action['attributes'] : '' !!}>
            @if(isset($action['icon']))
              <i class="{{ $action['icon'] }} me-1"></i>
            @endif
            {{ $action['label'] }}
          </button>
        @endforeach
      @endif
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="masterDataTable" class="table table-striped table-hover">
        <thead>
          <tr>
            @foreach($columns as $column)
              <th>{{ $column['title'] }}</th>
            @endforeach
            <th width="120">{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
$(function () {
  // Initialize DataTable
  window.dataTable = $('#masterDataTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: pageData.urls.datatable,
      type: 'GET'
    },
    columns: [
      @foreach($columns as $column)
      {
        data: '{{ $column['data'] }}',
        name: '{{ $column['name'] ?? $column['data'] }}',
        @if(isset($column['searchable']) && !$column['searchable'])
        searchable: false,
        @endif
        @if(isset($column['orderable']) && !$column['orderable'])
        orderable: false,
        @endif
        @if(isset($column['className']))
        className: '{{ $column['className'] }}',
        @endif
        @if(isset($column['render']))
        render: {!! $column['render'] !!},
        @endif
      },
      @endforeach
      {
        data: 'actions',
        name: 'actions',
        searchable: false,
        orderable: false,
        className: 'text-center'
      }
    ],
    order: [[0, 'desc']], // Default sort
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    responsive: true,
    language: {
      search: pageData.labels.search,
      lengthMenu: "_MENU_",
      info: "_START_ to _END_ of _TOTAL_ entries",
      infoEmpty: pageData.labels.noRecords,
      infoFiltered: "(filtered from _MAX_ total entries)",
      paginate: {
        first: "First",
        last: "Last",
        next: "Next",
        previous: "Previous"
      },
      processing: '<div class="d-flex justify-content-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">' + pageData.labels.loading + '</span></div></div>'
    },
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
    drawCallback: function() {
      // Re-initialize tooltips after table redraw
      $('[data-bs-toggle="tooltip"]').tooltip();
    }
  });

  // Custom search functionality
  $('#customSearch').on('keyup', function() {
    window.dataTable.search(this.value).draw();
  });

  // Bulk actions if needed
  @if(isset($bulkActions) && count($bulkActions) > 0)
  $('#selectAll').on('change', function() {
    $('.row-select').prop('checked', this.checked);
    toggleBulkActions();
  });

  $(document).on('change', '.row-select', function() {
    toggleBulkActions();
  });

  function toggleBulkActions() {
    const selectedRows = $('.row-select:checked').length;
    if (selectedRows > 0) {
      $('#bulkActionsContainer').show();
      $('#selectedCount').text(selectedRows);
    } else {
      $('#bulkActionsContainer').hide();
    }
  }
  @endif
});

// Export function for other scripts
window.refreshTable = function() {
  if (typeof window.dataTable !== 'undefined') {
    window.dataTable.ajax.reload(null, false);
  }
};
</script>
@endpush