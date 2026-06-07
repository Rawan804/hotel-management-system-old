<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHotelNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',

            'content_ar' => 'required|string',
            'content_en' => 'required|string',

            'image' => 'nullable|image',

            'is_pinned' => 'nullable|boolean',

            'published_at' => 'nullable|date',
        ];
    }
}