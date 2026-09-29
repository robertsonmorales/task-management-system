<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleAccess
{
    /**
     * Only let admins through.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // abort_unless($request->user()?->isAdmin(), 403);

        if(!$request->user()?->isAdmin()) {
            return back();
        }

        return $next($request);
    }
}
