<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Model Eloquent yang merepresentasikan siswa (`siswa`).
 *
 * Didukung oleh tabel `siswa`. Primary key berupa string `nis`
 * (Nomor Induk Siswa) — kolom ini juga menjadi route-model binding key.
 *
 * @property string $nis
 * @property int|null $user_id
 * @property string $nama_siswa
 * @property int|null $kelas_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nis', 'user_id', 'nama_siswa', 'kelas_id'])]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;

    protected $table = 'siswa';

    protected $primaryKey = 'nis';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Menggunakan `nis` untuk route-model binding (lihat definisi `Route::resource('siswa', ...)`).
     *
     * @return string Nama kunci rute (route key name).
     */
    public function getRouteKeyName(): string
    {
        return 'nis';
    }

    /**
     * Akun login yang terkait dengan siswa ini (1:1, opsional).
     *
     * @return BelongsTo<User, Siswa> Relasi ke model User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * `Kelas` tempat siswa ini terdaftar.
     *
     * @return BelongsTo<Kelas, Siswa> Relasi ke model Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Baris data `Nilai` yang dimiliki oleh siswa ini.
     *
     * @return HasMany<Nilai> Relasi ke model Nilai.
     */
    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'nis', 'nis');
    }

    /**
     * Accessor kemudahan: nama tampilan dari kelas siswa, atau
     * `null` jika siswa belum dimasukkan ke kelas manapun. Berguna untuk view
     * yang sebelumnya mengandalkan kolom string `kelas` yang sekarang sudah dihapus.
     */
    protected function getKelasNamaAttribute(): ?string
    {
        return $this->kelas?->nama;
    }
}
