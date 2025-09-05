@extends('layouts.layoutMaster')

@section('title', __('Transaction Categories'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('content')
  <x-breadcrumb :title="__('Categories')" :breadcrumbs="$breadcrumbs" />

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('Transaction Categories') }}</h5>
      @can('accountingcore.categories.store')
        @if($allowCustomCategories)
          <button type="button" class="btn btn-primary create-category">
            <i class="bx bx-plus me-1"></i> {{ __('Add Category') }}
          </button>
        @endif
      @endcan
    </div>
    <div class="card-datatable table-responsive">
      <table class="dt-responsive table" id="categoriesTable">
        <thead>
          <tr>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Parent Category') }}</th>
            <th>{{ __('Icon') }}</th>
            <th>{{ __('Color') }}</th>
            <th>{{ __('Transactions') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  {{-- Category Form Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="categoryFormOffcanvas" aria-labelledby="offcanvasLabel">
    <div class="offcanvas-header">
      <h5 id="offcanvasLabel">{{ __('Add Category') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form id="categoryForm">
        @csrf
        <input type="hidden" id="categoryId" name="id">
        
        <div class="mb-3">
          <label class="form-label" for="name">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="type">{{ __('Type') }} <span class="text-danger">*</span></label>
          <select class="form-select" id="type" name="type" required>
            <option value="">{{ __('Select Type') }}</option>
            <option value="income">{{ __('Income') }}</option>
            <option value="expense">{{ __('Expense') }}</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label" for="parent_id">{{ __('Parent Category') }}</label>
          <select class="form-select" id="parent_id" name="parent_id">
            <option value="">{{ __('None (Top Level)') }}</option>
            @foreach($parentCategories as $parent)
              <option value="{{ $parent->id }}" data-type="{{ $parent->type }}">{{ $parent->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label" for="icon">{{ __('Icon') }}</label>
          <input type="text" class="form-control" id="icon" name="icon" placeholder="bx bx-dollar">
          <small class="text-muted">{{ __('Use Boxicons class names (e.g., bx bx-dollar)') }}</small>
        </div>

        <div class="mb-3">
          <label class="form-label" for="color">{{ __('Color') }}</label>
          <input type="color" class="form-control" id="color" name="color">
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">{{ __('Save') }}</button>
          <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('page-script')
  @vite(['Modules/AccountingCore/resources/assets/js/categories.js'])
  <script>
    // Pass data from PHP to JavaScript
    window.pageData = {
      urls: {
        datatable: "{{ route('accountingcore.categories.datatable') }}",
        store: "{{ route('accountingcore.categories.store') }}",
        show: "{{ route('accountingcore.categories.show', ':id') }}",
        update: "{{ route('accountingcore.categories.update', ':id') }}",
        destroy: "{{ route('accountingcore.categories.destroy', ':id') }}"
      },
      labels: {
        addCategory: @json(__('Add Category')),
        editCategory: @json(__('Edit Category')),
        savedSuccessfully: @json(__('Category saved successfully')),
        deletedSuccessfully: @json(__('Category deleted successfully')),
        areYouSure: @json(__('Are you sure?')),
        cannotRevert: @json(__('You won\'t be able to revert this!')),
        yesDelete: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        success: @json(__('Success')),
        error: @json(__('Error')),
        errorOccurred: @json(__('Something went wrong'))
      }
    };
  </script>
@endsection