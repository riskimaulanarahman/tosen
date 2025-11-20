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
            <div><span>Periode</span>{{ optional($record->payrollPeriod)->start_date?->format('d M Y') }} - {{ optional($record->payrollPeriod)->end_date?->format('d M Y') }}</div>
            <div><span>Dibayar</span>{{ $record->paid_at ? $record->paid_at->format('d M Y') : '-' }}</div>
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
                    <td>{{ number_format($record->base_salary, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Lembur</td>
                    <td>{{ number_format($record->overtime_pay, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan / Bonus</td>
                    <td>{{ number_format($record->bonus, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Potongan Cuti/Izin</td>
                    <td>{{ number_format($record->leave_deduction, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Potongan Lain</td>
                    <td>{{ number_format($record->other_deductions, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Pajak</td>
                    <td>{{ number_format($record->tax_deduction, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Net Diterima</td>
                    <td>{{ number_format($record->total_pay - $record->tax_deduction - $record->other_deductions, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Rincian Lembur</h3>
        @if($record->overtimeRecords->isEmpty())
            <p class="muted">Tidak ada lembur.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Menit</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->overtimeRecords as $overtime)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($overtime->date)->format('d M Y') }}</td>
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
            <p class="muted">Dicetak pada {{ now()->format('d M Y H:i') }}</p>
        </div>
        <div class="actions no-print">
            <a href="#" class="btn secondary" onclick="window.history.back();return false;">Kembali</a>
            <a href="#" class="btn" onclick="window.print();return false;">Cetak</a>
        </div>
    </div>
</body>
</html>
