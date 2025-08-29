@extends('layouts.layoutMaster')

@section('title', $moduleHandler->getModuleName() ?? 'Module Settings')

@section('content')
@php
  use Illuminate\Support\Str;
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __($moduleHandler->getModuleName() ?? 'Module Settings') }}</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-style1 mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('settings.index') }}">{{ __('Settings') }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __($moduleHandler->getModuleName() ?? 'Module Settings') }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="card-body">

<form id="moduleSettingsForm" data-module="{{ $module }}">
    @csrf

    @php
        $currentValues = $moduleHandler->getCurrentValues();
        $settingsDefinition = $moduleHandler->getSettingsDefinition();
    @endphp

    @foreach($settingsDefinition as $section => $items)
        <!-- Section Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="text-muted fw-light mb-3">{{ __(Str::title(str_replace('_', ' ', $section))) }}</h6>
            </div>
        </div>

        <div class="row g-4 mb-5">
            @foreach($items as $key => $config)
                <div class="col-md-6">
                    @switch($config['type'])
                        @case('select')
                            <x-settings-select
                                :name="$key"
                                :label="$config['label']"
                                :value="$currentValues[$key] ?? $config['default'] ?? ''"
                                :options="$config['options']"
                                :help="$config['help'] ?? ''"
                                :required="str_contains($config['validation'] ?? '', 'required')"
                            />
                            @break

                        @case('toggle')
                        @case('switch')
                            <div class="mb-3">
                                <label class="form-label">{{ $config['label'] }}</label>
                                <div class="form-check form-switch">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="{{ $key }}"
                                           name="{{ $key }}"
                                           value="1"
                                           {{ ($currentValues[$key] ?? $config['default'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="{{ $key }}"></label>
                                </div>
                                @if(!empty($config['help']))
                                    <div class="form-text">{{ $config['help'] }}</div>
                                @endif
                            </div>
                            @break

                        @case('multiselect')
                            <x-settings-multiselect
                                :name="$key"
                                :label="$config['label']"
                                :value="$currentValues[$key] ?? $config['default'] ?? []"
                                :options="$config['options']"
                                :help="$config['help'] ?? ''"
                                :required="str_contains($config['validation'] ?? '', 'required')"
                            />
                            @break

                        @case('number')
                            <label class="form-label">{{ $config['label'] }}</label>
                            <div class="input-group">
                                @if(!empty($config['prefix']))
                                    <span class="input-group-text">{{ $config['prefix'] }}</span>
                                @endif
                                <input
                                    type="number"
                                    name="{{ $key }}"
                                    class="form-control"
                                    value="{{ $currentValues[$key] ?? $config['default'] ?? '' }}"
                                    @if(isset($config['step'])) step="{{ $config['step'] }}" @endif
                                    @if(str_contains($config['validation'] ?? '', 'required')) required @endif
                                >
                                @if(!empty($config['suffix']))
                                    <span class="input-group-text">{{ $config['suffix'] }}</span>
                                @endif
                            </div>
                            @if(!empty($config['help']))
                                <div class="form-text">{{ $config['help'] }}</div>
                            @endif
                            @break

                        @case('text')
                        @default
                            <x-settings-input
                                :name="$key"
                                :label="$config['label']"
                                :value="$currentValues[$key] ?? $config['default'] ?? ''"
                                :help="$config['help'] ?? ''"
                                :required="str_contains($config['validation'] ?? '', 'required')"
                            />
                            @break
                    @endswitch
                </div>
            @endforeach
        </div>

        <!-- Section Actions -->
        @if(isset($moduleHandler->getSections()[$loop->index]['actions']))
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        @foreach($moduleHandler->getSections()[$loop->index]['actions'] as $action)
                            <button type="button" 
                                    class="btn {{ $action['class'] ?? 'btn-primary' }}" 
                                    id="{{ $action['id'] ?? '' }}"
                                    data-action="{{ $action['id'] ?? '' }}">
                                <i class="{{ $action['icon'] ?? '' }} me-1"></i>
                                {{ $action['label'] ?? '' }}
                            </button>
                        @endforeach
                    </div>
                    @if($module === 'searchplus')
                        <div id="index-stats" class="mt-4"></div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
        <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetModuleSettings('{{ $module }}')">
            <i class="bx bx-reset me-1"></i> {{ __('Reset to Defaults') }}
        </button>
    </div>
</form>

<script>
// Module settings form handler
$(function() {
    $('#moduleSettingsForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const module = form.data('module');
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        // Show loading
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Saving...") }}');

        // Prepare form data
        const formData = new FormData(this);

        // Handle toggle switches
        form.find('input[type="checkbox"]').each(function() {
            const name = $(this).attr('name');
            if (name) {
                formData.delete(name);
                formData.append(name, $(this).is(':checked') ? '1' : '0');
            }
        });

        $.ajax({
            url: `{{ route('settings.module.update', '') }}/${module}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: '{{ __("Success!") }}',
                        text: response.data?.message || '{{ __("Settings updated successfully") }}',
                        icon: 'success',
                        confirmButtonText: '{{ __("OK") }}'
                    });
                } else {
                    throw new Error(response.data || 'Unknown error');
                }
            },
            error: function(xhr) {
                let errorMessage = '{{ __("An error occurred while saving settings") }}';

                if (xhr.responseJSON?.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                } else if (xhr.responseJSON?.data) {
                    errorMessage = xhr.responseJSON.data;
                }

                Swal.fire({
                    title: '{{ __("Error") }}',
                    html: errorMessage,
                    icon: 'error',
                    confirmButtonText: '{{ __("OK") }}'
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});

// Reset module settings function
function resetModuleSettings(module) {
    Swal.fire({
        title: '{{ __("Reset Settings") }}',
        text: '{{ __("Are you sure you want to reset all settings to their default values?") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '{{ __("Yes, reset") }}',
        cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('settings.module.reset', '') }}/${module}`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        throw new Error(response.data || 'Reset failed');
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        title: '{{ __("Error") }}',
                        text: xhr.responseJSON?.data || '{{ __("Failed to reset settings") }}',
                        icon: 'error',
                        confirmButtonText: '{{ __("OK") }}'
                    });
                }
            });
        }
    });
}
</script>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
@if($module === 'searchplus')
    @vite(['Modules/SearchPlus/resources/assets/js/settings.js'])
@endif
@endsection
