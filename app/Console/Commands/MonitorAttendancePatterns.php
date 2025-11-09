<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Outlet;
use App\Services\AttendancePatternService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonitorAttendancePatterns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:monitor-patterns 
                            {--days=30 : Analyze patterns for the last N days}
                            {--user= : Analyze specific user ID}
                            {--outlet= : Analyze specific outlet ID}
                            {--export : Export results to file}
                            {--threshold=3 : Risk threshold for pattern alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor attendance patterns and detect anomalies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $userId = $this->option('user');
        $outletId = $this->option('outlet');
        $export = $this->option('export');
        $threshold = $this->option('threshold');

        $this->info("Attendance Pattern Monitor");
        $this->info("=========================");
        $this->info("Analyzing attendance patterns for the last {$days} days");

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        // Get attendance data
        $query = Attendance::with(['user', 'outlet'])
            ->whereBetween('check_in_time', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
            $this->info("Filtering by User ID: {$userId}");
        }

        if ($outletId) {
            $query->where('outlet_id', $outletId);
            $this->info("Filtering by Outlet ID: {$outletId}");
        }

        $attendances = $query->orderBy('check_in_time', 'desc')->get();

        if ($attendances->isEmpty()) {
            $this->warn("No attendance records found for the specified criteria.");
            return 0;
        }

        $this->info("Found {$attendances->count()} attendance records");

        // Analyze patterns
        $this->analyzeLateCheckins($attendances, $threshold);
        $this->analyzeEarlyCheckouts($attendances, $threshold);
        $this->analyzeMissingSelfies($attendances);
        $this->analyzeGeofenceIssues($attendances);
        $this->analyzeWorkDurationPatterns($attendances);

        // Generate summary
        $this->generateSummary($attendances, $startDate, $endDate);

        // Export if requested
        if ($export) {
            $this->exportResults($attendances, $startDate, $endDate);
        }

        return 0;
    }

    /**
     * Analyze late check-in patterns
     */
    private function analyzeLateCheckins($attendances, $threshold)
    {
        $this->info("\n🕐 Late Check-in Analysis");
        $this->info("-------------------------");

        $lateAttendances = $attendances->filter(function ($attendance) {
            return $attendance->late_minutes > 0;
        });

        $totalLate = $lateAttendances->count();
        $totalAttendances = $attendances->count();
        $latePercentage = $totalAttendances > 0 ? ($totalLate / $totalAttendances) * 100 : 0;

        $this->info("Total late check-ins: {$totalLate} (" . number_format($latePercentage, 1) . "%)");

        if ($totalLate > 0) {
            $avgLateMinutes = $lateAttendances->avg('late_minutes');
            $maxLateMinutes = $lateAttendances->max('late_minutes');
            
            $this->info("Average late time: " . round($avgLateMinutes, 1) . " minutes");
            $this->info("Maximum late time: {$maxLateMinutes} minutes");

            // Group by user
            $userLateness = $lateAttendances->groupBy('user_id')->map(function ($userAttendances) {
                return [
                    'user_name' => $userAttendances->first()->user->name,
                    'count' => $userAttendances->count(),
                    'avg_late' => round($userAttendances->avg('late_minutes'), 1),
                    'max_late' => $userAttendances->max('late_minutes'),
                ];
            })->sortByDesc('count');

            if ($userLateness->count() > 0) {
                $this->info("\nTop users with most late check-ins:");
                foreach ($userLateness->take(5) as $userId => $data) {
                    $riskLevel = $data['count'] >= $threshold ? '🔴 HIGH' : '🟡 MEDIUM';
                    $this->line("  {$riskLevel} {$data['user_name']}: {$data['count']} times, avg {$data['avg_late']} min late");
                }
            }
        }
    }

    /**
     * Analyze early check-out patterns
     */
    private function analyzeEarlyCheckouts($attendances, $threshold)
    {
        $this->info("\n🕕 Early Check-out Analysis");
        $this->info("---------------------------");

        $earlyCheckouts = $attendances->filter(function ($attendance) {
            return $attendance->early_checkout_minutes > 0;
        });

        $totalEarly = $earlyCheckouts->count();
        $totalWithCheckout = $attendances->whereNotNull('check_out_time')->count();
        $earlyPercentage = $totalWithCheckout > 0 ? ($totalEarly / $totalWithCheckout) * 100 : 0;

        $this->info("Total early check-outs: {$totalEarly} (" . number_format($earlyPercentage, 1) . "% of completed days)");

        if ($totalEarly > 0) {
            $avgEarlyMinutes = $earlyCheckouts->avg('early_checkout_minutes');
            $maxEarlyMinutes = $earlyCheckouts->max('early_checkout_minutes');
            
            $this->info("Average early checkout: " . round($avgEarlyMinutes, 1) . " minutes");
            $this->info("Maximum early checkout: {$maxEarlyMinutes} minutes");

            // Group by user
            $userEarly = $earlyCheckouts->groupBy('user_id')->map(function ($userAttendances) {
                return [
                    'user_name' => $userAttendances->first()->user->name,
                    'count' => $userAttendances->count(),
                    'avg_early' => round($userAttendances->avg('early_checkout_minutes'), 1),
                    'max_early' => $userAttendances->max('early_checkout_minutes'),
                ];
            })->sortByDesc('count');

            if ($userEarly->count() > 0) {
                $this->info("\nTop users with most early check-outs:");
                foreach ($userEarly->take(5) as $userId => $data) {
                    $riskLevel = $data['count'] >= $threshold ? '🔴 HIGH' : '🟡 MEDIUM';
                    $this->line("  {$riskLevel} {$data['user_name']}: {$data['count']} times, avg {$data['avg_early']} min early");
                }
            }
        }
    }

    /**
     * Analyze missing selfies
     */
    private function analyzeMissingSelfies($attendances)
    {
        $this->info("\n📸 Selfie Analysis");
        $this->info("------------------");

        $missingCheckinSelfies = $attendances->filter(function ($attendance) {
            return !$attendance->check_in_selfie_path;
        })->count();

        $missingCheckoutSelfies = $attendances->whereNotNull('check_out_time')
            ->filter(function ($attendance) {
                return !$attendance->check_out_selfie_path;
            })->count();

        $this->info("Missing check-in selfies: {$missingCheckinSelfies}");
        $this->info("Missing check-out selfies: {$missingCheckoutSelfies}");

        $totalWithCheckout = $attendances->whereNotNull('check_out_time')->count();
        
        if ($missingCheckinSelfies > 0) {
            $percentage = ($missingCheckinSelfies / $attendances->count()) * 100;
            $this->warn("  ⚠️  " . number_format($percentage, 1) . "% of check-ins missing selfies");
        }

        if ($missingCheckoutSelfies > 0 && $totalWithCheckout > 0) {
            $percentage = ($missingCheckoutSelfies / $totalWithCheckout) * 100;
            $this->warn("  ⚠️  " . number_format($percentage, 1) . "% of check-outs missing selfies");
        }

        if ($missingCheckinSelfies === 0 && $missingCheckoutSelfies === 0) {
            $this->info("  ✅ All attendance records have selfies");
        }
    }

    /**
     * Analyze geofence issues
     */
    private function analyzeGeofenceIssues($attendances)
    {
        $this->info("\n📍 Geofence Analysis");
        $this->info("---------------------");

        $issues = 0;
        $byOutlet = [];

        foreach ($attendances as $attendance) {
            if ($attendance->outlet) {
                $distance = $attendance->outlet->getDistanceFrom($attendance->check_in_latitude, $attendance->check_in_longitude);
                if ($distance > ($attendance->outlet->radius ?? 50)) {
                    $issues++;
                    $outletName = $attendance->outlet->name;
                    
                    if (!isset($byOutlet[$outletName])) {
                        $byOutlet[$outletName] = [
                            'count' => 0,
                            'max_distance' => 0,
                            'avg_distance' => 0,
                            'distances' => []
                        ];
                    }
                    
                    $byOutlet[$outletName]['count']++;
                    $byOutlet[$outletName]['distances'][] = $distance;
                    $byOutlet[$outletName]['max_distance'] = max($byOutlet[$outletName]['max_distance'], $distance);
                }
            }
        }

        $this->info("Total geofence violations: {$issues}");

        if ($issues > 0) {
            foreach ($byOutlet as $outletName => $data) {
                $avgDistance = array_sum($data['distances']) / count($data['distances']);
                $this->warn("  ⚠️  {$outletName}: {$data['count']} violations");
                $this->line("     Average distance: " . round($avgDistance) . "m");
                $this->line("     Maximum distance: " . round($data['max_distance']) . "m");
            }
        } else {
            $this->info("  ✅ No geofence violations detected");
        }
    }

    /**
     * Analyze work duration patterns
     */
    private function analyzeWorkDurationPatterns($attendances)
    {
        $this->info("\n⏱️  Work Duration Analysis");
        $this->info("-------------------------");

        $completedDays = $attendances->whereNotNull('check_out_time');
        
        if ($completedDays->isEmpty()) {
            $this->info("No completed work days to analyze");
            return;
        }

        $durations = $completedDays->map(function ($attendance) {
            return $attendance->work_duration_minutes;
        })->filter();

        if ($durations->isEmpty()) {
            $this->info("No valid duration data available");
            return;
        }

        $avgDuration = $durations->avg();
        $minDuration = $durations->min();
        $maxDuration = $durations->max();
        
        $this->info("Average work duration: " . $this->formatDuration($avgDuration));
        $this->info("Minimum work duration: " . $this->formatDuration($minDuration));
        $this->info("Maximum work duration: " . $this->formatDuration($maxDuration));

        // Find unusually short work days
        $shortDays = $completedDays->filter(function ($attendance) {
            return $attendance->work_duration_minutes < 240; // Less than 4 hours
        });

        if ($shortDays->count() > 0) {
            $this->warn("\n⚠️  Unusually short work days (< 4 hours): {$shortDays->count()}");
            
            $shortDays->take(3)->each(function ($attendance) {
                $this->line("  - {$attendance->user->name}: " . $this->formatDuration($attendance->work_duration_minutes) . 
                    " on " . $attendance->check_in_time->format('Y-m-d'));
            });
        }

        // Find overtime patterns
        $overtimeDays = $completedDays->filter(function ($attendance) {
            return $attendance->overtime_minutes > 0;
        });

        if ($overtimeDays->count() > 0) {
            $this->info("\n💰 Overtime Analysis");
            $this->info("  Overtime days: {$overtimeDays->count()}");
            
            $avgOvertime = $overtimeDays->avg('overtime_minutes');
            $this->info("  Average overtime: " . $this->formatDuration($avgOvertime));
            
            $totalOvertime = $overtimeDays->sum('overtime_minutes');
            $this->info("  Total overtime: " . $this->formatDuration($totalOvertime));
        }
    }

    /**
     * Generate summary report
     */
    private function generateSummary($attendances, $startDate, $endDate)
    {
        $this->info("\n📊 Summary Report");
        $this->info("==================");
        
        $totalDays = $endDate->diffInDays($startDate);
        $uniqueUsers = $attendances->pluck('user_id')->unique()->count();
        $uniqueOutlets = $attendances->pluck('outlet_id')->unique()->count();
        
        $this->info("Analysis period: {$totalDays} days ({$startDate->toDateString()} to {$endDate->toDateString()})");
        $this->info("Total attendance records: {$attendances->count()}");
        $this->info("Unique employees: {$uniqueUsers}");
        $this->info("Unique outlets: {$uniqueOutlets}");

        // Calculate completion rate
        $completedDays = $attendances->whereNotNull('check_out_time')->count();
        $completionRate = $attendances->count() > 0 ? ($completedDays / $attendances->count()) * 100 : 0;
        $this->info("Day completion rate: " . number_format($completionRate, 1) . "%");

        // Calculate attendance score average
        $avgScore = $attendances->whereNotNull('attendance_score')->avg('attendance_score');
        if ($avgScore) {
            $this->info("Average attendance score: " . round($avgScore, 1));
        }
    }

    /**
     * Export results to file
     */
    private function exportResults($attendances, $startDate, $endDate)
    {
        $filename = "attendance_patterns_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.csv";
        $filepath = storage_path("app/public/{$filename}");

        $handle = fopen($filepath, 'w');
        
        // Header
        fputcsv($handle, [
            'Date',
            'User Name',
            'Outlet',
            'Check In Time',
            'Check Out Time',
            'Work Duration (minutes)',
            'Late Minutes',
            'Early Checkout Minutes',
            'Overtime Minutes',
            'Attendance Score',
            'Status',
            'Has Checkin Selfie',
            'Has Checkout Selfie',
            'Distance from Outlet'
        ]);

        // Data
        foreach ($attendances as $attendance) {
            fputcsv($handle, [
                $attendance->check_in_time->format('Y-m-d'),
                $attendance->user->name,
                $attendance->outlet->name ?? 'N/A',
                $attendance->check_in_time->format('H:i:s'),
                $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : 'N/A',
                $attendance->work_duration_minutes ?? 'N/A',
                $attendance->late_minutes ?? 0,
                $attendance->early_checkout_minutes ?? 0,
                $attendance->overtime_minutes ?? 0,
                $attendance->attendance_score ?? 'N/A',
                $attendance->attendance_status ?? 'N/A',
                $attendance->check_in_selfie_path ? 'Yes' : 'No',
                $attendance->check_out_selfie_path ? 'Yes' : 'No',
                $attendance->outlet ? round($attendance->outlet->getDistanceFrom($attendance->check_in_latitude, $attendance->check_in_longitude)) . 'm' : 'N/A'
            ]);
        }

        fclose($handle);

        $this->info("\n📁 Results exported to: {$filename}");
        $this->info("   File size: " . $this->formatBytes(filesize($filepath)));
    }

    /**
     * Format duration in minutes to readable format
     */
    private function formatDuration($minutes)
    {
        if (!$minutes) return 'N/A';
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        } else {
            return "{$mins}m";
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
