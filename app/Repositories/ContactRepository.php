<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Models\User;
use App\Repositories\Interfaces\ContactRepositoryInterface;

class ContactRepository implements ContactRepositoryInterface
{
    protected $model;
    protected $userModel;

    public function __construct(Contact $model, User $userModel)
    {
        $this->model = $model;
        $this->userModel = $userModel;
    }

    public function create(array $data)
    {
        // Vérifie si l'utilisateur existe
        $user = $this->userModel->where('telephone', $data['telephone'])->first();
        if (!$user) {
            throw new \Exception("Ce numéro n'est pas inscrit sur la plateforme");
        }

        return $this->model->create([
            'user_id' => $data['user_id'],
            'contact_id' => $user->id,
            'is_favori' => true
        ]);
    }

    public function findByUser($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['contact:id,nom,prenom,telephone'])
            ->orderBy('is_favori', 'desc')
            ->orderBy('last_transaction', 'desc')
            ->get();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function exists($userId, $contactId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('contact_id', $contactId)
            ->exists();
    }

    public function toggleFavori($userId, $contactId)
    {
        $contact = $this->model
            ->where('user_id', $userId)
            ->where('contact_id', $contactId)
            ->first();

        if ($contact) {
            $contact->update(['is_favori' => !$contact->is_favori]);
            return $contact;
        }

        return $this->model->create([
            'user_id' => $userId,
            'contact_id' => $contactId,
            'is_favori' => true
        ]);
    }

    public function updateLastTransaction($userId, $contactId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('contact_id', $contactId)
            ->update(['last_transaction' => now()]);
    }

    public function getFavoris($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('is_favori', true)
            ->with(['contact:id,nom,prenom,telephone'])
            ->get();
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->model->updateOrCreate($attributes, $values);
    }
}