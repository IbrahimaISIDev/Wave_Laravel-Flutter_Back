<?php

namespace App\Services;

use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantService
{
    protected $transactionRepository;
    protected $userRepository;

    const ROLE_MARCHAND = 3; // Assurez-vous que cet ID correspond à votre rôle marchand

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
    }

    public function processMerchantPayment($client, $codeMarchand, $montant, $description = null)
    {
        try {
            DB::beginTransaction();

            // Trouver le marchand par son code
            $marchand = $this->userRepository->findByCode($codeMarchand);

            if (!$marchand) {
                return [
                    'status' => false,
                    'message' => 'Code marchand invalide'
                ];
            }

            // Vérifier que c'est bien un marchand
            if ($marchand->role_id !== self::ROLE_MARCHAND) {
                return [
                    'status' => false,
                    'message' => 'Ce code ne correspond pas à un compte marchand'
                ];
            }

            // Vérifier le solde du client
            if ($client->solde < $montant) {
                return [
                    'status' => false,
                    'message' => 'Solde insuffisant'
                ];
            }

            // Créer la transaction
            $transaction = $this->transactionRepository->create([
                'montant' => $montant,
                'exp' => $client->id,
                'destinataire' => $marchand->id,
                'type_id' => 4, // PAIEMENT_MARCHAND
                'agent' => null,
                'description' => $description,
                'status' => 'completed'
            ]);

            // Mettre à jour les soldes
            $client->solde -= $montant;
            $marchand->solde += $montant;

            $client->save();
            $marchand->save();

            DB::commit();

            // Formater le reçu
            $recu = [
                'transaction_id' => $transaction->id,
                'date' => now()->format('Y-m-d H:i:s'),
                'marchand' => [
                    'nom' => $marchand->nom,
                    'prenom' => $marchand->prenom,
                    'telephone' => $marchand->telephone
                ],
                'montant' => $montant,
                'description' => $description,
                'nouveau_solde' => $client->solde
            ];

            return [
                'status' => true,
                'message' => 'Paiement effectué avec succès',
                'recu' => $recu
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur paiement marchand : ' . $e->getMessage());
            throw $e;
        }
    }

    public function getMerchantStats($merchantId)
    {
        try {
            $today = now()->format('Y-m-d');
            $thisMonth = now()->format('Y-m');

            // Transactions du jour
            $dailyTransactions = $this->transactionRepository->getMerchantTransactions(
                $merchantId,
                ['start_date' => $today]
            );

            // Transactions du mois
            $monthlyTransactions = $this->transactionRepository->getMerchantTransactions(
                $merchantId,
                ['start_date' => $thisMonth . '-01']
            );

            return [
                'daily' => [
                    'total' => $dailyTransactions->sum('montant'),
                    'count' => $dailyTransactions->count()
                ],
                'monthly' => [
                    'total' => $monthlyTransactions->sum('montant'),
                    'count' => $monthlyTransactions->count()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur statistiques marchand : ' . $e->getMessage());
            throw $e;
        }
    }
}