@php
    $moduleHandler = app($moduleConfig['handler']);
    use Illuminate\Support\Str;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __($moduleHandler->getModuleName() ?? 'Module Settings') }}</h4>
</div>

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
                            <x-settings-toggle
                                :name="$key"
                                :label="$config['label']"
                                :value="$currentValues[$key] ?? $config['default'] ?? false"
                                :help="$config['help'] ?? ''"
                            />
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
    @endforeach

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
        <button type="button" class="btn btn-outline-secondary ms-2" data-module="{{ $module }}" onclick="resetModuleSettings(this.dataset.module)">
            <i class="bx bx-reset me-1"></i> {{ __('Reset to Defaults') }}
        </button>
    </div>
</form>
