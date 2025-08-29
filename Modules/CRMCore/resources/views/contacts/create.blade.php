@extends('layouts.layoutMaster')

@section('title', __('Add New Contact'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/select2/select2.scss'])
  @vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/select2/select2.js'])
  @vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('page-script')
  <script>
    window.pageData = {
      urls: {
        indexUrl: @json(route('contacts.index')),
        storeUrl: @json(route('contacts.store')),
        companiesSearchUrl: @json(route('companies.selectSearch')),
        usersSearchUrl: @json(route('users.selectSearch'))
      },
      labels: {
        error: @json(__('An error occurred. Please try again.')),
        createSuccess: @json(__('Contact created successfully!'))
      }
    };
  </script>
  @vite(['Modules/CRMCore/resources/assets/js/contacts-form.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => '#'],
    ['name' => __('Contacts'), 'url' => route('contacts.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Add New Contact')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('New Contact Information') }}</h5>
      </div>
      <div class="card-body">
        <form id="createContactForm" action="{{ route('contacts.store') }}" method="POST">
          @include('crmcore::contacts._form')

          <div class="d-flex gap-2 justify-content-end mt-4">
            <a href="{{ route('contacts.index') }}" class="btn btn-label-secondary">
              <i class="bx bx-x me-1"></i>{{ __('Cancel') }}
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-1"></i>{{ __('Save Contact') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
