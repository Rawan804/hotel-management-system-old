<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
       
        $staffId = $this->route('staff') ? $this->route('staff')->staff_id : null;

        return [
            'dep_id'   => 'nullable|exists:departments,dep_id',
            'name'     => 'nullable|string|max:255',
            'phone'    => 'nullable|string',
            'email'    => 'nullable|email|unique:staff,email,' . $staffId . ',staff_id',
            'password' => 'nullable|min:6', 
            'image'    => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ];
    }
}