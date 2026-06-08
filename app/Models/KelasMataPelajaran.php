<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent yang merepresentasikan keanggotaan satu pasangan
 * (kelas, mata_pelajaran) di tabel pivot `kelas_mata_pelajaran`.
 *
 * Tabel ini menyimpan daftar master mengenai "mata pelajaran apa saja yang valid untuk kelas
 * mana". Satu baris data di tabel ini diperlukan sebelum admin dapat menugaskan guru ke
 * pasangan (kelas, mata_pelajaran) tersebut di tabel `guru_mengajar`. Kolom `kelas_id`
 * dan `mata_pelajaran_id` merupakan Foreign Key ke tabel master `kelas` dan
 * `mata_pelajaran` dengan `ON DELETE CASCADE` sehingga penghapusan master akan
 * secara otomatis membersihkan entri pivot-nya.
 *
 * @property int $id
 * @property int $kelas_id
 * @property int $mata_pelajaran_id
 */
#[Fillable(['kelas_id', 'mata_pelajaran_id'])]
class KelasMataPelajaran extends Model
{
    protected $table = 'kelas_mata_pelajaran';

    /**
     * Kelas tempat baris pivot ini bernaung.
     *
     * @return BelongsTo<Kelas, KelasMataPelajaran> Relasi ke model Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Mata pelajaran tempat baris pivot ini bernaung.
     *
     * @return BelongsTo<MataPelajaran, KelasMataPelajaran> Relasi ke model MataPelajaran.
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
