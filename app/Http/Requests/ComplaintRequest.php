<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
       if ($this->isMethod('put')) {
            return [
                'status' => 'required|in:resolved,rejected',
            ];
        }

        return [
            'title'       => 'required|string|max:255',
            'description' => 'required|string|min:10',
        ];
    }
}