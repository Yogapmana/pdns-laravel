<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Notification;
use App\Models\Siswa;
use App\Notifications\NotificationDispatcher;

/**
 * Observer that translates `Nilai` save events into user-facing
 * notification rows.
 *
 * Two transition types are handled:
 *   1. `updated` where `status_validasi` goes Draft → Final
 *      → notify every siswa with a `Nilai` row in that combo.
 *   2. `saved` (any write) where the guru has at least one row that is
 *      still in Draft → notify the guru of that combo so they know it
 *      is ready to be validated to Final.
 *
 * Save events that do not match either pattern are intentionally
 * ignored (avoids notification spam on plain numeric edits).
 */
class GradeObserver
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * Fire a `Nilai::updated` listener. Detects the Draft→Final
     * transition per-row and notifies every siswa in the combo.
     */
    public function updated(Nilai $nilai): void
    {
        if (! $this->isDraftToFinalTransition($nilai)) {
            return;
        }

        $this->notifyStudentsFinal($nilai);
    }

    /**
     * Fire a `Nilai::saved` listener. After the write, check whether
     * the (guru, kelas, mata_pelajaran) combo still has any Draft rows
     * and, if so, push a `nilai_masih_draft` reminder to the owning
     * guru (deduplicated by composite key).
     */
    public function saved(Nilai $nilai): void
    {
        $this->notifyGuruDraft($nilai);
    }

    /**
     * @return bool `true` when `status_validasi` changed from Draft to Final on this row.
     */
    private function isDraftToFinalTransition(Nilai $nilai): bool
    {
        $original = $nilai->getOriginal('status_validasi');
        $current = $nilai->status_validasi;

        return $original === Nilai::STATUS_DRAFT
            && $current === Nilai::STATUS_FINAL;
    }

    /**
     * Notify every siswa enrolled in the (kelas, mata_pelajaran) combo
     * of this Nilai row that their grade is now final.
     */
    private function notifyStudentsFinal(Nilai $nilai): void
    {
        $combo = $this->comboKey($nilai);

        $siswaList = Siswa::where('kelas', $nilai->kelas)
            ->whereNotNull('user_id')
            ->get(['nis', 'user_id']);

        if ($siswaList->isEmpty()) {
            return;
        }

        $title = 'Nilai '.$nilai->mata_pelajaran.' sudah Final';
        $body = sprintf(
            'Nilai %s kelas %s telah divalidasi oleh guru. Silakan cek halaman Nilai Saya.',
            $nilai->mata_pelajaran,
            $nilai->kelas,
        );
        $link = '/siswa/nilai';

        $users = $siswaList->map(fn (Siswa $s) => $s->user)->filter();

        if ($users->isEmpty()) {
            return;
        }

        $this->dispatcher->sendMany(
            $users,
            Notification::TYPE_NILAI_SUDAH_FINAL,
            $title,
            $body,
            $link.'?combo='.urlencode($combo),
        );
    }

    /**
     * Push a `nilai_masih_draft` reminder to the owning guru when
     * their combo still has any Draft row. Skips when all rows are
     * already Final (or the guru has no user account yet).
     */
    private function notifyGuruDraft(Nilai $nilai): void
    {
        $guru = Guru::with('user')->find($nilai->id_guru);

        if (! $guru || ! $guru->user) {
            return;
        }

        $hasDraft = Nilai::where('id_guru', $guru->id)
            ->where('kelas', $nilai->kelas)
            ->where('mata_pelajaran', $nilai->mata_pelajaran)
            ->where('status_validasi', Nilai::STATUS_DRAFT)
            ->exists();

        if (! $hasDraft) {
            return;
        }

        $title = 'Nilai masih Draft';
        $body = sprintf(
            'Semua nilai %s kelas %s sudah diinput namun masih berstatus Draft. Klik tombol Validasi Final untuk mengunci.',
            $nilai->mata_pelajaran,
            $nilai->kelas,
        );
        $link = '/guru/input-nilai?kelas='.urlencode((string) $nilai->kelas)
            .'&mata_pelajaran='.urlencode((string) $nilai->mata_pelajaran);

        $this->dispatcher->send(
            $guru->user,
            Notification::TYPE_NILAI_MASIH_DRAFT,
            $title,
            $body,
            $link,
        );
    }

    /**
     * Build a deterministic per-combo string used in the `link` field
     * so the dedup composite `(user_id, type, link)` works for
     * student notifications too.
     */
    private function comboKey(Nilai $nilai): string
    {
        return $nilai->kelas.'|'.$nilai->mata_pelajaran;
    }
}
