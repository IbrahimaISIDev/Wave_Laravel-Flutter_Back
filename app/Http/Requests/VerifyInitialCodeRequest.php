<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyInitialCodeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'telephone' => 'required|string',
            'verification_code' => 'required|string|min:6|max:6'
        ];
    }
}