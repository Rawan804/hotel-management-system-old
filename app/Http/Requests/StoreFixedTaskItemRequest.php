<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedTaskItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fixed_task_id' => 'required|exists:fixed_tasks,id',

            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ];
    }
}