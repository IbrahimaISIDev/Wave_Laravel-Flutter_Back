<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\RegistrationService;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    protected $registrationService;

    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->registrationService->registerUser($request->validated());

            // Masquer le code dans la réponse
            $userResponse = $result['user']->toArray();
            $userResponse['code'];

            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'user' => $userResponse,
                'qr_url' => $result['qrUrl']
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur registration: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de l\'inscription',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}