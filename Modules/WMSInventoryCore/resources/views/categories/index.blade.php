@extends('layouts.layoutMaster')

@section('title', __('Product Categories'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        categoriesData: @json(route('wmsinventorycore.categories.data')),
        categoriesStore: @json(route('wmsinventorycore.categories.store')),
        categoriesUpdate: @json(route('wmsinventorycore.categories.update', ['category' => '__CATEGORY_ID__'])),
        categoriesDelete: @json(route('wmsinventorycore.categories.destroy', ['category' => '__CATEGORY_ID__']))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-categories.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Product Categories')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Categories') }}</h5>
      @can('wmsinventory.create-category')
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddCategory">
          <i class="bx bx-plus"></i> {{ __('Add New Category') }}
        </button>
      @endcan
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table table-bordered datatables-categories">
      <thead>
        <tr>
          <th>{{ __('ID') }}</th>
          <th>{{ __('Name') }}</th>
          <th>{{ __('Description') }}</th>
          <th>{{ __('Parent Category') }}</th>
          <th>{{ __('Products') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@can('wmsinventory.create-category')
<!-- Add Category Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddCategory" aria-labelledby="offcanvasAddCategoryLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddCategoryLabel" class="offcanvas-title">{{ __('Add New Category') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <form id="addCategoryForm" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label" for="name">{{ __('Category Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" required />
        <div class="invalid-feedback">{{ __('Please enter a category name.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="description">{{ __('Description') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label" for="parent_id">{{ __('Parent Category') }}</label>
        <select class="form-select" id="parent_id" name="parent_id">
          <option value="">{{ __('None (Top Level)') }}</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label" for="status">{{ __('Status') }}</label>
        <select class="form-select" id="status" name="status" required>
          <option value="active">{{ __('Active') }}</option>
          <option value="inactive">{{ __('Inactive') }}</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">{{ __('Submit') }}</button>
      <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
    </form>
  </div>
</div>
@endcan

@can('wmsinventory.edit-category')
<!-- Edit Category Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditCategory" aria-labelledby="offcanvasEditCategoryLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditCategoryLabel" class="offcanvas-title">{{ __('Edit Category') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <form id="editCategoryForm" class="needs-validation" novalidate>
      <input type="hidden" id="edit_id" name="id" />
      <div class="mb-3">
        <label class="form-label" for="edit_name">{{ __('Category Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit_name" name="name" required />
        <div class="invalid-feedback">{{ __('Please enter a category name.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="edit_description">{{ __('Description') }}</label>
        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label" for="edit_parent_id">{{ __('Parent Category') }}</label>
        <select class="form-select" id="edit_parent_id" name="parent_id">
          <option value="">{{ __('None (Top Level)') }}</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label" for="edit_status">{{ __('Status') }}</label>
        <select class="form-select" id="edit_status" name="status" required>
          <option value="active">{{ __('Active') }}</option>
          <option value="inactive">{{ __('Inactive') }}</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">{{ __('Update') }}</button>
      <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
    </form>
  </div>
</div>
@endcan
@endsection
