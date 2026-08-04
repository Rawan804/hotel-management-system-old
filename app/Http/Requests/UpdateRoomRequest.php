<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_category_id' => 'sometimes|exists:room_categories,id',
            'room_number' => 'sometimes|string|unique:rooms,room_number,' . $this->id,
            'status' => 'sometimes|in:available,occupied,maintenance',

            'images' => 'nullable|array',
            'images.*' => 'image'
        ];
    }
}