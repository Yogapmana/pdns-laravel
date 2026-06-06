<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Notification;
use App\Models\Siswa;
use App\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;

/**
 * Generate `nilai_belum_diinput` notification rows for every (guru,
 * kelas, mata_pelajaran) combination that the guru has been assigned
 * to teach but for which no `Nilai` rows exist yet.
 *
 * Registered on the daily schedule in `routes/console.php`.
 *
 * The dispatcher's de-dup key `(user_id, type, link)` ensures a guru
 * only ever sees a single `nilai_belum_diinput` row per combo, even
 * if the command runs more than once per day (e.g. manually).
 */
class NotificationsGenerateUninputed extends Command
{
    /**
     * @var string The artisan signature for this command.
     */
    protected $signature = 'notifications:generate-uninputed';

    /**
     * @var string The human-readable description.
     */
    protected $description = 'Notify guru whose mengajar combinations have no Nilai rows yet.';

    /**
     * Run the daily sweep.
     */
    public function handle(NotificationDispatcher $dispatcher): int
    {
        $created = 0;

        GuruMengajar::with('guru.user')
            ->orderBy('id_guru')
            ->orderBy('kelas')
            ->orderBy('mata_pelajaran')
            ->chunk(100, function ($assignments) use ($dispatcher, &$created): void {
                foreach ($assignments as $assignment) {
                    $guru = $assignment->guru;

                    if (! $guru || ! $guru->user_id) {
                        continue;
                    }

                    $hasAnyNilai = Nilai::where('id_guru', $guru->id)
                        ->where('kelas', $assignment->kelas)
                        ->where('mata_pelajaran', $assignment->mata_pelajaran)
                        ->exists();

                    if ($hasAnyNilai) {
                        continue;
                    }

                    $jumlahSiswa = Siswa::where('kelas', $assignment->kelas)->count();

                    $title = 'Nilai belum diinput';
                    $body = $jumlahSiswa > 0
                        ? sprintf(
                            'Nilai %s untuk kelas %s (%d siswa) belum diinput. Mohon segera lengkapi sebelum akhir semester.',
                            $assignment->mata_pelajaran,
                            $assignment->kelas,
                            $jumlahSiswa,
                        )
                        : sprintf(
                            'Nilai %s untuk kelas %s belum diinput.',
                            $assignment->mata_pelajaran,
                            $assignment->kelas,
                        );

                    $link = '/guru/input-nilai?kelas='.urlencode($assignment->kelas)
                        .'&mata_pelajaran='.urlencode($assignment->mata_pelajaran);

                    $dispatcher->send(
                        $guru->user,
                        Notification::TYPE_NILAI_BELUM_DIINPUT,
                        $title,
                        $body,
                        $link,
                    );

                    $created++;
                }
            });

        $this->info("Generated {$created} 'nilai_belum_diinput' notification(s).");

        return self::SUCCESS;
    }
}
