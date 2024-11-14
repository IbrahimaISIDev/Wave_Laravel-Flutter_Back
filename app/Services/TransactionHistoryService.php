<?php

namespace App\Services;

use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransactionHistoryService
{
    protected $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function getHistory($userId, array $filters = [])
    {
        try {
            $perPage = $filters['per_page'] ?? 15;
            $transactions = $this->transactionRepository->getHistory($userId, $filters, $perPage);
            $stats = $this->transactionRepository->getStats($userId, $filters);

            $formattedTransactions = $transactions->map(function ($transaction) use ($userId) {
                $isExpediteur = $transaction->exp === $userId;
                $autrePartie = $isExpediteur ? $transaction->beneficiaire : $transaction->expediteur;
                $type = $transaction->type;

                return [
                    'id' => $transaction->id,
                    'type' => [
                        'id' => $type->id,
                        'libelle' => $type->libelle
                    ],
                    'type_operation' => $isExpediteur ? 'envoi' : 'reception',
                    'montant' => $transaction->montant,
                    'date' => Carbon::parse($transaction->created_at)->format('Y-m-d H:i:s'),
                    'status' => $transaction->status,
                    'is_expediteur' => $isExpediteur,
                    'autre_partie' => $autrePartie ? [
                        'id' => $autrePartie->id,
                        'nom' => $autrePartie->nom,
                        'prenom' => $autrePartie->prenom,
                        'telephone' => $autrePartie->telephone
                    ] : null,
                    'cancelled_at' => $transaction->cancelled_at ? 
                        Carbon::parse($transaction->cancelled_at)->format('Y-m-d H:i:s') : null,
                    'cancel_reason' => $transaction->cancel_reason
                ];
            });

            return [
                'status' => true,
                'data' => [
                    'transactions' => $formattedTransactions,
                    'stats' => [
                        'total_envoye' => number_format($stats['total_envoyé'], 0, ',', ' ') . ' FCFA',
                        'total_recu' => number_format($stats['total_reçu'], 0, ',', ' ') . ' FCFA',
                        'nombre_envois' => $stats['nombre_envois'],
                        'nombre_receptions' => $stats['nombre_receptions']
                    ],
                    'pagination' => [
                        'total' => $transactions->total(),
                        'per_page' => $transactions->perPage(),
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage()
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur récupération historique : ' . $e->getMessage());
            throw $e;
        }
    }
}