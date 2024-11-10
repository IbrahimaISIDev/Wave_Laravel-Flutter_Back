<?php

namespace App\Providers;

use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\MerchantService;
use App\Services\QRPaymentService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repositories
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Bind MerchantService
        $this->app->singleton(MerchantService::class, function ($app) {
            return new MerchantService(
                $app->make(TransactionRepositoryInterface::class),
                $app->make(UserRepositoryInterface::class)
            );
        });

        // Bind QRPaymentService
        $this->app->singleton(QRPaymentService::class, function ($app) {
            return new QRPaymentService($app->make(MerchantService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}