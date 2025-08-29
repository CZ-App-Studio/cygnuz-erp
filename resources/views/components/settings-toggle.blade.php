@props([
    'name',
    'label',
    'value' => false,
    'help' => null,
    'disabled' => false
])

<div class="mb-3">
    <div class="form-check form-switch">
        <input 
            class="form-check-input" 
            type="checkbox" 
            id="{{ $name }}" 
            name="{{ $name }}"
            value="1"
            @if(old($name, $value)) checked @endif
            @if($disabled) disabled @endif
        >
        <label class="form-check-label" for="{{ $name }}">
            {{ $label }}
        </label>
    </div>
    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>