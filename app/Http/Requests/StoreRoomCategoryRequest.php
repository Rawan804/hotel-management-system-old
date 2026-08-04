<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_type_id' => 'required|exists:room_types,id',

            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',

            'capacity' => 'required|integer|min:1',

            'price' => 'required|numeric|min:0',

            'total_rooms' => 'required|integer|min:1',

            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}