<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class OtpRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input('email');
        $ip = $request->ip();
        
        if (!$email) {
            return response()->json([
                'message' => 'Email is required',
                'errors' => ['email' => ['Email field is required.']]
            ], 422);
        }

        // Rate limiting: max 5 OTP requests per minute per email
        $emailKey = "otp_rate_limit:email:{$email}";
        $ipKey = "otp_rate_limit:ip:{$ip}";
        
        $emailAttempts = Redis::get($emailKey) ?? 0;
        $ipAttempts = Redis::get($ipKey) ?? 0;
        
        if ($emailAttempts >= 5) {
            return response()->json([
                'message' => 'Too many OTP requests. Please try again later.',
                'errors' => ['email' => ['Too many OTP requests for this email. Please wait 1 minute.']]
            ], 429);
        }
        
        if ($ipAttempts >= 10) {
            return response()->json([
                'message' => 'Too many requests from this IP. Please try again later.',
                'errors' => ['ip' => ['Too many requests from this IP. Please wait 1 minute.']]
            ], 429);
        }
        
        // Increment counters
        Redis::incr($emailKey);
        Redis::incr($ipKey);
        Redis::expire($emailKey, 60); // 1 minute
        Redis::expire($ipKey, 60); // 1 minute
        
        return $next($request);
    }
}
