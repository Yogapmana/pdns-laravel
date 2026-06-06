<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model representing the membership of a single
 * (kelas, mata_pelajaran) pair in the `kelas_mata_pelajaran` pivot table.
 *
 * The table holds the master list of "which subjects are valid for which
 * class". A row here is required before an admin can assign a guru to
 * that (kelas, mata_pelajaran) pair in `guru_mengajar`. The `kelas` and
 * `mata_pelajaran` columns are non-FK string references matching the
 * `kelas.nama` and `mata_pelajaran.nama` primary display keys, in line
 * with the rest of the schema.
 *
 * @property int $id
 * @property string $kelas
 * @property string $mata_pelajaran
 */
#[Fillable(['kelas', 'mata_pelajaran'])]
class KelasMataPelajaran extends Model
{
    protected $table = 'kelas_mata_pelajaran';
}
