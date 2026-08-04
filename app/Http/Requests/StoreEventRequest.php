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
        'title_ar' => 'required|string|max:255',
        'title_en' => 'required|string|max:255',

        'description_ar' => 'nullable|string',
        'description_en' => 'nullable|string',

        'location_ar' => 'required|string|max:255',
        'location_en' => 'required|string|max:255',

        'event_date' => 'required|date',
        'event_time' => 'required',

        'image' => 'nullable|image',

        'contact_phone' => 'required|string',
    ];
}}