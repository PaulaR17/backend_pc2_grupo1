<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Admin
{
    //corta el paso a quien no sea ADMIN
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            throw new UnauthorizedHttpException('', 'User not authenticated');
        } else if ($user->rol !== 'ADMIN') {
            throw new UnauthorizedHttpException('', 'User is not admin');
        }

        return $next($request);
    }
}
