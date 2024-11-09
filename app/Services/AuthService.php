<?php

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(string $telephone, string $secretCode)
    {
        try {
            // Vérifier si l'utilisateur existe avec ce numéro de téléphone
            $user = $this->userRepository->findByPhone($telephone);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'Numéro de téléphone non trouvé',
                    'code' => 401
                ];
            }

            // Vérifier si le code secret correspond
            if (!Hash::check($secretCode, $user->secret_code)) {
                return [
                    'status' => false,
                    'message' => 'Code secret invalide',
                    'code' => 401
                ];
            }

            // Créer un nouveau token d'authentification
            $token = $user->createToken('auth_token')->plainTextToken;

            // Préparer la réponse utilisateur
            $userResponse = $user->toArray();
            $userResponse['secret_code'] = '******';

            return [
                'status' => true,
                'message' => 'Connexion réussie',
                'user' => $userResponse,
                'token' => $token,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erreur login service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function logout($user)
    {
        try {
            $user->currentAccessToken()->delete();
            return [
                'status' => true,
                'message' => 'Déconnexion réussie'
            ];
        } catch (\Exception $e) {
            Log::error('Erreur logout service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCurrentUser($user)
    {
        try {
            $userResponse = $user->toArray();
            $userResponse['secret_code'] = '******';

            return [
                'status' => true,
                'user' => $userResponse
            ];
        } catch (\Exception $e) {
            Log::error('Erreur get current user service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateSecretCode($user, string $newSecretCode)
    {
        try {
            $user->secret_code = Hash::make($newSecretCode);
            $user->save();

            return [
                'status' => true,
                'message' => 'Code secret mis à jour avec succès'
            ];
        } catch (\Exception $e) {
            Log::error('Erreur update secret code service: ' . $e->getMessage());
            throw $e;
        }
    }
}