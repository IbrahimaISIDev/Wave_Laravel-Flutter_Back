<?php

namespace App\Repositories\Interfaces;

interface ScheduledTransferRepositoryInterface
{
    public function create(array $data);
    public function findById(int $id);

    public function findByUser(int $userId);
    public function cancel(int $id);
    public function findActive();
    public function update(int $id, array $data);

     /**
     * Cherche un transfert planifié en double
     * 
     * @param int $expediteur ID de l'expéditeur
     * @param int $destinataire ID du destinataire
     * @param float $montant Montant du transfert
     * @param string $dateDebut Date de début (Y-m-d)
     * @param string $heureExecution Heure d'exécution (H:i)
     * @return mixed Le transfert trouvé ou null
     */
    public function findDuplicate(
        int $expediteur,
        int $destinataire, 
        float $montant,
        string $dateDebut,
        string $heureExecution
    );
}