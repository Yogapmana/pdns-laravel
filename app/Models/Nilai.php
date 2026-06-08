<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent yang merepresentasikan satu entri nilai.
 *
 * Didukung oleh tabel `nilai`. Setiap baris data berisi tuple (tugas, UTS, UAS)
 * untuk seorang siswa dalam pasangan (kelas, mata_pelajaran) tertentu, yang diinput oleh
 * seorang guru. Flag `status_lulus` dan `status_validasi` mengontrol apakah
 * nilai tersebut bersifat read-only (Final) dan apakah siswa tersebut lulus
 * (Lulus) berdasarkan ambang batas KKM.
 *
 * @property int $id
 * @property string $nis
 * @property int $id_guru
 * @property int $kelas_id
 * @property int $mata_pelajaran_id
 * @property string|null $nilai_tugas
 * @property string|null $nilai_uts
 * @property string|null $nilai_uas
 * @property string|null $nilai_akhir
 * @property string|null $status_lulus
 * @property string $status_validasi
 */
#[Fillable([
    'nis',
    'id_guru',
    'kelas_id',
    'mata_pelajaran_id',
    'nilai_tugas',
    'nilai_uts',
    'nilai_uas',
    'nilai_akhir',
    'status_lulus',
    'status_validasi',
])]
class Nilai extends Model
{
    protected $table = 'nilai';

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_FINAL = 'Final';

    public const LULUS = 'Lulus';

    public const TIDAK_LULUS = 'Tidak Lulus';

    public const BOBOT_TUGAS = 0.30;

    public const BOBOT_UTS = 0.30;

    public const BOBOT_UAS = 0.40;

    public const KKM = 70.0;

    /**
     * Cast atribut yang diterapkan pada kolom tabel database.
     *
     * @return array<string, string> Definisi cast atribut.
     */
    protected function casts(): array
    {
        return [
            'nilai_tugas' => 'decimal:2',
            'nilai_uts' => 'decimal:2',
            'nilai_uas' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
        ];
    }

    /**
     * Menghitung nilai akhir berbobot untuk seorang siswa.
     *
     * Menggunakan bobot konfigurasi `BOBOT_TUGAS` / `BOBOT_UTS` / `BOBOT_UAS`
     * dan membulatkan hasilnya hingga 2 tempat desimal.
     *
     * @param  float  $tugas  Nilai tugas (0-100).
     * @param  float  $uts  Nilai UTS (0-100).
     * @param  float  $uas  Nilai UAS (0-100).
     * @return float Nilai akhir yang dihitung, dibulatkan ke 2 desimal.
     */
    public static function hitungNilaiAkhir(float $tugas, float $uts, float $uas): float
    {
        return round(
            ($tugas * self::BOBOT_TUGAS) + ($uts * self::BOBOT_UTS) + ($uas * self::BOBOT_UAS),
            2
        );
    }

    /**
     * Menentukan label kelulusan (lulus/tidak lulus) berdasarkan nilai akhir.
     *
     * @param  float  $nilaiAkhir  Nilai akhir, biasanya didapat dari `hitungNilaiAkhir()`.
     * @return string Antara `LULUS` atau `TIDAK_LULUS`, tergantung ambang batas KKM.
     */
    public static function tentukanKelulusan(float $nilaiAkhir): string
    {
        return $nilaiAkhir >= self::KKM ? self::LULUS : self::TIDAK_LULUS;
    }

    /**
     * Memvalidasi apakah skor komponen berada di rentang 0-100 yang diperbolehkan.
     *
     * @param  float  $nilai  Nilai yang akan divalidasi.
     * @return bool `true` jika 0 ≤ `$nilai` ≤ 100, sebaliknya `false`.
     */
    public static function validasiNilai(float $nilai): bool
    {
        return $nilai >= 0 && $nilai <= 100;
    }

    /**
     * Siswa pemilik baris nilai ini.
     *
     * @return BelongsTo<Siswa, Nilai> Relasi ke model Siswa.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nis', 'nis');
    }

    /**
     * Guru yang menginput baris nilai ini.
     *
     * @return BelongsTo<Guru, Nilai> Relasi ke model Guru.
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    /**
     * Kelas tempat baris nilai ini bernaung.
     *
     * @return BelongsTo<Kelas, Nilai> Relasi ke model Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Mata pelajaran tempat baris nilai ini bernaung.
     *
     * @return BelongsTo<MataPelajaran, Nilai> Relasi ke model MataPelajaran.
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
