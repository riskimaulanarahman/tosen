<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $otp;
    public $password;
    public $outletName;
    public $outletAddress;
    public $ownerName;
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, string $otp, string $password = null, string $outletName = null, string $outletAddress = null, string $ownerName = null)
    {
        $this->email = $email;
        $this->otp = $otp;
        $this->password = $password;
        $this->outletName = $outletName;
        $this->outletAddress = $outletAddress;
        $this->ownerName = $ownerName;
    }

    /**
     * Execute job.
     */
    public function handle(): void
    {
        try {
            // INTENSIVE DEBUG LOGGING
            Log::info("=== SEND OTP EMAIL JOB STARTED ===");
            Log::info("Email: {$this->email}");
            Log::info("Password: " . ($this->password ? 'EXISTS: ' . substr($this->password, 0, 10) . '...' : 'NULL'));
            Log::info("Outlet Name: " . ($this->outletName ?? 'NULL'));
            Log::info("Outlet Address: " . ($this->outletAddress ?? 'NULL'));
            Log::info("Owner Name: " . ($this->ownerName ?? 'NULL'));
            Log::info("OTP: {$this->otp}");
            
            $emailBody = $this->buildEmailBody();
            
            // Determine email type for logging
            $isNewEmployee = $this->password && $this->outletName;
            $emailType = $isNewEmployee ? 'NEW_EMPLOYEE' : 'OTP_ONLY';
            
            Log::info("=== EMAIL TYPE DETERMINED: {$emailType} ===");
            Log::info("Password AND OutletName condition: " . (($this->password && $this->outletName) ? 'TRUE' : 'FALSE'));
            
            // Set subject based on email type
            $subject = $isNewEmployee 
                ? 'Welcome to Absensi System - Account Verification' 
                : 'Email Verification Code';
            
            Log::info("=== SENDING EMAIL ===");
            Log::info("Subject: {$subject}");
            Log::info("Email Body Length: " . strlen($emailBody) . " characters");
            
            Mail::html($emailBody, function ($message) use ($subject) {
                $message->to($this->email)
                    ->subject($subject);
            });

            Log::info("=== EMAIL SENT SUCCESSFULLY ===");
            Log::info("{$emailType} email sent successfully to {$this->email}");
        } catch (\Exception $e) {
            Log::error("=== EMAIL FAILED ===");
            Log::error("Failed to send email to {$this->email}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            // Re-queue job if it fails
            $this->release(60);
        }
    }

    /**
     * Build the email body template
     */
    private function buildEmailBody(): string
    {
        // Determine if this is a new employee setup or just OTP verification
        $isNewEmployee = $this->password && $this->outletName;

        if ($isNewEmployee) {
            return $this->buildNewEmployeeEmail();
        } else {
            return $this->buildOtpOnlyEmail();
        }
    }

    /**
     * Build email for new employee setup
     */
    private function buildNewEmployeeEmail(): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8f9fa; padding: 20px;'>
            <div style='background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <!-- Header -->
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2563eb; margin: 0; font-size: 28px;'>🏢 Welcome to Absensi System</h1>
                    <p style='color: #6b7280; margin: 10px 0 0 0; font-size: 16px;'>Your account has been created successfully</p>
                </div>

                <!-- Welcome Message -->
                <div style='background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 15px; margin-bottom: 25px;'>
                    <p style='margin: 0; color: #1e40af; font-weight: 600;'>Hello! 👋</p>
                    <p style='margin: 8px 0 0 0; color: #374151;'>{$this->ownerName} has added you as an employee in the Absensi System. Please complete your account setup below.</p>
                </div>

                <!-- Account Information -->
                <div style='background-color: #f3f4f6; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
                    <h3 style='color: #111827; margin: 0 0 15px 0; font-size: 18px;'>📋 Your Account Information</h3>
                    
                    <div style='margin-bottom: 15px;'>
                        <strong style='color: #374151;'>Email:</strong>
                        <span style='color: #111827; margin-left: 8px;'>{$this->email}</span>
                    </div>
                    
                    <div style='margin-bottom: 15px;'>
                        <strong style='color: #374151;'>Temporary Password:</strong>
                        <span style='color: #111827; margin-left: 8px; font-family: monospace; background-color: #e5e7eb; padding: 4px 8px; border-radius: 4px;'>{$this->password}</span>
                    </div>

                    <div style='margin-bottom: 15px;'>
                        <strong style='color: #374151;'>Assigned Outlet:</strong>
                        <div style='margin-top: 5px; padding: 10px; background-color: #ffffff; border-radius: 4px; border: 1px solid #d1d5db;'>
                            <div style='color: #111827; font-weight: 600;'>📍 {$this->outletName}</div>
                            <div style='color: #6b7280; font-size: 14px; margin-top: 3px;'>{$this->outletAddress}</div>
                        </div>
                    </div>
                </div>

                <!-- OTP Section -->
                <div style='background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 25px; text-align: center;'>
                    <h3 style='color: #92400e; margin: 0 0 10px 0; font-size: 18px;'>🔐 Email Verification Code</h3>
                    <div style='background-color: #ffffff; border: 2px dashed #f59e0b; border-radius: 8px; padding: 15px; margin: 10px 0;'>
                        <div style='font-size: 32px; font-weight: bold; color: #92400e; letter-spacing: 8px; font-family: monospace;'>{$this->otp}</div>
                    </div>
                    <p style='color: #92400e; margin: 10px 0 0 0; font-size: 14px;'>This code will expire in <strong>10 minutes</strong></p>
                </div>

                <!-- Next Steps -->
                <div style='background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; margin-bottom: 25px;'>
                    <h3 style='color: #065f46; margin: 0 0 10px 0; font-size: 16px;'>🚀 Next Steps:</h3>
                    <ol style='margin: 0; padding-left: 20px; color: #374151;'>
                        <li style='margin-bottom: 8px;'>Login with your email and the temporary password above</li>
                        <li style='margin-bottom: 8px;'>Enter the OTP code to verify your email</li>
                        <li style='margin-bottom: 8px;'>Set your permanent password</li>
                        <li>Start using the Absensi System!</li>
                    </ol>
                </div>

                <!-- Verification Link -->
                <div style='text-align: center; margin-bottom: 20px;'>
                    <a href='" . route('verification.notice', ['email' => $this->email]) . "' style='display: inline-block; background-color: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;'>🔐 Verify Email & Set Password</a>
                </div>

                <!-- Login Button -->
                <div style='text-align: center; margin-bottom: 20px;'>
                    <a href='" . url('/login') . "' style='display: inline-block; background-color: #6b7280; color: white; padding: 8px 20px; text-decoration: none; border-radius: 6px; font-size: 14px;'>Or Login First</a>
                </div>

                <!-- Security Notice -->
                <div style='background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin-bottom: 20px;'>
                    <p style='margin: 0; color: #991b1b; font-size: 14px;'>
                        <strong>🔒 Security Notice:</strong> Never share your password or OTP code with anyone. 
                        Our team will never ask for these credentials via email or phone.
                    </p>
                </div>

                <!-- Footer -->
                <div style='text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; margin: 0; font-size: 14px;'>Best regards,<br>Absensi System Team</p>
                    <p style='color: #9ca3af; margin: 10px 0 0 0; font-size: 12px;'>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </div>";
    }

    /**
     * Build email for OTP verification only
     */
    private function buildOtpOnlyEmail(): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8f9fa; padding: 20px;'>
            <div style='background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <!-- Header -->
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2563eb; margin: 0; font-size: 28px;'>🔐 Email Verification</h1>
                    <p style='color: #6b7280; margin: 10px 0 0 0; font-size: 16px;'>Complete your account verification</p>
                </div>

                <!-- OTP Section -->
                <div style='background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 25px; text-align: center;'>
                    <h3 style='color: #92400e; margin: 0 0 10px 0; font-size: 18px;'>Your Verification Code</h3>
                    <div style='background-color: #ffffff; border: 2px dashed #f59e0b; border-radius: 8px; padding: 15px; margin: 10px 0;'>
                        <div style='font-size: 32px; font-weight: bold; color: #92400e; letter-spacing: 8px; font-family: monospace;'>{$this->otp}</div>
                    </div>
                    <p style='color: #92400e; margin: 10px 0 0 0; font-size: 14px;'>This code was sent at " . now()->format('H:i') . " and will expire in <strong>10 minutes</strong></p>
                </div>

                <!-- Instructions -->
                <div style='background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 15px; margin-bottom: 25px;'>
                    <h3 style='color: #1e40af; margin: 0 0 10px 0; font-size: 16px;'>📝 Instructions:</h3>
                    <ol style='margin: 0; padding-left: 20px; color: #374151;'>
                        <li style='margin-bottom: 8px;'>Return to the verification page</li>
                        <li style='margin-bottom: 8px;'>Enter this 6-digit code</li>
                        <li>Set your permanent password</li>
                    </ol>
                </div>

                <!-- Security Notice -->
                <div style='background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin-bottom: 20px;'>
                    <p style='margin: 0; color: #991b1b; font-size: 14px;'>
                        <strong>🔒 Security Notice:</strong> Never share this OTP code with anyone.
                    </p>
                </div>

                <!-- Footer -->
                <div style='text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #6b7280; margin: 0; font-size: 14px;'>Best regards,<br>Absensi System Team</p>
                </div>
            </div>
        </div>";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendOtpEmail job failed for {$this->email}: " . $exception->getMessage());
    }
}
