<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    // Ce préfixe est important
    protected $namespace = 'App\\Http\\Controllers';
    
    public function boot()
    {
        // ...

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')  // Assurez-vous que cette ligne existe
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
