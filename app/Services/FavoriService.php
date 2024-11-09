<?php

namespace App\Services;

use App\Repositories\Interfaces\FavoriRepositoryInterface;
use Illuminate\Support\Facades\Log;

class FavoriService
{
    protected $favoriRepository;

    public function __construct(FavoriRepositoryInterface $favoriRepository)
    {
        $this->favoriRepository = $favoriRepository;
    }

    /**
     * Ajouter un contact aux favoris
     */
    public function addFavori($userId, $telephone, $nomComplet)
    {
        try {
            // Vérifier si le numéro existe déjà en favori
            if ($this->favoriRepository->existsByPhone($userId, $telephone)) {
                return [
                    'status' => false,
                    'message' => 'Ce contact est déjà dans vos favoris'
                ];
            }

            // Créer le nouveau favori
            $favori = $this->favoriRepository->create([
                'user_id' => $userId,
                'telephone' => $telephone,
                'nom_complet' => $nomComplet
            ]);

            return [
                'status' => true,
                'message' => 'Contact ajouté aux favoris avec succès',
                'favori' => [
                    'id' => $favori->id,
                    'telephone' => $favori->telephone,
                    'nom_complet' => $favori->nom_complet,
                    'created_at' => $favori->created_at
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur ajout favori : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer la liste des favoris
     */
    public function getFavoris($userId)
    {
        try {
            $favoris = $this->favoriRepository->findByUser($userId);
            
            return [
                'status' => true,
                'message' => 'Liste des favoris récupérée avec succès',
                'favoris' => $favoris->map(function ($favori) {
                    return [
                        'id' => $favori->id,
                        'telephone' => $favori->telephone,
                        'nom_complet' => $favori->nom_complet,
                        'created_at' => $favori->created_at
                    ];
                })
            ];

        } catch (\Exception $e) {
            Log::error('Erreur récupération favoris : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer un favori
     */
    public function deleteFavori($userId, $favoriId)
    {
        try {
            // Vérifier si le favori appartient à l'utilisateur
            if (!$this->favoriRepository->belongsToUser($userId, $favoriId)) {
                return [
                    'status' => false,
                    'message' => 'Ce favori n\'appartient pas à l\'utilisateur'
                ];
            }

            // Supprimer le favori
            $this->favoriRepository->delete($favoriId);

            return [
                'status' => true,
                'message' => 'Favori supprimé avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur suppression favori : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Vérifier si un numéro est en favori
     */
    public function isFavori($userId, $telephone)
    {
        try {
            $exists = $this->favoriRepository->existsByPhone($userId, $telephone);

            return [
                'status' => true,
                'is_favori' => $exists
            ];

        } catch (\Exception $e) {
            Log::error('Erreur vérification favori : ' . $e->getMessage());
            throw $e;
        }
    }
}