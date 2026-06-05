<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that reads the `appearance` cookie (light/dark/system) and
 * shares the value with every Blade view, so layouts and partials can
 * render the right CSS variant.
 */
class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The current HTTP request.
     * @param  Closure(Request): (Response)  $next  The next middleware in the pipeline.
     * @return Response The downstream response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance') ?? 'system');

        return $next($request);
    }
}
