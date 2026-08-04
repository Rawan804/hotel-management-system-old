<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantRequest extends FormRequest
{
    public function authorize()
    {
        
        return true;
    }
  public function rules(): array
    {

        $isUpdate = $this->route('restaurant') || $this->route('res_id'); 

        return [
    
            'name_ar'    => ($isUpdate ? 'nullable' : 'required') . '|string|max:255',
            'name_en'    => ($isUpdate ? 'nullable' : 'required') . '|string|max:255',
            'details_ar' => ($isUpdate ? 'nullable' : 'required') . '|string',
            'details_en' => ($isUpdate ? 'nullable' : 'required') . '|string',
            'image'      => ($isUpdate ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
