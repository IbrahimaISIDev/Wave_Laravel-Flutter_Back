<?php

namespace App\Repositories;

use App\Models\Favori;
use App\Repositories\Interfaces\FavoriRepositoryInterface;

class FavoriRepository implements FavoriRepositoryInterface
{
    protected $model;

    public function __construct(Favori $model)
    {
        $this->model = $model;
    }

    /**
     * Créer un nouveau favori
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Récupérer tous les favoris d'un utilisateur
     */
    public function findByUser($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Supprimer un favori
     */
    public function delete($id)
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Vérifier si un numéro est déjà en favori pour un utilisateur
     */
    public function existsByPhone($userId, $telephone)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('telephone', $telephone)
            ->exists();
    }

    /**
     * Vérifier si un favori appartient à un utilisateur
     */
    public function belongsToUser($userId, $favoriId)
    {
        return $this->model
            ->where('id', $favoriId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function exists(int $userId, int $favoriId)
    {
        return $this->model
            ->where('id', $favoriId)
            ->where('user_id', $userId)
            ->exists();
    }
}