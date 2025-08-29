<?php

namespace Modules\WMSInventoryCore\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'code')->ignore($productId),
            ],
            'description' => 'nullable|string|max:1000',
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($productId),
            ],
            'additional_barcodes' => 'nullable|array',
            'additional_barcodes.*' => 'string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'unit_id' => 'sometimes|required|exists:units,id',
            'category_id' => 'sometimes|required|exists:categories,id',
            'track_weight' => 'boolean',
            'track_quantity' => 'boolean',
            'track_serial_number' => 'boolean',
            'track_batch' => 'boolean',
            'track_expiry' => 'boolean',
            'alert_on' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'is_returnable' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_sellable' => 'boolean',
            'status' => 'sometimes|required|in:active,inactive,discontinued',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'code.required' => 'Product code is required.',
            'code.unique' => 'Product code already exists.',
            'sku.unique' => 'SKU already exists.',
            'barcode.unique' => 'Barcode already exists.',
            'unit_id.required' => 'Unit is required.',
            'unit_id.exists' => 'Selected unit does not exist.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be jpeg, png, jpg, gif, or svg format.',
            'image.max' => 'Image size must not exceed 2MB.',
        ];
    }
}
