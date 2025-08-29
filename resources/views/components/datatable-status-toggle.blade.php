@props([
    'id' => null,
    'checked' => false,
    'url' => '#',
    'class' => 'status-toggle'
])

<div class="d-flex justify-content-center">
    <label class="switch mb-0">
        <input type="checkbox" 
               class="switch-input {{ $class }}" 
               data-id="{{ $id }}" 
               data-url="{{ $url }}" 
               {{ $checked ? 'checked' : '' }} />
        <span class="switch-toggle-slider">
            <span class="switch-on"><i class="bx bx-check"></i></span>
            <span class="switch-off"><i class="bx bx-x"></i></span>
        </span>
    </label>
</div>