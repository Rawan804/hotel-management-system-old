<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Allow request (authorization handled in controller)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules (Partial Update + multilingual support)
     */
    public function rules(): array
    {
        return [
            'title_ar' => 'sometimes|string|max:255',
            'title_en' => 'sometimes|string|max:255',

            'description_ar' => 'sometimes|string',
            'description_en' => 'sometimes|string',

            'location_ar' => 'sometimes|string|max:255',
            'location_en' => 'sometimes|string|max:255',

            'event_date' => 'sometimes|date',
            'event_time' => 'sometimes',

            'image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',

            'contact_phone' => 'sometimes|string|max:20',

            'is_active' => 'sometimes|boolean',
        ];
    }
}