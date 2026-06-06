<?php

declare(strict_types=1);

use App\Console\Commands\NotificationsCleanup;
use App\Console\Commands\NotificationsGenerateUninputed;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Daily notification maintenance.
|
| `notifications:cleanup` hard-deletes `notifications` rows that have
| been read (`read_at IS NOT NULL`) AND are older than 30 days. Unread
| rows are preserved indefinitely.
|
| `notifications:generate-uninputed` scans `guru_mengajar` combinations
| that still have no `Nilai` rows and pushes a `nilai_belum_diinput`
| reminder to the owning guru.
*/

Schedule::command(NotificationsCleanup::class)
    ->daily()
    ->at('00:05')
    ->name('notifications-cleanup')
    ->withoutOverlapping();

Schedule::command(NotificationsGenerateUninputed::class)
    ->daily()
    ->at('07:00')
    ->name('notifications-generate-uninputed')
    ->withoutOverlapping();
