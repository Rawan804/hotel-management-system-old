<?php

namespace App\Http\Requests;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;



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

            'date'           => 'required|date|after_or_equal:today',
            'start_hour'     => 'required|date_format:H:i',
            'duration_hours' => 'required|integer|min:1|max:24',
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {

            $selectedDate = Carbon::parse($this->date);

            $selectedDateTime = Carbon::parse(
                $this->date . ' ' . $this->start_hour
            );

            $now = Carbon::now();


            // إذا كان الحجز اليوم
            if ($selectedDate->isToday()) {

                if ($selectedDateTime->lessThanOrEqualTo($now)) {

                    $validator->errors()->add(
                        'start_hour',
                        'لا يمكن حجز وقت مضى، يرجى اختيار وقت قادم.'
                    );
                }
            }
        });
    }


    public function messages(): array
    {
        return [
            'hall_id.required' => 'يجب اختيار القاعة التي ترغب بحجزها.',

            'date.required' => 'يجب تحديد تاريخ الحجز.',
            'date.date' => 'صيغة التاريخ غير صحيحة.',
            'date.after_or_equal' => 'لا يمكن اختيار تاريخ سابق لليوم.',

            'start_hour.required' => 'يجب تحديد وقت بدء الحجز.',
            'start_hour.date_format' => 'صيغة وقت البدء يجب أن تكون مثل 16:30.',

            'duration_hours.required' => 'يجب تحديد مدة الحجز بالساعات.',
            'duration_hours.integer' => 'مدة الحجز يجب أن تكون رقماً صحيحاً.',
            'duration_hours.max' => 'أقصى مدة حجز مسموح بها هي 24 ساعة.',
 ];
        }    }
