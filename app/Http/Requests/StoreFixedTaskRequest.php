<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dep_id' => 'required|exists:departments,dep_id',
          //  'staff_id' => 'required|exists:staff,staff_id',

            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            // ممكن نخليها اختيارية حسب النظام
            'is_active' => 'nullable|boolean',
            'weight' => 'nullable|integer|min:1',

            // items checklist
            'items' => 'nullable|array',
            'items.*.name_ar' => 'required_with:items|string|max:255',
            'items.*.name_en' => 'required_with:items|string|max:255',
        ];
    }
}