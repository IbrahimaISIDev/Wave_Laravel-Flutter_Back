<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecretCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'new_secret_code' => 'required|string|min:4|max:6',
        ];
    }

    public function messages()
    {
        return [
            'new_secret_code.required' => 'Le nouveau code secret est requis',
            'new_secret_code.min' => 'Le code secret doit contenir au moins 4 caractères',
            'new_secret_code.max' => 'Le code secret ne doit pas dépasser 6 caractères',
        ];
    }
}