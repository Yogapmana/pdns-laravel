<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Baris log audit yang mencatat satu intervensi "unlock" (pembukaan kunci) admin pada
 * kelompok `Nilai` yang sebelumnya sudah berstatus Final.
 *
 * Setiap baris mencatat admin yang melakukan unlock, kombinasi target (guru, kelas, mata_pelajaran),
 * jumlah baris `Nilai` yang dikembalikan dari `Final` ke `Draft`, dan alasan wajib yang
 * diberikan saat proses unlock. Log ini sengaja dibuat bersifat append-only (hanya tambah):
 * tabel tidak memiliki kolom `updated_at` dan model menonaktifkan timestamp pembaruan.
 *
 * @property int $id
 * @property int $id_admin
 * @property int $id_guru
 * @property int $kelas_id
 * @property int $mata_pelajaran_id
 * @property int $affected_rows
 * @property string $reason
 * @property Carbon $created_at
 */
class NilaiUnlockLog extends Model
{
    protected $table = 'nilai_unlock_log';

    public const UPDATED_AT = null;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_admin',
        'id_guru',
        'kelas_id',
        'mata_pelajaran_id',
        'affected_rows',
        'reason',
    ];

    /**
     * Cast atribut yang diterapkan pada kolom tabel database.
     *
     * @return array<string, string> Definisi cast atribut.
     */
    protected function casts(): array
    {
        return [
            'affected_rows' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Admin (User) yang melakukan unlock.
     *
     * @return BelongsTo<User, NilaiUnlockLog> Relasi ke model User (Admin).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin');
    }

    /**
     * Guru yang kelompok nilainya di-unlock.
     *
     * @return BelongsTo<Guru, NilaiUnlockLog> Relasi ke model Guru.
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    /**
     * Kelas yang ditargetkan oleh proses unlock ini.
     *
     * @return BelongsTo<Kelas, NilaiUnlockLog> Relasi ke model Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Mata pelajaran yang ditargetkan oleh proses unlock ini.
     *
     * @return BelongsTo<MataPelajaran, NilaiUnlockLog> Relasi ke model MataPelajaran.
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
