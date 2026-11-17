<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $userRole = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;

        if ($userRole !== $role) {
            return $userRole === UserRole::Admin->value
                ? redirect()->route('admin.dashboard')
                : redirect()->route('tenant.dashboard');
        }

        return $next($request);
    }
}