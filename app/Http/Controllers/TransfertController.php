<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Services\TransferService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CancelTransferRequest;
use App\Http\Requests\MultipleTransferRequest;
use App\Http\Requests\TransactionHistoryRequest;
use App\Services\TransactionHistoryService;

class TransfertController extends Controller
{
    protected $transferService;
    protected $historyService;

    public function __construct(
        TransferService $transferService,
        TransactionHistoryService $historyService
    ) {
        $this->transferService = $transferService;
        $this->historyService = $historyService;
    }

    public function transfer(TransferRequest $request)
    {
        try {
            $result = $this->transferService->transfer(
                $request->user(),
                $request->telephone,
                $request->montant
            );

            if (!$result['status']) {
                return response()->json($result, 400);
            }

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('Erreur transfert : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors du transfert',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function cancelTransfer(CancelTransferRequest $request)
    {
        try {
            $result = $this->transferService->cancelTransfer(
                $request->user()->id,
                $request->transaction_id,
                $request->reason
            );

            return response()->json($result, $result['status'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Erreur annulation transfert : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de l\'annulation du transfert',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function multipleTransfer(MultipleTransferRequest $request)
    {
        try {
            $result = $this->transferService->multipleTransfer(
                $request->user(),
                $request->telephones,
                $request->montant
            );

            if (!$result['status']) {
                return response()->json($result, 400);
            }

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('Erreur transferts multiples : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors des transferts multiples',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function history(TransactionHistoryRequest $request)
    {
        try {
            $result = $this->historyService->getHistory(
                $request->user()->id,
                $request->validated()
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erreur historique : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}