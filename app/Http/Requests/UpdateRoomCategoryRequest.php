<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_type_id' => 'sometimes|exists:room_types,id',

            'name_ar' => 'sometimes|string|max:255',
            'name_en' => 'sometimes|string|max:255',

            'capacity' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'total_rooms' => 'sometimes|integer|min:1',

            'description_ar' => 'sometimes|string',
            'description_en' => 'sometimes|string',

            'image' => 'nullable|image'
        ];
    }
}