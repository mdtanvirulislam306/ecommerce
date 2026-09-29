<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the admin session of anyone deactivated while signed in, on their very next request.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user === null || ! $user->isDeactivated()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->regenerateToken();

        $message = 'Your access to this shop has been turned off. Ask the shop owner to restore it.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->route('login')->with('status', $message);
    }
}
