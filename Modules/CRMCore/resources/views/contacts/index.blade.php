@extends('layouts.layoutMaster')

@section('title', __('Contact Management'))

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
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
    // Pass data needed by the contacts-list.js file
    const pageData = {
      urls: {
        datatable: @json(route('contacts.ajax')),
        destroy: @json(route('contacts.destroy', ['contact' => ':id'])),
        toggleStatus: @json(route('contacts.toggleStatus', ['contact' => ':id']))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__("You won't be able to revert this!")),
        deleteButton: @json(__('Yes, delete it!')),
        cancelButton: @json(__('Cancel')),
        deleting: @json(__('Deleting...')),
        deleted: @json(__('Deleted!')),
        error: @json(__('Error')),
        requestFailed: @json(__('Request Failed')),
        updated: @json(__('Updated!')),
        searchPlaceholder: @json(__('Search Contacts...'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/contacts-list.js'])
@endsection

@section('content')
  @php
    $breadcrumbs = [
      ['name' => __('CRM'), 'url' => '#'],
      ['name' => __('Contacts'), 'url' => '']
    ];
  @endphp

  <x-breadcrumb
    :title="__('Contact Management')"
    :breadcrumbs="$breadcrumbs"
    :homeUrl="route('dashboard')"
  />

  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('All Contacts') }}</h5>
        <a href="{{ route('contacts.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Add New Contact') }}
        </a>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="table table-bordered" id="contactsTable">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Full Name') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Company') }}</th>
            <th>{{ __('Assigned To') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-center">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
@endsection
