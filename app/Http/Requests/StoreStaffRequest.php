<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
  public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('put')) {
            return [
                'role' => 'required|in:supervisor,employee'
            ];
        }
        return [
            'dep_id'   => 'nullable|exists:departments,dep_id',
            'name'     => 'required',
            'phone'    => 'required',
            'email'    => 'required|email|unique:staff,email',
            'password' => 'required|min:6',
            'image'    => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'role'     => 'required|in:supervisor,employee'
        ];
    }
}