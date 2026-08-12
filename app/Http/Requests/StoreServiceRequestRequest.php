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
            'dep_id' => 'required|exists:departments,dep_id',

            // اختيار خدمة (اختياري لأن في "غير ذلك")
            'ser_id' => 'nullable|exists:services,ser_id',

            // تفاصيل الطلب
            'details' => 'nullable|string',

            // مكان الطلب
            'location' => 'nullable|string|max:255',

            // مهم: إذا "غير ذلك" ممكن نحتاج label بسيط
            'custom_title' => 'nullable|string|max:255',
        ];
    }
}