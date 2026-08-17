<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequests extends FormRequest
{
    
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
       if ($this->isMethod('put')) {
            return [
                'status' => 'required|in:approved,rejected',
            ];
        }
    
    
    return [
        'type' =>'required|string',
        'start_date'=>'required|date|after_or_equal:today',
        'end_date'=>'required|date|after:start_date',
         'reason'=>'required|string'
        ];
    }
}