<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Repositories\FavoriRepository;
use App\Repositories\ContactRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\TransactionRepository;
use App\Repositories\ScheduledTransferRepository;
use App\Services\Interfaces\AuthServiceInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\FavoriRepositoryInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\ScheduledTransferRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );
        // Nouveau binding pour TransactionRepository
        $this->app->bind(
            TransactionRepositoryInterface::class,
            TransactionRepository::class
        );
        $this->app->bind(
            FavoriRepositoryInterface::class,
            FavoriRepository::class
        );
        // ScheduledTransfer Repository
        $this->app->bind(
            ScheduledTransferRepositoryInterface::class,
            ScheduledTransferRepository::class
        );
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(FavoriRepositoryInterface::class, FavoriRepository::class);


    }
}