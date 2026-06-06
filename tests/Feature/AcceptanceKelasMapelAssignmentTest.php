<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\KelasMataPelajaran;
use App\Models\MataPelajaran;
use App\Models\User;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

test('AC-KM-01: Create kelas baru bisa sekaligus menentukan mapel yang diizinkan', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/kelas', [
        'nama' => 'X-MIPA-1',
        'mata_pelajaran' => ['Matematika', 'Bahasa Indonesia', 'IPA', 'Bahasa Inggris'],
    ]);

    $response->assertRedirect('/admin/kelas');
    $response->assertSessionHas('success');

    $kelas = Kelas::where('nama', 'X-MIPA-1')->first();
    expect($kelas)->not->toBeNull();
    expect($kelas->mataPelajaran->pluck('nama')->sort()->values()->all())
        ->toBe(['Bahasa Indonesia', 'Bahasa Inggris', 'IPA', 'Matematika']);
});

test('AC-KM-02: Create kelas tanpa mapel tetap berhasil tapi tidak punya mapel diizinkan', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/kelas', ['nama' => 'XII-C'])
        ->assertRedirect('/admin/kelas');

    $kelas = Kelas::where('nama', 'XII-C')->first();
    expect($kelas)->not->toBeNull();
    expect($kelas->mataPelajaran)->toHaveCount(0);
});

test('AC-KM-03: Edit kelas bisa menambah mapel baru', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'X-A']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->actingAs($admin)->put("/admin/kelas/{$kelas->id}", [
        'nama' => 'X-A',
        'mata_pelajaran' => ['Matematika', 'Bahasa Indonesia'],
    ])->assertRedirect('/admin/kelas');

    expect($kelas->fresh()->mataPelajaran->pluck('nama')->sort()->values()->all())
        ->toBe(['Bahasa Indonesia', 'Matematika']);
});

test('AC-KM-04: Edit kelas tanpa kirim field mata_pelajaran tidak mengubah mapel', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'X-A']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'IPA']);

    $this->actingAs($admin)->put("/admin/kelas/{$kelas->id}", [
        'nama' => 'X-A',
    ])->assertRedirect('/admin/kelas');

    expect($kelas->fresh()->mataPelajaran->pluck('nama')->sort()->values()->all())
        ->toBe(['IPA', 'Matematika']);
});

test('AC-KM-05: Edit kelas dengan array kosong menghapus semua mapel', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'X-A']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->actingAs($admin)->put("/admin/kelas/{$kelas->id}", [
        'nama' => 'X-A',
        'mata_pelajaran' => [],
    ])->assertRedirect('/admin/kelas');

    expect($kelas->fresh()->mataPelajaran)->toHaveCount(0);
});

test('AC-KM-06: Mapel yang dikirim harus ada di master mata_pelajaran', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/kelas', [
        'nama' => 'X-MIPA-2',
        'mata_pelajaran' => ['Matematika', 'MAPEL-FAKTA-99'],
    ])->assertSessionHasErrors('mata_pelajaran.1');

    expect(Kelas::where('nama', 'X-MIPA-2')->count())->toBe(0);
});

test('AC-KM-07: Hapus kelas juga menghapus baris kelas_mata_pelajaran terkait', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'XII-C']);
    KelasMataPelajaran::create(['kelas' => 'XII-C', 'mata_pelajaran' => 'Matematika']);

    expect(KelasMataPelajaran::where('kelas', 'XII-C')->count())->toBe(1);

    $this->actingAs($admin)->delete("/admin/kelas/{$kelas->id}")
        ->assertRedirect('/admin/kelas');

    expect(Kelas::find($kelas->id))->toBeNull();
    expect(KelasMataPelajaran::where('kelas', 'XII-C')->count())->toBe(0);
});

test('AC-KM-08: Halaman edit kelas mengirim semua mapel master + selected_mapel', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'X-A']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'IPA']);

    $this->actingAs($admin)
        ->get("/admin/kelas/{$kelas->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/kelas/edit')
            ->where('kelas.nama', 'X-A')
            ->has('semua_mapel', 10)
            ->where('selected_mapel', ['IPA', 'Matematika'])
        );
});

test('AC-KM-09: Halaman create kelas mengirim semua mapel master', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/kelas/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/kelas/create')
            ->has('semua_mapel', 10)
        );
});

test('AC-KM-10: Halaman index kelas juga menghitung jumlah mapel per kelas', function () {
    $admin = User::factory()->admin()->create();
    Kelas::firstOrCreate(['nama' => 'X-A']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'IPA']);

    $this->actingAs($admin)
        ->get('/admin/kelas')
        ->assertInertia(fn ($page) => $page
            ->where('kelas.data.0.nama', 'X-A')
            ->where('kelas.data.0.mata_pelajaran_count', 2)
        );
});

test('AC-GM-01: Guru form: kombinasi (kelas, mapel) yang ada di master diterima', function () {
    $admin = User::factory()->admin()->create();
    $this->seedKelasMataPelajaran();

    $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Pak Joko',
        'mengajar' => [
            ['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika'],
        ],
    ])->assertRedirect('/admin/guru');

    expect(Guru::where('nama_guru', 'Pak Joko')->first()->mengajar()->count())->toBe(1);
});

test('AC-GM-02: Guru form: kombinasi (kelas, mapel) yang TIDAK ada di master DITOLAK dengan 422', function () {
    $admin = User::factory()->admin()->create();
    Kelas::firstOrCreate(['nama' => 'X-A']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Pak Sejarah',
        'mengajar' => [
            ['kelas' => 'X-A', 'mata_pelajaran' => 'Sejarah'],
        ],
    ])->assertSessionHasErrors('mengajar.0.mata_pelajaran');

    expect(Guru::where('nama_guru', 'Pak Sejarah')->count())->toBe(0);
});

test('AC-GM-03: Guru form: jika kelas belum punya mapel diizinkan, semua mengajar ditolak', function () {
    $admin = User::factory()->admin()->create();
    Kelas::firstOrCreate(['nama' => 'XII-C']);
    MataPelajaran::firstOrCreate(['nama' => 'Matematika']);

    $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Pak Tanpa Mapel',
        'mengajar' => [
            ['kelas' => 'XII-C', 'mata_pelajaran' => 'Matematika'],
        ],
    ])->assertSessionHasErrors('mengajar.0.kelas');
});

test('AC-GM-04: Edit guru: replace kombinasi ke mapel yang TIDAK diizinkan ditolak', function () {
    $admin = User::factory()->admin()->create();
    $guru = Guru::create(['nama_guru' => 'Pak Edit']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    KelasMataPelajaran::create(['kelas' => 'X-B', 'mata_pelajaran' => 'IPA']);

    $this->actingAs($admin)->put("/admin/guru/{$guru->id}", [
        'nama_guru' => 'Pak Edit Updated',
        'mengajar' => [
            ['kelas' => 'X-A', 'mata_pelajaran' => 'Sejarah'],
        ],
    ])->assertSessionHasErrors('mengajar.0.mata_pelajaran');

    $guru->refresh();
    expect($guru->mengajar()->count())->toBe(1);
    expect($guru->mengajar()->first()->mata_pelajaran)->toBe('Matematika');
});

test('AC-GM-05: Halaman create guru mengirim mapel_by_kelas (nested, key = nama kelas)', function () {
    $admin = User::factory()->admin()->create();
    $this->seedKelasMataPelajaran(['X-A', 'X-B'], ['Matematika', 'IPA']);

    $this->actingAs($admin)
        ->get('/admin/guru/create')
        ->assertInertia(fn ($page) => $page
            ->component('admin/guru/create')
            ->has('mapel_by_kelas.X-A', 2)
            ->has('mapel_by_kelas.X-B', 2)
            ->where('mapel_by_kelas.X-A', ['IPA', 'Matematika'])
        );
});

test('AC-GM-06: Halaman edit guru mengirim mapel_by_kelas', function () {
    $admin = User::factory()->admin()->create();
    $this->seedKelasMataPelajaran(['X-A'], ['Matematika', 'IPA']);
    $guru = Guru::create(['nama_guru' => 'Pak Test']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->actingAs($admin)
        ->get("/admin/guru/{$guru->id}/edit")
        ->assertInertia(fn ($page) => $page
            ->component('admin/guru/edit')
            ->has('mapel_by_kelas.X-A', 2)
        );
});

test('AC-GM-07: Kombinasi yang ada di master + duplikat tetap kena error duplikat', function () {
    $admin = User::factory()->admin()->create();
    $this->seedKelasMataPelajaran();

    $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Pak Duplikat',
        'mengajar' => [
            ['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika'],
            ['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika'],
        ],
    ])->assertSessionHasErrors('mengajar.1.mata_pelajaran');
});

test('AC-GM-08: Hapus mapel dari master TIDAK menghapus guru_mengajar yang sudah ada (backward-compat)', function () {
    $admin = User::factory()->admin()->create();
    $guru = Guru::create(['nama_guru' => 'Pak Backward']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    KelasMataPelajaran::create(['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    KelasMataPelajaran::where('kelas', 'X-A')->where('mata_pelajaran', 'Matematika')->delete();

    expect(GuruMengajar::where('id_guru', $guru->id)->count())->toBe(1);
    expect(KelasMataPelajaran::where('kelas', 'X-A')->count())->toBe(0);
});
