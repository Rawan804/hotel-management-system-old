<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HallReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hall_id'        => 'required|exists:halls,hall_id',
            'date'           => 'required|date|after_or_equal:now', 
            'start_hour'     => 'required|date_format:H:i',
            'duration_hours' => 'required|integer|min:1|max:24', 
        ];
    }

    public function messages(): array
    {
        return [
            'hall_id.required'        => 'يجب اختيار القاعة التي ترغب بحجزها.',
        
            'date.required'           => 'يجب تحديد تاريخ الحجز.',
            'date.date'               => 'صيغة التاريخ غير صحيحة.',
            'start_hour.required'     => 'يجب تحديد وقت بدء الحجز.',
            'start_hour.date_format'  => 'صيغة وقت البدء يجب أن تكون بالساعات والدقائق (مثال 16:30).',
            'duration_hours.required' => 'يجب تحديد مدة الحجز بالساعات.',
            'duration_hours.integer'  => 'مدة الحجز يجب أن تكون رقماً صحيحاً.',
            'duration_hours.max'      => 'أقصى مدة حجز مسموح بها هي 24 ساعة.',
        ];
    }
}