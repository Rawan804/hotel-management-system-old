<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $isUpdate = $this->route('hall') || $this->route('hall_id'); 

        return [
    
            'name_ar'    => ($isUpdate ? 'nullable' : 'required') . '|string|max:255',
            'name_en'    => ($isUpdate ? 'nullable' : 'required') . '|string|max:255',
            'details_ar' => ($isUpdate ? 'nullable' : 'required') . '|string',
            'details_en' => ($isUpdate ? 'nullable' : 'required') . '|string',
            'capacity'   => ($isUpdate ? 'nullable' : 'required') . '|integer|min:1',
            'price'      => ($isUpdate ? 'nullable' : 'required') . '|numeric|min:0',
            'image'      => ($isUpdate ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}

