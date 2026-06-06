<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Notification;
use App\Models\Siswa;
use App\Models\User;
use App\Notifications\NotificationDispatcher;
use Illuminate\Support\Carbon;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

/*
|--------------------------------------------------------------------------
| Observer-driven notifications
|--------------------------------------------------------------------------
*/

test('Nilai Draft save creates nilai_masih_draft notification for the guru', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);

    $this->actingAs($userGuru)->post('/guru/input-nilai/save', [
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'nilai' => [
            ['nis' => '00001', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90],
        ],
    ])->assertSessionHas('success');

    expect(Notification::where('user_id', $userGuru->id)
        ->where('type', Notification::TYPE_NILAI_MASIH_DRAFT)
        ->count())->toBe(1);
});

test('Validating to Final creates nilai_sudah_final notifications for all siswa in combo', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $siswaA = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $siswaA->id, 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    $siswaB = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00002', 'user_id' => $siswaB->id, 'nama_siswa' => 'Budi', 'kelas' => 'X-A']);

    $this->actingAs($userGuru)->post('/guru/input-nilai/save', [
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'nilai' => [
            ['nis' => '00001', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90],
            ['nis' => '00002', 'nilai_tugas' => 75, 'nilai_uts' => 80, 'nilai_uas' => 85],
        ],
    ])->assertSessionHas('success');

    $this->actingAs($userGuru)->post('/guru/input-nilai/validate-final', [
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
    ])->assertSessionHas('success');

    foreach ([$siswaA->id, $siswaB->id] as $userId) {
        expect(Notification::where('user_id', $userId)
            ->where('type', Notification::TYPE_NILAI_SUDAH_FINAL)
            ->count())->toBe(1);
    }
});

test('Draft-save notification is deduplicated when guru saves the same combo twice', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);

    $payload = [
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'nilai' => [
            ['nis' => '00001', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90],
        ],
    ];

    $this->actingAs($userGuru)->post('/guru/input-nilai/save', $payload)->assertSessionHas('success');
    $this->actingAs($userGuru)->post('/guru/input-nilai/save', $payload)->assertSessionHas('success');

    expect(Notification::where('user_id', $userGuru->id)
        ->where('type', Notification::TYPE_NILAI_MASIH_DRAFT)
        ->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| HTTP endpoints (bell)
|--------------------------------------------------------------------------
*/

test('GET /notifications/unread-count returns the correct count for the authenticated user', function () {
    $user = User::factory()->siswa()->create();
    $other = User::factory()->siswa()->create();

    Notification::create(['user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'A', 'body' => 'a', 'read_at' => null]);
    Notification::create(['user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'B', 'body' => 'b', 'read_at' => null]);
    Notification::create(['user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'C', 'body' => 'c', 'read_at' => Carbon::now()]);
    Notification::create(['user_id' => $other->id, 'type' => Notification::TYPE_INFO, 'title' => 'Other', 'body' => 'o', 'read_at' => null]);

    $response = $this->actingAs($user)->getJson('/notifications/unread-count');

    $response->assertOk()->assertJson(['count' => 2]);
});

test('GET /notifications returns the 20 most-recent rows for the authenticated user', function () {
    $user = User::factory()->siswa()->create();

    for ($i = 1; $i <= 25; $i++) {
        Notification::create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_INFO,
            'title' => "T{$i}",
            'body' => "b{$i}",
            'created_at' => Carbon::now()->subMinutes(25 - $i),
        ]);
    }

    $response = $this->actingAs($user)->getJson('/notifications');

    $response->assertOk();
    $payload = $response->json('data');
    expect(count($payload))->toBe(20);
    expect($payload[0]['title'])->toBe('T25');
    expect($payload[19]['title'])->toBe('T6');
});

test('POST /notifications/{id}/read marks the row as read and 404s for other users', function () {
    $user = User::factory()->siswa()->create();
    $other = User::factory()->siswa()->create();

    $own = Notification::create(['user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'A', 'body' => 'a', 'read_at' => null]);
    $foreign = Notification::create(['user_id' => $other->id, 'type' => Notification::TYPE_INFO, 'title' => 'B', 'body' => 'b', 'read_at' => null]);

    $this->actingAs($user)->postJson("/notifications/{$own->id}/read")
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($own->fresh()->read_at)->not->toBeNull();

    $this->actingAs($user)->postJson("/notifications/{$foreign->id}/read")
        ->assertNotFound();
});

test('POST /notifications/read-all marks every unread row as read for the authenticated user', function () {
    $user = User::factory()->siswa()->create();
    $other = User::factory()->siswa()->create();

    Notification::create(['user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'A', 'body' => 'a', 'read_at' => null]);
    Notification::create(['user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'B', 'body' => 'b', 'read_at' => null]);
    Notification::create(['user_id' => $other->id, 'type' => Notification::TYPE_INFO, 'title' => 'C', 'body' => 'c', 'read_at' => null]);

    $response = $this->actingAs($user)->postJson('/notifications/read-all');

    $response->assertOk()->assertJson(['ok' => true, 'updated' => 2]);
    expect(Notification::where('user_id', $user->id)->whereNull('read_at')->count())->toBe(0);
    expect(Notification::where('user_id', $other->id)->whereNull('read_at')->count())->toBe(1);
});

test('Notification endpoints require authentication', function () {
    $this->getJson('/notifications')->assertRedirect();
    $this->getJson('/notifications/unread-count')->assertRedirect();
    $this->postJson('/notifications/read-all')->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Console commands
|--------------------------------------------------------------------------
*/

test('notifications:cleanup deletes only read+old rows and preserves unread', function () {
    $user = User::factory()->siswa()->create();

    Carbon::setTestNow(Carbon::now()->subDays(40));
    $oldRead = Notification::create([
        'user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'old-read', 'body' => 'b',
        'read_at' => Carbon::now(),
    ]);
    Carbon::setTestNow();

    Carbon::setTestNow(Carbon::now()->subDays(50));
    $oldUnread = Notification::create([
        'user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'old-unread', 'body' => 'b',
        'read_at' => null,
    ]);
    Carbon::setTestNow();

    $recentRead = Notification::create([
        'user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'recent-read', 'body' => 'b',
        'read_at' => Carbon::now(),
    ]);

    $this->artisan('notifications:cleanup')->assertExitCode(0);

    expect(Notification::find($oldRead->id))->toBeNull();
    expect(Notification::find($oldUnread->id))->not->toBeNull();
    expect(Notification::find($recentRead->id))->not->toBeNull();
});

test('notifications:cleanup honours a custom --days option', function () {
    $user = User::factory()->siswa()->create();

    Carbon::setTestNow(Carbon::now()->subDays(10));
    $tenDayOldRead = Notification::create([
        'user_id' => $user->id, 'type' => Notification::TYPE_INFO, 'title' => 'r', 'body' => 'b',
        'read_at' => Carbon::now(),
    ]);
    Carbon::setTestNow();

    $this->artisan('notifications:cleanup', ['--days' => 5])->assertExitCode(0);

    expect(Notification::find($tenDayOldRead->id))->toBeNull();
});

test('notifications:generate-uninputed creates notifications only for combos with no Nilai rows', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika']);

    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create([
        'nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81,
        'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $this->artisan('notifications:generate-uninputed')->assertExitCode(0);

    $rows = Notification::where('user_id', $userGuru->id)
        ->where('type', Notification::TYPE_NILAI_BELUM_DIINPUT)
        ->get();

    expect($rows->count())->toBe(1);
    expect($rows->first()->link)->toContain('X-B');
});

test('notifications:generate-uninputed skips gurus with no user account', function () {
    $guru = Guru::create(['user_id' => null, 'nama_guru' => 'No User Guru']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->artisan('notifications:generate-uninputed')->assertExitCode(0);

    expect(Notification::where('type', Notification::TYPE_NILAI_BELUM_DIINPUT)->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Controller-driven notifications (rapor, account)
|--------------------------------------------------------------------------
*/

test('Siswa downloading their rapor dispatches rapor_tersedia notification', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $userSiswa = User::factory()->siswa()->create();
    Siswa::create([
        'nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A',
    ]);
    Nilai::create([
        'nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81,
        'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/rapor/pdf');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');

    expect(Notification::where('user_id', $userSiswa->id)
        ->where('type', Notification::TYPE_RAPOR_TERSEDIA)
        ->count())->toBe(1);
});

test('Admin reset password dispatches akun_diubah notification to the target user', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();

    $this->actingAs($admin)->post("/admin/akun/{$guruUser->id}/reset-password", [
        'password' => 'reset123',
    ])->assertRedirect();

    expect(Notification::where('user_id', $guruUser->id)
        ->where('type', Notification::TYPE_AKUN_DIUBAH)
        ->count())->toBe(1);
});

test('Admin toggle active dispatches akun_diubah notification to the target user', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();

    $this->actingAs($admin)->patch("/admin/akun/{$guruUser->id}/toggle-active")
        ->assertRedirect();

    expect(Notification::where('user_id', $guruUser->id)
        ->where('type', Notification::TYPE_AKUN_DIUBAH)
        ->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Dispatcher unit checks
|--------------------------------------------------------------------------
*/

test('Dispatcher send() inserts a row when no duplicate exists', function () {
    $user = User::factory()->siswa()->create();
    $dispatcher = app(NotificationDispatcher::class);

    $row = $dispatcher->send($user, Notification::TYPE_INFO, 'Hello', 'world body', '/somewhere');

    expect($row->exists)->toBeTrue();
    expect($row->user_id)->toBe($user->id);
    expect($row->read_at)->toBeNull();
});

test('Dispatcher send() dedupes by (user_id, type, link) composite', function () {
    $user = User::factory()->siswa()->create();
    $dispatcher = app(NotificationDispatcher::class);

    $first = $dispatcher->send($user, Notification::TYPE_INFO, 'Hello', 'world body', '/somewhere');
    $second = $dispatcher->send($user, Notification::TYPE_INFO, 'Hello (updated)', 'updated body', '/somewhere');

    expect($first->id)->toBe($second->id);
    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});
