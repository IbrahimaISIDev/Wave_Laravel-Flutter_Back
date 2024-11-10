<?php

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Events\UserRegistered;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RegistrationService
{
    protected $userRepository;
    protected $smsService;
    protected $cardPdfGenerator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        SendSms $smsService,
        CardPdfGenerator $cardPdfGenerator
    ) {
        $this->userRepository = $userRepository;
        $this->smsService = $smsService;
        $this->cardPdfGenerator = $cardPdfGenerator;
    }

    public function registerUser(array $validatedData)
    {
        try {
            // Générer le code secret
            $originalCode = strtoupper(Str::random(6));
            $hashedCode = Hash::make($originalCode);

            // Initialiser photoUrl
            $photoUrl = '';

            // Téléverser la photo seulement si elle est présente et valide
            if (
                isset($validatedData['photo']) &&
                $validatedData['photo'] instanceof \Illuminate\Http\UploadedFile &&
                $validatedData['photo']->isValid()
            ) {
                try {
                    $result = Cloudinary::upload($validatedData['photo']->getRealPath(), [
                        'folder' => 'users_photos',
                        'public_id' => 'user_' . Str::slug($validatedData['nom'] . '_' . $validatedData['prenom'])
                    ]);
                    $photoUrl = $result->getSecurePath();
                } catch (\Exception $e) {
                    Log::error('Erreur upload photo: ' . $e->getMessage());
                }
            }

            // Préparer les données utilisateur
            $userData = $this->prepareUserData($validatedData, $hashedCode, $photoUrl);

            // Créer l'utilisateur
            $user = $this->userRepository->create($userData);

            // Générer et sauvegarder le QR Code
            $qrUrl = $this->generateQRCode($user, $originalCode);

            if ($qrUrl) {
                $this->userRepository->update($user->id, ['carte' => $qrUrl]);
            }

            // Générer le PDF
            $cardPdfPath = $this->cardPdfGenerator->generateCard($user, $qrUrl ?: '');

            // Envoyer les notifications
            $this->sendNotifications($user, $qrUrl ?: '', $cardPdfPath, $originalCode);

            // Nettoyer les fichiers temporaires
            if ($cardPdfPath && file_exists($cardPdfPath)) {
                unlink($cardPdfPath);
            }

            return [
                'user' => $user,
                'qrUrl' => $qrUrl ?: '',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur registration service: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateQRCode($user, $code)
    {
        try {
            // Créer le répertoire s'il n'existe pas
            $directory = storage_path('app/public/qrcodes');
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            // Générer le QR code localement
            $qrCodePath = $directory . "/qr_{$user->id}.svg";

            QrCode::format('svg')
                ->size(200)
                ->backgroundColor(255, 255, 255)
                ->color(0, 0, 0)
                ->margin(1)
                ->generate($code, $qrCodePath);

            // Vérifier si le fichier a été créé
            if (!file_exists($qrCodePath)) {
                throw new \Exception('QR Code file was not created');
            }

            try {
                // Upload vers Cloudinary
                $uploadResult = Cloudinary::upload($qrCodePath, [
                    'folder' => 'qrcodes',
                    'public_id' => "qr_{$user->id}",
                    'resource_type' => 'raw'
                ]);

                // Supprimer le fichier local
                unlink($qrCodePath);

                // Retourner l'URL sécurisée
                return $uploadResult->getSecurePath();
            } catch (\Exception $e) {
                Log::error('Erreur upload QR vers Cloudinary : ' . $e->getMessage());

                // En cas d'erreur d'upload, on retourne une chaîne vide
                if (file_exists($qrCodePath)) {
                    unlink($qrCodePath);
                }
                return '';
            }
        } catch (\Exception $e) {
            Log::error('Erreur génération QR Code : ' . $e->getMessage());
            return '';
        }
    }

    protected function prepareUserData(array $validatedData, string $hashedCode, string $photoUrl): array
    {
        return [
            'nom' => $validatedData['nom'],
            'prenom' => $validatedData['prenom'],
            'telephone' => $validatedData['telephone'],
            'email' => $validatedData['email'],
            'adresse' => $validatedData['adresse'],
            'date_naissance' => $validatedData['date_naissance'],
            'sexe' => $validatedData['sexe'], // Inclut le sexe (homme ou femme)
            'role_id' => $validatedData['roleId'] ?? 2, // Définit role_id par défaut à 2 (user)
            'solde' => 0,
            'promo' => 0,
            'etatcarte' => true,
            'code' => $hashedCode,
            'photo' => $photoUrl
        ];
    }
    


    protected function sendNotifications($user, $qrUrl, $cardPdfPath, $code)
    {
        try {
            $this->smsService->send(
                '+221' . $user->telephone,
                "Bienvenue sur SamaXalis ! Votre code de vérification est : {$code}"
            );

            $mailData = [
                'user' => $user,
                'qrUrl' => $qrUrl,
                'cardPdfPath' => $cardPdfPath,
                'code' => $code,
            ];

            event(new UserRegistered($mailData));

            Log::info('Notifications envoyées avec succès pour l\'utilisateur : ' . $user->id);
        } catch (\Exception $e) {
            Log::error('Erreur notifications : ' . $e->getMessage());
            throw $e;
        }
    }
}
