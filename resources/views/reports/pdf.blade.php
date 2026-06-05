<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kelas {{ $kelas }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Calibri', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1A56DB; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; color: #1E3A5F; }
        .header p { margin: 4px 0; font-size: 11px; color: #374151; }
        .summary { display: flex; justify-content: space-between; margin-bottom: 15px; padding: 8px; background: #F1F5F9; border-radius: 6px; }
        .summary-item { text-align: center; }
        .summary-item strong { display: block; font-size: 18px; color: #1A56DB; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: center; }
        th { background: #1A56DB; color: white; font-weight: bold; }
        .nis { font-family: 'Consolas', monospace; text-align: left; }
        .nama { text-align: left; }
        .badge-lulus { background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-tidak { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN REKAPITULASI NILAI SISWA</h1>
        <p>Kelas: <strong>{{ $kelas }}</strong> &mdash; Tanggal Cetak: {{ $tanggal_cetak }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <strong>{{ $stats['jumlah_siswa'] }}</strong>
            <span>Total Siswa</span>
        </div>
        <div class="summary-item">
            <strong style="color: #10B981;">{{ $stats['jumlah_lulus'] }}</strong>
            <span>Lulus</span>
        </div>
        <div class="summary-item">
            <strong style="color: #EF4444;">{{ $stats['jumlah_tidak_lulus'] }}</strong>
            <span>Tidak Lulus</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 6%;">No</th>
                <th rowspan="2" style="width: 10%;">NIS</th>
                <th rowspan="2" style="width: 18%;">Nama Siswa</th>
                @foreach($mapel_list as $mapel)
                    <th colspan="3">{{ $mapel }}</th>
                @endforeach
                <th rowspan="2" style="width: 7%;">Rata-rata</th>
            </tr>
            <tr>
                @foreach($mapel_list as $mapel)
                    <th style="width: 3%; font-size: 8px;">Tgs</th>
                    <th style="width: 3%; font-size: 8px;">UTS</th>
                    <th style="width: 3%; font-size: 8px;">UAS</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="nis">{{ $row['siswa']->nis }}</td>
                    <td class="nama">{{ $row['siswa']->nama_siswa }}</td>
                    @foreach($mapel_list as $mapel)
                        @php $n = $row['nilai_per_mapel'][$mapel] ?? null; @endphp
                        <td>{{ $n?->nilai_tugas ?? '—' }}</td>
                        <td>{{ $n?->nilai_uts ?? '—' }}</td>
                        <td>{{ $n?->nilai_uas ?? '—' }}</td>
                    @endforeach
                    <td><strong>{{ $row['rata_rata'] ?? '—' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini dicetak otomatis oleh sistem NilaiSiswa pada {{ now()->format('d F Y H:i') }}</p>
    </div>
</body>
</html>
