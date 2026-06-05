<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru_mengajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_guru')->constrained('guru')->cascadeOnDelete();
            $table->string('kelas', 20);
            $table->string('mata_pelajaran', 100);
            $table->timestamps();

            $table->unique(['id_guru', 'kelas', 'mata_pelajaran'], 'guru_mengajar_unique');
            $table->index(['kelas', 'mata_pelajaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_mengajar');
    }
};
