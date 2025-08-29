@props([
    'name',
    'label',
    'value' => [],
    'options' => [],
    'help' => null,
    'required' => false,
    'disabled' => false
])

@php
    // Ensure value is always an array
    $selectedValues = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) ?? [] : []);
    // Handle old values from validation
    $oldValues = old($name, $selectedValues);
    $finalValues = is_array($oldValues) ? $oldValues : [];
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    <select 
        class="form-select @error($name) is-invalid @enderror" 
        id="{{ $name }}" 
        name="{{ $name }}[]"
        multiple
        @if($required) required @endif
        @if($disabled) disabled @endif
    >
        @foreach($options as $key => $option)
            <option value="{{ $key }}" @if(in_array($key, $finalValues)) selected @endif>
                {{ $option }}
            </option>
        @endforeach
    </select>
    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>