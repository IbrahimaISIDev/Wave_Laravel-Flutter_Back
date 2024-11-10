<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetCustomSecretCodeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'new_secret_code' => 'required|string|min:4|max:4',
            'confirm_secret_code' => 'required|string|min:4|max:4'
        ];
    }
}