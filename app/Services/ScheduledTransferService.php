<?php

namespace App\Services;

use App\Repositories\Interfaces\ScheduledTransferRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\TransferService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ScheduledTransferService
{
    protected $scheduledTransferRepository;
    protected $userRepository;
    protected $transferService;

    const FREQUENCE_DAILY = 'daily';
    const FREQUENCE_WEEKLY = 'weekly';
    const FREQUENCE_MONTHLY = 'monthly';

    public function __construct(
        ScheduledTransferRepositoryInterface $scheduledTransferRepository,
        UserRepositoryInterface $userRepository,
        TransferService $transferService
    ) {
        $this->scheduledTransferRepository = $scheduledTransferRepository;
        $this->userRepository = $userRepository;
        $this->transferService = $transferService;
    }

    public function scheduleTransfer($expediteur, $telephone, $montant, $frequence, $dateDebut, $dateFin, $heureExecution)
{
    try {
        DB::beginTransaction();

        // Validation du bénéficiaire
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
                'message' => 'Vous ne pouvez pas planifier un transfert vers vous-même'
            ];
        }

        // Vérification des transferts en double
        $duplicateTransfer = $this->scheduledTransferRepository->findDuplicate(
            $expediteur->id,
            $destinataire->id,
            $montant,
            $dateDebut,
            $heureExecution
        );

        if ($duplicateTransfer) {
            return [
                'status' => false,
                'message' => 'Un transfert identique est déjà planifié pour cette date et cette heure'
            ];
        }

        // Validation de la date
        $dateDebutCarbon = Carbon::parse($dateDebut . ' ' . $heureExecution);
        if ($dateDebutCarbon->isPast()) {
            return [
                'status' => false,
                'message' => 'La date de début doit être future'
            ];
        }

        // Validation de la période si date de fin fournie
        if ($dateFin) {
            $dateFinCarbon = Carbon::parse($dateFin . ' ' . $heureExecution);
            if ($dateFinCarbon->isBefore($dateDebutCarbon)) {
                return [
                    'status' => false,
                    'message' => 'La date de fin doit être postérieure à la date de début'
                ];
            }
        }

        // Création du transfert planifié
        $scheduledTransfer = $this->scheduledTransferRepository->create([
            'exp' => $expediteur->id,
            'destinataire' => $destinataire->id,
            'montant' => $montant,
            'frequence' => $frequence,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'heure_execution' => $heureExecution,
            'next_execution' => $dateDebutCarbon,
            'is_active' => true
        ]);

        DB::commit();

        return [
            'status' => true,
            'message' => 'Transfert planifié avec succès',
            'data' => [
                'scheduled_transfer' => [
                    'id' => $scheduledTransfer->id,
                    'montant' => $montant,
                    'frequence' => $frequence,
                    'destinataire' => [
                        'nom' => $destinataire->nom,
                        'prenom' => $destinataire->prenom,
                        'telephone' => $destinataire->telephone
                    ],
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                    'heure_execution' => $heureExecution,
                    'next_execution' => $dateDebutCarbon->format('Y-m-d H:i:s')
                ]
            ]
        ];

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Erreur planification transfert : ' . $e->getMessage());
        throw $e;
    }
}

    public function executeScheduledTransfers()
    {
        try {
            $now = Carbon::now();
            $activeTransfers = $this->scheduledTransferRepository->findActive();

            foreach ($activeTransfers as $transfer) {
                try {
                    if ($this->shouldExecuteTransfer($transfer, $now)) {
                        // Vérification du solde
                        $expediteur = $this->userRepository->findById($transfer->exp);
                        if ($expediteur->solde < $transfer->montant) {
                            Log::warning("Solde insuffisant pour le transfert planifié #{$transfer->id}");
                            continue;
                        }

                        // Exécution du transfert
                        $result = $this->transferService->transfer(
                            $expediteur,
                            $transfer->beneficiaire->telephone,
                            $transfer->montant
                        );

                        if ($result['status']) {
                            // Calcul de la prochaine exécution
                            $nextExecution = $this->calculateNextExecution($transfer);
                            
                            // Mise à jour du transfert planifié
                            $updateData = [
                                'last_execution' => now(),
                                'next_execution' => $nextExecution
                            ];

                            // Désactivation si c'était la dernière exécution
                            if (!$nextExecution) {
                                $updateData['is_active'] = false;
                            }

                            $this->scheduledTransferRepository->update($transfer->id, $updateData);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur lors de l'exécution du transfert planifié #{$transfer->id}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur exécution transferts planifiés : ' . $e->getMessage());
            throw $e;
        }
    }

    public function cancelScheduledTransfer(int $userId, int $transferId)
    {
        try {
            DB::beginTransaction();

            $transfer = $this->scheduledTransferRepository->findById($transferId);

            if (!$transfer) {
                return [
                    'status' => false,
                    'message' => 'Transfert planifié introuvable'
                ];
            }

            if ($transfer->exp !== $userId) {
                return [
                    'status' => false,
                    'message' => 'Vous n\'êtes pas autorisé à annuler ce transfert planifié'
                ];
            }

            if (!$transfer->is_active) {
                return [
                    'status' => false,
                    'message' => 'Ce transfert planifié est déjà inactif'
                ];
            }

            $this->scheduledTransferRepository->update($transferId, [
                'is_active' => false
            ]);

            DB::commit();

            return [
                'status' => true,
                'message' => 'Transfert planifié annulé avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur annulation transfert planifié : ' . $e->getMessage());
            throw $e;
        }
    }

    protected function shouldExecuteTransfer($transfer, Carbon $now)
    {
        if (!$transfer->is_active || !$transfer->next_execution) {
            return false;
        }

        $nextExecution = Carbon::parse($transfer->next_execution);
        return $now->gte($nextExecution);
    }

    protected function calculateNextExecution($transfer)
    {
        $lastExecution = $transfer->next_execution ?? Carbon::parse($transfer->date_debut . ' ' . $transfer->heure_execution);
        $nextExecution = Carbon::parse($lastExecution);

        switch ($transfer->frequence) {
            case self::FREQUENCE_DAILY:
                $nextExecution->addDay();
                break;
            case self::FREQUENCE_WEEKLY:
                $nextExecution->addWeek();
                break;
            case self::FREQUENCE_MONTHLY:
                $nextExecution->addMonth();
                break;
        }

        // Vérifie si on dépasse la date de fin
        if ($transfer->date_fin && $nextExecution->startOfDay()->gt(Carbon::parse($transfer->date_fin)->endOfDay())) {
            return null;
        }

        return $nextExecution;
    }

    public function getScheduledTransfers(int $userId)
    {
        try {
            $transfers = $this->scheduledTransferRepository->findByUser($userId);

            return [
                'status' => true,
                'data' => $transfers->map(function ($transfer) {
                    return [
                        'id' => $transfer->id,
                        'montant' => $transfer->montant,
                        'frequence' => $transfer->frequence,
                        'date_debut' => $transfer->date_debut,
                        'date_fin' => $transfer->date_fin,
                        'heure_execution' => $transfer->heure_execution,
                        'is_active' => $transfer->is_active,
                        'last_execution' => $transfer->last_execution,
                        'next_execution' => $transfer->next_execution,
                        'beneficiaire' => [
                            'nom' => $transfer->beneficiaire->nom,
                            'prenom' => $transfer->beneficiaire->prenom,
                            'telephone' => $transfer->beneficiaire->telephone
                        ]
                    ];
                })
            ];
        } catch (\Exception $e) {
            Log::error('Erreur récupération transferts planifiés : ' . $e->getMessage());
            throw $e;
        }
    }
}