<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return [
            'nilai_tugas' => 'decimal:2',
            'nilai_uts' => 'decimal:2',
            'nilai_uas' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
        ];
    }

    public static function hitungNilaiAkhir(float $tugas, float $uts, float $uas): float
    {
        return round(
            ($tugas * self::BOBOT_TUGAS) + ($uts * self::BOBOT_UTS) + ($uas * self::BOBOT_UAS),
            2
        );
    }

    public static function tentukanKelulusan(float $nilaiAkhir): string
    {
        return $nilaiAkhir >= self::KKM ? self::LULUS : self::TIDAK_LULUS;
    }

    public static function validasiNilai(float $nilai): bool
    {
        return $nilai >= 0 && $nilai <= 100;
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nis', 'nis');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }
}
