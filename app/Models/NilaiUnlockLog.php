<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit log row capturing a single admin "unlock" intervention on a
 * previously-Final `Nilai` group.
 *
 * Each row records the admin who performed the unlock, the targeted
 * (guru, kelas, mata_pelajaran) combination, the number of `Nilai` rows
 * that were reverted from `Final` to `Draft`, and the mandatory reason
 * provided at unlock time. The log is intentionally append-only: the
 * table has no `updated_at` column and the model disables update
 * timestamps.
 *
 * @property int $id
 * @property int $id_admin
 * @property int $id_guru
 * @property string $kelas
 * @property string $mata_pelajaran
 * @property int $affected_rows
 * @property string $reason
 * @property Carbon $created_at
 */
class NilaiUnlockLog extends Model
{
    protected $table = 'nilai_unlock_log';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_admin',
        'id_guru',
        'kelas',
        'mata_pelajaran',
        'affected_rows',
        'reason',
    ];

    /**
     * Attribute casts applied to the underlying table columns.
     *
     * @return array<string, string> The cast definitions.
     */
    protected function casts(): array
    {
        return [
            'affected_rows' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The admin (User) who performed the unlock.
     *
     * @return BelongsTo<User, NilaiUnlockLog>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin');
    }

    /**
     * The guru whose nilai group was unlocked.
     *
     * @return BelongsTo<Guru, NilaiUnlockLog>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }
}
