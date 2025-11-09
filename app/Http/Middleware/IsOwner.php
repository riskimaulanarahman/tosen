<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Check if user is authenticated and is an owner
        if (!$user || !$user->isOwner()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Owner access required.'], 403);
            }
            
            return redirect()->route('login')->with('error', 'Unauthorized access. Owner privileges required.');
        }

        return $next($request);
    }
}
