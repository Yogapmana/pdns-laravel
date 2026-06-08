<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Nilai {{ implode(', ', $kelas_list) }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 10px; color: #000; }
        
        /* Kop Surat */
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 15px; }
        .kop-surat h1 { margin: 0; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .kop-surat h2 { margin: 2px 0 0; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .kop-surat p { margin: 3px 0 0; font-size: 11px; }
        
        /* Title */
        .title { text-align: center; font-size: 14px; font-weight: bold; text-decoration: underline; margin-bottom: 15px; }
        
        /* Info Table */
        .info-table { width: 100%; margin-bottom: 12px; font-size: 11px; }
        .info-table td { border: none; padding: 2px 0; vertical-align: top; }
        
        /* Data Table */
        table.data-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        table.data-table th { background-color: #f3f4f6; font-weight: bold; text-transform: uppercase; }
        .nis { text-align: center; }
        .nama { text-align: left; padding-left: 5px !important; }
        .text-danger { color: #000; font-weight: bold; text-decoration: underline; } /* Formal style usually just underlines or bolds failing grades */
        
        /* Signature */
        .signature-section { margin-top: 30px; width: 100%; }
        .signature-box { float: right; width: 250px; text-align: center; font-size: 11px; }
        .signature-box .date { margin-bottom: 60px; }
        .signature-box .name { font-weight: bold; text-decoration: underline; }
        
        /* Utilities */
        .page-break { page-break-after: always; }
        .clear { clear: both; }
    </style>
</head>
<body>

    @if(count($sections) === 0)
        <div class="kop-surat">
            <h1>PEMERINTAH PROVINSI JAWA TENGAH<br>DINAS PENDIDIKAN DAN KEBUDAYAAN</h1>
            <h2>SMA NEGERI 7 SURAKARTA</h2>
            <p>Jl. Mr. Muh. Yamin No.79, Tipes, Kec. Serengan, Kota Surakarta, Jawa Tengah 57154</p>
            <p>Telepon: (0271) 716441 | Email: info@sman7ska.sch.id</p>
        </div>
        <div class="title">DAFTAR KUMPULAN NILAI (DKN)</div>
        <p style="text-align: center; color: #6b7280; padding: 24px; font-size: 12px;">Tidak ada data nilai untuk filter yang dipilih.</p>
    @endif

    @foreach($sections as $index => $section)
        <div class="kop-surat">
            <h1>PEMERINTAH PROVINSI JAWA TENGAH<br>DINAS PENDIDIKAN DAN KEBUDAYAAN</h1>
            <h2>SMA NEGERI 7 SURAKARTA</h2>
            <p>Jl. Mr. Muh. Yamin No.79, Tipes, Kec. Serengan, Kota Surakarta, Jawa Tengah 57154</p>
            <p>Telepon: (0271) 716441 | Email: info@sman7ska.sch.id</p>
        </div>

        <div class="title">DAFTAR KUMPULAN NILAI (DKN)</div>

        <table class="info-table">
            <tr>
                <td style="width: 12%;"><strong>Kelas</strong></td>
                <td style="width: 2%;">:</td>
                <td style="width: 36%;">{{ $section['kelas'] }}</td>
                <td style="width: 15%;"><strong>Tahun Pelajaran</strong></td>
                <td style="width: 2%;">:</td>
                <td style="width: 33%;">{{ date('Y') - 1 }}/{{ date('Y') }}</td>
            </tr>
            <tr>
                <td><strong>Jumlah Siswa</strong></td>
                <td>:</td>
                <td>{{ $section['stats']['jumlah_siswa'] }} Orang (Lulus: {{ $section['stats']['jumlah_lulus'] }}, Tidak Lulus: {{ $section['stats']['jumlah_tidak_lulus'] }})</td>
                <td><strong>Semester</strong></td>
                <td>:</td>
                <td>Genap</td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">NO</th>
                    <th rowspan="2" style="width: 6%;">NIS</th>
                    <th rowspan="2" style="width: 18%;">NAMA SISWA</th>
                    @foreach($section['mapel_list'] as $mapel)
                        <th colspan="4">{{ $mapel }}</th>
                    @endforeach
                    <th rowspan="2" style="width: 5%;">RATA-<br>RATA</th>
                </tr>
                <tr>
                    @foreach($section['mapel_list'] as $mapel)
                        <th style="width: 3%;">TGS</th>
                        <th style="width: 3%;">UTS</th>
                        <th style="width: 3%;">UAS</th>
                        <th style="width: 4%;">AKHIR</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($section['rows'] as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="nis">{{ $row['siswa']->nis }}</td>
                        <td class="nama">{{ $row['siswa']->nama_siswa }}</td>
                        @foreach($section['mapel_list'] as $mapel)
                            @php 
                                $n = $row['nilai_per_mapel'][$mapel] ?? null; 
                                $isLulus = $n && $n->status_lulus === 'Lulus';
                            @endphp
                            <td>{{ $n?->nilai_tugas ?? '-' }}</td>
                            <td>{{ $n?->nilai_uts ?? '-' }}</td>
                            <td>{{ $n?->nilai_uas ?? '-' }}</td>
                            <td class="{{ $n && !$isLulus ? 'text-danger' : '' }}">
                                {{ $n?->nilai_akhir ?? '-' }}
                            </td>
                        @endforeach
                        <td><strong>{{ $row['rata_rata'] ?? '-' }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature-section">
            <div class="signature-box">
                <div class="date">Surakarta, {{ $tanggal_cetak }}<br>Kepala Sekolah</div>
                <div class="name">Drs. H. Sukamto, M.Pd.</div>
                <div>NIP. 19680715 199512 1 002</div>
            </div>
            <div class="clear"></div>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
