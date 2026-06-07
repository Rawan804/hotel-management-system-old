<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHotelNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_ar' => 'sometimes|string|max:255',
            'title_en' => 'sometimes|string|max:255',

            'content_ar' => 'sometimes|string',
            'content_en' => 'sometimes|string',

            'image' => 'nullable|image',

            'is_pinned' => 'nullable|boolean',

            'published_at' => 'nullable|date',
        ];
    }
}