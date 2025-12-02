<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $rolesArray = explode(',', $roles);

        if (!in_array($user->role, $rolesArray)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
