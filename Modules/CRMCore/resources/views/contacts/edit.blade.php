@extends('layouts.layoutMaster')

@section('title', __('Edit Contact') . ': ' . $contact->full_name)

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
        updateUrl: @json(route('contacts.update', $contact->id)),
        companiesSearchUrl: @json(route('companies.selectSearch')),
        usersSearchUrl: @json(route('users.selectSearch'))
      },
      labels: {
        error: @json(__('An error occurred. Please try again.')),
        updateSuccess: @json(__('Contact updated successfully!'))
      },
      contactId: @json($contact->id)
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
  :title="__('Edit Contact')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Edit Contact') }}: <span class="text-primary">{{ $contact->full_name }}</span></h5>
      </div>
      <div class="card-body">
        <form id="editContactForm" action="{{ route('contacts.update', $contact->id) }}" method="POST">
          @method('PUT')
          @include('crmcore::contacts._form', ['contact' => $contact])

          <div class="d-flex gap-2 justify-content-end mt-4">
            <a href="{{ route('contacts.index') }}" class="btn btn-label-secondary">
              <i class="bx bx-x me-1"></i>{{ __('Cancel') }}
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-1"></i>{{ __('Update Contact') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
