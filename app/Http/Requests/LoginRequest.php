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
            'telephone' => 'required|string|min:9|max:15',
            'code' => 'required|string|min:4|max:6'
        ];
    }

    public function messages()
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est requis',
            'telephone.min' => 'Le numéro de téléphone est invalide',
            'telephone.max' => 'Le numéro de téléphone est trop long',
            'code.required' => 'Le code est requis',
            'code.min' => 'Le code doit avoir au moins 4 caractères',
            'code.max' => 'Le code ne doit pas dépasser 6 caractères',
        ];
    }
}