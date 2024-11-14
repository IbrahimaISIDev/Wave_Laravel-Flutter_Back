<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\Contact;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected $model;
    protected $contactModel;

    public function __construct(Transaction $model, Contact $contactModel)
    {
        $this->model = $model;
        $this->contactModel = $contactModel;
    }

    public function create(array $data)
    {
        $transaction = $this->model->create($data);

        // Crée ou met à jour le contact après une transaction
        $this->contactModel->updateOrCreate(
            [
                'user_id' => $data['exp'],
                'contact_id' => $data['destinataire']
            ],
            [
                'last_transaction' => now()
            ]
        );

        return $transaction;
    }

    public function findByUser(int $userId)
    {
        return $this->model->where('exp', $userId)
                          ->orWhere('destinataire', $userId)
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function update(int $id, array $data)
    {
        $transaction = $this->model->findOrFail($id);
        $transaction->update($data);
        return $transaction;
    }

    public function getHistory($userId, array $filters = [], $perPage = 15)
    {
        $query = $this->model
            ->where(function ($query) use ($userId) {
                $query->where('exp', $userId)
                      ->orWhere('destinataire', $userId);
            })
            ->with([
                'expediteur:id,nom,prenom,telephone',
                'beneficiaire:id,nom,prenom,telephone',
                'type:id,libelle'
            ]);

        // Application des filtres
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['type'])) {
            $query->where('type_id', $filters['type']);
        }

        if (!empty($filters['montant_min'])) {
            $query->where('montant', '>=', $filters['montant_min']);
        }

        if (!empty($filters['montant_max'])) {
            $query->where('montant', '<=', $filters['montant_max']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getStats($userId, array $filters = [])
    {
        $query = $this->model->where(function ($query) use ($userId) {
            $query->where('exp', $userId)
                  ->orWhere('destinataire', $userId);
        });

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_envoyé' => $query->clone()->where('exp', $userId)->where('status', 'completed')->sum('montant'),
            'total_reçu' => $query->clone()->where('destinataire', $userId)->where('status', 'completed')->sum('montant'),
            'nombre_envois' => $query->clone()->where('exp', $userId)->where('status', 'completed')->count(),
            'nombre_receptions' => $query->clone()->where('destinataire', $userId)->where('status', 'completed')->count()
        ];
    }

    public function getMerchantTransactions(int $merchantId, array $filters = [])
    {
        $query = $this->model
            ->where('destinataire', $merchantId)
            ->where('type_id', 4)  // PAIEMENT_MARCHAND
            ->where('status', 'completed')
            ->with([
                'expediteur:id,nom,prenom,telephone',
                'type:id,libelle'
            ]);

        // Filtrage par date
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Filtrage par montant
        if (isset($filters['montant_min'])) {
            $query->where('montant', '>=', $filters['montant_min']);
        }

        if (isset($filters['montant_max'])) {
            $query->where('montant', '<=', $filters['montant_max']);
        }

        // Par défaut, trier par date décroissante
        $query->orderBy('created_at', 'desc');

        // Si pagination demandée
        if (isset($filters['paginate']) && $filters['paginate']) {
            return $query->paginate($filters['per_page'] ?? 15);
        }

        return $query->get();
    }
}