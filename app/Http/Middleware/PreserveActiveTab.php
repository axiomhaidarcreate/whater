<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: PreserveActiveTab
 * 
 * Intercepts every POST/PUT/PATCH request coming from the dashboard.
 * If the request carries `_active_tab`, it flashes it to the session
 * so the Blade template can restore the correct tab after a form submit redirect.
 */
class PreserveActiveTab
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only act on non-GET requests (form submissions)
        if (!$request->isMethod('GET')) {
            $tab = $request->input('_active_tab');
            if ($tab && preg_match('/^[a-z_]{2,30}$/', $tab)) {
                // Flash to session so the next page load can read it
                session()->flash('_active_tab', $tab);
            }
        }

        return $response;
    }
}
