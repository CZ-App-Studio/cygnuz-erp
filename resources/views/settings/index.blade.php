@extends('layouts.layoutMaster')

@section('title', __('Settings'))

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('page-style')
<style>
    .settings-sidebar {
        min-height: calc(100vh - 200px);
        border-right: 1px solid rgba(67, 89, 113, 0.1);
    }
    .settings-menu-item {
        padding: 0.75rem 1rem;
        border-radius: 0.375rem;
        margin-bottom: 0.25rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .settings-menu-item:hover {
        background-color: rgba(67, 89, 113, 0.04);
    }
    .settings-menu-item.active {
        background-color: rgba(105, 108, 255, 0.08);
        color: #696cff;
    }
    .settings-menu-item i {
        width: 1.5rem;
    }
    .settings-submenu {
        margin-left: 2.5rem;
        margin-top: 0.25rem;
    }
    .settings-submenu .settings-menu-item {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
</style>
@endsection

@section('content')
<x-breadcrumb
    :title="__('Settings')"
    :items="[
        ['label' => __('Settings')]
    ]" />

<div class="row">
    <!-- Left Sidebar -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-body p-0">
                <div class="settings-sidebar">
                    <!-- Search -->
                    <div class="p-3 border-bottom">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" class="form-control" id="settings-search" placeholder="{{ __('Search settings...') }}">
                        </div>
                    </div>

                    <!-- Menu Items -->
                    <div class="p-3">
                        <!-- System Settings Section -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">{{ __('System Settings') }}</h6>
                            @foreach($categories as $key => $category)
                            <div class="settings-menu-item" data-category="{{ $key }}" onclick="loadSettingsContent('system', '{{ $key }}')">
                                <i class="{{ $category['icon'] }} me-2"></i>
                                <span>{{ $category['title'] }}</span>
                            </div>
                            @endforeach
                        </div>

                        <!-- Module Settings Section -->
                        @if(count($moduleSettings) > 0)
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">{{ __('Module Settings') }}</h6>
                            @foreach($moduleSettings as $moduleKey => $module)
                                @if($moduleKey === 'billing')
                                    @can('billing.manage-billing-settings')
                                        <div class="settings-menu-item" data-module="{{ $moduleKey }}" onclick="loadSettingsContent('module', '{{ $moduleKey }}')">
                                            <i class="{{ $module['icon'] ?? 'bx bx-credit-card' }} me-2"></i>
                                            <span>{{ $module['name'] }}</span>
                                        </div>
                                    @endcan
                                @else
                                    <div class="settings-menu-item" data-module="{{ $moduleKey }}" onclick="loadSettingsContent('module', '{{ $moduleKey }}')">
                                        <i class="{{ $module['icon'] ?? 'bx bx-cog' }} me-2"></i>
                                        <span>{{ $module['name'] }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        <!-- Additional Options -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">{{ __('Options') }}</h6>
                            <div class="settings-menu-item" onclick="loadSettingsContent('history')">
                                <i class="bx bx-history me-2"></i>
                                <span>{{ __('Change History') }}</span>
                            </div>
                            @can('billing.export-settings')
                                <div class="settings-menu-item" onclick="exportSettings()">
                                    <i class="bx bx-download me-2"></i>
                                    <span>{{ __('Export Settings') }}</span>
                                </div>
                            @endcan
                            @can('billing.import-settings')
                                <div class="settings-menu-item" onclick="importSettings()">
                                    <i class="bx bx-upload me-2"></i>
                                    <span>{{ __('Import Settings') }}</span>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Content Area -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <div id="settings-content">
                    <!-- Welcome Message -->
                    <div class="text-center py-5">
                        <i class="bx bx-cog bx-lg text-muted mb-3"></i>
                        <h4>{{ __('Welcome to Settings') }}</h4>
                        <p class="text-muted">{{ __('Select a category from the left sidebar to manage settings') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Import Settings') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">{{ __('Select Settings File') }}</label>
                        <input type="file" class="form-control" id="importFile" name="file" accept=".json" required>
                        <div class="form-text">{{ __('Upload a JSON file exported from settings') }}</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-1"></i>
                        {{ __('Warning: This will overwrite existing settings with the imported values.') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-upload me-1"></i> {{ __('Import') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
// Page data for JavaScript
const pageData = {
    urls: {
        import: '{{ route('settings.import') }}',
        export: '{{ route('settings.export') }}',
        history: '{{ route('settings.history.index') }}',
        systemSettings: '{{ url('settings/system') }}/:category',
        moduleForm: '{{ url('settings/module') }}/:module/form',
        updateSystem: '{{ url('settings/system') }}/:category',
        updateModule: '{{ url('settings/module') }}/:module',
        rollback: '{{ url('settings/history') }}/:id/rollback'
    },
    labels: {
        loading: @json(__('Loading...')),
        importing: @json(__('Importing...')),
        saving: @json(__('Saving...')),
        success: @json(__('Success')),
        error: @json(__('Error')),
        ok: @json(__('OK')),
        cancel: @json(__('Cancel')),
        importFailed: @json(__('Import failed')),
        importError: @json(__('An error occurred while importing settings')),
        loadError: @json(__('Failed to load settings')),
        saveError: @json(__('Failed to save settings')),
        errorOccurred: @json(__('An error occurred')),
        changeHistory: @json(__('Change History')),
        rollbackTitle: @json(__('Rollback Setting?')),
        rollbackText: @json(__('This will restore the setting to its previous value.')),
        yesRollback: @json(__('Yes, rollback')),
        rollbackError: @json(__('Failed to rollback setting'))
    }
};
</script>
@vite(['resources/assets/js/pages/settings.js'])
@endsection
