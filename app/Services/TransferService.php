<?php

namespace App\Services;

use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransferService
{
    protected $transactionRepository;
    protected $userRepository;
    protected $contactRepository;

    const TYPE_TRANSFERT_SIMPLE = 1;
    const TYPE_TRANSFERT_MULTIPLE = 2;
    const DELAI_ANNULATION = 30; // minutes

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        UserRepositoryInterface $userRepository,
        ContactRepositoryInterface $contactRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->contactRepository = $contactRepository;
    }

    public function transfer($expediteur, $telephone, $montant)
    {
        try {
            DB::beginTransaction();

            // Vérifications de base
            if ($expediteur->solde < $montant) {
                return [
                    'status' => false,
                    'message' => 'Solde insuffisant'
                ];
            }

            $destinataire = $this->userRepository->findByPhone($telephone);
            
            if (!$destinataire) {
                return [
                    'status' => false,
                    'message' => 'Ce numéro n\'est pas inscrit sur la plateforme'
                ];
            }

            if ($expediteur->id === $destinataire->id) {
                return [
                    'status' => false,
                    'message' => 'Vous ne pouvez pas effectuer un transfert vers vous-même'
                ];
            }

            // Création de la transaction
            $transaction = $this->transactionRepository->create([
                'montant' => $montant,
                'exp' => $expediteur->id,
                'destinataire' => $destinataire->id,
                'type_id' => self::TYPE_TRANSFERT_SIMPLE,
                'status' => 'completed'
            ]);

            // Mise à jour des soldes
            $expediteur->solde -= $montant;
            $destinataire->solde += $montant;

            $expediteur->save();
            $destinataire->save();

            // Mise à jour des contacts
            $this->contactRepository->updateOrCreate(
                ['user_id' => $expediteur->id, 'contact_id' => $destinataire->id],
                ['last_transaction' => now()]
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Transfert effectué avec succès',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'montant' => $montant,
                    'destinataire' => [
                        'nom' => $destinataire->nom,
                        'prenom' => $destinataire->prenom,
                        'telephone' => $destinataire->telephone
                    ],
                    'date' => $transaction->created_at,
                    'nouveau_solde' => $expediteur->solde
                ]
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur transfert : ' . $e->getMessage());
            throw $e;
        }
    }

    public function multipleTransfer($expediteur, array $telephones, $montant)
    {
        try {
            DB::beginTransaction();

            $successfulTransfers = [];
            $failedTransfers = [];
            $totalAmount = $montant * count($telephones);

            // Vérification du solde total nécessaire
            if ($expediteur->solde < $totalAmount) {
                return [
                    'status' => false,
                    'message' => 'Solde insuffisant pour effectuer tous les transferts',
                    'data' => [
                        'solde_actuel' => $expediteur->solde,
                        'montant_necessaire' => $totalAmount,
                        'montant_manquant' => $totalAmount - $expediteur->solde
                    ]
                ];
            }

            foreach ($telephones as $telephone) {
                try {
                    $destinataire = $this->userRepository->findByPhone($telephone);
                    
                    if (!$destinataire) {
                        $failedTransfers[] = [
                            'telephone' => $telephone,
                            'reason' => 'Numéro non inscrit sur la plateforme'
                        ];
                        continue;
                    }

                    if ($destinataire->id === $expediteur->id) {
                        $failedTransfers[] = [
                            'telephone' => $telephone,
                            'reason' => 'Impossible de transférer à soi-même'
                        ];
                        continue;
                    }

                    // Création de la transaction
                    $transaction = $this->transactionRepository->create([
                        'montant' => $montant,
                        'exp' => $expediteur->id,
                        'destinataire' => $destinataire->id,
                        'type_id' => self::TYPE_TRANSFERT_MULTIPLE,
                        'status' => 'completed'
                    ]);

                    // Mise à jour des soldes
                    $expediteur->solde -= $montant;
                    $destinataire->solde += $montant;
                    $destinataire->save();

                    // Mise à jour des contacts
                    $contact = $this->contactRepository->updateOrCreate(
                        ['user_id' => $expediteur->id, 'contact_id' => $destinataire->id],
                        ['last_transaction' => now()]
                    );

                    $successfulTransfers[] = [
                        'transaction_id' => $transaction->id,
                        'destinataire' => [
                            'nom' => $destinataire->nom,
                            'prenom' => $destinataire->prenom,
                            'telephone' => $destinataire->telephone,
                            'is_favori' => $contact->is_favori
                        ],
                        'montant' => $montant
                    ];

                } catch (\Exception $e) {
                    Log::error('Erreur transfert multiple vers ' . $telephone . ': ' . $e->getMessage());
                    $failedTransfers[] = [
                        'telephone' => $telephone,
                        'reason' => 'Erreur technique lors du transfert'
                    ];
                }
            }

            $expediteur->save();

            DB::commit();

            // Construction du message de retour
            $message = $this->buildMultipleTransferMessage(
                count($successfulTransfers),
                count($failedTransfers),
                $montant,
                $expediteur->solde
            );

            return [
                'status' => count($successfulTransfers) > 0,
                'message' => $message,
                'data' => [
                    'successful_transfers' => $successfulTransfers,
                    'failed_transfers' => $failedTransfers,
                    'total_transferred' => count($successfulTransfers) * $montant,
                    'remaining_solde' => $expediteur->solde,
                    'transfers_completed' => count($successfulTransfers),
                    'transfers_failed' => count($failedTransfers)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur transferts multiples : ' . $e->getMessage());
            throw $e;
        }
    }

    public function cancelTransfer($userId, $transactionId, $reason)
    {
        try {
            DB::beginTransaction();

            $transaction = $this->transactionRepository->findById($transactionId);

            if (!$transaction) {
                return [
                    'status' => false,
                    'message' => 'Transaction introuvable'
                ];
            }

            // Vérifications
            if ($transaction->exp !== $userId) {
                return [
                    'status' => false,
                    'message' => 'Vous n\'êtes pas autorisé à annuler cette transaction'
                ];
            }

            if ($transaction->status === 'cancelled') {
                return [
                    'status' => false,
                    'message' => 'Cette transaction est déjà annulée'
                ];
            }

            if ($transaction->status !== 'completed') {
                return [
                    'status' => false,
                    'message' => 'Cette transaction ne peut pas être annulée'
                ];
            }

            $timeDiff = Carbon::now()->diffInMinutes($transaction->created_at);
            if ($timeDiff > self::DELAI_ANNULATION) {
                return [
                    'status' => false,
                    'message' => 'Le délai d\'annulation de 30 minutes est dépassé'
                ];
            }

            // Récupération des utilisateurs
            $expediteur = $this->userRepository->findById($transaction->exp);
            $destinataire = $this->userRepository->findById($transaction->destinataire);

            // Remboursement
            $expediteur->solde += $transaction->montant;
            $destinataire->solde -= $transaction->montant;

            // Mise à jour de la transaction
            $this->transactionRepository->update($transactionId, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'cancelled_by' => $userId
            ]);

            $expediteur->save();
            $destinataire->save();

            DB::commit();

            return [
                'status' => true,
                'message' => 'Transaction annulée avec succès',
                'data' => [
                    'transaction_id' => $transactionId,
                    'montant_remboursé' => $transaction->montant,
                    'nouveau_solde' => $expediteur->solde,
                    'date_annulation' => now()->format('Y-m-d H:i:s')
                ]
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur annulation transfert : ' . $e->getMessage());
            throw $e;
        }
    }

    private function buildMultipleTransferMessage($successCount, $failCount, $montant, $remainingSolde)
    {
        $parts = [];

        if ($successCount > 0) {
            $parts[] = sprintf(
                '%d transfert%s effectué%s avec succès',
                $successCount,
                $successCount > 1 ? 's' : '',
                $successCount > 1 ? 's' : ''
            );
        }

        if ($failCount > 0) {
            $parts[] = sprintf(
                '%d transfert%s échoué%s',
                $failCount,
                $failCount > 1 ? 's' : '',
                $failCount > 1 ? 's' : ''
            );
        }

        $message = implode(', ', $parts);
        $message .= sprintf('. Solde restant : %s FCFA', number_format($remainingSolde, 0, ',', ' '));

        return $message;
    }
}