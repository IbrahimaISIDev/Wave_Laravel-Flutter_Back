<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'telephone' => 'required|string|max:20|unique:users,telephone',
            'email' => 'required|string|email|max:100|unique:users,email',
            'roleId' => 'required|exists:role,id',
            'adresse' => 'required|string|max:255',
            'date_naissance' => 'required|date|before:today',
            'secret' => 'required|string|min:5|max:6',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Règle de validation pour la photo
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.string' => 'Le nom doit être une chaîne de caractères.',
            'nom.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'prenom.string' => 'Le prénom doit être une chaîne de caractères.',
            'prenom.max' => 'Le prénom ne peut pas dépasser 50 caractères.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'telephone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.string' => 'L\'adresse email doit être une chaîne de caractères.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 100 caractères.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'roleId.required' => 'Le rôle est obligatoire.',
            'roleId.exists' => 'Le rôle sélectionné n\'existe pas.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'adresse.max' => 'L\'adresse ne peut pas dépasser 255 caractères.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'La photo doit être au format jpeg, png, jpg, gif ou svg.',
            'photo.max' => 'La photo ne peut pas dépasser 2 Mo.',
        ];
    }
}
