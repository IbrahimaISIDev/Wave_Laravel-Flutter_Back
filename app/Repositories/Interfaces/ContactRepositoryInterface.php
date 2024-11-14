<?php

namespace App\Repositories\Interfaces;

interface ContactRepositoryInterface
{
    /**
     * Créer un nouveau contact
     */
    public function create(array $data);

    /**
     * Trouver tous les contacts d'un utilisateur
     */
    public function findByUser($userId);

    /**
     * Trouver un contact par son ID
     */
    public function findById($id);

    /**
     * Vérifier si un contact existe déjà
     */
    public function exists($userId, $contactId);

    /**
     * Basculer le statut favori d'un contact
     */
    public function toggleFavori($userId, $contactId);

    /**
     * Mettre à jour la date de dernière transaction
     */
    public function updateLastTransaction($userId, $contactId);

    /**
     * Récupérer la liste des favoris d'un utilisateur
     */
    public function getFavoris($userId);

    /**
     * Créer ou mettre à jour un contact
     */
    public function updateOrCreate(array $attributes, array $values = []);
}