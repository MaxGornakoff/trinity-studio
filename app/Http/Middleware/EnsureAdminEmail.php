<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedEmail = env('ADMIN_EMAIL');
        $user = $request->user();

        if (! $user || empty($allowedEmail) || strcasecmp($user->email, $allowedEmail) !== 0) {
            abort(403, 'Доступ в админку запрещён.');
        }

        return $next($request);
    }
}
