<?php

namespace App\Services;

use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\FavoriRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ContactService
{
    protected $contactRepository;
    protected $userRepository;
    protected $favoriRepository;

    public function __construct(
        ContactRepositoryInterface $contactRepository,
        UserRepositoryInterface $userRepository,
        FavoriRepositoryInterface $favoriRepository
    ) {
        $this->contactRepository = $contactRepository;
        $this->userRepository = $userRepository;
        $this->favoriRepository = $favoriRepository;
    }

    public function listContacts($userId)
    {
        try {
            $contacts = $this->contactRepository->findByUser($userId);
            
            return [
                'status' => true,
                'message' => 'Liste des contacts récupérée avec succès',
                'contacts' => $contacts->map(function ($contact) {
                    return [
                        'id' => $contact->contact->id,
                        'nom' => $contact->contact->nom,
                        'prenom' => $contact->contact->prenom,
                        'telephone' => $contact->contact->telephone,
                        'is_favori' => $contact->is_favori,
                        'derniere_transaction' => $contact->last_transaction
                    ];
                })
            ];
        } catch (\Exception $e) {
            Log::error('Erreur liste contacts : ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleFavori($userId, $contactId)
    {
        try {
            DB::beginTransaction();

            // Toggle le statut favori dans la table contacts
            $contact = $this->contactRepository->toggleFavori($userId, $contactId);

            // Si is_favori est true, ajouter dans la table favoris
            if ($contact->is_favori) {
                // Récupérer l'utilisateur pour avoir son nom et prénom
                $user = $this->userRepository->findById($contactId);
                
                $this->favoriRepository->create([
                    'user_id' => $userId,
                    'favori_id' => $contactId,
                    'alias' => $user->prenom . ' ' . $user->nom
                ]);
            } else {
                // Si is_favori est false, retirer de la table favoris
                $this->favoriRepository->deleteByFavoriId($userId, $contactId);
            }

            DB::commit();

            return [
                'status' => true,
                'message' => $contact->is_favori ? 
                    'Contact ajouté aux favoris avec succès' : 
                    'Contact retiré des favoris avec succès',
                'is_favori' => $contact->is_favori
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur toggle favori : ' . $e->getMessage());
            throw $e;
        }
    }

}