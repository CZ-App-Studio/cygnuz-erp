@extends('layouts.layoutMaster')

@section('title', __('Edit Company'))

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
            update: @json(route('companies.update', $company->id)),
            index: @json(route('companies.index'))
        },
        labels: {
            success: @json(__('Success')),
            error: @json(__('Error')),
            updateSuccess: @json(__('Company updated successfully!')),
            validationError: @json(__('Please correct the errors below'))
        }
    };
</script>
@vite(['Modules/CRMCore/resources/assets/js/companies-edit.js'])
@endsection

@section('content')
<x-breadcrumb
    :title="__('Edit Company')"
    :breadcrumbs="[
        ['name' => __('Home'), 'url' => route('dashboard')],
        ['name' => __('CRM'), 'url' => ''],
        ['name' => __('Companies'), 'url' => route('companies.index')],
        ['name' => $company->name, 'url' => route('companies.show', $company->id)],
        ['name' => __('Edit'), 'url' => '']
    ]"
    :home-url="route('dashboard')"
/>

<div class="row">
    <div class="col-md-12">
        <form id="companyForm" action="{{ route('companies.update', $company->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            @include('crmcore::companies._form', ['company' => $company])

            <!-- Form Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>{{ __('Update Company') }}
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
