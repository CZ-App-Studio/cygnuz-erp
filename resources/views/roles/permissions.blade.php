@php
  use Illuminate\Support\Str;
@endphp

@extends('layouts.layoutMaster')

@section('title', __('Manage Role Permissions') . ' - ' . $role->name)

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/role-permissions.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Manage Role Permissions')"
      :breadcrumbs="[
        ['name' => __('User Management'), 'url' => ''],
        ['name' => __('Roles'), 'url' => route('roles.index')],
        ['name' => $role->name, 'url' => '']
      ]"
      :home-url="url('/')"
    >
      <a href="{{ route('roles.index') }}" class="btn btn-label-secondary">
        <i class="bx bx-arrow-back me-1"></i>{{ __('Back to Roles') }}
      </a>
    </x-breadcrumb>

    {{-- Role Info Card --}}
    <div class="card mb-4">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h5 class="mb-2">{{ __('Role:') }} <span class="text-primary">{{ $role->name }}</span></h5>
            @if($role->description)
              <p class="text-muted mb-0">{{ $role->description }}</p>
            @endif
          </div>
          <div class="col-md-6 text-md-end">
            <div class="d-flex justify-content-md-end gap-2">
              <button type="button" class="btn btn-primary" id="savePermissions">
                <i class="bx bx-save me-1"></i>{{ __('Save Permissions') }}
              </button>
              <button type="button" class="btn btn-label-secondary" id="selectAll">
                <i class="bx bx-check-square me-1"></i>{{ __('Select All') }}
              </button>
              <button type="button" class="btn btn-label-secondary" id="deselectAll">
                <i class="bx bx-square me-1"></i>{{ __('Deselect All') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Permissions Grid --}}
    <form id="permissionsForm">
      @csrf
      @method('PUT')
      
      @foreach($permissions as $module => $modulePermissions)
        <div class="card mb-4">
          <div class="card-header bg-label-primary">
            <div class="d-flex align-items-center">
              <div class="form-check form-check-primary">
                <input class="form-check-input module-checkbox" type="checkbox" data-module="{{ $module }}" id="module_{{ Str::slug($module) }}">
                <label class="form-check-label ms-2" for="module_{{ Str::slug($module) }}">
                  <h5 class="mb-0">{{ $module }}</h5>
                </label>
              </div>
              <span class="badge bg-primary ms-auto permission-count" data-module="{{ $module }}">
                <span class="selected-count">0</span> / {{ $modulePermissions->count() }}
              </span>
            </div>
          </div>
          <div class="card-body">
            <div class="row g-3">
              @foreach($modulePermissions->chunk(ceil($modulePermissions->count() / 3)) as $chunk)
                <div class="col-md-4">
                  @foreach($chunk as $permission)
                    <div class="form-check mb-2">
                      <input 
                        class="form-check-input permission-checkbox" 
                        type="checkbox" 
                        name="permissions[]" 
                        value="{{ $permission->id }}" 
                        id="permission_{{ $permission->id }}"
                        data-module="{{ $module }}"
                        {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                      >
                      <label class="form-check-label" for="permission_{{ $permission->id }}">
                        <span class="fw-medium">{{ $permission->name }}</span>
                        @if($permission->description)
                          <br><small class="text-muted">{{ $permission->description }}</small>
                        @endif
                      </label>
                    </div>
                  @endforeach
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endforeach
    </form>

    {{-- Empty State --}}
    @if($permissions->isEmpty())
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bx bx-lock bx-lg text-muted mb-3"></i>
          <h5 class="text-muted">{{ __('No permissions available') }}</h5>
          <p class="text-muted">{{ __('Please create permissions first before managing role permissions.') }}</p>
          <a href="{{ route('permissions.index') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i>{{ __('Go to Permissions') }}
          </a>
        </div>
      </div>
    @endif
  </div>

  {{-- Page Data for JavaScript --}}
  <script>
    const pageData = {
      urls: {
        updatePermissions: @json(route('roles.updatePermissions', $role->id))
      },
      labels: {
        saving: @json(__('Saving...')),
        saveSuccess: @json(__('Permissions saved successfully')),
        error: @json(__('An error occurred. Please try again.'))
      },
      roleId: {{ $role->id }}
    };
  </script>
@endsection