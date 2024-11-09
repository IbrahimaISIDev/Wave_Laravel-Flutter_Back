<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class QRPaymentService
{
    protected $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Générer un QR code pour un marchand
     */
    public function generateMerchantQR($merchant)
    {
        // Créer les données du marchand à encoder
        $merchantData = [
            'code' => $merchant->code_marchand,
            'name' => $merchant->nom . ' ' . $merchant->prenom,
            'timestamp' => now()->timestamp
        ];

        // Encoder et chiffrer les données
        $encodedData = Crypt::encrypt(json_encode($merchantData));

        // Générer le QR code
        return QrCode::size(300)
                    ->format('svg')
                    ->generate($encodedData);
    }

    /**
     * Traiter un paiement via QR code
     */
    public function processQRPayment($qrData, $client, $montant, $description = null)
    {
        try {
            // Décrypter et décoder les données du QR
            $merchantData = json_decode(Crypt::decrypt($qrData), true);

            // Valider les données du QR
            $validator = Validator::make($merchantData, [
                'code' => 'required|string',
                'name' => 'required|string',
                'timestamp' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return [
                    'status' => false,
                    'message' => 'QR code invalide'
                ];
            }

            // Vérifier que le QR code n'a pas expiré (24h de validité)
            if (now()->timestamp - $merchantData['timestamp'] > 86400) {
                return [
                    'status' => false,
                    'message' => 'QR code expiré'
                ];
            }

            // Traiter le paiement via le service marchand
            return $this->merchantService->processMerchantPayment(
                $client,
                $merchantData['code'],
                $montant,
                $description
            );

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Erreur lors du traitement du QR code',
                'error' => $e->getMessage()
            ];
        }
    }
}