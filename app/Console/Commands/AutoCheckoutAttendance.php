<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Outlet;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCheckoutAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-checkout {--dry-run : Hanya simulasi tanpa menyimpan perubahan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto checkout setelah melewati toleransi lembur, dengan waktu checkout dicatat pada jam tutup operasional';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $dryRun = $this->option('dry-run');
        $processed = 0;
        $skipped = 0;

        $attendances = Attendance::with(['outlet', 'user'])
            ->whereNull('check_out_time')
            ->whereNotNull('check_in_time')
            ->get();

        if ($attendances->isEmpty()) {
            $this->info('Tidak ada check-in aktif yang perlu di-auto checkout.');
            return 0;
        }

        foreach ($attendances as $attendance) {
            $outlet = $attendance->outlet;

            if (!$outlet || !$outlet->operational_end_time) {
                $skipped++;
                continue;
            }

            $timezone = $outlet->timezone ?? config('app.timezone', 'Asia/Jakarta');
            $reference = Carbon::parse($attendance->check_in_time)->setTimezone($timezone);
            $window = $this->resolveOperationalWindow($outlet, $reference);

            if (!$window) {
                $skipped++;
                continue;
            }

            [$startTime, $endTime] = $window;
            $thresholdMinutes = $outlet->overtime_threshold_minutes ?? 60;
            $triggerTime = $endTime->copy()->addMinutes($thresholdMinutes);
            $nowInOutletTz = $now->copy()->setTimezone($timezone);

            // Belum lewat batas toleransi lembur
            if ($nowInOutletTz->lt($triggerTime)) {
                $skipped++;
                continue;
            }

            // Checkout time dicatat sebagai jam tutup operasional (bukan waktu auto)
            $checkoutTime = $endTime->copy()->setTimezone(config('app.timezone', 'Asia/Jakarta'));

            if ($dryRun) {
                $this->line(sprintf(
                    '[DRY RUN] Auto checkout untuk user #%d di outlet #%d pada %s (recorded as %s, trigger setelah %s)',
                    $attendance->user_id,
                    $outlet->id,
                    $nowInOutletTz->toDateTimeString(),
                    $checkoutTime->toDateTimeString(),
                    $triggerTime->toDateTimeString()
                ));
                $processed++;
                continue;
            }

            DB::transaction(function () use ($attendance, $checkoutTime) {
                $attendance->update([
                    'check_out_time' => $checkoutTime,
                    'status' => 'checked_out',
                    'check_out_latitude' => null,
                    'check_out_longitude' => null,
                    'check_out_accuracy' => null,
                    'has_check_out_selfie' => false,
                    'check_out_selfie_path' => null,
                    'check_out_thumbnail_path' => null,
                    'check_out_file_size' => null,
                    'checkout_remarks' => 'Auto checkout oleh sistem setelah melewati toleransi lembur',
                    'is_overtime' => false,
                    'overtime_minutes' => 0,
                ]);

                // Hitung ulang metrik dengan checkout time baru
                $attendance->calculateAndUpdateMetrics();
            });

            try {
                AuditService::logCheckout($attendance, null, [
                    'trigger' => 'auto',
                    'recorded_checkout_time' => $checkoutTime->toISOString(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Gagal mencatat audit auto checkout', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $processed++;
        }

        $this->info("Auto checkout selesai. Diproses: {$processed}, dilewati: {$skipped}" . ($dryRun ? ' (dry run)' : ''));

        return 0;
    }

    /**
     * Dapatkan window jam operasional berdasarkan outlet dan tanggal referensi.
     */
    private function resolveOperationalWindow(Outlet $outlet, Carbon $reference): ?array
    {
        if (!$outlet->operational_start_time || !$outlet->operational_end_time) {
            return null;
        }

        $timezone = $outlet->timezone ?? config('app.timezone', 'Asia/Jakarta');
        $reference = $reference->copy()->setTimezone($timezone);

        $startTime = $this->parseOperationalTime(
            $outlet->operational_start_time,
            $reference,
            $timezone,
            '09:00'
        );

        $endTime = $this->parseOperationalTime(
            $outlet->operational_end_time,
            $reference,
            $timezone,
            '18:00'
        );

        // Penyesuaian jika shift melewati tengah malam
        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();

            if ($reference->lt($startTime)) {
                $startTime->subDay();
                $endTime->subDay();
            }
        }

        return [$startTime, $endTime];
    }

    /**
     * Parse jam operasional dalam timezone outlet.
     */
    private function parseOperationalTime($timeValue, Carbon $referenceDate, string $timezone, string $fallback): Carbon
    {
        $timeString = null;

        if ($timeValue instanceof Carbon) {
            $timeString = $timeValue->format('H:i');
        } elseif ($timeValue instanceof \DateTimeInterface) {
            $timeString = Carbon::instance($timeValue)->format('H:i');
        } elseif (is_string($timeValue) && trim($timeValue) !== '') {
            $timeString = $timeValue;
        }

        if (!$timeString) {
            $timeString = $fallback;
        }

        [$hour, $minute] = array_pad(explode(':', $timeString), 2, 0);

        return $referenceDate->copy()->setTimezone($timezone)->setTime((int) $hour, (int) $minute, 0);
    }
}
