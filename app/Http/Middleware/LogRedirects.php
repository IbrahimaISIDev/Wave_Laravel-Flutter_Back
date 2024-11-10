<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogRedirects
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response->isRedirection()) {
            Log::info('Redirection detected', [
                'from' => $request->url(),
                'to' => $response->headers->get('Location'),
                'status' => $response->status()
            ]);
        }

        return $response;
    }
}
