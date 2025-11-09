<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Outlet;
use App\Models\Attendance;
use App\Models\AuditLog;
use Carbon\Carbon;

class CacheService
{
    /**
     * Cache TTL constants in seconds.
     */
    const TTL_SHORT = 300;      // 5 minutes
    const TTL_MEDIUM = 1800;    // 30 minutes
    const TTL_LONG = 3600;      // 1 hour
    const TTL_DAILY = 86400;    // 24 hours

    /**
     * Get user outlet assignment with caching.
     */
    public static function getUserOutlet($userId)
    {
        $cacheKey = "user_outlet_{$userId}";
        
        return Cache::remember($cacheKey, self::TTL_MEDIUM, function () use ($userId) {
            return User::find($userId)?->outlet;
        });
    }

    /**
     * Get outlet details with caching.
     */
    public static function getOutletDetails($outletId)
    {
        $cacheKey = "outlet_details_{$outletId}";
        
        return Cache::remember($cacheKey, self::TTL_LONG, function () use ($outletId) {
            return Outlet::with(['employees' => function ($query) {
                $query->select('id', 'name', 'email', 'outlet_id');
            }])->find($outletId);
        });
    }

    /**
     * Get today's attendance statistics with caching.
     */
    public static function getTodayAttendanceStats($outletId = null)
    {
        $cacheKey = $outletId 
            ? "attendance_stats_today_outlet_{$outletId}" 
            : "attendance_stats_today_global";
        
        return Cache::remember($cacheKey, self::TTL_SHORT, function () use ($outletId) {
            $query = Attendance::whereDate('created_at', Carbon::today());
            
            if ($outletId) {
                $query->where('outlet_id', $outletId);
            }
            
            return [
                'total_checkins' => $query->whereNotNull('check_in_time')->count(),
                'total_checkouts' => $query->whereNotNull('check_out_time')->count(),
                'active_attendances' => $query->whereNull('check_out_time')->count(),
                'unique_users' => $query->distinct('user_id')->count('user_id'),
            ];
        });
    }

    /**
     * Get user attendance history with caching.
     */
    public static function getUserAttendanceHistory($userId, int $limit = 10)
    {
        $cacheKey = "user_attendance_history_{$userId}_{$limit}";
        
        return Cache::remember($cacheKey, self::TTL_SHORT, function () use ($userId, $limit) {
            return Attendance::with('outlet')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get dashboard data with caching.
     */
    public static function getDashboardData($userId)
    {
        $cacheKey = "dashboard_data_{$userId}";
        
        return Cache::remember($cacheKey, self::TTL_SHORT, function () use ($userId) {
            $user = User::find($userId);
            
            // Get user's outlet
            $outlet = $user?->outlet;
            
            // Get today's attendance
            $todayAttendance = Attendance::where('user_id', $userId)
                ->whereDate('created_at', Carbon::today())
                ->with('outlet')
                ->orderBy('created_at', 'desc')
                ->first();
            
            // Get recent attendances
            $recentAttendances = Attendance::where('user_id', $userId)
                ->with('outlet')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
            
            // Get weekly stats
            $weeklyStats = self::getWeeklyStats($userId);
            
            return [
                'user' => $user,
                'outlet' => $outlet,
                'today_attendance' => $todayAttendance,
                'recent_attendances' => $recentAttendances,
                'weekly_stats' => $weeklyStats,
            ];
        });
    }

    /**
     * Get weekly attendance statistics.
     */
    public static function getWeeklyStats($userId)
    {
        $cacheKey = "weekly_stats_{$userId}";
        
        return Cache::remember($cacheKey, self::TTL_MEDIUM, function () use ($userId) {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
            
            return Attendance::where('user_id', $userId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->selectRaw('
                    COUNT(*) as total_attendances,
                    COUNT(CASE WHEN check_in_time IS NOT NULL THEN 1 END) as total_checkins,
                    COUNT(CASE WHEN check_out_time IS NOT NULL THEN 1 END) as total_checkouts,
                    AVG(TIMESTAMPDIFF(MINUTE, check_in_time, COALESCE(check_out_time, NOW()))) as avg_duration_minutes
                ')
                ->first();
        });
    }

    /**
     * Get system health metrics with caching.
     */
    public static function getSystemHealth()
    {
        $cacheKey = 'system_health_metrics';
        
        return Cache::remember($cacheKey, self::TTL_SHORT, function () {
            return [
                'database' => [
                    'connection_status' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
                    'total_users' => User::count(),
                    'total_outlets' => Outlet::count(),
                    'total_attendances_today' => Attendance::whereDate('created_at', Carbon::today())->count(),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'status' => 'operational',
                ],
                'security' => [
                    'recent_high_risk_activities' => AuditLog::highRisk()
                        ->recent(24) // Last 24 hours
                        ->count(),
                    'failed_login_attempts_today' => AuditLog::forAction('login_failed')
                        ->whereDate('created_at', Carbon::today())
                        ->count(),
                ],
                'timestamp' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get employee list for owner with caching.
     */
    public static function getOwnerEmployees($ownerId)
    {
        $cacheKey = "owner_employees_{$ownerId}";
        
        return Cache::remember($cacheKey, self::TTL_MEDIUM, function () use ($ownerId) {
            return User::where('owner_id', $ownerId)
                ->with(['outlet:id,name,latitude,longitude,radius'])
                ->select('id', 'name', 'email', 'role', 'outlet_id', 'email_verified_at', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Clear user-specific cache.
     */
    public static function clearUserCache($userId): void
    {
        $patterns = [
            "user_outlet_{$userId}",
            "user_attendance_history_{$userId}",
            "dashboard_data_{$userId}",
            "weekly_stats_{$userId}",
        ];
        
        foreach ($patterns as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear outlet-specific cache.
     */
    public static function clearOutletCache($outletId): void
    {
        $patterns = [
            "outlet_details_{$outletId}",
            "attendance_stats_today_outlet_{$outletId}",
        ];
        
        foreach ($patterns as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear owner-specific cache.
     */
    public static function clearOwnerCache($ownerId): void
    {
        Cache::forget("owner_employees_{$ownerId}");
    }

    /**
     * Clear system health cache.
     */
    public static function clearSystemHealthCache(): void
    {
        Cache::forget('system_health_metrics');
    }

    /**
     * Clear all attendance-related cache.
     */
    public static function clearAttendanceCache(): void
    {
        // This would be called after attendance operations
        $keys = [
            'attendance_stats_today_global',
            'system_health_metrics',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Warm up cache for frequently accessed data.
     */
    public static function warmUpCache($userId = null): void
    {
        if ($userId) {
            // Warm up user-specific cache
            self::getUserOutlet($userId);
            self::getUserAttendanceHistory($userId);
            self::getWeeklyStats($userId);
        }
        
        // Warm up system cache
        self::getSystemHealth();
    }

    /**
     * Get cache statistics for monitoring.
     */
    public static function getCacheStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'stores' => config('cache.stores'),
            'supported_drivers' => ['file', 'redis', 'memcached', 'database'],
            'recommendation' => self::getCacheRecommendation(),
        ];
    }

    /**
     * Get cache recommendation based on current setup.
     */
    private static function getCacheRecommendation(): string
    {
        $currentDriver = config('cache.default');
        
        return match($currentDriver) {
            'file' => 'Consider using Redis or Memcached for better performance in production',
            'redis' => 'Redis is optimal for production use',
            'memcached' => 'Memcached is optimal for production use',
            'database' => 'Consider using Redis or Memcached for better performance',
            default => 'Configure a dedicated cache driver for production',
        };
    }

    /**
     * Store temporary data with automatic cleanup.
     */
    public static function storeTempData($key, $data, int $ttl = self::TTL_SHORT): void
    {
        Cache::put("temp_{$key}", $data, $ttl);
    }

    /**
     * Get temporary data.
     */
    public static function getTempData($key, $default = null)
    {
        return Cache::get("temp_{$key}", $default);
    }

    /**
     * Clear temporary data.
     */
    public static function clearTempData($key): void
    {
        Cache::forget("temp_{$key}");
    }
}
