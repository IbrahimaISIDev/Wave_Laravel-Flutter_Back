<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddFavoriRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // L'authentification est gérée par le middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'telephone' => 'required|string|max:20',
            'nom_complet' => 'required|string|max:255'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire',
            'telephone.max' => 'Le numéro de téléphone ne doit pas dépasser 20 caractères',
            'nom_complet.required' => 'Le nom complet est obligatoire',
            'nom_complet.max' => 'Le nom complet ne doit pas dépasser 255 caractères'
        ];
    }
}