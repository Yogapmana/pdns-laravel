<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model representing a single nilai entry.
 *
 * Backed by the `nilai` table. Each row is the (tugas, UTS, UAS) tuple
 * for a single siswa in a single (kelas, mata_pelajaran) pair, input by
 * a single guru. The `status_lulus` and `status_validasi` flags control
 * whether the value is read-only (Final) and whether the siswa passed
 * (Lulus) per the KKM threshold.
 *
 * @property int $id
 * @property string $nis
 * @property int $id_guru
 * @property string $kelas
 * @property string $mata_pelajaran
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
    'kelas',
    'mata_pelajaran',
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
     * Attribute casts applied to the underlying table columns.
     *
     * @return array<string, string> The cast definitions.
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
     * Compute the weighted final score (Nilai Akhir) for a single siswa.
     *
     * Uses the configured `BOBOT_TUGAS` / `BOBOT_UTS` / `BOBOT_UAS` weights
     * and rounds the result to 2 decimal places.
     *
     * @param  float  $tugas  The tugas score (0-100).
     * @param  float  $uts  The UTS score (0-100).
     * @param  float  $uas  The UAS score (0-100).
     * @return float The computed nilai akhir, rounded to 2 decimals.
     */
    public static function hitungNilaiAkhir(float $tugas, float $uts, float $uas): float
    {
        return round(
            ($tugas * self::BOBOT_TUGAS) + ($uts * self::BOBOT_UTS) + ($uas * self::BOBOT_UAS),
            2
        );
    }

    /**
     * Resolve the pass/fail label for a given nilai akhir.
     *
     * @param  float  $nilaiAkhir  The final score, typically produced by `hitungNilaiAkhir()`.
     * @return string Either `LULUS` or `TIDAK_LULUS`, depending on the KKM threshold.
     */
    public static function tentukanKelulusan(float $nilaiAkhir): string
    {
        return $nilaiAkhir >= self::KKM ? self::LULUS : self::TIDAK_LULUS;
    }

    /**
     * Validate that a single component score is within the allowed 0-100 range.
     *
     * @param  float  $nilai  The score to validate.
     * @return bool `true` when 0 ≤ `$nilai` ≤ 100, `false` otherwise.
     */
    public static function validasiNilai(float $nilai): bool
    {
        return $nilai >= 0 && $nilai <= 100;
    }

    /**
     * The siswa this nilai row belongs to.
     *
     * @return BelongsTo<Siswa, Nilai>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nis', 'nis');
    }

    /**
     * The guru who input this nilai row.
     *
     * @return BelongsTo<Guru, Nilai>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }
}
