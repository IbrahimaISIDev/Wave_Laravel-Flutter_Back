<?php

namespace App\Repositories;

use App\Models\ScheduledTransfer;
use App\Repositories\Interfaces\ScheduledTransferRepositoryInterface;

class ScheduledTransferRepository implements ScheduledTransferRepositoryInterface
{
    protected $model;

    public function __construct(ScheduledTransfer $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function findByUser(int $userId)
    {
        return $this->model->where('exp', $userId)
                          ->with(['beneficiaire:id,nom,prenom,telephone'])
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    public function cancel(int $id)
    {
        return $this->model->where('id', $id)->update(['is_active' => false]);
    }

    public function findActive()
    {
        return $this->model->where('is_active', true)
                          ->where(function ($query) {
                              $query->whereNull('date_fin')
                                    ->orWhere('date_fin', '>=', now()->toDateString());
                          })
                          ->get();
    }

    public function update(int $id, array $data)
    {
        $scheduledTransfer = $this->model->findOrFail($id);
        $scheduledTransfer->update($data);
        return $scheduledTransfer;
    }


    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function findDuplicate($expediteur, $destinataire, $montant, $dateDebut, $heureExecution)
    {
        return $this->model
            ->where('exp', $expediteur)
            ->where('destinataire', $destinataire)
            ->where('montant', $montant)
            ->where('date_debut', $dateDebut)
            ->where('heure_execution', $heureExecution)
            ->where('is_active', true)
            ->first();
    }
  
}