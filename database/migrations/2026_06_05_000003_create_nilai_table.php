<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20);
            $table->unsignedBigInteger('id_guru');
            $table->string('mata_pelajaran', 100);
            $table->decimal('nilai_tugas', 5, 2)->nullable();
            $table->decimal('nilai_uts', 5, 2)->nullable();
            $table->decimal('nilai_uas', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->enum('status_lulus', ['Lulus', 'Tidak Lulus'])->nullable();
            $table->enum('status_validasi', ['Draft', 'Final'])->default('Draft');
            $table->timestamps();

            $table->foreign('nis')
                ->references('nis')->on('siswa')
                ->cascadeOnDelete();

            $table->foreign('id_guru')
                ->references('id')->on('guru')
                ->restrictOnDelete();

            $table->unique(['nis', 'mata_pelajaran'], 'nilai_nis_mapel_unique');
            $table->index(['id_guru', 'mata_pelajaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai');
    }
};
