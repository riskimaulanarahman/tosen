<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Check if user is authenticated
        if (!$user) {
            // If no user, redirect to login
            return redirect()->route('login')
                ->with('message', 'Please log in to continue.')
                ->with('message_type', 'info');
        }
        
        // Check if email is verified - only redirect if not recently verified
        if (is_null($user->email_verified_at)) {
            // Check if this is a verification route to prevent infinite loop
            if (!$request->routeIs('verification.notice', 'verification.verify', 'verification.otp.notice', 'verification.otp.verify')) {
                // Log out user since they need to verify first
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect to verification page with email and message
                return redirect()->route('verification.otp.notice', ['email' => $user->email])
                    ->with('message', 'Your email address needs to be verified before you can access this page. Please check your email for the OTP verification code.')
                    ->with('message_type', 'warning');
            }
        }

        return $next($request);
    }
}
