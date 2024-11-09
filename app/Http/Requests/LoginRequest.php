<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'telephone' => 'required|string|min:8|max:15',
            'secret_code' => 'required|string|min:4|max:6',
        ];
    }

    public function messages()
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est requis',
            'telephone.min' => 'Le numéro de téléphone doit contenir au moins 8 caractères',
            'telephone.max' => 'Le numéro de téléphone ne doit pas dépasser 15 caractères',
            'secret_code.required' => 'Le code secret est requis',
            'secret_code.min' => 'Le code secret doit contenir au moins 4 caractères',
            'secret_code.max' => 'Le code secret ne doit pas dépasser 6 caractères',
        ];
    }
}