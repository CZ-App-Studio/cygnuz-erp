@extends('layouts.layoutMaster')

@section('title', __('Create Company'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
<script>
    window.pageData = {
        urls: {
            userSearch: @json(route('users.selectSearch')),
            store: @json(route('companies.store')),
            index: @json(route('companies.index'))
        },
        labels: {
            success: @json(__('Success')),
            error: @json(__('Error')),
            createSuccess: @json(__('Company created successfully!')),
            validationError: @json(__('Please correct the errors below'))
        }
    };
</script>
@vite(['Modules/CRMCore/resources/assets/js/companies-create.js'])
@endsection

@section('content')
<x-breadcrumb
    :title="__('Create Company')"
    :breadcrumbs="[
        ['name' => __('Home'), 'url' => route('dashboard')],
        ['name' => __('CRM'), 'url' => ''],
        ['name' => __('Companies'), 'url' => route('companies.index')],
        ['name' => __('Create'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
/>

<div class="row">
    <div class="col-md-12">
        <form id="companyForm" action="{{ route('companies.store') }}" method="POST">
            @csrf
            
            @include('crmcore::companies._form')

            <!-- Form Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>{{ __('Save Company') }}
                        </button>
                        <a href="{{ route('companies.index') }}" class="btn btn-label-secondary">
                            <i class="bx bx-x me-1"></i>{{ __('Cancel') }}
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
