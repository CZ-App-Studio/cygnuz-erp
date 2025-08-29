@props(['status', 'type' => 'secondary'])

@php
    $classes = match($type) {
        'success' => 'badge bg-label-success',
        'danger' => 'badge bg-label-danger',
        'warning' => 'badge bg-label-warning',
        'info' => 'badge bg-label-info',
        'primary' => 'badge bg-label-primary',
        default => 'badge bg-label-secondary',
    };
@endphp

<span class="{{ $classes }}">{{ ucfirst($status) }}</span>