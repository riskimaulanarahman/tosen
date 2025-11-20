<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Jobs\SendOtpEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'outlet_id',
        'email_verified_at',
        'base_salary',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'base_salary' => 'decimal:2',
        ];
    }

    /**
     * Get the outlet that belongs to the user (for employees).
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    /**
     * Get the outlets owned by the user (for owners).
     */
    public function ownedOutlets()
    {
        return $this->hasMany(Outlet::class, 'owner_id');
    }

    /**
     * Get the attendances for the user.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Check if user is an owner.
     */
    public function isOwner()
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification(): ?string
    {
        return $this->email;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        // Generate OTP for verification
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing OTP for this email
        \DB::table('email_verifications')->where('email', $this->email)->delete();
        
        // Store OTP in email_verifications table
        \DB::table('email_verifications')->insert([
            'email' => $this->email,
            'token' => $otp,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Use our custom SendOtpEmail job for consistent template
        SendOtpEmail::dispatch(
            $this->email,
            $otp,
            null, // No password for email verification notification
            $this->outlet ? $this->outlet->name : 'Your Outlet',
            $this->outlet ? $this->outlet->address : 'Your Address',
            $this->isOwner() ? $this->name : 'System Admin'
        );
    }
}
