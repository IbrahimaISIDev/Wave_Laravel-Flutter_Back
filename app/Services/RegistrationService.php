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

            // Générer un secret pour l'API
            $secret = Str::random(32);
            $hashedSecret = Hash::make($secret);

            // Téléverser la photo si elle est présente
            $photoUrl = null;
            if (isset($validatedData['photo'])) {
                $result = Cloudinary::upload($validatedData['photo']->getRealPath(), [
                    'folder' => 'users_photos',
                    'public_id' => 'user_' . Str::slug($validatedData['nom'] . '_' . $validatedData['prenom'])
                ]);
                $photoUrl = $result->getSecurePath();
            }

            // Préparer les données utilisateur, incluant l'URL de la photo
            $userData = $this->prepareUserData($validatedData, $hashedCode, $hashedSecret, $photoUrl);


            // Créer l'utilisateur
            $user = $this->userRepository->create($userData);

            // Générer et sauvegarder le QR Code
            $qrUrl = $this->generateQRCode($user, $originalCode);

            // Mettre à jour la carte de l'utilisateur
            $this->userRepository->update($user->id, ['carte' => $qrUrl]);

            // Générer le PDF
            $cardPdfPath = $this->cardPdfGenerator->generateCard($user, $qrUrl);

            // Envoyer les notifications
            $this->sendNotifications($user, $qrUrl, $cardPdfPath, $originalCode, $secret);

            // Nettoyer les fichiers temporaires
            if ($cardPdfPath && file_exists($cardPdfPath)) {
                unlink($cardPdfPath);
            }

            return [
                'user' => $user,
                'qrUrl' => $qrUrl,
                'secret' => $secret // Retourner le secret non hashé pour l'afficher une seule fois à l'utilisateur
            ];
        } catch (\Exception $e) {
            Log::error('Erreur registration service: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function prepareUserData(array $validatedData, string $hashedCode, string $hashedSecret, ?string $photoUrl): array
    {
        return [
            'nom' => $validatedData['nom'],
            'prenom' => $validatedData['prenom'],
            'telephone' => $validatedData['telephone'],
            'email' => $validatedData['email'],
            'adresse' => $validatedData['adresse'],
            'date_naissance' => $validatedData['date_naissance'],
            'role_id' => $validatedData['roleId'],
            'solde' => 0,
            'promo' => 0,
            'etatcarte' => true,
            'code' => $hashedCode,
            'secret' => $hashedSecret,
            'photo' => $photoUrl // ajout de l'URL de la photo
        ];
    }
    

    protected function generateQRCode($user, $code)
    {
        try {
            $directory = storage_path('app/public/qrcodes');
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $qrCodePath = storage_path("app/public/qrcodes/qr_{$user->id}.svg");
            QrCode::format('svg')
                ->size(200)
                ->backgroundColor(255, 255, 255)
                ->color(0, 0, 0)
                ->margin(1)
                ->generate($code, $qrCodePath);

            $result = Cloudinary::upload($qrCodePath, [
                'folder' => 'qrcodes/',
                'public_id' => "qr_{$user->id}",
                'resource_type' => 'raw'
            ]);

            if (file_exists($qrCodePath)) {
                unlink($qrCodePath);
            }

            return $result->getSecurePath();
        } catch (\Exception $e) {
            Log::error('Erreur QR Code : ' . $e->getMessage());
            throw $e;
        }
    }

    protected function sendNotifications($user, $qrUrl, $cardPdfPath, $code, $secret)
    {
        try {
            // Envoyer SMS
            $this->smsService->send(
                '+221' . $user->telephone,
                "Bienvenue sur Wave ! Votre code de vérification est : {$code}"
            );

            // Préparer et déclencher l'événement pour l'envoi d'email
            $mailData = [
                'user' => $user,
                'qrUrl' => $qrUrl,
                'cardPdfPath' => $cardPdfPath,
                'code' => $code,
                'secret' => $secret // Ajouter le secret dans les données d'email
            ];

            event(new UserRegistered($mailData));

            Log::info('Notifications envoyées avec succès pour l\'utilisateur : ' . $user->id);
        } catch (\Exception $e) {
            Log::error('Erreur notifications : ' . $e->getMessage());
            throw $e;
        }
    }
}
