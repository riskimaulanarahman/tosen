<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class PasswordPolicyService
{
    /**
     * Password complexity requirements.
     */
    const REQUIREMENTS = [
        'min_length' => 8,
        'max_length' => 128,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => true,
        'forbidden_patterns' => [
            'password',
            '123456',
            'qwerty',
            'admin',
            'user',
            'login',
            'welcome',
            'change',
        ],
        'forbidden_sequences' => [
            '12345678',
            'qwertyuiop',
            'abcdefghij',
            '11111111',
            '22222222',
            '33333333',
        ],
        'max_repeated_chars' => 2, // Maximum same character in sequence
        'min_entropy' => 50, // Minimum password entropy score
    ];

    /**
     * Password history settings.
     */
    const HISTORY_SETTINGS = [
        'prevent_reuse' => true,
        'history_count' => 5, // Check last 5 passwords
        'history_days' => 90, // Keep history for 90 days
    ];

    /**
     * Password expiration settings.
     */
    const EXPIRATION_SETTINGS = [
        'enabled' => true,
        'days' => 90, // Password expires every 90 days
        'warning_days' => 7, // Start warning 7 days before expiration
    ];

    /**
     * Account lockout settings.
     */
    const LOCKOUT_SETTINGS = [
        'enabled' => true,
        'max_attempts' => 5,
        'decay_minutes' => 15, // Reset attempts after 15 minutes
        'lockout_duration' => 30, // Lockout for 30 minutes
    ];

    /**
     * Validate password against security policies.
     */
    public static function validatePassword(string $password, $userId = null): array
    {
        $validation = [
            'is_valid' => true,
            'score' => 0,
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
        ];

        // Check minimum length
        if (strlen($password) < self::REQUIREMENTS['min_length']) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Password must be at least " . self::REQUIREMENTS['min_length'] . " characters long";
        }

        // Check maximum length
        if (strlen($password) > self::REQUIREMENTS['max_length']) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Password must not exceed " . self::REQUIREMENTS['max_length'] . " characters";
        }

        // Check for uppercase letters
        if (self::REQUIREMENTS['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Password must contain at least one uppercase letter";
        }

        // Check for lowercase letters
        if (self::REQUIREMENTS['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Password must contain at least one lowercase letter";
        }

        // Check for numbers
        if (self::REQUIREMENTS['require_number'] && !preg_match('/[0-9]/', $password)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Password must contain at least one number";
        }

        // Check for special characters
        if (self::REQUIREMENTS['require_special'] && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:"|,<.>\/?]/', $password)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Password must contain at least one special character";
        }

        // Check for forbidden patterns
        $passwordLower = strtolower($password);
        foreach (self::REQUIREMENTS['forbidden_patterns'] as $pattern) {
            if (strpos($passwordLower, $pattern) !== false) {
                $validation['warnings'][] = "Password contains common pattern: {$pattern}";
                $validation['score'] -= 10;
            }
        }

        // Check for forbidden sequences
        foreach (self::REQUIREMENTS['forbidden_sequences'] as $sequence) {
            if (strpos($passwordLower, $sequence) !== false) {
                $validation['warnings'][] = "Password contains predictable sequence";
                $validation['score'] -= 15;
            }
        }

        // Check for repeated characters
        if (self::hasExcessiveRepeatedChars($password)) {
            $validation['warnings'][] = "Password contains too many repeated characters";
            $validation['score'] -= 10;
        }

        // Check password history
        if ($userId && self::isPasswordReused($password, $userId)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Password has been used recently. Please choose a different password";
        }

        // Calculate password entropy
        $entropy = self::calculateEntropy($password);
        $validation['entropy'] = $entropy;
        
        if ($entropy < self::REQUIREMENTS['min_entropy']) {
            $validation['warnings'][] = "Password has low entropy (weak)";
            $validation['score'] -= 20;
        }

        // Calculate password strength score
        $validation['score'] = max(0, 100 + $validation['score'] + $entropy);
        $validation['strength'] = self::getStrengthLabel($validation['score']);

        // Add recommendations
        $validation['recommendations'] = self::getPasswordRecommendations($password, $validation);

        return $validation;
    }

    /**
     * Check if password has been compromised in data breaches.
     */
    public static function isPasswordCompromised(string $password): bool
    {
        $hash = sha1($password);
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        // In production, integrate with HaveIBeenPwned API or similar service
        // For now, we'll use a local cache of common compromised passwords
        $compromisedPasswords = Cache::remember('compromised_passwords', 86400, function () {
            return [
                '123456',
                'password',
                '123456789',
                '12345678',
                '12345',
                '1234567',
                '1234567890',
                'qwerty',
                'abc123',
                'password123',
                // Add more common compromised passwords
            ];
        });

        return in_array(strtolower($password), $compromisedPasswords);
    }

    /**
     * Check if password is in user's history.
     */
    private static function isPasswordReused(string $password, $userId): bool
    {
        if (!self::HISTORY_SETTINGS['prevent_reuse']) {
            return false;
        }

        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $history = self::getPasswordHistory($userId);
        
        foreach ($history as $oldPassword) {
            if (Hash::check($password, $oldPassword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store password in history.
     */
    public static function storePasswordHistory($userId, string $password): void
    {
        $history = self::getPasswordHistory($userId);
        
        $history[] = [
            'password_hash' => Hash::make($password),
            'created_at' => now()->toISOString(),
        ];

        // Keep only specified number of passwords
        $history = array_slice($history, -self::HISTORY_SETTINGS['history_count']);

        Cache::put("password_history_{$userId}", $history, now()->addDays(self::HISTORY_SETTINGS['history_days']));
    }

    /**
     * Get password history for user.
     */
    private static function getPasswordHistory($userId): array
    {
        return Cache::get("password_history_{$userId}", []);
    }

    /**
     * Check if password is expired.
     */
    public static function isPasswordExpired($user): array
    {
        if (!self::EXPIRATION_SETTINGS['enabled']) {
            return ['is_expired' => false, 'days_remaining' => null];
        }

        $lastPasswordChange = $user->password_changed_at;
        if (!$lastPasswordChange) {
            return ['is_expired' => true, 'days_remaining' => 0];
        }

        $daysSinceChange = $lastPasswordChange->diffInDays(now());
        $daysRemaining = self::EXPIRATION_SETTINGS['days'] - $daysSinceChange;

        return [
            'is_expired' => $daysRemaining <= 0,
            'days_remaining' => max(0, $daysRemaining),
            'warning_period' => $daysRemaining <= self::EXPIRATION_SETTINGS['warning_days'],
        ];
    }

    /**
     * Generate secure random password.
     */
    public static function generateSecurePassword(int $length = 12): string
    {
        $characters = '';
        
        // Ensure at least one of each required type
        $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // Uppercase
        $characters .= 'abcdefghijklmnopqrstuvwxyz'; // Lowercase
        $characters .= '0123456789'; // Numbers
        $characters .= '!@#$%^&*()_+-=[]{};:|,<.>?/'; // Special chars

        $password = '';
        $charCount = strlen($characters);

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $charCount - 1)];
        }

        // Ensure the generated password meets requirements
        $validation = self::validatePassword($password);
        if (!$validation['is_valid']) {
            // Regenerate if it doesn't meet requirements
            return self::generateSecurePassword($length);
        }

        return $password;
    }

    /**
     * Calculate password entropy.
     */
    private static function calculateEntropy(string $password): float
    {
        $charSet = 0;
        
        if (preg_match('/[a-z]/', $password)) $charSet += 26;
        if (preg_match('/[A-Z]/', $password)) $charSet += 26;
        if (preg_match('/[0-9]/', $password)) $charSet += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $charSet += 32; // Special chars

        $entropy = strlen($password) * log($charSet, 2);
        
        return round($entropy, 2);
    }

    /**
     * Get strength label based on score.
     */
    private static function getStrengthLabel(int $score): string
    {
        if ($score >= 80) return 'Very Strong';
        if ($score >= 60) return 'Strong';
        if ($score >= 40) return 'Medium';
        if ($score >= 20) return 'Weak';
        return 'Very Weak';
    }

    /**
     * Check for excessive repeated characters.
     */
    private static function hasExcessiveRepeatedChars(string $password): bool
    {
        $maxRepeated = self::REQUIREMENTS['max_repeated_chars'];
        $length = strlen($password);

        for ($i = 0; $i < $length - $maxRepeated; $i++) {
            $chunk = substr($password, $i, $maxRepeated + 1);
            if (count_chars(str_split($chunk)) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get password recommendations.
     */
    private static function getPasswordRecommendations(string $password, array $validation): array
    {
        $recommendations = [];

        if (strlen($password) < 12) {
            $recommendations[] = "Consider using a longer password (12+ characters)";
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:"|,<.>\/?]/', $password)) {
            $recommendations[] = "Add special characters to increase strength";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $recommendations[] = "Include numbers in your password";
        }

        // Check for dictionary words
        if (self::containsDictionaryWords($password)) {
            $recommendations[] = "Avoid using common dictionary words";
        }

        // Check for personal information
        if (auth()->user()) {
            $userInfo = auth()->user();
            $personalInfo = [
                strtolower($userInfo->name ?? ''),
                strtolower($userInfo->email ?? ''),
            ];

            foreach ($personalInfo as $info) {
                if ($info && strpos(strtolower($password), $info) !== false) {
                    $recommendations[] = "Avoid using personal information in password";
                    break;
                }
            }
        }

        if ($validation['score'] < 60) {
            $recommendations[] = "Consider using a password manager to generate a strong password";
        }

        return array_unique($recommendations);
    }

    /**
     * Check if password contains dictionary words.
     */
    private static function containsDictionaryWords(string $password): bool
    {
        // Simplified dictionary check
        $commonWords = [
            'password', 'admin', 'user', 'login', 'welcome', 'change',
            'qwerty', 'asdfgh', 'zxcvbn', 'letmein', 'access',
            'master', 'default', 'temp', 'test', 'demo', 'guest',
        ];

        $passwordLower = strtolower($password);
        foreach ($commonWords as $word) {
            if (strpos($passwordLower, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get account lockout status.
     */
    public static function getLockoutStatus($userId): array
    {
        if (!self::LOCKOUT_SETTINGS['enabled']) {
            return ['is_locked' => false, 'remaining_attempts' => null, 'lockout_time' => null];
        }

        $cacheKey = "login_attempts_{$userId}";
        $attempts = Cache::get($cacheKey, []);
        
        $recentAttempts = array_filter($attempts, function ($attempt) {
            return $attempt['timestamp'] > now()->subMinutes(self::LOCKOUT_SETTINGS['decay_minutes'])->timestamp;
        });

        if (count($recentAttempts) >= self::LOCKOUT_SETTINGS['max_attempts']) {
            $lastAttempt = end($recentAttempts);
            $lockoutEnd = $lastAttempt['timestamp'] + (self::LOCKOUT_SETTINGS['lockout_duration'] * 60);
            
            if (time() < $lockoutEnd) {
                return [
                    'is_locked' => true,
                    'remaining_attempts' => 0,
                    'lockout_time' => $lockoutEnd,
                    'lockout_duration' => self::LOCKOUT_SETTINGS['lockout_duration'],
                ];
            }
        }

        return [
            'is_locked' => false,
            'remaining_attempts' => max(0, self::LOCKOUT_SETTINGS['max_attempts'] - count($recentAttempts)),
            'lockout_time' => null,
        ];
    }

    /**
     * Record failed login attempt.
     */
    public static function recordFailedAttempt($userId): void
    {
        if (!self::LOCKOUT_SETTINGS['enabled']) {
            return;
        }

        $cacheKey = "login_attempts_{$userId}";
        $attempts = Cache::get($cacheKey, []);
        
        $attempts[] = [
            'timestamp' => time(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        Cache::put($cacheKey, $attempts, now()->addHours(1));
    }

    /**
     * Clear failed login attempts.
     */
    public static function clearFailedAttempts($userId): void
    {
        $cacheKey = "login_attempts_{$userId}";
        Cache::forget($cacheKey);
    }

    /**
     * Get password policy settings for UI.
     */
    public static function getPolicySettings(): array
    {
        return [
            'requirements' => self::REQUIREMENTS,
            'history' => self::HISTORY_SETTINGS,
            'expiration' => self::EXPIRATION_SETTINGS,
            'lockout' => self::LOCKOUT_SETTINGS,
        ];
    }

    /**
     * Validate password format for common issues.
     */
    public static function validatePasswordFormat(string $password): array
    {
        $issues = [];

        // Check for whitespace
        if (preg_match('/\s/', $password)) {
            $issues[] = 'Password contains whitespace characters';
        }

        // Check for control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $password)) {
            $issues[] = 'Password contains invalid control characters';
        }

        // Check for Unicode normalization issues
        if (strlen($password) !== strlen(utf8_decode($password))) {
            $issues[] = 'Password contains complex Unicode characters that may cause issues';
        }

        return $issues;
    }
}
