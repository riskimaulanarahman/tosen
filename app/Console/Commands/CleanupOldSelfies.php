<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOldSelfies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:cleanup-selfies 
                            {--days=90 : Delete selfies older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old selfie files and orphaned database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Selfie Cleanup Command");
        $this->info("======================");
        $this->info("Cleaning up selfies older than {$days} days");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No files will be deleted");
        }

        $cutoffDate = Carbon::now()->subDays($days);
        
        // Get attendances with selfies older than cutoff date
        $attendances = Attendance::where(function ($query) use ($cutoffDate) {
                $query->where('check_in_time', '<', $cutoffDate)
                      ->orWhere('check_out_time', '<', $cutoffDate);
            })
            ->where(function ($query) {
                $query->whereNotNull('check_in_selfie_path')
                      ->orWhereNotNull('check_out_selfie_path');
            })
            ->get();

        $totalAttendances = $attendances->count();
        $filesToDelete = [];
        $recordsToUpdate = [];

        foreach ($attendances as $attendance) {
            // Check check-in selfie
            if ($attendance->check_in_selfie_path) {
                $checkInPath = $attendance->check_in_selfie_path;
                $thumbnailPath = $attendance->check_in_thumbnail_path;
                
                if (Storage::disk('public')->exists($checkInPath)) {
                    $filesToDelete[] = $checkInPath;
                }
                
                if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                    $filesToDelete[] = $thumbnailPath;
                }
                
                if (!$dryRun) {
                    $recordsToUpdate[] = [
                        'id' => $attendance->id,
                        'type' => 'check_in'
                    ];
                }
            }

            // Check check-out selfie
            if ($attendance->check_out_selfie_path) {
                $checkOutPath = $attendance->check_out_selfie_path;
                $thumbnailPath = $attendance->check_out_thumbnail_path;
                
                if (Storage::disk('public')->exists($checkOutPath)) {
                    $filesToDelete[] = $checkOutPath;
                }
                
                if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                    $filesToDelete[] = $thumbnailPath;
                }
                
                if (!$dryRun) {
                    $recordsToUpdate[] = [
                        'id' => $attendance->id,
                        'type' => 'check_out'
                    ];
                }
            }
        }

        $uniqueFiles = array_unique($filesToDelete);
        $totalFiles = count($uniqueFiles);
        $totalSize = 0;

        // Calculate total size
        foreach ($uniqueFiles as $file) {
            if (Storage::disk('public')->exists($file)) {
                $totalSize += Storage::disk('public')->size($file);
            }
        }

        $this->info("Found {$totalAttendances} attendance records with old selfies");
        $this->info("Found {$totalFiles} selfie files to delete");
        $this->info("Total size to free: " . $this->formatBytes($totalSize));

        if ($totalFiles === 0) {
            $this->info("No files to clean up.");
            return 0;
        }

        // Show sample files
        $this->info("\nSample files to be deleted:");
        foreach (array_slice($uniqueFiles, 0, 5) as $file) {
            $size = Storage::disk('public')->exists($file) 
                ? $this->formatBytes(Storage::disk('public')->size($file)) 
                : 'N/A';
            $this->line("  - {$file} ({$size})");
        }
        
        if ($totalFiles > 5) {
            $this->line("  ... and " . ($totalFiles - 5) . " more files");
        }

        if (!$dryRun && !$force) {
            if (!$this->confirm("Do you want to proceed with deletion?")) {
                $this->info("Cleanup cancelled.");
                return 0;
            }
        }

        if ($dryRun) {
            $this->info("\nDry run completed. No files were deleted.");
            return 0;
        }

        // Delete files
        $deletedCount = 0;
        $failedCount = 0;
        $freedSpace = 0;

        $this->info("\nDeleting files...");
        $progressBar = $this->output->createProgressBar($totalFiles);
        $progressBar->start();

        foreach ($uniqueFiles as $file) {
            try {
                if (Storage::disk('public')->exists($file)) {
                    $fileSize = Storage::disk('public')->size($file);
                    Storage::disk('public')->delete($file);
                    $freedSpace += $fileSize;
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to delete {$file}: " . $e->getMessage());
                $failedCount++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Update database records
        $this->info("\nUpdating database records...");
        $progressBar = $this->output->createProgressBar(count($recordsToUpdate));
        $progressBar->start();

        foreach ($recordsToUpdate as $record) {
            $attendance = Attendance::find($record['id']);
            if ($attendance) {
                if ($record['type'] === 'check_in') {
                    $attendance->update([
                        'check_in_selfie_path' => null,
                        'check_in_thumbnail_path' => null,
                        'check_in_file_size' => null,
                    ]);
                } else {
                    $attendance->update([
                        'check_out_selfie_path' => null,
                        'check_out_thumbnail_path' => null,
                        'check_out_file_size' => null,
                    ]);
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Summary
        $this->info("\nCleanup Summary:");
        $this->info("================");
        $this->info("Attendances processed: {$totalAttendances}");
        $this->info("Files deleted: {$deletedCount}");
        $this->info("Files failed: {$failedCount}");
        $this->info("Space freed: " . $this->formatBytes($freedSpace));
        $this->info("Database records updated: " . count($recordsToUpdate));

        // Clean up orphaned files
        $this->cleanupOrphanedFiles();

        return 0;
    }

    /**
     * Clean up orphaned selfie files
     */
    private function cleanupOrphanedFiles()
    {
        $this->info("\nChecking for orphaned files...");
        
        $orphanedFiles = [];
        $directories = ['selfies'];
        
        foreach ($directories as $directory) {
            if (!Storage::disk('public')->exists($directory)) {
                continue;
            }
            
            $files = Storage::disk('public')->allFiles($directory);
            
            foreach ($files as $file) {
                $existsInDb = Attendance::where('check_in_selfie_path', $file)
                    ->orWhere('check_out_selfie_path', $file)
                    ->orWhere('check_in_thumbnail_path', $file)
                    ->orWhere('check_out_thumbnail_path', $file)
                    ->exists();
                
                if (!$existsInDb) {
                    $orphanedFiles[] = $file;
                }
            }
        }

        if (empty($orphanedFiles)) {
            $this->info("No orphaned files found.");
            return;
        }

        $this->info("Found " . count($orphanedFiles) . " orphaned files:");
        
        $orphanedSize = 0;
        foreach ($orphanedFiles as $file) {
            if (Storage::disk('public')->exists($file)) {
                $orphanedSize += Storage::disk('public')->size($file);
                $this->line("  - {$file}");
            }
        }

        if ($this->confirm("Delete " . count($orphanedFiles) . " orphaned files? (" . $this->formatBytes($orphanedSize) . ")")) {
            foreach ($orphanedFiles as $file) {
                Storage::disk('public')->delete($file);
            }
            $this->info("Orphaned files deleted successfully.");
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
