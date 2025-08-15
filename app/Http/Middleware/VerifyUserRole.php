<?php

namespace App\Http\Middleware;

use Closure;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorizedResponse();
        }

        /** @var UserRole|string $role */
        $role = $user->role;
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        if (! in_array($roleValue, $roles, true)) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    protected function unauthorizedResponse(): Response
    {
        return response()->json([
            'error' => true,
            'message' => 'Unauthorized access',
            'code' => 'unauthorized_access',
        ], 403);
    }
}
