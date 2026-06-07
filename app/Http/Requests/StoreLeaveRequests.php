<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequests extends FormRequest
{
    
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
        'title'=> 'required|string|max:255',
        'type' =>'required|string',
        'start_date'=>'required|date',
        'end_date'=>'required|date',
        'reason'=>'required|string|min:10'
        ];
    }


}