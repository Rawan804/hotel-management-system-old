<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

            'room_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'room_number')
                    ->ignore($this->route('room'))
            ],

            'status' => 'nullable|in:available,occupied,maintenance'
        ];
    }
}