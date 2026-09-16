<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManagePlatform
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canManagePlatform(), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
