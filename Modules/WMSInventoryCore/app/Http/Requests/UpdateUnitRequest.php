<?php

namespace Modules\WMSInventoryCore\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        $unitId = $this->route('unit');

        return [
            'name' => 'required|string|max:255|unique:units,name,'.$unitId,
            'code' => 'required|string|max:50|unique:units,code,'.$unitId,
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function authorize(): bool
    {
        return auth()->user()->can('wmsinventory.edit-unit');
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Unit name is required.'),
            'name.unique' => __('This unit name already exists.'),
            'code.required' => __('Unit code is required.'),
            'code.unique' => __('This unit code already exists.'),
            'code.max' => __('Unit code cannot be longer than 50 characters.'),
            'description.max' => __('Description cannot be longer than 1000 characters.'),
        ];
    }
}
