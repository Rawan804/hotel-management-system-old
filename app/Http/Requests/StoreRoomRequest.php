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
            'type' => 'required|string|max:255',

            'price' => 'required|numeric|min:0',

            'person_num' => 'required|integer|min:1',

            'images' => 'required|array',

            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048'
        ];
    }
}