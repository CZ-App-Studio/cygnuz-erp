<form id="module-settings-form" method="POST">
    @csrf
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">{{ __('CRM Module Settings') }}</h4>
                <button type="button" class="btn btn-sm btn-label-secondary" onclick="resetModuleSettings('crmcore')">
                    <i class="bx bx-reset me-1"></i> {{ __('Reset to Defaults') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#companies-tab" type="button">
                <i class="bx bx-building me-1"></i> {{ __('Companies') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#contacts-tab" type="button">
                <i class="bx bx-user me-1"></i> {{ __('Contacts') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#customers-tab" type="button">
                <i class="bx bx-group me-1"></i> {{ __('Customers') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#leads-tab" type="button">
                <i class="bx bx-target-lock me-1"></i> {{ __('Leads') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#deals-tab" type="button">
                <i class="bx bx-dollar-circle me-1"></i> {{ __('Deals') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tasks-tab" type="button">
                <i class="bx bx-task me-1"></i> {{ __('Tasks') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notifications-tab" type="button">
                <i class="bx bx-bell me-1"></i> {{ __('Notifications') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#display-tab" type="button">
                <i class="bx bx-desktop me-1"></i> {{ __('Display') }}
            </button>
        </li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content">
        <!-- Companies Tab -->
        <div class="tab-pane fade show active" id="companies-tab">
            <div class="row">
                @foreach($settings['companies'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ ($values[$key] ?? $setting['default']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'text')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="text" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'number')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="number" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}" min="0">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Contacts Tab -->
        <div class="tab-pane fade" id="contacts-tab">
            <div class="row">
                @foreach($settings['contacts'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ $values[$key] ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'text')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="text" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'number')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="number" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}" min="0">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'select')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <select class="form-select" id="{{ $key }}" name="{{ $key }}">
                            @foreach($setting['options'] as $value => $label)
                                <option value="{{ $value }}" {{ ($values[$key] ?? $setting['default']) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Customers Tab -->
        <div class="tab-pane fade" id="customers-tab">
            <div class="row">
                @foreach($settings['customers'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ $values[$key] ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'text')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="text" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'number')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="number" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}" min="0">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Leads Tab -->
        <div class="tab-pane fade" id="leads-tab">
            <div class="row">
                @foreach($settings['leads'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ $values[$key] ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'text')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="text" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'number')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="number" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}" min="0">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Deals Tab -->
        <div class="tab-pane fade" id="deals-tab">
            <div class="row">
                @foreach($settings['deals'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ $values[$key] ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'text')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="text" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'number')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="number" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}" min="0">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Tasks Tab -->
        <div class="tab-pane fade" id="tasks-tab">
            <div class="row">
                @foreach($settings['tasks'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ $values[$key] ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'text')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="text" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'number')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <input type="number" class="form-control" id="{{ $key }}" name="{{ $key }}" 
                               value="{{ $values[$key] ?? $setting['default'] }}" min="0">
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Notifications Tab -->
        <div class="tab-pane fade" id="notifications-tab">
            <div class="row">
                @foreach($settings['notifications'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ $values[$key] ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Display Tab -->
        <div class="tab-pane fade" id="display-tab">
            <div class="row">
                @foreach($settings['display'] as $key => $setting)
                <div class="col-md-6 mb-3">
                    @if($setting['type'] === 'toggle')
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="{{ $key }}" name="{{ $key }}" 
                                   {{ $values[$key] ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $key }}">
                                {{ $setting['label'] }}
                            </label>
                        </div>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @elseif($setting['type'] === 'select')
                        <label for="{{ $key }}" class="form-label">{{ $setting['label'] }}</label>
                        <select class="form-select" id="{{ $key }}" name="{{ $key }}">
                            @foreach($setting['options'] as $value => $label)
                                <option value="{{ $value }}" {{ ($values[$key] ?? $setting['default']) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if(isset($setting['help']))
                            <small class="text-muted">{{ $setting['help'] }}</small>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
            </button>
        </div>
    </div>
</form>