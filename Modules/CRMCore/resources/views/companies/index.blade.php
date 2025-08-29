@extends('layouts.layoutMaster')

@section('title', __('Companies'))

@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => '#'],
    ['name' => __('Companies'), 'url' => '']
  ];
@endphp

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
      // Add Select2 if you use it for column filtering in DataTables, Flatpickr if any date filters
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
      // Add Select2, Flatpickr JS if used
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('companies.ajax')),
        destroy: @json(route('companies.destroy', ['company' => ':id'])),
        toggleStatus: @json(route('companies.toggleStatus', ['company' => ':id']))
      },
      labels: {
        confirmDelete: @json(__('Are you sure you want to delete this company?')),
        deleteSuccess: @json(__('Company deleted successfully')),
        deleteError: @json(__('Failed to delete company')),
        statusSuccess: @json(__('Status updated successfully')),
        statusError: @json(__('Failed to update status')),
        hasContacts: @json(__('This company has associated contacts. Delete them first.'))
      },
      settings: {
        itemsPerPage: @json($itemsPerPage ?? 25),
        showActivityTimeline: @json($showActivityTimeline ?? true)
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/companies-list.js'])
@endsection

@section('content')
  <x-breadcrumb
    :title="__('Companies')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('All Companies') }}</h5>
        <a href="{{ route('companies.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Add New Company') }}
        </a>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="table table-bordered" id="companiesTable">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Website') }}</th>
            <th>{{ __('Assigned To') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-center">{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
@endsection
