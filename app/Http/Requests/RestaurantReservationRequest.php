<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'res_id'           => 'required|exists:restaurants,res_id',
            'person_num'       => 'required|integer|min:1', 
            'reservation_time' => 'required|date|after_or_equal:now',
        ];
    }

    public function messages(): array
    {
        return [
            'res_id.required'       => 'يجب اختيار المطعم.',
            'person_num.required'   => 'يجب تحديد عدد الأشخاص.',
            'person_num.min'        => 'يجب أن يكون الحجز لشخص واحد على الأقل.',
            'reservation_time.required' => 'يجب تحديد وقت وتاريخ الحجز.',
        ];
    }
}