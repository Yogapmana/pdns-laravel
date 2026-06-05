<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Nilai {{ implode(', ', $kelas_list) }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Calibri', 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #1A56DB; padding-bottom: 8px; }
        .header h1 { margin: 0; font-size: 16px; color: #1E3A5F; }
        .header p { margin: 2px 0; font-size: 10px; color: #374151; }
        .section-header { margin: 14px 0 6px; padding: 4px 8px; background: #1E3A5F; color: white; font-weight: bold; font-size: 12px; }
        .summary { display: flex; justify-content: space-around; margin-bottom: 8px; padding: 6px; background: #F1F5F9; border-radius: 4px; }
        .summary-item { text-align: center; }
        .summary-item strong { display: block; font-size: 14px; color: #1A56DB; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 3px 5px; text-align: center; }
        th { background: #1A56DB; color: white; font-weight: bold; font-size: 7px; }
        .nis { font-family: 'Consolas', monospace; text-align: left; }
        .nama { text-align: left; }
        .badge-lulus { background: #dcfce7; color: #166534; padding: 1px 4px; border-radius: 3px; font-weight: bold; }
        .badge-tidak { background: #fee2e2; color: #991b1b; padding: 1px 4px; border-radius: 3px; font-weight: bold; }
        .footer { margin-top: 14px; text-align: right; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SMAN 7 SOLO</h1>
        <p style="font-size: 12px; font-weight: bold; margin-top: 4px;">LAPORAN REKAPITULASI NILAI SISWA</p>
        <p>Kelas: <strong>{{ implode(', ', $kelas_list) }}</strong> @if(count($mapel_list) > 0) &mdash; Mata Pelajaran: <strong>{{ implode(', ', $mapel_list) }}</strong> @endif &mdash; Tanggal Cetak: {{ $tanggal_cetak }}</p>
    </div>

    @if(count($sections) === 0)
        <p style="text-align: center; color: #6b7280; padding: 24px;">Tidak ada data nilai untuk filter yang dipilih.</p>
    @endif

    @foreach($sections as $section)
        <div class="section-header">KELAS {{ $section['kelas'] }}</div>

        <div class="summary">
            <div class="summary-item">
                <strong>{{ $section['stats']['jumlah_siswa'] }}</strong>
                <span>Siswa</span>
            </div>
            <div class="summary-item">
                <strong style="color: #10B981;">{{ $section['stats']['jumlah_lulus'] }}</strong>
                <span>Lulus</span>
            </div>
            <div class="summary-item">
                <strong style="color: #EF4444;">{{ $section['stats']['jumlah_tidak_lulus'] }}</strong>
                <span>Tidak Lulus</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 4%;">No</th>
                    <th rowspan="2" style="width: 8%;">NIS</th>
                    <th rowspan="2" style="width: 14%;">Nama Siswa</th>
                    @foreach($mapel_list as $mapel)
                        <th colspan="4">{{ $mapel }}</th>
                    @endforeach
                    <th rowspan="2" style="width: 6%;">Rata-rata</th>
                </tr>
                <tr>
                    @foreach($mapel_list as $mapel)
                        <th style="width: 3%; font-size: 7px;">Tgs</th>
                        <th style="width: 3%; font-size: 7px;">UTS</th>
                        <th style="width: 3%; font-size: 7px;">UAS</th>
                        <th style="width: 4%; font-size: 7px;">Akhir</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($section['rows'] as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="nis">{{ $row['siswa']->nis }}</td>
                        <td class="nama">{{ $row['siswa']->nama_siswa }}</td>
                        @foreach($mapel_list as $mapel)
                            @php $n = $row['nilai_per_mapel'][$mapel] ?? null; @endphp
                            <td>{{ $n?->nilai_tugas ?? '—' }}</td>
                            <td>{{ $n?->nilai_uts ?? '—' }}</td>
                            <td>{{ $n?->nilai_uas ?? '—' }}</td>
                            <td>
                                @if($n?->nilai_akhir !== null)
                                    <span class="{{ $n->status_lulus === 'Lulus' ? 'badge-lulus' : 'badge-tidak' }}">{{ $n->nilai_akhir }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        @endforeach
                        <td><strong>{{ $row['rata_rata'] ?? '—' }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        <p>Dokumen ini dicetak otomatis oleh sistem SMAN 7 Solo pada {{ now()->format('d F Y H:i') }} &mdash; Total: {{ $stats['jumlah_siswa'] }} siswa, {{ $stats['jumlah_lulus'] }} lulus, {{ $stats['jumlah_tidak_lulus'] }} tidak lulus</p>
    </div>
</body>
</html>
