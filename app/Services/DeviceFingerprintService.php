<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as HttpRequest;

class DeviceFingerprintService
{
    /**
     * Generate a unique device fingerprint.
     */
    public static function generateFingerprint(HttpRequest $request = null): string
    {
        $request = $request ?: request();
        
        $components = [
            // HTTP headers that help identify the device
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
            'accept' => $request->header('Accept'),
            'connection' => $request->header('Connection'),
            'upgrade_insecure_requests' => $request->header('Upgrade-Insecure-Requests'),
            
            // Screen and browser info (if available via JavaScript)
            'screen_width' => $request->header('X-Screen-Width'),
            'screen_height' => $request->header('X-Screen-Height'),
            'color_depth' => $request->header('X-Color-Depth'),
            'timezone' => $request->header('X-Timezone'),
            
            // Canvas and WebGL fingerprints (if available)
            'canvas_fingerprint' => $request->header('X-Canvas-Fingerprint'),
            'webgl_fingerprint' => $request->header('X-WebGL-Fingerprint'),
            
            // Browser capabilities
            'cookies_enabled' => $request->header('X-Cookies-Enabled'),
            'do_not_track' => $request->header('X-Do-Not-Track'),
            'java_enabled' => $request->header('X-Java-Enabled'),
            
            // Network information
            'ip_address' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'real_ip' => $request->header('X-Real-IP'),
        ];

        // Clean and filter components
        $components = array_filter($components, function ($value) {
            return $value !== null && $value !== '';
        });

        // Create a stable hash
        $fingerprintString = json_encode($components, JSON_SORT_KEYS);
        $fingerprint = hash('sha256', $fingerprintString);

        // Store for comparison
        self::storeFingerprint($fingerprint, $components);

        return $fingerprint;
    }

    /**
     * Get device information from request.
     */
    public static function getDeviceInfo(HttpRequest $request = null): array
    {
        $request = $request ?: request();
        $userAgent = $request->userAgent();

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'browser' => self::parseBrowser($userAgent),
            'os' => self::parseOperatingSystem($userAgent),
            'device' => self::parseDevice($userAgent),
            'is_mobile' => self::isMobile($userAgent),
            'is_bot' => self::isBot($userAgent),
            'language' => $request->header('Accept-Language'),
            'timezone' => $request->header('X-Timezone', 'Unknown'),
        ];
    }

    /**
     * Check if device fingerprint is suspicious.
     */
    public static function isSuspiciousFingerprint(string $fingerprint, $userId = null): array
    {
        $analysis = [
            'is_suspicious' => false,
            'risk_score' => 0,
            'warnings' => [],
        ];

        // Check for known VPN/proxy IPs
        $ipAddress = request()->ip();
        if (self::isVpnOrProxy($ipAddress)) {
            $analysis['is_suspicious'] = true;
            $analysis['risk_score'] += 30;
            $analysis['warnings'][] = 'VPN or proxy detected';
        }

        // Check for known malicious user agents
        $userAgent = request()->userAgent();
        if (self::isSuspiciousUserAgent($userAgent)) {
            $analysis['is_suspicious'] = true;
            $analysis['risk_score'] += 40;
            $analysis['warnings'][] = 'Suspicious user agent detected';
        }

        // Check for bot activity
        if (self::isBot($userAgent)) {
            $analysis['is_suspicious'] = true;
            $analysis['risk_score'] += 50;
            $analysis['warnings'][] = 'Bot or automated tool detected';
        }

        // Check for multiple devices from same IP
        if (self::hasMultipleDevicesFromIp($ipAddress, $userId)) {
            $analysis['is_suspicious'] = true;
            $analysis['risk_score'] += 20;
            $analysis['warnings'][] = 'Multiple devices from same IP address';
        }

        // Check for device fingerprint changes
        if ($userId && self::hasDeviceFingerprintChanged($userId, $fingerprint)) {
            $analysis['is_suspicious'] = true;
            $analysis['risk_score'] += 25;
            $analysis['warnings'][] = 'Device fingerprint changed for user';
        }

        // Check for geolocation anomalies
        if ($userId && self::hasGeolocationAnomaly($userId, $ipAddress)) {
            $analysis['is_suspicious'] = true;
            $analysis['risk_score'] += 35;
            $analysis['warnings'][] = 'Unusual location detected';
        }

        return $analysis;
    }

    /**
     * Store device fingerprint for tracking.
     */
    private static function storeFingerprint(string $fingerprint, array $components): void
    {
        $cacheKey = "device_fingerprint_{$fingerprint}";
        
        Cache::put($cacheKey, [
            'fingerprint' => $fingerprint,
            'components' => $components,
            'first_seen' => now()->toISOString(),
            'last_seen' => now()->toISOString(),
            'access_count' => Cache::get($cacheKey . '_count', 0) + 1,
        ], now()->addDays(30));
    }

    /**
     * Check if IP is VPN or proxy.
     */
    private static function isVpnOrProxy(string $ip): bool
    {
        // Common VPN/proxy headers
        $proxyHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_VIA',
            'REMOTE_ADDR',
        ];

        foreach ($proxyHeaders as $header) {
            if (!empty($_SERVER[$header]) && $_SERVER[$header] !== $ip) {
                return true;
            }
        }

        // Check against known VPN/proxy ranges (simplified)
        $vpnRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            // Add more known VPN ranges as needed
        ];

        foreach ($vpnRanges as $range) {
            if (self::ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user agent is suspicious.
     */
    private static function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/java/i',
            '/perl/i',
            '/php/i',
            '/go/i',
            '/node/i',
            '/ruby/i',
            '/sqlmap/i',
            '/nmap/i',
            '/nikto/i',
            '/scanner/i',
            '/automated/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        // Check for empty or very short user agents
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return true;
        }

        return false;
    }

    /**
     * Parse browser from user agent.
     */
    private static function parseBrowser(string $userAgent): array
    {
        $browsers = [
            'Chrome' => '/Chrome\/([0-9.]+)/i',
            'Firefox' => '/Firefox\/([0-9.]+)/i',
            'Safari' => '/Safari\/([0-9.]+)/i',
            'Edge' => '/Edge\/([0-9.]+)/i',
            'Opera' => '/Opera\/([0-9.]+)/i',
            'IE' => '/MSIE ([0-9.]+)/i',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return [
                    'name' => $name,
                    'version' => $matches[1] ?? 'Unknown',
                ];
            }
        }

        return ['name' => 'Unknown', 'version' => 'Unknown'];
    }

    /**
     * Parse operating system from user agent.
     */
    private static function parseOperatingSystem(string $userAgent): string
    {
        $osPatterns = [
            'Windows' => '/Windows/i',
            'Mac' => '/Macintosh|Mac OS/i',
            'Linux' => '/Linux/i',
            'Android' => '/Android/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Ubuntu' => '/Ubuntu/i',
        ];

        foreach ($osPatterns as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    /**
     * Parse device type from user agent.
     */
    private static function parseDevice(string $userAgent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
            return 'Mobile';
        } elseif (preg_match('/Tablet|iPad/i', $userAgent)) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /**
     * Check if device is mobile.
     */
    private static function isMobile(string $userAgent): bool
    {
        return preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent);
    }

    /**
     * Check if user agent is a bot.
     */
    private static function isBot(string $userAgent): bool
    {
        $botPatterns = [
            '/googlebot/i',
            '/bingbot/i',
            '/slurp/i',
            '/duckduckbot/i',
            '/baiduspider/i',
            '/yandexbot/i',
            '/facebookexternalhit/i',
            '/twitterbot/i',
            '/rogerbot/i',
            '/linkedinbot/i',
            '/embedly/i',
            '/quora link preview/i',
            '/showyoubot/i',
            '/outbrain/i',
            '/pinterest/i',
            '/developers.google.com/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in range.
     */
    private static function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);
        $subnet_long = ip2long($subnet);
        $ip_long = ip2long($ip);
        $mask_long = -1 << (32 - $mask);

        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }

    /**
     * Check for multiple devices from same IP.
     */
    private static function hasMultipleDevicesFromIp(string $ip, $userId = null): bool
    {
        $cacheKey = "ip_devices_{$ip}";
        $devices = Cache::get($cacheKey, []);

        $currentFingerprint = self::generateFingerprint();
        
        if (!in_array($currentFingerprint, $devices)) {
            $devices[] = $currentFingerprint;
            Cache::put($cacheKey, $devices, now()->addHours(1));
            
            return count($devices) > 3; // Threshold for multiple devices
        }

        return false;
    }

    /**
     * Check if device fingerprint has changed for user.
     */
    private static function hasDeviceFingerprintChanged($userId, string $currentFingerprint): bool
    {
        $cacheKey = "user_device_{$userId}";
        $lastFingerprint = Cache::get($cacheKey);

        if ($lastFingerprint && $lastFingerprint !== $currentFingerprint) {
            return true;
        }

        Cache::put($cacheKey, $currentFingerprint, now()->addDays(7));
        return false;
    }

    /**
     * Check for geolocation anomalies.
     */
    private static function hasGeolocationAnomaly($userId, string $currentIp): bool
    {
        $cacheKey = "user_locations_{$userId}";
        $locations = Cache::get($cacheKey, []);

        // Get location from IP (simplified - in production, use a proper geo IP service)
        $currentLocation = [
            'ip' => $currentIp,
            'country' => self::getCountryFromIp($currentIp),
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($locations)) {
            $lastLocation = end($locations);
            
            // Check if location changed significantly (different country)
            if ($lastLocation['country'] !== $currentLocation['country']) {
                // Check if time difference is reasonable (not possible to travel that fast)
                $timeDiff = now()->diffInMinutes($lastLocation['timestamp']);
                if ($timeDiff < 60) { // Less than 1 hour for international travel
                    return true;
                }
            }
        }

        $locations[] = $currentLocation;
        Cache::put($cacheKey, array_slice($locations, -10), now()->addDays(7)); // Keep last 10 locations
        
        return false;
    }

    /**
     * Get country from IP (simplified implementation).
     */
    private static function getCountryFromIp(string $ip): string
    {
        // In production, use a proper geo IP service like MaxMind or IP-API
        // This is a simplified placeholder
        $ipRanges = [
            'ID' => ['103.0.0.0/8', '202.0.0.0/8'], // Indonesia
            'US' => ['8.0.0.0/8', '64.0.0.0/8'],    // United States
            'SG' => ['103.21.244.0/22'],                  // Singapore
            'MY' => ['175.136.0.0/16'],                  // Malaysia
        ];

        foreach ($ipRanges as $country => $ranges) {
            foreach ($ranges as $range) {
                if (self::ipInRange($ip, $range)) {
                    return $country;
                }
            }
        }

        return 'Unknown';
    }

    /**
     * Get device fingerprint history for user.
     */
    public static function getDeviceHistory($userId): array
    {
        return Cache::get("user_device_history_{$userId}", []);
    }

    /**
     * Log device access for security monitoring.
     */
    public static function logDeviceAccess($userId, string $fingerprint): void
    {
        $cacheKey = "user_device_history_{$userId}";
        $history = Cache::get($cacheKey, []);
        
        $access = [
            'fingerprint' => $fingerprint,
            'device_info' => self::getDeviceInfo(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ];

        $history[] = $access;
        
        // Keep only last 50 access records
        $history = array_slice($history, -50);
        
        Cache::put($cacheKey, $history, now()->addDays(30));
    }
}
