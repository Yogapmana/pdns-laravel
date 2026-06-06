<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

/**
 * Hard-delete notification rows that have been read AND are older than
 * 30 days. Unread rows are preserved indefinitely.
 *
 * Registered on the daily schedule in `routes/console.php`.
 */
class NotificationsCleanup extends Command
{
    /**
     * @var string The artisan signature for this command.
     */
    protected $signature = 'notifications:cleanup {--days=30 : Retention in days for read rows}';

    /**
     * @var string The human-readable description.
     */
    protected $description = 'Delete read notifications older than N days (default 30).';

    /**
     * Run the cleanup.
     *
     * @return int Self::SUCCESS when the command finishes, regardless of whether rows were deleted.
     */
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = Notification::whereNotNull('read_at')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} read notification(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
