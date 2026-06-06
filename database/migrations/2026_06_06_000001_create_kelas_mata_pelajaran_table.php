<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_mata_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->string('kelas', 20);
            $table->string('mata_pelajaran', 100);
            $table->timestamps();

            $table->unique(['kelas', 'mata_pelajaran'], 'kelas_mata_pelajaran_unique');
            $table->index('kelas', 'kelas_mata_pelajaran_kelas_index');
            $table->index('mata_pelajaran', 'kelas_mata_pelajaran_mapel_index');
        });

        if (Schema::hasTable('guru_mengajar')) {
            $now = now();
            $rows = DB::table('guru_mengajar')
                ->select('kelas', 'mata_pelajaran')
                ->distinct()
                ->get();

            $payload = $rows->map(fn ($r) => [
                'kelas' => $r->kelas,
                'mata_pelajaran' => $r->mata_pelajaran,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($payload !== []) {
                DB::table('kelas_mata_pelajaran')->insertOrIgnore($payload);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_mata_pelajaran');
    }
};
