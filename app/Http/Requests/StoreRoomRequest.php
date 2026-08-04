<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_category_id' => 'required|exists:room_categories,id',

            'room_number' => 'required|string|max:255|unique:rooms,room_number',

            'status' => 'nullable|in:available,occupied,maintenance'
        ];
    }
}