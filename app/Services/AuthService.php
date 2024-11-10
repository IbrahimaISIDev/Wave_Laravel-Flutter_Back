<?php

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    protected $userRepository;
    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCKOUT_TIME = 300; // 5 minutes en secondes

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(string $telephone, string $code)
    {
        try {
            $telephone = $this->cleanPhoneNumber($telephone);

            if ($this->isAccountLocked($telephone)) {
                return [
                    'status' => false,
                    'message' => 'Compte temporairement bloqué. Veuillez réessayer dans quelques minutes.',
                    'code' => 429
                ];
            }

            $user = $this->userRepository->findByPhone($telephone);
            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'Identifiants invalides',
                    'code' => 401
                ];
            }

            if (!$user->etatcarte) {
                return [
                    'status' => false,
                    'message' => 'Votre compte est désactivé. Veuillez contacter le support.',
                    'code' => 403
                ];
            }

            if (!Hash::check($code, $user->code)) {
                $this->incrementLoginAttempts($telephone);

                $remainingAttempts = $this->getRemainingAttempts($telephone);
                if ($remainingAttempts <= 0) {
                    $this->lockAccount($telephone);
                    return [
                        'status' => false,
                        'message' => 'Compte bloqué pour des raisons de sécurité. Veuillez réessayer dans 5 minutes.',
                        'code' => 429
                    ];
                }

                return [
                    'status' => false,
                    'message' => "Code incorrect. Il vous reste {$remainingAttempts} tentative(s).",
                    'code' => 401
                ];
            }

            $this->resetLoginAttempts($telephone);
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            $userResponse = $this->prepareUserResponse($user);

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

    public function verifyInitialCode(string $telephone, string $verificationCode)
    {
        try {
            $telephone = $this->cleanPhoneNumber($telephone);
            
            // Récupérer l'utilisateur
            $user = $this->userRepository->findByPhone($telephone);
            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'Utilisateur non trouvé',
                    'code' => 404
                ];
            }

            // Vérifier que le compte n'est pas désactivé
            if (!$user->etatcarte) {
                return [
                    'status' => false,
                    'message' => 'Votre compte est désactivé',
                    'code' => 403
                ];
            }

            // Vérifier le code de vérification
            if (!Hash::check($verificationCode, $user->code)) {
                return [
                    'status' => false,
                    'message' => 'Code de vérification invalide',
                    'code' => 401
                ];
            }

            // Générer un token temporaire pour la deuxième étape
            $tempToken = $user->createToken('verification_token', ['create-secret'])->plainTextToken;

            return [
                'status' => true,
                'message' => 'Code de vérification validé',
                'verification_token' => $tempToken,
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erreur vérification code initial: ' . $e->getMessage());
            throw $e;
        }
    }

    public function setCustomSecretCode(string $newSecretCode, string $confirmSecretCode, $user)
    {
        try {
            // Vérifier si les codes secrets correspondent
            if ($newSecretCode !== $confirmSecretCode) {
                return [
                    'status' => false,
                    'message' => 'Les codes secrets ne correspondent pas',
                    'code' => 400
                ];
            }

            // Mettre à jour le code secret
            $user->code = Hash::make($newSecretCode);
            $user->save();

            // Supprimer le token de vérification
            $user->tokens()->where('name', 'verification_token')->delete();

            return [
                'status' => true,
                'message' => 'Code secret personnalisé créé avec succès',
                'code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Erreur création code secret personnalisé: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function cleanPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '221')) {
            $phone = substr($phone, 3);
        }
        return $phone;
    }

    protected function getLoginAttemptsKey(string $phone): string
    {
        return 'login_attempts_' . $phone;
    }

    protected function getLockoutKey(string $phone): string
    {
        return 'login_lockout_' . $phone;
    }

    protected function incrementLoginAttempts(string $phone): void
    {
        $key = $this->getLoginAttemptsKey($phone);
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, 300);
    }

    protected function getRemainingAttempts(string $phone): int
    {
        $attempts = Cache::get($this->getLoginAttemptsKey($phone), 0);
        return max(self::MAX_LOGIN_ATTEMPTS - $attempts, 0);
    }

    protected function resetLoginAttempts(string $phone): void
    {
        Cache::forget($this->getLoginAttemptsKey($phone));
        Cache::forget($this->getLockoutKey($phone));
    }

    protected function lockAccount(string $phone): void
    {
        Cache::put($this->getLockoutKey($phone), true, self::LOCKOUT_TIME);
    }

    protected function isAccountLocked(string $phone): bool
    {
        return Cache::has($this->getLockoutKey($phone));
    }

    protected function prepareUserResponse($user): array
    {
        $response = $user->toArray();
        unset($response['code']);
        return $response;
    }

    public function logout($user)
    {
        try {
            $user->tokens()->delete();
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
            return [
                'status' => true,
                'user' => $this->prepareUserResponse($user)
            ];
        } catch (\Exception $e) {
            Log::error('Erreur get current user service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateSecretCode($user, string $newCode)
    {
        try {
            $user->code = Hash::make($newCode);
            $user->save();
            $user->tokens()->delete();

            return [
                'status' => true,
                'message' => 'Code secret mis à jour avec succès. Veuillez vous reconnecter.'
            ];
        } catch (\Exception $e) {
            Log::error('Erreur update secret code service: ' . $e->getMessage());
            throw $e;
        }
    }
}