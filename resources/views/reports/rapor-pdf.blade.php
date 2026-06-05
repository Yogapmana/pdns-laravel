<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Digital - {{ $siswa->nama_siswa }}</title>
    <style>
        @page { margin: 1.5cm 1.5cm 1.5cm 1.5cm; }
        body { font-family: 'Calibri', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.4; }
        .header { text-align: center; border-bottom: 3px double #1E3A5F; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 18px; color: #1E3A5F; letter-spacing: 1px; }
        .header h2 { margin: 4px 0 0; font-size: 14px; color: #1A56DB; font-weight: normal; }
        .header p { margin: 2px 0; font-size: 10px; color: #374151; }
        .identity { width: 100%; margin-bottom: 16px; font-size: 11px; }
        .identity td { padding: 2px 4px; vertical-align: top; }
        .identity .label { width: 18%; color: #6b7280; }
        .identity .colon { width: 2%; }
        .identity .value { width: 30%; font-weight: bold; }
        table.nilai { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
        table.nilai th, table.nilai td { border: 1px solid #cbd5e1; padding: 4px 6px; }
        table.nilai th { background: #1A56DB; color: white; font-weight: bold; text-align: center; font-size: 9px; }
        table.nilai td.center { text-align: center; }
        table.nilai td.nilai { font-family: 'Consolas', monospace; font-weight: bold; text-align: center; }
        table.nilai td.mapel { text-align: left; }
        table.nilai td.guru { font-size: 9px; color: #6b7280; }
        .badge-lulus { background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 9px; }
        .badge-tidak { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 9px; }
        .badge-draft { background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 9px; }
        .summary { display: flex; justify-content: space-around; margin-bottom: 14px; padding: 8px; background: #F1F5F9; border-radius: 4px; }
        .summary-item { text-align: center; }
        .summary-item strong { display: block; font-size: 16px; color: #1A56DB; }
        .summary-item span { font-size: 9px; color: #6b7280; }
        .footer-section { margin-top: 30px; }
        .signature { width: 100%; }
        .signature td { text-align: center; padding: 4px; font-size: 10px; }
        .signature .name { font-weight: bold; text-decoration: underline; margin-top: 60px; }
        .meta { font-size: 8px; color: #9ca3af; text-align: right; margin-top: 8px; font-style: italic; }
        .empty { text-align: center; color: #6b7280; padding: 20px; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SMAN 7 SOLO</h1>
        <h2>RAPOR DIGITAL SISWA</h2>
        <p>Tahun Ajaran {{ $tahun_ajaran }}</p>
    </div>

    <table class="identity">
        <tr>
            <td class="label">Nama Siswa</td>
            <td class="colon">:</td>
            <td class="value">{{ strtoupper($siswa->nama_siswa) }}</td>
            <td class="label">Kelas</td>
            <td class="colon">:</td>
            <td class="value">{{ $siswa->kelas }}</td>
        </tr>
        <tr>
            <td class="label">NIS</td>
            <td class="colon">:</td>
            <td class="value">{{ $siswa->nis }}</td>
            <td class="label">KKM</td>
            <td class="colon">:</td>
            <td class="value">{{ number_format((float) $kkm, 0) }}</td>
        </tr>
    </table>

    @if(count($per_mapel) === 0)
        <p class="empty">Belum ada data nilai yang diinput untuk siswa ini.</p>
    @else
        <table class="nilai">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 22%;">Mata Pelajaran</th>
                    <th style="width: 18%;">Guru Pengajar</th>
                    <th style="width: 8%;">Tugas<br><span style="font-weight:normal;font-size:8px;">(30%)</span></th>
                    <th style="width: 8%;">UTS<br><span style="font-weight:normal;font-size:8px;">(30%)</span></th>
                    <th style="width: 8%;">UAS<br><span style="font-weight:normal;font-size:8px;">(40%)</span></th>
                    <th style="width: 10%;">Nilai<br>Akhir</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 10%;">Validasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($per_mapel as $i => $m)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td class="mapel"><strong>{{ $m['mapel'] }}</strong></td>
                        <td class="guru">{{ $m['nama_guru'] }}</td>
                        <td class="nilai">{{ $m['tugas'] !== null ? number_format((float) $m['tugas'], 2) : '—' }}</td>
                        <td class="nilai">{{ $m['uts'] !== null ? number_format((float) $m['uts'], 2) : '—' }}</td>
                        <td class="nilai">{{ $m['uas'] !== null ? number_format((float) $m['uas'], 2) : '—' }}</td>
                        <td class="nilai">
                            @if($m['akhir'] !== null)
                                <strong>{{ number_format((float) $m['akhir'], 2) }}</strong>
                            @else
                                —
                            @endif
                        </td>
                        <td class="center">
                            @if($m['status'] === 'Lulus')
                                <span class="badge-lulus">LULUS</span>
                            @elseif($m['status'] === 'Tidak Lulus')
                                <span class="badge-tidak">TIDAK LULUS</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="center">
                            @if($m['status_validasi'] === 'Final')
                                <span class="badge-lulus">FINAL</span>
                            @else
                                <span class="badge-draft">DRAFT</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-item">
                <strong>{{ $jumlah_mapel }}</strong>
                <span>Mata Pelajaran</span>
            </div>
            <div class="summary-item">
                <strong>{{ $rata_rata !== null ? number_format((float) $rata_rata, 2) : '—' }}</strong>
                <span>Rata-rata</span>
            </div>
            <div class="summary-item">
                <strong style="color: #10B981;">{{ $lulus }}</strong>
                <span>Lulus</span>
            </div>
            <div class="summary-item">
                <strong style="color: #EF4444;">{{ $tidak_lulus }}</strong>
                <span>Tidak Lulus</span>
            </div>
        </div>
    @endif

    <div class="footer-section">
        <table class="signature">
            <tr>
                <td style="width: 33%;">
                    Orang Tua / Wali<br><br><br><br>
                    <div class="name">( ............................. )</div>
                </td>
                <td style="width: 33%;">
                    Wali Kelas<br><br><br><br>
                    <div class="name">( ............................. )</div>
                </td>
                <td style="width: 33%;">
                    Solo, {{ $tanggal_cetak }}<br>
                    Kepala Sekolah<br><br><br><br>
                    <div class="name">( ............................. )</div>
                </td>
            </tr>
        </table>
    </div>

    <p class="meta">
        Dokumen ini dicetak otomatis oleh Sistem Akademik SMAN 7 Solo pada {{ now()->translatedFormat('d F Y H:i') }} WIB.<br>
        Rapor digital ini sah dan dihasilkan langsung dari basis data sistem.
    </p>
</body>
</html>
