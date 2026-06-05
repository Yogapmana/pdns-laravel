<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that enforces role-based access control on a route.
 *
 * Registered under the alias `role` in `bootstrap/app.php`. Performs three
 * checks in order:
 *  1. The request must carry an authenticated user (401 otherwise).
 *  2. The user account must be `is_active`; inactive accounts are forcibly
 *     logged out and the request is rejected with 403.
 *  3. The user role must be in the variadic list of allowed roles.
 */
class EnsureUserHasRole
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request  The current HTTP request.
     * @param  Closure  $next  The next middleware in the pipeline.
     * @param  string  ...$roles  The list of allowed user roles for this route.
     * @return Response Either the downstream response or an aborted 401/403.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Anda harus login terlebih dahulu.');
        }

        if (! $user->is_active) {
            auth()->logout();
            abort(403, 'Akun Anda telah dinonaktifkan. Hubungi admin.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
