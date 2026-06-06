<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the `notifications` table backing the in-app notification
     * bell. A row is generated whenever a notification event fires
     * (Grade transitions, rapor download, account changes, periodic
     * "belum diinput" sweep). Rows are soft-flagged with `read_at` so
     * the user can see what is still pending; the daily
     * `notifications:cleanup` artisan command hard-deletes rows older
     * than 30 days that have been read.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('title');
            $table->text('body');
            $table->string('link', 255)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'read_at', 'created_at'], 'idx_user_unread_recent');
            $table->index(['user_id', 'created_at'], 'idx_user_recent');
        });
    }

    /**
     * Reverse the migration by dropping the table.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
