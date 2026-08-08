<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Redirect any authenticated request to the forced password-change screen
     * until the user (typically a newly admin-created account) has set their
     * own password. Excludes the password-change route itself and logout,
     * to avoid an unbreakable redirect loop.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->must_change_password
            && ! $request->routeIs('password.force.*')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('password.force.edit');
        }

        return $next($request);
    }
}
