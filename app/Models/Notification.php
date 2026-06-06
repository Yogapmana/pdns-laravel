<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eloquent model for an in-app notification row.
 *
 * Backed by the `notifications` table. Each row is a discrete event
 * surfaced to one user (e.g. "Nilai Matematika kelas X-A sudah Final").
 * The bell UI lists the most-recent 20 for the current user, marked as
 * read on click; the daily `notifications:cleanup` command hard-deletes
 * `read_at IS NOT NULL` rows older than 30 days.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $body
 * @property string|null $link
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'user_id',
    'type',
    'title',
    'body',
    'link',
    'read_at',
])]
class Notification extends Model
{
    public const UPDATED_AT = null;

    /**
     * Canonical notification type values. Used by the bell icon and the
     * dispatcher to choose the right icon/colour.
     */
    public const TYPE_NILAI_BELUM_DIINPUT = 'nilai_belum_diinput';

    public const TYPE_NILAI_MASIH_DRAFT = 'nilai_masih_draft';

    public const TYPE_NILAI_SUDAH_FINAL = 'nilai_sudah_final';

    public const TYPE_RAPOR_TERSEDIA = 'rapor_tersedia';

    public const TYPE_AKUN_DIUBAH = 'akun_diubah';

    public const TYPE_INFO = 'info';

    /**
     * Attribute casts applied to the underlying table columns.
     *
     * @return array<string, string> The cast definitions.
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The user this notification is addressed to.
     *
     * @return BelongsTo<User, Notification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether this row has been read by the recipient.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
