<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class RaporController extends Controller
{
    /**
     * Mengalirkan (stream) rapor pribadi siswa yang terautentikasi sebagai unduhan file PDF.
     *
     * Hanya baris nilai berstatus `Final` yang dimasukkan — nilai Draft disembunyikan
     * sampai guru mengunci nilai tersebut, sehingga rapor yang dicetak cocok dengan
     * apa yang dilihat siswa di halaman nilai.
     *
     * @return Response Respon unduhan dengan header `Content-Type: application/pdf` dan nama file per siswa.
     */
    public function pdf(): Response
    {
        $siswa = Siswa::with('kelas:id,nama')->where('user_id', auth()->id())->firstOrFail();

        $nilai = Nilai::with(['guru:id,nama_guru', 'mataPelajaran:id,nama'])
            ->where('nis', $siswa->nis)
            ->where('status_validasi', Nilai::STATUS_FINAL)
            ->get()
            ->sortBy(fn ($n) => $n->mataPelajaran?->nama ?? '')
            ->values();

        $perMapel = $nilai
            ->groupBy('mata_pelajaran_id')
            ->map(function ($items) {
                $item = $items->first();
                $nilaiAkhir = $item->nilai_akhir !== null ? (float) $item->nilai_akhir : null;
                $tugas = $item->nilai_tugas !== null ? (float) $item->nilai_tugas : null;
                $uts = $item->nilai_uts !== null ? (float) $item->nilai_uts : null;
                $uas = $item->nilai_uas !== null ? (float) $item->nilai_uas : null;

                return [
                    'mapel' => $item->mataPelajaran?->nama ?? '',
                    'nama_guru' => $item->guru?->nama_guru ?? '—',
                    'tugas' => $tugas,
                    'uts' => $uts,
                    'uas' => $uas,
                    'akhir' => $nilaiAkhir,
                    'status' => $item->status_lulus,
                    'status_validasi' => $item->status_validasi,
                ];
            })
            ->values()
            ->all();

        $nilaiAkhirValues = array_filter(array_column($perMapel, 'akhir'), fn ($v) => $v !== null);
        $jumlahMapel = count($perMapel);
        $lulus = collect($perMapel)->where('status', Nilai::LULUS)->count();
        $tidakLulus = collect($perMapel)->where('status', Nilai::TIDAK_LULUS)->count();
        $rataRata = count($nilaiAkhirValues) > 0
            ? round(array_sum($nilaiAkhirValues) / count($nilaiAkhirValues), 2)
            : null;

        $tahunAjaran = $this->guessTahunAjaran();
        $kelasName = $siswa->kelas?->nama ?? '—';

        $data = [
            'siswa' => $siswa,
            'kelas' => $kelasName,
            'per_mapel' => $perMapel,
            'jumlah_mapel' => $jumlahMapel,
            'lulus' => $lulus,
            'tidak_lulus' => $tidakLulus,
            'rata_rata' => $rataRata,
            'kkm' => Nilai::KKM,
            'tanggal_cetak' => now()->translatedFormat('d F Y'),
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $this->guessSemester(),
            'logo_base64' => $this->getLogoBase64(),
            'npsn' => config('pdns.sekolah.npsn', '20312345'),
            'alamat_sekolah' => config('pdns.sekolah.alamat', 'Jl. Ir. Sutami No. 17, Surakarta'),
            'kepala_sekolah' => config('pdns.sekolah.kepala', 'Drs. H. Kepala Sekolah, M.Pd.'),
            'nip_kepsek' => config('pdns.sekolah.nip_kepsek', '—'),
            'wali_kelas' => '—',
            'next_kelas' => $this->guessNextKelas($kelasName),
        ];

        $pdf = Pdf::loadView('reports.rapor-pdf', $data)->setPaper('a4', 'portrait');

        $filename = 'Rapor_'.str_replace(' ', '_', $siswa->nama_siswa).'_'.$siswa->nis.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Menebak string "tahun ajaran" saat ini (contoh: `2025/2026`) berdasarkan bulan saat ini.
     * Sekolah-sekolah di Indonesia biasanya memulai tahun ajaran baru di bulan Juli, sehingga
     * tanggal di paruh kedua tahun akan menggunakan tahun saat ini sebagai awal periode.
     *
     * @return string Tahun ajaran yang diformat (`YYYY/YYYY`).
     */
    private function guessTahunAjaran(): string
    {
        $now = now();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        if ($month >= 7) {
            return $year.'/'.($year + 1);
        }

        return ($year - 1).'/'.$year;
    }

    /**
     * Menebak semester yang sedang aktif (Ganjil = Jul-Jan, Genap = Feb-Jun).
     *
     * @return string Antara `Ganjil` atau `Genap`.
     */
    private function guessSemester(): string
    {
        $month = (int) now()->format('n');

        return $month >= 7 || $month <= 1 ? 'Ganjil' : 'Genap';
    }

    /**
     * Membaca file logo untuk ukuran rapor dan mengembalikan string base64 untuk disematkan
     * secara inline di PDF (menghindari masalah path file/lokal pada DomPDF).
     *
     * @return string|null Data base64, atau null jika file tidak ditemukan.
     */
    private function getLogoBase64(): ?string
    {
        $path = public_path('brand/logo-sman7-rapor.png');

        if (! is_file($path)) {
            return null;
        }

        return base64_encode(file_get_contents($path));
    }

    /**
     * Menghitung label kelas berikutnya (contoh: `X-A` -> `XI-A`, `XII-A` -> `LULUS`).
     *
     * @param  string|null  $kelas  Nama kelas saat ini.
     * @return string Label kelas berikutnya, atau `LULUS` untuk kelas 12.
     */
    private function guessNextKelas(?string $kelas): string
    {
        if (! is_string($kelas) || $kelas === '') {
            return '—';
        }

        $romanMap = [
            'X' => 'XI',
            'XI' => 'XII',
            'XII' => null,
        ];

        if (preg_match('/^([X]+|XI{0,2})\s*[-]?\s*(.*)$/u', $kelas, $m) === 1) {
            $current = strtoupper($m[1]);
            $suffix = $m[2] ?? '';

            if (! array_key_exists($current, $romanMap)) {
                return '—';
            }

            $next = $romanMap[$current];

            if ($next === null) {
                return 'LULUS';
            }

            return $next.($suffix !== '' ? '-'.$suffix : '');
        }

        return '—';
    }
}
