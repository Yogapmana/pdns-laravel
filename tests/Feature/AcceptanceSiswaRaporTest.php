<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Tests\Traits\SeedsAkademikMasters;

if (! function_exists('decompressPdfStreams')) {
    function decompressPdfStreams(string $binary): ?string
    {
        $decoded = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $binary, $matches)) {
            foreach ($matches[1] as $stream) {
                $inflated = @gzinflate($stream);
                if ($inflated !== false) {
                    $decoded .= $inflated."\n";
                }
            }
        }

        return $decoded !== '' ? $decoded : null;
    }
}

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

test('Siswa nilai page mengirimkan chart_data dengan overall + per_mapel', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
        'nilai_tugas' => 60, 'nilai_uts' => 50, 'nilai_uas' => 55, 'nilai_akhir' => 55, 'status_lulus' => 'Tidak Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/nilai');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('siswa/nilai/index')
        ->where('chart_data.overall.tugas', 70)
        ->where('chart_data.overall.uts', 60)
        ->where('chart_data.overall.uas', 72.5)
        ->where('chart_data.overall.count', 2)
        ->where('chart_data.kkm', 70)
        ->where('chart_data.stats.total_mapel', 2)
        ->where('chart_data.stats.lulus', 1)
        ->where('chart_data.stats.tidak_lulus', 1)
        ->has('chart_data.per_mapel', 2)
    );
});

test('Siswa nilai page chart_data menghitung ulang untuk siswa tanpa nilai', function () {
    $userSiswa = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userSiswa)->get('/siswa/nilai');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('siswa/nilai/index')
        ->where('chart_data.overall.count', 0)
        ->where('chart_data.overall.tugas', null)
        ->where('chart_data.overall.akhir', null)
        ->where('chart_data.stats.total_mapel', 0)
        ->has('chart_data.per_mapel', 0)
    );
});

test('Siswa dapat mengunduh rapor PDF mereka', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/rapor/pdf');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $this->assertStringStartsWith('%PDF', $response->getContent());
});

test('Rapor PDF berisi nama siswa, NIS, kelas, dan mapel (UTF-16BE encoded)', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad Subagja', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari Wahyuni']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/rapor/pdf');

    $response->assertOk();
    $content = $response->getContent();

    $hex = bin2hex(mb_convert_encoding('Ahmad Subagja', 'UTF-16BE', 'UTF-8'));
    $this->assertStringContainsString($hex, bin2hex($content), 'PDF harus memuat nama siswa di title (UTF-16BE)');

    $this->assertStringContainsString('00001', $content, 'PDF harus memuat NIS di title');

    $body = decompressPdfStreams($content);
    if ($body !== null) {
        $this->assertStringContainsString('X-A', $body, 'PDF body harus memuat kelas');
        $this->assertStringContainsString('Matematika', $body, 'PDF body harus memuat nama mapel');
    } else {
        $this->assertGreaterThan(2000, strlen($content), 'PDF harus cukup besar untuk memuat data rapor');
    }
});

test('Rapor PDF untuk siswa lain tidak exposing data siswa A', function () {
    $userSiswaA = User::factory()->siswa()->create();
    $userSiswaB = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $userSiswaA->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    Siswa::create(['nis' => '00002', 'user_id' => $userSiswaB->id, 'nama_siswa' => 'Budi', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userSiswaB)->get('/siswa/rapor/pdf');

    $response->assertOk();
    $content = $response->getContent();

    $hex = bin2hex(mb_convert_encoding('Ahmad', 'UTF-16BE', 'UTF-8'));
    $this->assertStringNotContainsString($hex, bin2hex($content), 'PDF tidak boleh berisi data siswa lain (title)');
    $this->assertStringContainsString('00002', $content, 'PDF harus memuat NIS siswa login di title');
});

test('Rapor PDF filename berisi nama siswa dan NIS', function () {
    $userSiswa = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad Subagja', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userSiswa)->get('/siswa/rapor/pdf');

    $response->assertOk();
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('Rapor_Ahmad_Subagja_00001.pdf');
});

test('Guru tidak bisa akses /siswa/rapor/pdf (403)', function () {
    $userGuru = User::factory()->guru()->create();

    $this->actingAs($userGuru)->get('/siswa/rapor/pdf')->assertForbidden();
});

test('Admin tidak bisa akses /siswa/rapor/pdf (403)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/siswa/rapor/pdf')->assertForbidden();
});

test('Siswa dashboard menampilkan has_nilai=true jika ada nilai', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('siswa/dashboard')
        ->where('has_nilai', true)
    );
});

test('Siswa dashboard menampilkan has_nilai=false jika belum ada nilai', function () {
    $userSiswa = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userSiswa)->get('/siswa/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('siswa/dashboard')
        ->where('has_nilai', false)
    );
});
