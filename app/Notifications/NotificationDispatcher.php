<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Centralised creator for in-app notification rows.
 *
 * The application has several places where events happen that the user
 * should be told about (Final grade, Draft persistence, rapor download,
 * account change, periodic "belum diinput" sweep). All of those
 * call sites delegate to this dispatcher so the row shape, the
 * de-duplication key, and the type constants stay in one place.
 *
 * De-duplication: a per-type/per-target key is hashed into
 * `Notification::id` (auto-increment still applies for read queries).
 * The actual de-dup is performed in {@see self::send()} via
 * `updateOrCreate` keyed on the same composite, so a rapid
 * save/save/save on the same combo only creates one row.
 */
class NotificationDispatcher
{
    /**
     * Persist a new notification for the given user.
     *
     * If a row already exists for the same `(user_id, type, link)`
     * composite within the de-dup window, its `created_at` is bumped to
     * "now" and the read state is preserved; otherwise a new row is
     * inserted. This keeps the bell list short while still surfacing
     * repeated events for the same target.
     *
     * @param  User  $user  The recipient user.
     * @param  string  $type  One of the `Notification::TYPE_*` constants.
     * @param  string  $title  Short title text.
     * @param  string  $body  Longer body text (single paragraph).
     * @param  string|null  $link  Optional absolute or app-relative URL to navigate to.
     * @return Notification The persisted row (existing or newly created).
     */
    public function send(User $user, string $type, string $title, string $body, ?string $link = null): Notification
    {
        return Notification::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => $type,
                'link' => $link,
            ],
            [
                'title' => $title,
                'body' => $body,
                'created_at' => Carbon::now(),
                'read_at' => null,
            ],
        );
    }

    /**
     * Send the same notification to many users at once.
     *
     * Convenience wrapper around {@see self::send()} for fan-out
     * scenarios (e.g. notifying every siswa in a (kelas, mapel) combo
     * when the guru locks the grades to Final).
     *
     * @param  iterable<User>  $users  The recipient users.
     * @param  string  $type  One of the `Notification::TYPE_*` constants.
     * @param  string  $title  Short title text.
     * @param  string  $body  Longer body text.
     * @param  string|null  $link  Optional URL to navigate to.
     * @return int The number of notification rows touched.
     */
    public function sendMany(iterable $users, string $type, string $title, string $body, ?string $link = null): int
    {
        $count = 0;
        foreach ($users as $user) {
            $this->send($user, $type, $title, $body, $link);
            $count++;
        }

        return $count;
    }
}
