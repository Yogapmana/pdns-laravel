<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Digital - {{ $siswa->nama_siswa }}</title>
    <style>
        @page { margin: 1.4cm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', 'Liberation Serif', 'DejaVu Serif', serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.4;
            margin: 0;
        }
        table { border-collapse: collapse; }

        /* ============== KOP SURAT (Kemdikbud style) ============== */
        .kop-table {
            width: 100%;
            border-bottom: 3px double #1E3A5F;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .kop-table td { border: none; padding: 0; vertical-align: middle; }
        .kop-logo { width: 90px; }
        .kop-logo img { width: 80px; height: 80px; object-fit: contain; }
        .kop-text { text-align: center; }
        .kop-text .level1 { font-size: 11px; letter-spacing: 2px; color: #374151; text-transform: uppercase; }
        .kop-text .level2 { font-size: 11px; letter-spacing: 1px; color: #374151; text-transform: uppercase; }
        .kop-text .nama { font-size: 18px; font-weight: bold; color: #1E3A5F; margin: 4px 0 2px; letter-spacing: 1.5px; }
        .kop-text .alamat { font-size: 10px; color: #374151; }
        .kop-text .kontak { font-size: 9.5px; color: #6b7280; margin-top: 1px; }

        /* ============== MINIKOP HALAMAN 2 ============== */
        .minikop-table {
            width: 100%;
            border-bottom: 1.5px solid #1E3A5F;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .minikop-table td { border: none; padding: 0; font-size: 10px; color: #374151; }
        .minikop-table .right { text-align: right; color: #6b7280; font-style: italic; }
        .minikop-table .left strong { color: #1E3A5F; }

        /* ============== JUDUL ============== */
        .judul-page { text-align: center; margin: 4px 0 14px; }
        .judul-page h1 { font-size: 16px; margin: 0; color: #1E3A5F; letter-spacing: 1.5px; text-transform: uppercase; }
        .judul-page p { font-size: 11px; margin: 3px 0 0; color: #374151; }

        /* ============== IDENTITAS ============== */
        .identitas-table { width: 100%; margin-bottom: 14px; }
        .identitas-table > tbody > tr > td { border: none; padding: 0; vertical-align: top; }
        .foto-cell { width: 3.6cm; padding-right: 14px; }
        .foto {
            width: 3.2cm;
            height: 4cm;
            border: 1.5px solid #1E3A5F;
            background: #f8fafc;
            text-align: center;
            color: #94a3b8;
            font-size: 9.5px;
            font-style: italic;
            line-height: 1.3;
            padding: 6px 4px;
        }
        .identitas { font-size: 11px; }
        .identitas > tbody > tr > td { height: 0.5cm; padding: 0; vertical-align: middle; }
        .identitas .lbl { width: 32%; color: #6b7280; }
        .identitas .sep { width: 3%; }
        .identitas .val { font-weight: bold; color: #1f2937; }
        .identitas .val.empty { color: #94a3b8; font-weight: normal; font-style: italic; }

        /* ============== SECTION TITLE ============== */
        .section { margin: 12px 0 6px; }
        .section-title {
            background: #1E3A5F;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .section-body {
            border: 1px solid #cbd5e1;
            border-top: none;
            padding: 10px 12px;
            font-size: 11px;
        }
        .section-body.p0 { padding: 0; }

        /* ============== TABEL KEHADIRAN ============== */
        .kehadiran-table { width: 100%; }
        .kehadiran-table td {
            border: none;
            padding: 3px 0;
            vertical-align: top;
        }
        .kehadiran-table .lbl { width: 33%; }
        .kehadiran-table .sep { width: 5%; }
        .kehadiran-table .val { width: 15%; font-weight: bold; color: #94a3b8; font-style: italic; }
        .kehadiran-table .padl { width: 5%; }
        .kehadiran-table .lbl2 { width: 33%; }

        /* ============== TABEL NILAI ============== */
        table.nilai {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        table.nilai th, table.nilai td {
            border: 1px solid #475569;
            padding: 5px 6px;
        }
        table.nilai thead th {
            background: #1E3A5F;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.nilai thead .sub {
            background: #2d4a6f;
            font-weight: normal;
            font-size: 8.5px;
            font-style: italic;
            text-transform: none;
        }
        table.nilai td.center { text-align: center; }
        table.nilai td.mapel { text-align: left; font-weight: 600; }
        table.nilai td.nilai {
            font-family: 'Consolas', 'Courier New', monospace;
            text-align: center;
            font-weight: 600;
        }
        table.nilai td.akhir { background: #fef3c7; font-size: 12px; }
        .badge-lulus {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-tidak {
            display: inline-block;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .empty-cell { color: #94a3b8; }

        /* ============== RINGKASAN ============== */
        .ringkasan {
            width: 100%;
            border-collapse: collapse;
        }
        .ringkasan td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: center;
            background: #f8fafc;
            width: 25%;
        }
        .ringkasan .num {
            font-size: 18px;
            font-weight: bold;
            display: block;
            color: #1A56DB;
            font-family: 'Consolas', monospace;
        }
        .ringkasan .num.success { color: #047857; }
        .ringkasan .num.danger { color: #b91c1c; }
        .ringkasan .lbl { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ============== STATUS KENAIKAN ============== */
        .status-kenaikan {
            margin-top: 10px;
            padding: 8px 12px;
            border: 1.5px solid #1E3A5F;
            border-radius: 4px;
            text-align: center;
            font-size: 11px;
        }
        .status-kenaikan .lbl { color: #6b7280; margin-right: 6px; }
        .status-kenaikan .val { font-weight: bold; color: #1E3A5F; font-size: 12px; }

        /* ============== TTD ============== */
        .ttd {
            width: 100%;
            margin-top: 18px;
        }
        .ttd td {
            text-align: center;
            padding: 4px 6px;
            font-size: 10.5px;
            vertical-align: top;
            border: none;
        }
        .ttd .lokasi { font-size: 10px; color: #374151; margin-bottom: 2px; }
        .ttd .jabatan { font-weight: bold; }
        .ttd .nama {
            margin-top: 56px;
            font-weight: bold;
            text-decoration: underline;
        }
        .ttd .nip { font-size: 9px; color: #6b7280; margin-top: 1px; }
        .ttd-pemisah { height: 56px; }

        /* ============== UTILITY ============== */
        .page-break { page-break-after: always; }
        .empty-state {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 30px 0;
        }
        .footer-page {
            text-align: center;
            font-size: 8.5px;
            color: #94a3b8;
            font-style: italic;
            margin-top: 14px;
            padding-top: 8px;
            border-top: 0.5px solid #e2e8f0;
        }
    </style>
</head>
<body>

{{-- ================================================================ --}}
{{-- HALAMAN 1: COVER + IDENTITAS + KEHADIRAN + EKSKUL + CATATAN       --}}
{{-- ================================================================ --}}

<table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if($logo_base64)
                <img src="data:image/png;base64,{{ $logo_base64 }}" alt="Logo SMAN 7 Solo">
            @else
                <div style="width:80px;height:80px;background:#e2e8f0;"></div>
            @endif
        </td>
        <td class="kop-text">
            <div class="level1">Pemerintah Provinsi Jawa Tengah</div>
            <div class="level2">Dinas Pendidikan</div>
            <div class="nama">{{ strtoupper(config('pdns.sekolah.nama_formal', 'SMA NEGERI 7 SURAKARTA')) }}</div>
            <div class="alamat">{{ $alamat_sekolah }}</div>
            <div class="kontak">
                NPSN: {{ $npsn }}
                @if(config('pdns.sekolah.telepon')) &middot; Telp. {{ config('pdns.sekolah.telepon') }} @endif
                @if(config('pdns.sekolah.website')) &middot; {{ config('pdns.sekolah.website') }} @endif
            </div>
        </td>
    </tr>
</table>

<div class="judul-page">
    <h1>Rapor Digital Siswa</h1>
    <p>Tahun Ajaran {{ $tahun_ajaran }} &middot; Semester {{ $semester }}</p>
</div>

<table class="identitas-table">
    <tr>
        <td class="foto-cell">
            <div class="foto">Pas Foto<br>3 x 4 cm</div>
        </td>
        <td class="identitas">
            <table>
                <tr>
                    <td class="lbl">Nama Siswa</td>
                    <td class="sep">:</td>
                    <td class="val">{{ strtoupper($siswa->nama_siswa) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Nomor Induk (NIS)</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $siswa->nis }}</td>
                </tr>
                <tr>
                    <td class="lbl">Kelas</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $siswa->kelas }}</td>
                </tr>
                <tr>
                    <td class="lbl">Semester</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $semester }} ({{ $tahun_ajaran }})</td>
                </tr>
                <tr>
                    <td class="lbl">Tempat, Tanggal Lahir</td>
                    <td class="sep">:</td>
                    <td class="val empty">&mdash;</td>
                </tr>
                <tr>
                    <td class="lbl">Jenis Kelamin</td>
                    <td class="sep">:</td>
                    <td class="val empty">&mdash;</td>
                </tr>
                <tr>
                    <td class="lbl">Agama</td>
                    <td class="sep">:</td>
                    <td class="val empty">&mdash;</td>
                </tr>
                <tr>
                    <td class="lbl">Alamat</td>
                    <td class="sep">:</td>
                    <td class="val empty">&mdash;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">A. Ketidakhadiran</div>
    <div class="section-body">
        <table class="kehadiran-table">
            <tr>
                <td class="lbl">Sakit</td>
                <td class="sep">:</td>
                <td class="val">— hari</td>
                <td class="padl"></td>
                <td class="lbl2">Izin</td>
                <td class="sep">:</td>
                <td class="val">— hari</td>
            </tr>
            <tr>
                <td class="lbl">Tanpa Keterangan (Alpha)</td>
                <td class="sep">:</td>
                <td class="val">— hari</td>
                <td class="padl"></td>
                <td class="lbl2"></td>
                <td class="sep"></td>
                <td class="val"></td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">B. Ekstrakurikuler</div>
    <div class="section-body">
        <p class="empty-state" style="padding:14px 0;">Belum ada data ekstrakurikuler.</p>
    </div>
</div>

<div class="section">
    <div class="section-title">C. Catatan Wali Kelas</div>
    <div class="section-body">
        <p class="empty-state" style="padding:14px 0;">Belum ada catatan wali kelas.</p>
    </div>
</div>

<div class="footer-page">
    Halaman 1 dari 2 &middot; Rapor Digital SMAN 7 Solo &middot; Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
</div>

<div class="page-break"></div>

{{-- ================================================================ --}}
{{-- HALAMAN 2: NILAI AKADEMIK + RINGKASAN + STATUS + TTD              --}}
{{-- ================================================================ --}}

<table class="minikop-table">
    <tr>
        <td class="left">
            <strong>{{ strtoupper(config('pdns.sekolah.nama_formal', 'SMA NEGERI 7 SURAKARTA')) }}</strong>
            &middot; Rapor Digital &middot; {{ $tahun_ajaran }} ({{ $semester }})
        </td>
        <td class="right">Halaman 2 dari 2</td>
    </tr>
</table>

<table class="identitas" style="margin-bottom: 12px; width: 100%;">
    <colgroup>
        <col style="width:18%;">
        <col style="width:2%;">
        <col style="width:30%;">
        <col style="width:18%;">
        <col style="width:2%;">
        <col style="width:30%;">
    </colgroup>
    <tr>
        <td class="lbl">Nama Siswa</td>
        <td class="sep">:</td>
        <td class="val">{{ strtoupper($siswa->nama_siswa) }}</td>
        <td class="lbl">Kelas</td>
        <td class="sep">:</td>
        <td class="val">{{ $siswa->kelas }}</td>
    </tr>
    <tr>
        <td class="lbl">NIS</td>
        <td class="sep">:</td>
        <td class="val">{{ $siswa->nis }}</td>
        <td class="lbl">Semester</td>
        <td class="sep">:</td>
        <td class="val">{{ $semester }}</td>
    </tr>
</table>

<div class="section">
    <div class="section-title">D. Nilai Akademik</div>
    <div class="section-body p0">
        @if(count($per_mapel) === 0)
            <p class="empty-state">Belum ada data nilai yang diinput untuk siswa ini.</p>
        @else
            <table class="nilai">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:4%;">No</th>
                        <th rowspan="2" style="width:26%;">Mata Pelajaran</th>
                        <th rowspan="2" style="width:18%;">Guru Pengajar</th>
                        <th rowspan="2" style="width:6%;">KKM</th>
                        <th colspan="3">Komponen Penilaian</th>
                        <th rowspan="2" style="width:9%;">Nilai<br>Akhir</th>
                        <th rowspan="2" style="width:14%;">Status<br>Kelulusan</th>
                    </tr>
                    <tr>
                        <th class="sub" style="width:7%;">Tugas<br>30%</th>
                        <th class="sub" style="width:7%;">UTS<br>30%</th>
                        <th class="sub" style="width:7%;">UAS<br>40%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($per_mapel as $i => $m)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="mapel">{{ $m['mapel'] }}</td>
                            <td class="center" style="font-size:9.5px;">{{ $m['nama_guru'] }}</td>
                            <td class="center" style="font-weight:600;">{{ number_format((float) $kkm, 0) }}</td>
                            <td class="nilai">{{ $m['tugas'] !== null ? number_format((float) $m['tugas'], 2) : '—' }}</td>
                            <td class="nilai">{{ $m['uts'] !== null ? number_format((float) $m['uts'], 2) : '—' }}</td>
                            <td class="nilai">{{ $m['uas'] !== null ? number_format((float) $m['uas'], 2) : '—' }}</td>
                            <td class="nilai akhir">
                                @if($m['akhir'] !== null)
                                    {{ number_format((float) $m['akhir'], 2) }}
                                @else
                                    <span class="empty-cell">—</span>
                                @endif
                            </td>
                            <td class="center">
                                @if($m['status'] === 'Lulus')
                                    <span class="badge-lulus">Lulus</span>
                                @elseif($m['status'] === 'Tidak Lulus')
                                    <span class="badge-tidak">Tidak Lulus</span>
                                @else
                                    <span class="empty-cell">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@if(count($per_mapel) > 0)
    <div class="section" style="margin-top: 10px;">
        <div class="section-title">E. Ringkasan Capaian</div>
        <div class="section-body p0">
            <table class="ringkasan">
                <tr>
                    <td>
                        <span class="num">{{ $jumlah_mapel }}</span>
                        <span class="lbl">Mata Pelajaran</span>
                    </td>
                    <td>
                        <span class="num">{{ $rata_rata !== null ? number_format((float) $rata_rata, 2) : '—' }}</span>
                        <span class="lbl">Rata-rata</span>
                    </td>
                    <td>
                        <span class="num success">{{ $lulus }}</span>
                        <span class="lbl">Lulus (&ge; KKM)</span>
                    </td>
                    <td>
                        <span class="num danger">{{ $tidak_lulus }}</span>
                        <span class="lbl">Tidak Lulus (&lt; KKM)</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="status-kenaikan">
        <span class="lbl">Keputusan:</span>
        <span class="val">{{ $tidak_lulus === 0 ? 'NAIK KE KELAS ' . $next_kelas : 'TINGGAL DI KELAS ' . $siswa->kelas }}</span>
    </div>
@endif

<table class="ttd">
    <tr>
        <td style="width: 33%;">
            <div class="lokasi">&nbsp;</div>
            <div class="jabatan">Orang Tua / Wali</div>
            <div class="ttd-pemisah"></div>
            <div class="nama">( ............................. )</div>
        </td>
        <td style="width: 34%;">
            <div class="lokasi">Surakarta, {{ $tanggal_cetak }}</div>
            <div class="jabatan">Wali Kelas</div>
            <div class="ttd-pemisah"></div>
            @if($wali_kelas && $wali_kelas !== '—')
                <div class="nama">{{ $wali_kelas }}</div>
                <div class="nip">NIP. {{ $nip_wali_kelas ?? '—' }}</div>
            @else
                <div class="nama">( ............................. )</div>
                <div class="nip">&nbsp;</div>
            @endif
        </td>
        <td style="width: 33%;">
            <div class="lokasi">&nbsp;</div>
            <div class="jabatan">Kepala Sekolah</div>
            <div class="ttd-pemisah"></div>
            <div class="nama">{{ $kepala_sekolah }}</div>
            <div class="nip">NIP. {{ $nip_kepsek }}</div>
        </td>
    </tr>
</table>

<div class="footer-page">
    Dokumen ini dicetak otomatis oleh Sistem Akademik SMAN 7 Solo pada {{ now()->translatedFormat('d F Y H:i') }} WIB.<br>
    Rapor digital ini sah dan dihasilkan langsung dari basis data sistem.
</div>

</body>
</html>
