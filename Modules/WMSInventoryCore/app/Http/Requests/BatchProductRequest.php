<?php

namespace Modules\WMSInventoryCore\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchProductRequest extends FormRequest
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
        return [
            'operation' => 'required|in:delete,update_status,update_category,update_unit,update_prices',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|exists:products,id',

            // For status updates
            'status' => 'required_if:operation,update_status|in:active,inactive,discontinued',

            // For category updates
            'category_id' => 'required_if:operation,update_category|exists:categories,id',

            // For unit updates
            'unit_id' => 'required_if:operation,update_unit|exists:units,id',

            // For price updates
            'cost_price' => 'required_if:operation,update_prices|numeric|min:0',
            'selling_price' => 'required_if:operation,update_prices|numeric|min:0',
            'price_adjustment_type' => 'nullable|in:fixed,percentage',
            'price_adjustment_value' => 'nullable|numeric',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'operation.required' => 'Batch operation type is required.',
            'operation.in' => 'Invalid batch operation type.',
            'product_ids.required' => 'At least one product must be selected.',
            'product_ids.min' => 'At least one product must be selected.',
            'product_ids.*.exists' => 'One or more selected products do not exist.',
            'status.required_if' => 'Status is required for status update operation.',
            'category_id.required_if' => 'Category is required for category update operation.',
            'unit_id.required_if' => 'Unit is required for unit update operation.',
            'cost_price.required_if' => 'Cost price is required for price update operation.',
            'selling_price.required_if' => 'Selling price is required for price update operation.',
        ];
    }
}
