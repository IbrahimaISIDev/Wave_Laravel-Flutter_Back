<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Récupérer l'historique des transactions d'un utilisateur
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkBalance(Request $request)
    {
        try {
            // Supposons que l'utilisateur est authentifié et que vous récupérez son ID
            $userId = auth()->id(); // ou $request->user()->id

            // Récupérer toutes les transactions de l'utilisateur (crédit et débit)
            $transactions = Transaction::getUserTransactionHistory($userId);

            // Calculer le solde en fonction des transactions
            $balance = $transactions->sum('montant'); // supposer que 'montant' est le champ de la transaction

            return response()->json([
                'status' => true,
                'balance' => $balance
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du solde : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la vérification du solde',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier le code QR et récupérer la transaction associée
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyQrCode(Request $request)
    {
        try {
            // Validation des données reçues
            $request->validate([
                'qr_code_data' => 'required|string', // Assurez-vous que le code QR est passé sous forme de chaîne
            ]);

            // Récupérer les données du code QR
            $qrData = $request->input('qr_code_data');

            // Recherche de la transaction associée au code QR
            $transaction = Transaction::where('qr_code', $qrData)->first(); // Assurez-vous d'avoir un champ 'qr_code' dans votre table

            if ($transaction) {
                return response()->json([
                    'status' => true,
                    'transaction' => $transaction
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction non trouvée pour ce QR code'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du code QR : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la vérification du code QR',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
