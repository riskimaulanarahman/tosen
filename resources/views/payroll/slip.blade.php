<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $record->user->name ?? 'Karyawan #' . $record->user_id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            margin: 24px;
            color: #1f2937;
        }
        h1, h2, h3, h4 { margin: 0; }
        .header, .footer { display: flex; justify-content: space-between; align-items: center; }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px 24px; margin-top: 8px; font-size: 14px; }
        .meta div span { display: block; color: #6b7280; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; }
        .total { font-weight: 700; }
        .muted { color: #6b7280; font-size: 13px; }
        .section-title { margin-top: 16px; font-size: 14px; font-weight: 700; }
        .pill { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: 12px; }
        @media print {
            body { margin: 12mm; }
            .no-print { display: none !important; }
            .card { box-shadow: none; }
        }
        .actions { margin-top: 12px; display: flex; gap: 8px; }
        .btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #111827;
            color: #fff;
            font-size: 14px;
            text-decoration: none;
        }
        .btn.secondary { background: #fff; color: #111827; }
    </style>
</head>
<body>
    @php
        $baseSalary = $record->base_salary ?? 0;
        $overtimePay = $record->overtime_pay ?? 0;
        $bonus = $record->bonus ?? 0;
        $leaveDeduction = $record->leave_deduction ?? 0;
        $otherDeductions = $record->other_deductions ?? 0;
        $taxDeduction = $record->tax_deduction ?? 0;

        // Timezone outlet (fallback ke app timezone)
        $tz = optional(optional($record->user)->outlet)->timezone ?? config('app.timezone');
        $formatDate = function ($date) use ($tz) {
            if (!$date) {
                return '-';
            }
            $carbon = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
            return $carbon->copy()->setTimezone($tz)->format('d M Y');
        };
        $formatDateTime = function ($date) use ($tz) {
            if (!$date) {
                return '-';
            }
            $carbon = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
            return $carbon->copy()->setTimezone($tz)->format('d M Y H:i');
        };

        // Periode & hari kerja (Senin–Jumat) dengan timezone outlet
        $periodStart = (optional($record->payrollPeriod)->start_date ?? now())->copy()->setTimezone($tz);
        $periodEnd = (optional($record->payrollPeriod)->end_date ?? now())->copy()->setTimezone($tz);
        $periodDays = $periodStart->copy()->startOfDay()->diffInDays($periodEnd->copy()->startOfDay()) + 1;

        $targetWorkDays = 0;
        foreach (\Carbon\CarbonPeriod::create($periodStart->copy()->startOfDay(), $periodEnd->copy()->startOfDay()) as $date) {
            if (! $date->isWeekend()) {
                $targetWorkDays++;
            }
        }
        $targetWorkDays = max(1, $targetWorkDays); // hindari pembagi nol

        // Komponen gaji pokok yang dibayar (gross - lembur)
        $baseComponentPaid = max(0, ($record->total_pay ?? 0) - $overtimePay);
        $dailyBaseRate = $targetWorkDays > 0 ? $baseSalary / $targetWorkDays : 0;
        $effectivePaidDays = $dailyBaseRate > 0 ? round($baseComponentPaid / $dailyBaseRate, 2) : 0;

        $grossIncome = $baseComponentPaid + $overtimePay + $bonus;
        $totalDeductions = $leaveDeduction + $otherDeductions + $taxDeduction;
        $netPay = $grossIncome - $totalDeductions;
    @endphp

    <div class="header">
        <div>
            <h1>Slip Gaji</h1>
            <p class="muted">{{ $record->payrollPeriod->name ?? '-' }}</p>
        </div>
        <div style="text-align:right;">
            <h3>{{ $record->user->name ?? 'Karyawan #' . $record->user_id }}</h3>
            <p class="muted">{{ $record->user->email ?? '-' }}</p>
            <span class="pill">{{ ucfirst($record->status) }}</span>
        </div>
    </div>

    <div class="card">
        <div class="meta">
            <div><span>Periode</span>{{ $formatDate(optional($record->payrollPeriod)->start_date) }} - {{ $formatDate(optional($record->payrollPeriod)->end_date) }}</div>
            <div><span>Dibayar</span>{{ $record->paid_at ? $formatDate($record->paid_at) : '-' }}</div>
            <div><span>Metode</span>{{ $record->payment_method ?? '-' }}</div>
            <div><span>Referensi</span>{{ $record->payment_reference ?? '-' }}</div>
        </div>
    </div>

    <div class="card">
        <h3>Rincian Gaji</h3>
        <table>
            <tbody>
                <tr>
                    <td>Gaji Pokok</td>
                    <td>{{ number_format($baseSalary, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Hari Kalender (periode)</td>
                    <td>{{ $periodDays }} hari</td>
                </tr>
                <tr>
                    <td>Hari Kerja (Senin–Jumat)</td>
                    <td>{{ $targetWorkDays }} hari</td>
                </tr>
                <tr>
                    <td>Rate Gaji per Hari (Gaji Pokok ÷ Hari Kerja)</td>
                    <td>{{ number_format($dailyBaseRate, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Hari Dibayar (Prorata)</td>
                    <td>{{ $effectivePaidDays }} hari</td>
                </tr>
                <tr class="total">
                    <td>Gaji Pokok Dibayar (Rate per Hari × Hari Dibayar)</td>
                    <td>{{ number_format($baseComponentPaid, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Lembur</td>
                    <td>{{ number_format($overtimePay, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan / Bonus</td>
                    <td>{{ number_format($bonus, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Total Pendapatan</td>
                    <td>{{ number_format($grossIncome, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Potongan Cuti/Izin</td>
                    <td>{{ number_format($leaveDeduction, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Potongan Lain</td>
                    <td>{{ number_format($otherDeductions, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Pajak</td>
                    <td>{{ number_format($taxDeduction, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Total Potongan</td>
                    <td>{{ number_format($totalDeductions, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Net Diterima</td>
                    <td>{{ number_format($netPay, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Detail Perhitungan Net</h3>
        <table>
            <tbody>
                <tr>
                    <td>Gaji Pokok Dibayar (prorata)</td>
                    <td>{{ number_format($baseComponentPaid, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tambahan (Lembur + Bonus)</td>
                    <td>{{ number_format($overtimePay + $bonus, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Pendapatan (Gaji Pokok + Lembur + Bonus)</td>
                    <td>{{ number_format($grossIncome, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Potongan (Cuti/Izin + Potongan Lain + Pajak)</td>
                    <td>- {{ number_format($totalDeductions, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Net Diterima</td>
                    <td>{{ number_format($netPay, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        <p class="muted" style="margin-top:8px;">Perhitungan otomatis berdasarkan data periode dan konfigurasi outlet.</p>
    </div>

    <div class="card">
        <h3>Rincian Lembur</h3>
        @if($record->overtimeRecords->isEmpty())
            <p class="muted">Tidak ada lembur.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Tanggal ({{ $tz }})</th>
                        <th>Menit</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->overtimeRecords as $overtime)
                        <tr>
                            <td>{{ $formatDate($overtime->date) }}</td>
                            <td>{{ $overtime->overtime_minutes }} menit</td>
                            <td>{{ ucfirst($overtime->overtime_type) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if($record->notes)
    <div class="card">
        <h3>Catatan</h3>
        <p>{{ $record->notes }}</p>
    </div>
    @endif

    <div class="card footer">
        <div>
            <p class="muted">Dicetak pada {{ $formatDateTime(now()) }}</p>
        </div>
        <div class="actions no-print">
            <a href="#" class="btn secondary" onclick="return handleClose(event);">Close</a>
            <a href="#" class="btn" onclick="window.print();return false;">Cetak</a>
        </div>
    </div>

    <script>
        function handleClose(event) {
            event.preventDefault();

            // If opened as a popup/tab from another window, close directly
            if (window.opener && !window.opener.closed) {
                window.close();
                return false;
            }

            // Fallback: go back if there is history
            if (window.history.length > 1) {
                window.history.back();
                return false;
            }

            // Final fallback: navigate to previous page if known, else home
            const ref = document.referrer;
            if (ref) {
                window.location.href = ref;
            } else {
                window.location.href = '/';
            }

            return false;
        }
    </script>
</body>
</html>
