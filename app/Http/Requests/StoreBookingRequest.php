<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الحماية تتم عبر middleware
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,room_id',
            'startDate' => 'required|date|after_or_equal:today',
            'endDate' => 'required|date|after:startDate',
        ];
    }
}