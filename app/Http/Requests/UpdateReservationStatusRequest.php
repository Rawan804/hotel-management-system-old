<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'confirmed',
                    'rejected',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' =>
                'يجب تحديد حالة الحجز.',

            'status.in' =>
                'حالة الحجز يجب أن تكون confirmed للموافقة أو rejected للرفض.',
        ];
    }
}