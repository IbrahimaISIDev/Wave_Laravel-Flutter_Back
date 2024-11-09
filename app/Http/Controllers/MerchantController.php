<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantPaymentRequest;
use App\Services\MerchantService;
use App\Services\QRPaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    protected $merchantService;
    protected $qrPaymentService;

    public function __construct(MerchantService $merchantService, QRPaymentService $qrPaymentService)
    {
        $this->merchantService = $merchantService;
        $this->qrPaymentService = $qrPaymentService;

    }

     /**
     * Générer un QR code pour le marchand
     */
    public function generateQR()
    {
        $merchant = auth()->user();
        $qrCode = $this->qrPaymentService->generateMerchantQR($merchant);
        
        return response($qrCode, 200)->header('Content-Type', 'image/svg+xml');
    }


    public function processPayment(MerchantPaymentRequest $request)
    {
        try {
            $result = $this->merchantService->processMerchantPayment(
                $request->user(),
                $request->code_marchand,
                $request->montant,
                $request->description
            );

            if (!$result['status']) {
                return response()->json($result, 400);
            }

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('Erreur paiement marchand : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStats(Request $request)
    {
        try {
            if ($request->user()->role_id !== MerchantService::ROLE_MARCHAND) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $stats = $this->merchantService->getMerchantStats($request->user()->id);

            return response()->json([
                'status' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur statistiques marchand : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Traiter un paiement via QR code
     */
    public function processQRPayment(Request $request)
    {
        $validated = $request->validate([
            'qr_data' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        $client = auth()->user();
        
        $result = $this->qrPaymentService->processQRPayment(
            $validated['qr_data'],
            $client,
            $validated['montant'],
            $validated['description'] ?? null
        );

        return response()->json($result);
    }
}