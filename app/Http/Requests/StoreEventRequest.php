<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'location' => 'required|string',
            'image' => 'nullable|image',
            'contact_phone' => 'required|string',
        ];
    }
}