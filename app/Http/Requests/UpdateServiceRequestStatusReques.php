<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أو تحط شرط حسب الصلاحيات
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ];
    }
}