<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Outlet;
use App\Models\Attendance;
use App\Models\AuditLog;
use Carbon\Carbon;

class MonitoringService
{
    /**
     * System health check levels.
     */
    const HEALTH_LEVELS = [
        'critical' => 0,
        'warning' => 50,
        'good' => 80,
        'excellent' => 95,
    ];

    /**
     * Get comprehensive system health report.
     */
    public static function getSystemHealth(): array
    {
        $cacheKey = 'system_health_comprehensive';
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                'timestamp' => now()->toISOString(),
                'overall_score' => self::calculateOverallScore(),
                'status' => self::getOverallStatus(),
                'checks' => [
                    'database' => self::checkDatabaseHealth(),
                    'cache' => self::checkCacheHealth(),
                    'storage' => self::checkStorageHealth(),
                    'memory' => self::checkMemoryUsage(),
                    'cpu' => self::checkCpuUsage(),
                    'queue' => self::checkQueueHealth(),
                    'scheduler' => self::checkSchedulerHealth(),
                    'security' => self::checkSecurityHealth(),
                    'performance' => self::checkPerformanceMetrics(),
                    'connectivity' => self::checkConnectivity(),
                ],
                'alerts' => self::getActiveAlerts(),
                'metrics' => self::getSystemMetrics(),
            ];
        });
    }

    /**
     * Calculate overall system health score.
     */
    private static function calculateOverallScore(): int
    {
        $scores = [
            self::checkDatabaseHealth()['score'],
            self::checkCacheHealth()['score'],
            self::checkStorageHealth()['score'],
            self::checkMemoryUsage()['score'],
            self::checkQueueHealth()['score'],
            self::checkSecurityHealth()['score'],
        ];

        return (int) round(array_sum($scores) / count($scores));
    }

    /**
     * Get overall system status.
     */
    private static function getOverallStatus(): string
    {
        $score = self::calculateOverallScore();

        if ($score >= self::HEALTH_LEVELS['excellent']) return 'excellent';
        if ($score >= self::HEALTH_LEVELS['good']) return 'good';
        if ($score >= self::HEALTH_LEVELS['warning']) return 'warning';
        return 'critical';
    }

    /**
     * Check database health.
     */
    private static function checkDatabaseHealth(): array
    {
        try {
            // Test database connection
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            if (!$pdo) {
                return [
                    'status' => 'critical',
                    'score' => 0,
                    'message' => 'Database connection failed',
                    'details' => [],
                ];
            }

            // Check table counts
            $userCount = User::count();
            $outletCount = Outlet::count();
            $attendanceCount = Attendance::count();
            $auditLogCount = AuditLog::count();

            // Check recent activity
            $recentAttendances = Attendance::whereDate('created_at', Carbon::today())->count();
            $recentLogins = AuditLog::forAction('login')
                ->whereDate('created_at', Carbon::today())
                ->count();

            // Calculate score based on various factors
            $score = 100;
            $issues = [];

            // Check if tables are accessible
            try {
                DB::table('users')->count();
                DB::table('outlets')->count();
                DB::table('attendances')->count();
                DB::table('audit_logs')->count();
            } catch (\Exception $e) {
                $score -= 30;
                $issues[] = 'Some tables are not accessible: ' . $e->getMessage();
            }

            // Check database size (simplified)
            $dbSize = self::getDatabaseSize();
            if ($dbSize > 1000) { // MB
                $score -= 10;
                $issues[] = 'Database size is large';
            }

            return [
                'status' => $score >= 70 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Database is healthy' : 'Database has issues',
                'details' => [
                    'connection' => 'connected',
                    'user_count' => $userCount,
                    'outlet_count' => $outletCount,
                    'attendance_count' => $attendanceCount,
                    'audit_log_count' => $auditLogCount,
                    'recent_attendances' => $recentAttendances,
                    'recent_logins' => $recentLogins,
                    'database_size_mb' => round($dbSize, 2),
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Database health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check cache health.
     */
    private static function checkCacheHealth(): array
    {
        try {
            $cacheDriver = config('cache.default');
            
            // Test cache operations
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            $score = 100;
            $issues = [];

            if ($retrieved !== $testValue) {
                $score -= 50;
                $issues[] = 'Cache read/write failed';
            }

            // Check cache configuration
            $cacheConfig = config('cache');
            if (!$cacheConfig) {
                $score -= 20;
                $issues[] = 'Cache configuration is missing';
            }

            return [
                'status' => $score >= 80 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Cache is healthy' : 'Cache has issues',
                'details' => [
                    'driver' => $cacheDriver,
                    'test_passed' => $retrieved === $testValue,
                    'configuration' => $cacheConfig,
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Cache health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check storage health.
     */
    private static function checkStorageHealth(): array
    {
        try {
            $score = 100;
            $issues = [];

            // Check storage directory permissions
            $storagePath = storage_path();
            $logsPath = storage_path('logs');
            $frameworkPath = storage_path('framework');

            if (!is_writable($storagePath)) {
                $score -= 30;
                $issues[] = 'Storage directory is not writable';
            }

            if (!is_writable($logsPath)) {
                $score -= 20;
                $issues[] = 'Logs directory is not writable';
            }

            if (!is_writable($frameworkPath)) {
                $score -= 20;
                $issues[] = 'Framework directory is not writable';
            }

            // Check disk usage
            $diskUsage = self::getDiskUsage();
            if ($diskUsage['percentage'] > 90) {
                $score -= 25;
                $issues[] = 'Disk usage is critical';
            } elseif ($diskUsage['percentage'] > 80) {
                $score -= 10;
                $issues[] = 'Disk usage is high';
            }

            return [
                'status' => $score >= 70 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Storage is healthy' : 'Storage has issues',
                'details' => [
                    'storage_path' => $storagePath,
                    'disk_usage' => $diskUsage,
                    'permissions' => [
                        'storage_writable' => is_writable($storagePath),
                        'logs_writable' => is_writable($logsPath),
                        'framework_writable' => is_writable($frameworkPath),
                    ],
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Storage health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Storage health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check memory usage.
     */
    private static function checkMemoryUsage(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            
            $usedMB = round($memoryUsage / 1024 / 1024, 2);
            $limitMB = $memoryLimit === '-1' ? 'unlimited' : round($this->parseBytes($memoryLimit) / 1024 / 1024, 2);
            
            $percentage = $memoryLimit === '-1' ? 0 : ($memoryUsage / $this->parseBytes($memoryLimit)) * 100;
            
            $score = 100;
            $issues = [];

            if ($percentage > 90) {
                $score -= 40;
                $issues[] = 'Memory usage is critical';
            } elseif ($percentage > 80) {
                $score -= 20;
                $issues[] = 'Memory usage is high';
            }

            return [
                'status' => $score >= 70 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Memory usage is normal' : 'Memory usage is high',
                'details' => [
                    'used_mb' => $usedMB,
                    'limit_mb' => $limitMB,
                    'percentage' => round($percentage, 2),
                    'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Memory health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Memory health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check CPU usage (simplified).
     */
    private static function checkCpuUsage(): array
    {
        try {
            // This is a simplified CPU check - in production, use proper monitoring tools
            $load = sys_getloadavg();
            
            $score = 100;
            $issues = [];

            if (is_array($load) && isset($load[0])) {
                $cpuLoad = $load[0];
                
                if ($cpuLoad > 2.0) {
                    $score -= 40;
                    $issues[] = 'CPU load is critical';
                } elseif ($cpuLoad > 1.5) {
                    $score -= 20;
                    $issues[] = 'CPU load is high';
                }

                return [
                    'status' => $score >= 70 ? 'healthy' : 'warning',
                    'score' => max(0, $score),
                    'message' => empty($issues) ? 'CPU load is normal' : 'CPU load is high',
                    'details' => [
                        'load_1min' => $cpuLoad,
                        'load_5min' => $load[1] ?? 'N/A',
                        'load_15min' => $load[2] ?? 'N/A',
                        'issues' => $issues,
                    ],
                ];
            }

            return [
                'status' => 'warning',
                'score' => 50,
                'message' => 'CPU load information not available',
                'details' => ['error' => 'Could not retrieve CPU load information'],
            ];

        } catch (\Exception $e) {
            Log::error('CPU health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'CPU health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check queue health.
     */
    private static function checkQueueHealth(): array
    {
        try {
            $score = 100;
            $issues = [];

            // Check queue configuration
            $queueConfig = config('queue.default');
            if (!$queueConfig) {
                $score -= 20;
                $issues[] = 'Queue configuration is missing';
            }

            // Check failed jobs (simplified)
            $failedJobs = Cache::get('failed_jobs_count', 0);
            if ($failedJobs > 10) {
                $score -= 30;
                $issues[] = 'High number of failed jobs';
            }

            return [
                'status' => $score >= 80 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Queue is healthy' : 'Queue has issues',
                'details' => [
                    'default_connection' => $queueConfig,
                    'failed_jobs_count' => $failedJobs,
                    'configuration' => config('queue'),
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Queue health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Queue health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check scheduler health.
     */
    private static function checkSchedulerHealth(): array
    {
        try {
            $score = 100;
            $issues = [];

            // Check if scheduler is configured
            $schedulerConfig = config('app.schedule');
            if (!$schedulerConfig) {
                $score -= 10;
                $issues[] = 'Scheduler is not configured';
            }

            return [
                'status' => $score >= 80 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Scheduler is healthy' : 'Scheduler has issues',
                'details' => [
                    'configured' => !empty($schedulerConfig),
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Scheduler health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Scheduler health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check security health.
     */
    private static function checkSecurityHealth(): array
    {
        try {
            $score = 100;
            $issues = [];

            // Check for recent high-risk activities
            $highRiskActivities = AuditLog::highRisk()
                ->recent(24) // Last 24 hours
                ->count();

            if ($highRiskActivities > 5) {
                $score -= 30;
                $issues[] = 'High number of security alerts';
            }

            // Check failed login attempts
            $failedLogins = AuditLog::forAction('login_failed')
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($failedLogins > 20) {
                $score -= 25;
                $issues[] = 'High number of failed login attempts';
            }

            // Check for suspicious IPs
            $suspiciousIps = Cache::get('suspicious_ips', []);
            if (count($suspiciousIps) > 10) {
                $score -= 20;
                $issues[] = 'Multiple suspicious IP addresses detected';
            }

            return [
                'status' => $score >= 70 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Security is healthy' : 'Security has concerns',
                'details' => [
                    'high_risk_activities_24h' => $highRiskActivities,
                    'failed_logins_today' => $failedLogins,
                    'suspicious_ips_count' => count($suspiciousIps),
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Security health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Security health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check performance metrics.
     */
    private static function checkPerformanceMetrics(): array
    {
        try {
            $score = 100;
            $issues = [];

            // Check average response time (simplified)
            $avgResponseTime = Cache::get('avg_response_time', 0);
            if ($avgResponseTime > 2000) { // 2 seconds
                $score -= 30;
                $issues[] = 'Average response time is high';
            }

            // Check error rate
            $errorRate = Cache::get('error_rate', 0);
            if ($errorRate > 5) { // 5%
                $score -= 25;
                $issues[] = 'Error rate is high';
            }

            return [
                'status' => $score >= 70 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Performance is good' : 'Performance needs attention',
                'details' => [
                    'avg_response_time_ms' => $avgResponseTime,
                    'error_rate_percent' => $errorRate,
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Performance health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Performance health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check connectivity.
     */
    private static function checkConnectivity(): array
    {
        try {
            $score = 100;
            $issues = [];

            // Check external connectivity (simplified)
            $testUrls = [
                'https://www.google.com',
                'https://api.github.com',
            ];

            foreach ($testUrls as $url) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 400) {
                    $score -= 10;
                    $issues[] = "Cannot connect to {$url}";
                }
            }

            return [
                'status' => $score >= 80 ? 'healthy' : 'warning',
                'score' => max(0, $score),
                'message' => empty($issues) ? 'Connectivity is good' : 'Connectivity issues detected',
                'details' => [
                    'external_reachable' => empty($issues),
                    'tested_urls' => $testUrls,
                    'issues' => $issues,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Connectivity health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'critical',
                'score' => 0,
                'message' => 'Connectivity health check failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Get active alerts.
     */
    private static function getActiveAlerts(): array
    {
        $alerts = [];

        // Check for critical issues
        $health = self::getSystemHealth();
        foreach ($health['checks'] as $component => $check) {
            if ($check['status'] === 'critical') {
                $alerts[] = [
                    'type' => 'critical',
                    'component' => $component,
                    'message' => $check['message'],
                    'timestamp' => now()->toISOString(),
                ];
            } elseif ($check['status'] === 'warning') {
                $alerts[] = [
                    'type' => 'warning',
                    'component' => $component,
                    'message' => $check['message'],
                    'timestamp' => now()->toISOString(),
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get system metrics.
     */
    private static function getSystemMetrics(): array
    {
        return [
            'uptime' => self::getUptime(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'timezone' => config('app.timezone'),
            'debug_mode' => config('app.debug'),
            'server_info' => $_SERVER ?? [],
        ];
    }

    /**
     * Get database size (simplified).
     */
    private static function getDatabaseSize(): float
    {
        try {
            // This is a simplified calculation
            // In production, use proper database size calculation
            return 0; // MB
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get disk usage.
     */
    private static function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $percentage = ($used / $total) * 100;

        return [
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'percentage' => round($percentage, 2),
        ];
    }

    /**
     * Get system uptime.
     */
    private static function getUptime(): string
    {
        // This is a simplified uptime calculation
        // In production, use proper uptime monitoring
        return 'Unknown';
    }

    /**
     * Parse bytes to human readable format.
     */
    private static function parseBytes($size): int
    {
        $unit = preg_replace('/[^bkmgtpe]/i', '', $size);
        $size = preg_replace('/[^0-9.]/', '', $size);
        
        return (int) round($size * pow(1024, stripos('bkmgtpe', substr($unit, 0, 1)) ?: 1));
    }

    /**
     * Log system event for monitoring.
     */
    public static function logSystemEvent(string $event, array $data = [], string $level = 'info'): void
    {
        $logData = array_merge([
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'level' => $level,
        ], $data);

        Log::{$level}('System event', $logData);
    }

    /**
     * Send alert notification (placeholder for integration).
     */
    public static function sendAlert(string $type, string $message, array $data = []): bool
    {
        // This would integrate with email, Slack, or other notification systems
        Log::warning('System Alert', [
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);

        return true;
    }
}
