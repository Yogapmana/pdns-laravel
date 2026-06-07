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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            $this->normalizeSiswa();
            $this->normalizeGuruMengajar();
            $this->normalizeKelasMataPelajaran();
            $this->normalizeNilai();
            $this->normalizeNilaiUnlockLog();
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            $this->denormalizeNilaiUnlockLog();
            $this->denormalizeNilai();
            $this->denormalizeKelasMataPelajaran();
            $this->denormalizeGuruMengajar();
            $this->denormalizeSiswa();
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    private function normalizeSiswa(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('nama_siswa');
            $table->index('kelas_id', 'siswa_kelas_id_index');
        });

        $this->portableUpdateJoin('siswa', 'kelas_id', 'kelas', 'id', 'kelas', 'nama');

        $orphans = DB::table('siswa')->whereNull('kelas_id')->count();
        if ($orphans > 0) {
            throw new RuntimeException("Cannot normalize siswa: {$orphans} rows have kelas not present in master.");
        }

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropIndex('siswa_kelas_index');
            $table->dropColumn('kelas');
        });

        $this->setColumnNotNull('siswa', 'kelas_id');

        Schema::table('siswa', function (Blueprint $table) {
            $table->foreign('kelas_id')->references('id')->on('kelas')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    private function denormalizeSiswa(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropIndex('siswa_kelas_id_index');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->string('kelas', 20)->nullable()->after('nama_siswa');
        });

        $this->portableUpdateJoin('siswa', 'kelas', 'kelas', 'nama', 'kelas_id', 'id');

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('kelas_id');
            $table->index('kelas', 'siswa_kelas_index');
        });
    }

    private function normalizeGuruMengajar(): void
    {
        Schema::table('guru_mengajar', function (Blueprint $table) {
            $table->dropForeign(['id_guru']);
        });

        Schema::table('guru_mengajar', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('id_guru');
            $table->foreignId('mata_pelajaran_id')->nullable()->after('kelas_id');
            $table->index('kelas_id', 'guru_mengajar_kelas_id_index');
            $table->index('mata_pelajaran_id', 'guru_mengajar_mapel_id_index');
        });

        $this->portableUpdateJoin('guru_mengajar', 'kelas_id', 'kelas', 'id', 'kelas', 'nama');
        $this->portableUpdateJoin('guru_mengajar', 'mata_pelajaran_id', 'mata_pelajaran', 'id', 'mata_pelajaran', 'nama');

        $orphans = DB::table('guru_mengajar')->whereNull('kelas_id')->orWhereNull('mata_pelajaran_id')->count();
        if ($orphans > 0) {
            throw new RuntimeException("Cannot normalize guru_mengajar: {$orphans} rows have kelas/mata_pelajaran not in master.");
        }

        Schema::table('guru_mengajar', function (Blueprint $table) {
            $table->dropUnique('guru_mengajar_unique');
            $table->dropIndex('guru_mengajar_kelas_mata_pelajaran_index');
            $table->dropColumn(['kelas', 'mata_pelajaran']);
        });

        $this->setColumnNotNull('guru_mengajar', 'kelas_id');
        $this->setColumnNotNull('guru_mengajar', 'mata_pelajaran_id');

        Schema::table('guru_mengajar', function (Blueprint $table) {
            $table->unique(['id_guru', 'kelas_id', 'mata_pelajaran_id'], 'guru_mengajar_unique');
            $table->index(['kelas_id', 'mata_pelajaran_id'], 'guru_mengajar_kelas_mapel_id_index');
            $table->foreign('id_guru')->references('id')->on('guru')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('kelas_id')->references('id')->on('kelas')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('mata_pelajaran_id')->references('id')->on('mata_pelajaran')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    private function denormalizeGuruMengajar(): void
    {
        Schema::table('guru_mengajar', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropForeign(['mata_pelajaran_id']);
            $table->dropForeign(['id_guru']);
            $table->dropUnique('guru_mengajar_unique');
            $table->dropIndex('guru_mengajar_kelas_id_index');
            $table->dropIndex('guru_mengajar_mapel_id_index');
            $table->dropIndex('guru_mengajar_kelas_mapel_id_index');
        });

        Schema::table('guru_mengajar', function (Blueprint $table) {
            $table->string('kelas', 20)->nullable()->after('id_guru');
            $table->string('mata_pelajaran', 100)->nullable()->after('kelas');
        });

        $this->portableUpdateJoin('guru_mengajar', 'kelas', 'kelas', 'nama', 'kelas_id', 'id');
        $this->portableUpdateJoin('guru_mengajar', 'mata_pelajaran', 'mata_pelajaran', 'nama', 'mata_pelajaran_id', 'id');

        Schema::table('guru_mengajar', function (Blueprint $table) {
            $table->dropColumn(['kelas_id', 'mata_pelajaran_id']);
            $table->unique(['id_guru', 'kelas', 'mata_pelajaran'], 'guru_mengajar_unique');
            $table->index(['kelas', 'mata_pelajaran'], 'guru_mengajar_kelas_mata_pelajaran_index');
            $table->foreign('id_guru')->references('id')->on('guru')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    private function normalizeKelasMataPelajaran(): void
    {
        Schema::table('kelas_mata_pelajaran', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('id');
            $table->foreignId('mata_pelajaran_id')->nullable()->after('kelas_id');
        });

        $this->portableUpdateJoin('kelas_mata_pelajaran', 'kelas_id', 'kelas', 'id', 'kelas', 'nama');
        $this->portableUpdateJoin('kelas_mata_pelajaran', 'mata_pelajaran_id', 'mata_pelajaran', 'id', 'mata_pelajaran', 'nama');

        $orphans = DB::table('kelas_mata_pelajaran')->whereNull('kelas_id')->orWhereNull('mata_pelajaran_id')->count();
        if ($orphans > 0) {
            throw new RuntimeException("Cannot normalize kelas_mata_pelajaran: {$orphans} rows have kelas/mata_pelajaran not in master.");
        }

        Schema::table('kelas_mata_pelajaran', function (Blueprint $table) {
            $table->dropUnique('kelas_mata_pelajaran_unique');
            $table->dropIndex('kelas_mata_pelajaran_kelas_index');
            $table->dropIndex('kelas_mata_pelajaran_mapel_index');
            $table->dropColumn(['kelas', 'mata_pelajaran']);
        });

        $this->setColumnNotNull('kelas_mata_pelajaran', 'kelas_id');
        $this->setColumnNotNull('kelas_mata_pelajaran', 'mata_pelajaran_id');

        Schema::table('kelas_mata_pelajaran', function (Blueprint $table) {
            $table->unique(['kelas_id', 'mata_pelajaran_id'], 'kelas_mata_pelajaran_unique');
            $table->index('kelas_id', 'kelas_mata_pelajaran_kelas_index');
            $table->index('mata_pelajaran_id', 'kelas_mata_pelajaran_mapel_index');
            $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('mata_pelajaran_id')->references('id')->on('mata_pelajaran')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    private function denormalizeKelasMataPelajaran(): void
    {
        Schema::table('kelas_mata_pelajaran', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropForeign(['mata_pelajaran_id']);
            $table->dropUnique('kelas_mata_pelajaran_unique');
            $table->dropIndex('kelas_mata_pelajaran_kelas_index');
            $table->dropIndex('kelas_mata_pelajaran_mapel_index');
        });

        Schema::table('kelas_mata_pelajaran', function (Blueprint $table) {
            $table->string('kelas', 20)->nullable()->after('id');
            $table->string('mata_pelajaran', 100)->nullable()->after('kelas');
        });

        $this->portableUpdateJoin('kelas_mata_pelajaran', 'kelas', 'kelas', 'nama', 'kelas_id', 'id');
        $this->portableUpdateJoin('kelas_mata_pelajaran', 'mata_pelajaran', 'mata_pelajaran', 'nama', 'mata_pelajaran_id', 'id');

        Schema::table('kelas_mata_pelajaran', function (Blueprint $table) {
            $table->dropColumn(['kelas_id', 'mata_pelajaran_id']);
            $table->unique(['kelas', 'mata_pelajaran'], 'kelas_mata_pelajaran_unique');
            $table->index('kelas', 'kelas_mata_pelajaran_kelas_index');
            $table->index('mata_pelajaran', 'kelas_mata_pelajaran_mapel_index');
        });
    }

    private function normalizeNilai(): void
    {
        Schema::table('nilai', function (Blueprint $table) {
            $table->dropForeign(['nis']);
            $table->dropForeign(['id_guru']);
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropUnique('nilai_nis_guru_kelas_mapel_unique');
            $table->dropIndex('nilai_id_guru_mata_pelajaran_index');
            $table->dropIndex('nilai_kelas_mata_pelajaran_index');
            $table->dropIndex('nilai_nis_fk_index');
        });

        $this->dropColumnIfSupported('nilai', 'kelas');
        $this->dropColumnIfSupported('nilai', 'mata_pelajaran');

        Schema::table('nilai', function (Blueprint $table) {
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('mata_pelajaran_id');
            $table->index('kelas_id', 'nilai_kelas_id_index');
            $table->index('mata_pelajaran_id', 'nilai_mapel_id_index');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->unique(['nis', 'id_guru', 'kelas_id', 'mata_pelajaran_id'], 'nilai_nis_guru_kelas_mapel_unique');
            $table->foreign('nis')->references('nis')->on('siswa')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('id_guru')->references('id')->on('guru')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('kelas_id')->references('id')->on('kelas')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('mata_pelajaran_id')->references('id')->on('mata_pelajaran')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    private function denormalizeNilai(): void
    {
        Schema::table('nilai', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropForeign(['mata_pelajaran_id']);
            $table->dropForeign(['nis']);
            $table->dropForeign(['id_guru']);
            $table->dropUnique('nilai_nis_guru_kelas_mapel_unique');
            $table->dropIndex('nilai_kelas_id_index');
            $table->dropIndex('nilai_mapel_id_index');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropColumn(['kelas_id', 'mata_pelajaran_id']);
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->string('kelas', 20)->nullable()->after('id_guru');
            $table->string('mata_pelajaran', 100)->after('kelas');
            $table->index('nis', 'nilai_nis_fk_index');
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->unique(['nis', 'id_guru', 'kelas', 'mata_pelajaran'], 'nilai_nis_guru_kelas_mapel_unique');
            $table->index(['id_guru', 'mata_pelajaran'], 'nilai_id_guru_mata_pelajaran_index');
            $table->index(['kelas', 'mata_pelajaran'], 'nilai_kelas_mata_pelajaran_index');
            $table->foreign('nis')->references('nis')->on('siswa')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('id_guru')->references('id')->on('guru')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    private function normalizeNilaiUnlockLog(): void
    {
        Schema::table('nilai_unlock_log', function (Blueprint $table) {
            $table->dropForeign(['id_admin']);
            $table->dropForeign(['id_guru']);
        });

        Schema::table('nilai_unlock_log', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('id_guru');
            $table->foreignId('mata_pelajaran_id')->nullable()->after('kelas_id');
        });

        $this->portableUpdateJoin('nilai_unlock_log', 'kelas_id', 'kelas', 'id', 'kelas', 'nama');
        $this->portableUpdateJoin('nilai_unlock_log', 'mata_pelajaran_id', 'mata_pelajaran', 'id', 'mata_pelajaran', 'nama');

        $orphans = DB::table('nilai_unlock_log')->whereNull('kelas_id')->orWhereNull('mata_pelajaran_id')->count();
        if ($orphans > 0) {
            throw new RuntimeException("Cannot normalize nilai_unlock_log: {$orphans} rows have kelas/mata_pelajaran not in master.");
        }

        Schema::table('nilai_unlock_log', function (Blueprint $table) {
            $table->dropIndex('nilai_unlock_log_id_guru_kelas_mata_pelajaran_index');
            $table->dropColumn(['kelas', 'mata_pelajaran']);
        });

        $this->setColumnNotNull('nilai_unlock_log', 'kelas_id');
        $this->setColumnNotNull('nilai_unlock_log', 'mata_pelajaran_id');

        Schema::table('nilai_unlock_log', function (Blueprint $table) {
            $table->index(['id_guru', 'kelas_id', 'mata_pelajaran_id'], 'nilai_unlock_log_id_guru_kelas_mapel_index');
            $table->foreign('id_admin')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('id_guru')->references('id')->on('guru')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('kelas_id')->references('id')->on('kelas')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('mata_pelajaran_id')->references('id')->on('mata_pelajaran')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    private function denormalizeNilaiUnlockLog(): void
    {
        Schema::table('nilai_unlock_log', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropForeign(['mata_pelajaran_id']);
            $table->dropIndex('nilai_unlock_log_id_guru_kelas_mapel_index');
        });

        Schema::table('nilai_unlock_log', function (Blueprint $table) {
            $table->string('kelas', 20)->nullable()->after('id_guru');
            $table->string('mata_pelajaran', 100)->nullable()->after('kelas');
        });

        $this->portableUpdateJoin('nilai_unlock_log', 'kelas', 'kelas', 'nama', 'kelas_id', 'id');
        $this->portableUpdateJoin('nilai_unlock_log', 'mata_pelajaran', 'mata_pelajaran', 'nama', 'mata_pelajaran_id', 'id');

        Schema::table('nilai_unlock_log', function (Blueprint $table) {
            $table->dropColumn(['kelas_id', 'mata_pelajaran_id']);
            $table->index(['id_guru', 'kelas', 'mata_pelajaran'], 'nilai_unlock_log_id_guru_kelas_mata_pelajaran_index');
        });
    }

    private function dropColumnIfSupported(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $columns = array_map(
                fn ($c) => $c->name,
                DB::select("PRAGMA table_info({$table})"),
            );
            if (! in_array($column, $columns, true)) {
                return;
            }

            DB::statement("ALTER TABLE {$table} DROP COLUMN {$column}");

            return;
        }

        Schema::table($table, function (Blueprint $t) use ($column) {
            $t->dropColumn($column);
        });
    }

    private function setColumnNotNull(string $table, string $column): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NOT NULL");

            return;
        }

        if ($driver === 'sqlite') {
            $info = collect(DB::select("PRAGMA table_info({$table})"))
                ->firstWhere('name', $column);

            if ($info !== null && (int) $info->notnull === 0) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET NOT NULL");
            }
        }
    }

    /**
     * Driver-portable UPDATE-with-join backfill.
     *
     * MySQL: `UPDATE t1 JOIN t2 ON ... SET t1.col = t2.col`
     * SQLite: `UPDATE t1 SET col = (SELECT t2.col FROM t2 WHERE t2.key = t1.key)`
     *
     * @param  string  $table  The table to update.
     * @param  string  $setColumn  The column on `$table` to set.
     * @param  string  $referenceTable  The source join table.
     * @param  string  $sourceColumn  The column on `$referenceTable` to copy.
     * @param  string  $tableKey  The join key column on `$table` (string value being matched).
     * @param  string  $referenceKey  The join key column on `$referenceTable` (string column being matched).
     */
    private function portableUpdateJoin(
        string $table,
        string $setColumn,
        string $referenceTable,
        string $sourceColumn,
        string $tableKey,
        string $referenceKey,
    ): void {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "UPDATE {$table} JOIN {$referenceTable} ON {$referenceTable}.{$referenceKey} = {$table}.{$tableKey} "
                ."SET {$table}.{$setColumn} = {$referenceTable}.{$sourceColumn}"
            );

            return;
        }

        DB::statement(
            "UPDATE {$table} SET {$setColumn} = ("
            ."SELECT {$sourceColumn} FROM {$referenceTable} WHERE {$referenceTable}.{$referenceKey} = {$table}.{$tableKey} LIMIT 1"
           .") WHERE EXISTS (SELECT 1 FROM {$referenceTable} WHERE {$referenceTable}.{$referenceKey} = {$table}.{$tableKey})"
        );
    }
};
