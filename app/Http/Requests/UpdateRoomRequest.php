<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

            'room_number' => [
                'sometimes',
                'string',
                Rule::unique('rooms', 'room_number')
                    ->ignore($this->route('id'))
            ],

            'status' => 'sometimes|in:available,occupied,maintenance',

            'images' => 'nullable|array',
            'images.*' => 'image'
        ];
    }
}