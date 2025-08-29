@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('CRM Settings'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
    @vite(['resources/js/main-helper.js'])
    
    <script>
        const pageData = {
            urls: {
                show: @json(route('settings.crm-settings.show')),
                update: @json(route('settings.crm-settings.update')),
                reset: @json(route('settings.crm-settings.reset')),
                export: @json(route('settings.crm-settings.export')),
                import: @json(route('settings.crm-settings.import')),
                updateSingle: @json(route('settings.crm-settings.update-single')),
            },
            permissions: {
                edit: @json(auth()->user()->can('manage-crm-settings')),
                view: @json(auth()->user()->can('view-crm-settings')),
            },
            labels: {
                crmSettings: @json(__('CRM Settings')),
                updateSettings: @json(__('Update Settings')),
                resetSettings: @json(__('Reset Settings')),
                exportSettings: @json(__('Export Settings')),
                importSettings: @json(__('Import Settings')),
                confirmReset: @json(__('Are you sure you want to reset all settings to default values?')),
                resetSuccess: @json(__('CRM settings reset to default values successfully!')),
                updateSuccess: @json(__('CRM settings updated successfully!')),
                exportSuccess: @json(__('CRM settings exported successfully!')),
                importSuccess: @json(__('CRM settings imported successfully!')),
                submit: @json(__('Submit')),
                cancel: @json(__('Cancel')),
                save: @json(__('Save')),
                reset: @json(__('Reset')),
                export: @json(__('Export')),
                import: @json(__('Import')),
                error: @json(__('An error occurred. Please try again.')),
                loading: @json(__('Loading...')),
                selectFile: @json(__('Select File')),
                company: @json(__('Company')),
                contacts: @json(__('Contacts')),
                customers: @json(__('Customers')),
                leads: @json(__('Leads')),
                deals: @json(__('Deals')),
                tasks: @json(__('Tasks')),
                notifications: @json(__('Notifications')),
                display: @json(__('Display')),
                integration: @json(__('Integration')),
                security: @json(__('Security')),
            }
        };
    </script>

    <script>
        $(function () {
            // Initialize settings management
            let currentSettings = {};
            let settingsStructure = {};

            // CSRF setup
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Load settings on page load
            loadSettings();

            // Form submission
            $('#crmSettingsForm').on('submit', function(e) {
                e.preventDefault();
                updateSettings();
            });

            function loadSettings() {
                showLoading();
                
                $.ajax({
                    url: pageData.urls.show,
                    type: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            currentSettings = response.data.settings;
                            settingsStructure = response.data.structure;
                            renderSettingsForm();
                        } else {
                            showError(response.message || pageData.labels.error);
                        }
                    },
                    error: function(xhr) {
                        console.error('Settings load error:', xhr);
                        showError(pageData.labels.error);
                    }
                });
            }

            function renderSettingsForm() {
                const tabContent = $('#settingsTabContent');
                tabContent.empty();

                // Get sections from the CRMCoreSettings class
                const sections = [
                    { id: 'company', title: pageData.labels.company, icon: 'bx bx-buildings' },
                    { id: 'contacts', title: pageData.labels.contacts, icon: 'bx bx-user' },
                    { id: 'customers', title: pageData.labels.customers, icon: 'bx bx-group' },
                    { id: 'leads', title: pageData.labels.leads, icon: 'bx bx-user-plus' },
                    { id: 'deals', title: pageData.labels.deals, icon: 'bx bx-trophy' },
                    { id: 'tasks', title: pageData.labels.tasks, icon: 'bx bx-task' },
                    { id: 'notifications', title: pageData.labels.notifications, icon: 'bx bx-bell' },
                    { id: 'display', title: pageData.labels.display, icon: 'bx bx-desktop' },
                    { id: 'integration', title: pageData.labels.integration, icon: 'bx bx-cog' },
                    { id: 'security', title: pageData.labels.security, icon: 'bx bx-shield' }
                ];

                sections.forEach((section, index) => {
                    const isActive = index === 0;
                    const sectionSettings = settingsStructure[section.id] || {};
                    
                    const tabPane = $(`
                        <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="${section.id}" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-section">
                                        <div class="section-header mb-4">
                                            <h6 class="fw-bold text-primary">
                                                <i class="${section.icon} me-2"></i>${section.title}
                                            </h6>
                                        </div>
                                        <div class="row" id="${section.id}-fields">
                                            ${renderSectionFields(section.id, sectionSettings)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    tabContent.append(tabPane);
                });

                // Show the save button
                $('#saveButtonContainer').removeClass('d-none');
                hideLoading();
            }

            function renderSectionFields(sectionId, sectionSettings) {
                let fieldsHtml = '';
                
                for (const [key, config] of Object.entries(sectionSettings)) {
                    const value = currentSettings[key] || config.default || '';
                    const fieldHtml = renderField(key, config, value);
                    fieldsHtml += `<div class="col-md-6 mb-3">${fieldHtml}</div>`;
                }
                
                return fieldsHtml;
            }

            function renderField(key, config, value) {
                const disabled = !pageData.permissions.edit ? 'disabled' : '';
                const required = config.validation && config.validation.includes('required') ? 'required' : '';
                
                let fieldHtml = '';
                
                switch (config.type) {
                    case 'text':
                        fieldHtml = `
                            <label class="form-label" for="${key}">${config.label}</label>
                            <input type="text" class="form-control" id="${key}" name="${key}" 
                                   value="${value}" ${disabled} ${required} 
                                   ${config.attributes ? Object.entries(config.attributes).map(([k,v]) => `${k}="${v}"`).join(' ') : ''}>
                            ${config.help ? `<small class="form-text text-muted">${config.help}</small>` : ''}
                        `;
                        break;
                        
                    case 'number':
                        fieldHtml = `
                            <label class="form-label" for="${key}">${config.label}</label>
                            <input type="number" class="form-control" id="${key}" name="${key}" 
                                   value="${value}" ${disabled} ${required}
                                   ${config.attributes ? Object.entries(config.attributes).map(([k,v]) => `${k}="${v}"`).join(' ') : ''}>
                            ${config.help ? `<small class="form-text text-muted">${config.help}</small>` : ''}
                        `;
                        break;
                        
                    case 'switch':
                        const checked = value === '1' || value === true || value === 'true' ? 'checked' : '';
                        fieldHtml = `
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="${key}" name="${key}" 
                                       ${checked} ${disabled} value="1">
                                <label class="form-check-label" for="${key}">${config.label}</label>
                            </div>
                            ${config.help ? `<small class="form-text text-muted d-block">${config.help}</small>` : ''}
                        `;
                        break;
                        
                    case 'select':
                        let options = '';
                        if (config.options) {
                            for (const [optionValue, optionLabel] of Object.entries(config.options)) {
                                const selected = value === optionValue ? 'selected' : '';
                                options += `<option value="${optionValue}" ${selected}>${optionLabel}</option>`;
                            }
                        }
                        fieldHtml = `
                            <label class="form-label" for="${key}">${config.label}</label>
                            <select class="form-select" id="${key}" name="${key}" ${disabled} ${required}>
                                <option value="">{{ __('Select...') }}</option>
                                ${options}
                            </select>
                            ${config.help ? `<small class="form-text text-muted">${config.help}</small>` : ''}
                        `;
                        break;
                        
                    default:
                        fieldHtml = `
                            <label class="form-label" for="${key}">${config.label}</label>
                            <input type="text" class="form-control" id="${key}" name="${key}" 
                                   value="${value}" ${disabled} ${required}>
                            ${config.help ? `<small class="form-text text-muted">${config.help}</small>` : ''}
                        `;
                }
                
                return fieldHtml;
            }

            function updateSettings() {
                const formData = new FormData($('#crmSettingsForm')[0]);
                
                // Handle checkbox values
                $('#crmSettingsForm input[type="checkbox"]').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        formData.delete(name);
                        formData.append(name, $(this).is(':checked') ? '1' : '0');
                    }
                });

                const submitBtn = $('#crmSettingsForm button[type="submit"]');
                const btnText = submitBtn.find('.btn-text');
                const btnSpinner = submitBtn.find('.spinner-border');
                
                btnSpinner.removeClass('d-none');
                btnText.text(pageData.labels.loading);
                submitBtn.prop('disabled', true);

                $.ajax({
                    url: pageData.urls.update,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status === 'success') {
                            currentSettings = response.data.settings;
                            showSuccess(response.data.message || pageData.labels.updateSuccess);
                        } else {
                            showError(response.message || pageData.labels.error);
                        }
                    },
                    error: function(xhr) {
                        console.error('Settings update error:', xhr);
                        let errorMessage = pageData.labels.error;
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showError(errorMessage);
                    },
                    complete: function() {
                        btnSpinner.addClass('d-none');
                        btnText.text(pageData.labels.save);
                        submitBtn.prop('disabled', false);
                    }
                });
            }

            // Export settings
            window.exportSettings = function() {
                window.location.href = pageData.urls.export;
            };

            // Reset settings
            window.resetSettings = function() {
                Swal.fire({
                    title: pageData.labels.resetSettings,
                    text: pageData.labels.confirmReset,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: pageData.labels.reset,
                    cancelButtonText: pageData.labels.cancel,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-label-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: pageData.urls.reset,
                            type: 'POST',
                            success: function(response) {
                                if (response.status === 'success') {
                                    currentSettings = response.data.settings;
                                    renderSettingsForm();
                                    showSuccess(response.data.message || pageData.labels.resetSuccess);
                                } else {
                                    showError(response.message || pageData.labels.error);
                                }
                            },
                            error: function(xhr) {
                                console.error('Settings reset error:', xhr);
                                showError(pageData.labels.error);
                            }
                        });
                    }
                });
            };

            // Import settings
            window.showImportModal = function() {
                $('#importModal').modal('show');
            };

            // Utility functions
            function showLoading() {
                $('#loadingPlaceholder').removeClass('d-none');
                $('#saveButtonContainer').addClass('d-none');
            }

            function hideLoading() {
                $('#loadingPlaceholder').addClass('d-none');
                $('#saveButtonContainer').removeClass('d-none');
            }

            function showSuccess(message) {
                Swal.fire({
                    title: pageData.labels.updateSuccess,
                    text: message,
                    icon: 'success',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
            }

            function showError(message) {
                Swal.fire({
                    title: pageData.labels.error,
                    text: message,
                    icon: 'error',
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    }
                });
            }
        });
    </script>
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('CRM Settings')"
      :breadcrumbs="[
        ['name' => __('CRM'), 'url' => ''],
        ['name' => __('Settings'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    {{-- Settings Content --}}
    <div class="row">
      {{-- Settings Navigation --}}
      <div class="col-md-3 col-12 mb-md-0 mb-4">
        <div class="d-flex justify-content-between flex-column mb-3 mb-md-0">
          <ul class="nav nav-align-left nav-pills flex-column" id="settingsNav">
            <li class="nav-item">
              <a class="nav-link active" data-bs-toggle="pill" href="#company">
                <i class="bx bx-buildings me-2"></i>
                <span class="align-middle">{{ __('Company') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#contacts">
                <i class="bx bx-user me-2"></i>
                <span class="align-middle">{{ __('Contacts') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#customers">
                <i class="bx bx-group me-2"></i>
                <span class="align-middle">{{ __('Customers') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#leads">
                <i class="bx bx-user-plus me-2"></i>
                <span class="align-middle">{{ __('Leads') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#deals">
                <i class="bx bx-trophy me-2"></i>
                <span class="align-middle">{{ __('Deals') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#tasks">
                <i class="bx bx-task me-2"></i>
                <span class="align-middle">{{ __('Tasks') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#notifications">
                <i class="bx bx-bell me-2"></i>
                <span class="align-middle">{{ __('Notifications') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#display">
                <i class="bx bx-desktop me-2"></i>
                <span class="align-middle">{{ __('Display') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#integration">
                <i class="bx bx-cog me-2"></i>
                <span class="align-middle">{{ __('Integration') }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="pill" href="#security">
                <i class="bx bx-shield me-2"></i>
                <span class="align-middle">{{ __('Security') }}</span>
              </a>
            </li>
          </ul>
        </div>
      </div>

      {{-- Settings Forms --}}
      <div class="col-md-9 col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('CRM Settings') }}</h5>
            <div class="d-flex gap-2">
              @can('manage-crm-settings')
                <button type="button" class="btn btn-sm btn-label-secondary" onclick="exportSettings()">
                  <i class="bx bx-download me-1"></i>{{ __('Export') }}
                </button>
                <button type="button" class="btn btn-sm btn-label-secondary" onclick="showImportModal()">
                  <i class="bx bx-upload me-1"></i>{{ __('Import') }}
                </button>
                <button type="button" class="btn btn-sm btn-label-warning" onclick="resetSettings()">
                  <i class="bx bx-reset me-1"></i>{{ __('Reset') }}
                </button>
              @endcan
            </div>
          </div>
          
          <div class="card-body">
            <form id="crmSettingsForm" class="needs-validation" novalidate>
              @csrf
              <div class="tab-content p-0" id="settingsTabContent">
                {{-- Loading placeholder --}}
                <div id="loadingPlaceholder" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">{{ __('Loading...') }}</span>
                  </div>
                  <div class="mt-2">{{ __('Loading settings...') }}</div>
                </div>
              </div>
              
              @can('manage-crm-settings')
                <div class="d-flex justify-content-end mt-4 d-none" id="saveButtonContainer">
                  <button type="submit" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    <span class="btn-text">{{ __('Save Settings') }}</span>
                  </button>
                </div>
              @endcan
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- Import Modal --}}
    @can('manage-crm-settings')
      <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ __('Import Settings') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
              @csrf
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label" for="settings_file">{{ __('Settings File') }}</label>
                  <input type="file" class="form-control" id="settings_file" name="settings_file" accept=".json" required>
                  <small class="form-text text-muted">{{ __('Select a JSON file containing CRM settings to import.') }}</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Import') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endcan
  </div>
@endsection