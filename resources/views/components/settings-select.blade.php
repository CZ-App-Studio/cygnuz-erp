@props([
    'name',
    'label',
    'value' => '',
    'options' => [],
    'help' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false
])

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
        name="{{ $name }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($multiple) multiple @endif
    >
        @if(!$multiple)
            <option value="">{{ __('Select...') }}</option>
        @endif
        @foreach($options as $key => $option)
            <option value="{{ $key }}" @if(old($name, $value) == $key) selected @endif>
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