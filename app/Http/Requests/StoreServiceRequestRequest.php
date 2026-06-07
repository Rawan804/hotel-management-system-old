<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id'   => 'required|exists:bookings,book_id',
            'dep_id'       => 'required|exists:departments,dep_id',
            'service_type' => 'required|string',
            'details'      => 'nullable|string',
        ];
    }
}