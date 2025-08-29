<?php

namespace Modules\WMSInventoryCore\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization should be handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('warehouses')->ignore($this->warehouse)],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'total_area' => ['nullable', 'numeric', 'min:0'],
            'storage_capacity' => ['nullable', 'numeric', 'min:0'],
            'max_weight_capacity' => ['nullable', 'numeric', 'min:0'],
            'shelf_count' => ['nullable', 'integer', 'min:0'],
            'rack_count' => ['nullable', 'integer', 'min:0'],
            'bin_count' => ['nullable', 'integer', 'min:0'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i', 'after:opening_time'],
            'is_24_hours' => ['boolean'],
            'operating_days' => ['nullable', 'array'],
            'operating_days.*' => ['integer', 'between:0,6'],
            'warehouse_type' => ['nullable', 'string', 'max:50'],
            'is_main' => ['boolean'],
            'allow_negative_inventory' => ['boolean'],
            'requires_approval' => ['boolean'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive,maintenance'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Warehouse name is required'),
            'code.required' => __('Warehouse code is required'),
            'code.unique' => __('Warehouse code must be unique'),
            'email.email' => __('Please provide a valid email address'),
            'contact_email.email' => __('Please provide a valid contact email address'),
            'latitude.between' => __('Latitude must be between -90 and 90'),
            'longitude.between' => __('Longitude must be between -180 and 180'),
            'closing_time.after' => __('Closing time must be after opening time'),
            'operating_days.*.between' => __('Operating days must be valid weekday numbers (0-6)'),
            'status.in' => __('Status must be one of: active, inactive, maintenance'),
        ];
    }
}
