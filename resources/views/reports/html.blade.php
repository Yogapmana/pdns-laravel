<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kelas {{ $kelas }}</title>
    <style>
        body { font-family: Calibri, sans-serif; padding: 20px; color: #1f2937; }
        h1 { color: #1E3A5F; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: center; }
        th { background: #1A56DB; color: white; }
    </style>
</head>
<body>
    <h1 style="margin: 0;">SMAN 7 SOLO</h1>
    <h2 style="margin: 6px 0 0 0; color: #1E3A5F; font-size: 18px;">Laporan Rekapitulasi Nilai - Kelas {{ $kelas }}</h2>
    <p>Tanggal: {{ $tanggal_cetak }}</p>
    <p>Total: {{ $stats['jumlah_siswa'] }} siswa | Lulus: {{ $stats['jumlah_lulus'] }} | Tidak Lulus: {{ $stats['jumlah_tidak_lulus'] }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama</th>
                @foreach($mapel_list as $mapel)
                    <th>{{ $mapel }}<br><small>(T/UTS/UAS)</small></th>
                @endforeach
                <th>Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['siswa']->nis }}</td>
                    <td style="text-align:left;">{{ $row['siswa']->nama_siswa }}</td>
                    @foreach($mapel_list as $mapel)
                        @php $n = $row['nilai_per_mapel'][$mapel] ?? null; @endphp
                        <td>
                            {{ $n?->nilai_tugas ?? '—' }} / {{ $n?->nilai_uts ?? '—' }} / {{ $n?->nilai_uas ?? '—' }}
                            @if($n)<br><strong>{{ $n->nilai_akhir ?? '—' }}</strong> @endif
                        </td>
                    @endforeach
                    <td><strong>{{ $row['rata_rata'] ?? '—' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
