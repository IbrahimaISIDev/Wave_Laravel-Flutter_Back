<?php

namespace App\Repositories\Interfaces;

interface FavoriRepositoryInterface
{
    public function create(array $data);
    public function findByUser(int $userId);
    //public function delete(int $userId, int $favoriId);
    public function exists(int $userId, int $favoriId);
    public function delete($id);
    public function existsByPhone($userId, $telephone);
    public function belongsToUser($userId, $favoriId);
}