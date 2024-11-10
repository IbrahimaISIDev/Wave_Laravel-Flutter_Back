<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateSecretCodeRequest;
use App\Http\Requests\VerifyInitialCodeRequest;
use App\Http\Requests\SetCustomSecretCodeRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login(
                $request->telephone,
                $request->code  // Changed from secret_code to code
            );
    
            return response()->json([
                'status' => $result['status'],
                'message' => $result['message'],
                'user' => $result['user'] ?? null,
                'access_token' => $result['token'] ?? null,
                'token_type' => 'Bearer'
            ], $result['code']);
    
        } catch (\Exception $e) {
            Log::error('Erreur de connexion: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyInitialCode(VerifyInitialCodeRequest $request)
    {
        try {
            $result = $this->authService->verifyInitialCode(
                $request->telephone,
                $request->verification_code
            );

            return response()->json([
                'status' => $result['status'],
                'message' => $result['message'],
                'verification_token' => $result['verification_token'] ?? null
            ], $result['code']);

        } catch (\Exception $e) {
            Log::error('Erreur vérification code: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la vérification du code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setCustomSecretCode(SetCustomSecretCodeRequest $request)
    {
        try {
            // L'utilisateur doit avoir un token de vérification valide
            if (!$request->user()->tokenCan('create-secret')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Veuillez d\'abord vérifier votre code initial'
                ], 403);
            }

            $result = $this->authService->setCustomSecretCode(
                $request->new_secret_code,
                $request->confirm_secret_code,
                $request->user()
            );

            return response()->json([
                'status' => $result['status'],
                'message' => $result['message']
            ], $result['code']);

        } catch (\Exception $e) {
            Log::error('Erreur création code secret: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la création du code secret',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $result = $this->authService->logout($request->user());
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur de déconnexion: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $result = $this->authService->getCurrentUser($request->user());
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur profil: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSecretCode(UpdateSecretCodeRequest $request)
    {
        try {
            $result = $this->authService->updateSecretCode(
                $request->user(),
                $request->new_secret_code
            );
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour code secret: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la mise à jour du code secret',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
