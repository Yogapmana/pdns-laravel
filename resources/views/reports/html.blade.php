<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Nilai {{ implode(', ', $kelas_list) }}</title>
    <style>
        body { font-family: Calibri, sans-serif; padding: 16px; color: #1f2937; font-size: 13px; }
        h1 { color: #1E3A5F; margin: 0; }
        h2 { color: #1E3A5F; margin: 4px 0 8px 0; font-size: 16px; }
        h3 { color: #1E3A5F; margin: 18px 0 6px 0; font-size: 14px; background: #1E3A5F; color: white; padding: 4px 8px; }
        .meta { color: #4b5563; font-size: 12px; margin-bottom: 8px; }
        .summary { display: flex; gap: 16px; margin: 6px 0 10px; padding: 8px; background: #F1F5F9; border-radius: 4px; font-size: 12px; }
        .summary-item strong { display: block; font-size: 16px; color: #1A56DB; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: center; font-size: 12px; }
        th { background: #1A56DB; color: white; }
        .nis { font-family: 'Consolas', monospace; text-align: left; }
        .nama { text-align: left; }
        .lulus { background: #dcfce7; color: #166534; font-weight: bold; padding: 1px 4px; border-radius: 3px; }
        .tidak { background: #fee2e2; color: #991b1b; font-weight: bold; padding: 1px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>SMAN 7 SOLO</h1>
    <h2>Laporan Rekapitulasi Nilai — Kelas {{ implode(', ', $kelas_list) }}</h2>
    <p class="meta">
        @if(count($mapel_list) > 0)
            Mata Pelajaran: <strong>{{ implode(', ', $mapel_list) }}</strong> &mdash;
        @endif
        Tanggal cetak: {{ $tanggal_cetak }} &mdash; Total: {{ $stats['jumlah_siswa'] }} siswa, {{ $stats['jumlah_lulus'] }} lulus, {{ $stats['jumlah_tidak_lulus'] }} tidak lulus
    </p>

    @if(count($sections) === 0)
        <p style="text-align: center; color: #6b7280; padding: 24px;">Tidak ada data nilai untuk filter yang dipilih.</p>
    @endif

    @foreach($sections as $section)
        <h3>KELAS {{ $section['kelas'] }}</h3>

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
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    @foreach($section['mapel_list'] as $mapel)
                        <th>{{ $mapel }}<br><small>(T/UTS/UAS/Akhir)</small></th>
                    @endforeach
                    <th>Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section['rows'] as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="nis">{{ $row['siswa']->nis }}</td>
                        <td class="nama">{{ $row['siswa']->nama_siswa }}</td>
                        @foreach($section['mapel_list'] as $mapel)
                            @php $n = $row['nilai_per_mapel'][$mapel] ?? null; @endphp
                            <td>
                                {{ $n?->nilai_tugas ?? '—' }} / {{ $n?->nilai_uts ?? '—' }} / {{ $n?->nilai_uas ?? '—' }}
                                @if($n?->nilai_akhir !== null)
                                    <br><span class="{{ $n->status_lulus === 'Lulus' ? 'lulus' : 'tidak' }}">{{ $n->nilai_akhir }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td><strong>{{ $row['rata_rata'] ?? '—' }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
