<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Jobs\SendOtpEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpVerificationController extends Controller
{
    /**
     * Display the email verification notice.
     */
    public function show(Request $request)
    {
        $email = $request->get('email');
        $existingOtp = null;
        $otpInfo = null;
        
        // Check if there's existing valid OTP for this email
        if ($email) {
            $existingOtp = DB::table('email_verifications')
                ->where('email', $email)
                ->where('expires_at', '>', now())
                ->first();
                
            if ($existingOtp) {
                // Calculate remaining time
                $expiresAt = Carbon::parse($existingOtp->expires_at);
                $remainingMinutes = $expiresAt->diffInMinutes(now());
                
                $otpInfo = [
                    'exists' => true,
                    'expiresAt' => $expiresAt->format('H:i'),
                    'remainingMinutes' => $remainingMinutes,
                    'canResend' => $remainingMinutes <= 2 // Allow resend after 2 minutes
                ];
            } else {
                // Only send new OTP if none exists or expired
                $this->sendOtp($email);
            }
        }
        
        return inertia('Auth/VerifyEmail', [
            'email' => $email,
            'otpInfo' => $otpInfo,
            'message' => session('message'),
            'messageType' => session('message_type'),
        ]);
    }

    /**
     * Send OTP to user's email.
     */
    private function sendOtp($email)
    {
        // Clean up expired OTP tokens first
        $this->cleanupExpiredTokens();
        
        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing OTP for this email
        DB::table('email_verifications')->where('email', $email)->delete();
        
        // Store OTP in email_verifications table with expiration
        DB::table('email_verifications')->insert([
            'email' => $email,
            'token' => $otp,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get user and outlet info for email template
        $user = User::where('email', $email)->first();
        if ($user && $user->outlet) {
            // Load the owner relationship to avoid relationship errors
            $user->outlet->load('owner');
            
            // Send OTP via queue with complete template
            SendOtpEmail::dispatch(
                $email,
                $otp,
                null, // No password for OTP resend (verification only)
                $user->outlet->name,
                $user->outlet->address,
                $user->outlet->owner ? $user->outlet->owner->name : 'System Admin'
            );
        } else {
            // Fallback for users without outlet (should not happen in normal flow)
            SendOtpEmail::dispatch($email, $otp);
        }
        
        return $otp;
    }

    /**
     * Send OTP to user's email.
     */
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Check for existing OTP and apply cooldown
        $existingOtp = DB::table('email_verifications')
            ->where('email', $request->email)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingOtp) {
            $createdAt = Carbon::parse($existingOtp->created_at);
            $minutesSinceCreation = $createdAt->diffInMinutes(now());
            
            // Apply 2-minute cooldown
            if ($minutesSinceCreation < 2) {
                $remainingSeconds = 120 - ($minutesSinceCreation * 60);
                return response()->json([
                    'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP",
                    'cooldown' => true,
                    'remainingSeconds' => $remainingSeconds
                ], 429);
            }
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing OTP for this email
        DB::table('email_verifications')->where('email', $request->email)->delete();
        
        // Store OTP in email_verifications table with expiration
        DB::table('email_verifications')->insert([
            'email' => $request->email,
            'token' => $otp,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get user and outlet info for email template
        $user = User::where('email', $request->email)->first();
        if ($user && $user->outlet) {
            // Load the owner relationship to avoid relationship errors
            $user->outlet->load('owner');
            
            // Send OTP via queue with complete template (verification only)
            SendOtpEmail::dispatch(
                $request->email,
                $otp,
                null, // No password for OTP resend (verification only)
                $user->outlet->name,
                $user->outlet->address,
                $user->outlet->owner ? $user->outlet->owner->name : 'System Admin'
            );
        } else {
            // Fallback for users without outlet (should not happen in normal flow)
            SendOtpEmail::dispatch($request->email, $otp);
        }

        return response()->json([
            'message' => 'OTP sent successfully',
            'cooldown' => false
        ]);
    }

    /**
     * Verify OTP and complete email verification.
     */
    public function verify(Request $request)
    {
        \Log::info('Email verification started', [
            'email' => $request->email,
            'otp' => $request->otp,
            'request_time' => now()->toISOString()
        ]);

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find OTP token
        $token = DB::table('email_verifications')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        \Log::info('OTP lookup result', [
            'email' => $request->email,
            'otp_found' => $token ? true : false,
            'otp_expires_at' => $token ? $token->expires_at : null,
        ]);

        if (!$token) {
            \Log::warning('Invalid OTP attempt', [
                'email' => $request->email,
                'otp' => $request->otp,
            ]);
            
            return response()->json([
                'message' => 'Invalid or expired OTP',
                'errors' => ['otp' => ['The OTP you entered is invalid or has expired.']]
            ], 422);
        }

        // Find user and verify email
        $user = User::where('email', $request->email)->first();
        
        \Log::info('User lookup result', [
            'email' => $request->email,
            'user_found' => $user ? true : false,
            'user_id' => $user ? $user->id : null,
            'current_email_verified_at' => $user ? $user->email_verified_at : null,
        ]);
        
        if (!$user) {
            \Log::error('User not found during verification', [
                'email' => $request->email,
            ]);
            
            return response()->json([
                'message' => 'User not found',
                'errors' => ['email' => ['User account not found.']]
            ], 422);
        }

        // Start database transaction
        try {
            DB::beginTransaction();
            
            // Update user with verified email and new password
            $updateData = [
                'email_verified_at' => now(),
                'password' => Hash::make($request->password),
            ];
            
            \Log::info('Attempting user update', [
                'user_id' => $user->id,
                'update_data' => $updateData,
            ]);

            $updateResult = $user->update($updateData);
            
            \Log::info('User update result', [
                'user_id' => $user->id,
                'update_successful' => $updateResult,
                'updated_at' => now()->toISOString(),
            ]);

            // Verify the update actually worked by refreshing the user
            $freshUser = $user->fresh();
            \Log::info('Fresh user data after update', [
                'user_id' => $freshUser->id,
                'email_verified_at' => $freshUser->email_verified_at,
                'email_verified_at_is_null' => is_null($freshUser->email_verified_at),
            ]);

            // Delete the used OTP
            $otpDeleteResult = DB::table('email_verifications')
                ->where('email', $request->email)
                ->delete();
                
            \Log::info('OTP deletion result', [
                'email' => $request->email,
                'otp_deleted' => $otpDeleteResult,
            ]);

            // Commit the transaction
            DB::commit();
            
            \Log::info('Database transaction committed successfully', [
                'user_id' => $user->id,
                'email_verified_at_final' => $freshUser->email_verified_at,
            ]);

            // Log the user in
            Auth::login($user);
            
            \Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Use proper redirect instead of JSON response
            return redirect()->route('dashboard')
                ->with('success', 'Email verified successfully! Welcome to the system.');
                
        } catch (\Exception $e) {
            // Rollback on error
            DB::rollBack();
            
            \Log::error('Email verification failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Verification failed due to a system error. Please try again.',
                'errors' => ['system' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Clean up expired OTP tokens
     */
    private function cleanupExpiredTokens()
    {
        try {
            DB::table('email_verifications')
                ->where('expires_at', '<', now())
                ->delete();
        } catch (\Exception $e) {
            // Log error but don't stop the process
            \Log::error('Failed to cleanup expired OTP tokens: ' . $e->getMessage());
        }
    }

}
