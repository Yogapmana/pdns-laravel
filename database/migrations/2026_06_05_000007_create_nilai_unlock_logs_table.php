<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_unlock_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_admin')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_guru')->constrained('guru')->restrictOnDelete();
            $table->string('kelas', 20);
            $table->string('mata_pelajaran', 100);
            $table->unsignedInteger('affected_rows')->default(0);
            $table->text('reason');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_guru', 'kelas', 'mata_pelajaran']);
            $table->index('id_admin');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_unlock_log');
    }
};
