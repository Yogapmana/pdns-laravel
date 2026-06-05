<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->dropIndex(['mata_pelajaran']);
            $table->dropColumn('mata_pelajaran');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->string('kelas', 20)->nullable()->after('id_guru');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->index('nis', 'nilai_nis_fk_index');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropUnique('nilai_nis_mapel_unique');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->unique(['nis', 'id_guru', 'kelas', 'mata_pelajaran'], 'nilai_nis_guru_kelas_mapel_unique');
            $table->index(['kelas', 'mata_pelajaran']);
        });
    }

    public function down(): void
    {
        Schema::table('nilai', function (Blueprint $table) {
            $table->dropUnique('nilai_nis_guru_kelas_mapel_unique');
            $table->dropIndex(['kelas', 'mata_pelajaran']);
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropColumn('kelas');
            $table->dropIndex('nilai_nis_fk_index');
            $table->unique(['nis', 'mata_pelajaran'], 'nilai_nis_mapel_unique');
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->string('mata_pelajaran', 100)->index();
        });
    }
};
