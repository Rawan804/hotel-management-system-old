<?php

namespace App\Http\Requests;


use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{

    public function authorize(): bool

    {
        return true;
    }


    public function rules(): array
    {
        
        if ($this->isMethod('put')) {

            return [ 'role' => ['nullable',
                    'in:supervisor,service_manager,employee',],
            ];
        }

        return [

            'dep_id' => ['nullable','exists:departments,dep_id',],

            'name' => ['required','string','max:255'],

            'phone' => [ 'required','string'],

            'email' => ['required','email','unique:staff,email',],

            'password' => ['required','string','min:6'],

            'image' => ['nullable','image','mimes:jpg,png,jpeg','max:2048'],

            'role' => ['nullable',
                'in:supervisor,service_manager,employee'],

            'status' => ['required','in:available,busy,offline,on_break,overloaded',
            ],

            'service_load' => ['required','integer','min:0'],

            'max_load' => ['required','integer','min:0'],

        ];
    }
}