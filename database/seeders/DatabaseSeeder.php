<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\KelasMataPelajaran;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const KELAS_LIST = [
        'X MIPA 1', 'X MIPA 2',
        'X IPS 1', 'X IPS 2',
        'XI MIPA 1', 'XI MIPA 2',
        'XI IPS 1', 'XI IPS 2',
        'XII MIPA 1', 'XII MIPA 2',
        'XII IPS 1', 'XII IPS 2',
    ];

    private const MAPEL_LIST = [
        'Matematika',
        'Bahasa Indonesia',
        'Bahasa Inggris',
        'PKN',
        'Penjaskes',
        'Seni Budaya',
        'Fisika',
        'Kimia',
        'Biologi',
        'Matematika Peminatan',
        'Informatika',
        'Koding',
        'Bahasa Jawa',
        'Pendidikan Pancasila',
        'Geografi',
        'Ekonomi',
        'Sosiologi',
        'Sejarah',
        'Antropologi',
        'Bahasa Arab',
    ];

    /**
     * Per-kelas mapel offering: 6 common + 4 specialization.
     *
     * Common (offered by all 12 kelas): Matematika, Bahasa Indonesia,
     * Bahasa Inggris, PKN, Penjaskes, Seni Budaya.
     *
     * MIPA-spec (8 mapel, each in exactly 3 MIPA kelas; each MIPA
     * kelas picks 4 of 8): Fisika, Kimia, Biologi, Matematika
     * Peminatan, Informatika, Koding, Bahasa Jawa, Pendidikan Pancasila.
     *
     * IPS-spec (6 mapel, each in exactly 4 IPS kelas; each IPS kelas
     * picks 4 of 6): Geografi, Ekonomi, Sosiologi, Sejarah, Antropologi,
     * Bahasa Arab.
     *
     * The balanced "3 per MIPA-spec" / "4 per IPS-spec" distribution
     * means every guru teaching a spec mapel has at least 3 eligible
     * kelas to assign to, supporting the "≥7 nilai per siswa" target.
     *
     * @var array<string, array<int, string>>
     */
    private const MAPEL_BY_KELAS = [
        'X MIPA 1' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Fisika', 'Kimia', 'Biologi', 'Matematika Peminatan',
        ],
        'X MIPA 2' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Informatika', 'Koding', 'Bahasa Jawa', 'Pendidikan Pancasila',
        ],
        'XI MIPA 1' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Fisika', 'Matematika Peminatan', 'Informatika', 'Bahasa Jawa',
        ],
        'XI MIPA 2' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Kimia', 'Biologi', 'Koding', 'Pendidikan Pancasila',
        ],
        'XII MIPA 1' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Biologi', 'Matematika Peminatan', 'Informatika', 'Pendidikan Pancasila',
        ],
        'XII MIPA 2' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Fisika', 'Kimia', 'Koding', 'Bahasa Jawa',
        ],
        'X IPS 1' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Geografi', 'Ekonomi', 'Sosiologi', 'Sejarah',
        ],
        'X IPS 2' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Geografi', 'Ekonomi', 'Antropologi', 'Bahasa Arab',
        ],
        'XI IPS 1' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Geografi', 'Sosiologi', 'Sejarah', 'Bahasa Arab',
        ],
        'XI IPS 2' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Ekonomi', 'Sosiologi', 'Antropologi', 'Bahasa Arab',
        ],
        'XII IPS 1' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Geografi', 'Ekonomi', 'Sejarah', 'Antropologi',
        ],
        'XII IPS 2' => [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'PKN', 'Penjaskes', 'Seni Budaya',
            'Sosiologi', 'Sejarah', 'Antropologi', 'Bahasa Arab',
        ],
    ];

    private const GURU_LIST = [
        'Sari Wahyuni',
        'Budi Hartono',
        'Rini Astuti',
        'Joko Santoso',
        'Dewi Lestari',
        'Hendra Wijaya',
        'Ani Rahayu',
        'Surya Pratama',
        'Maya Sari',
        'Andi Setiawan',
        'Fitri Handayani',
        'Rudi Hartono',
        'Nining Saputra',
        'Dedi Kurniawan',
        'Yuli Permata',
        'Agung Wibowo',
        'Lina Marlina',
        'Fajar Nugroho',
        'Tuti Astuti',
        'Bayu Pamungkas',
    ];

    private const SISWA_PER_KELAS = 25;

    public function run(): void
    {
        $kelasIds = $this->seedKelas();
        $mapelIds = $this->seedMataPelajaran();
        $this->seedKelasMataPelajaran();
        $this->seedAdmin();
        $guru = $this->seedGuru();
        $siswa = $this->seedSiswa();
        $this->seedNilai($guru, $siswa);
    }

    /**
     * @return array<string, int> Map of kelas nama => kelas id.
     */
    private function seedKelas(): array
    {
        $map = [];
        foreach (self::KELAS_LIST as $nama) {
            $kelas = Kelas::firstOrCreate(['nama' => $nama]);
            $map[$nama] = $kelas->id;
        }

        return $map;
    }

    /**
     * @return array<string, int> Map of mata pelajaran nama => mata pelajaran id.
     */
    private function seedMataPelajaran(): array
    {
        $map = [];
        foreach (self::MAPEL_LIST as $nama) {
            $mapel = MataPelajaran::firstOrCreate(['nama' => $nama]);
            $map[$nama] = $mapel->id;
        }

        return $map;
    }

    /**
     * Populate the `kelas_mata_pelajaran` pivot from MAPEL_BY_KELAS.
     *
     * @param  array<string, int>  $kelasIds  Map of kelas nama => id.
     * @param  array<string, int>  $mapelIds  Map of mapel nama => id.
     */
    private function seedKelasMataPelajaran(?array $kelasIds = null, ?array $mapelIds = null): void
    {
        $kelasIds ??= Kelas::pluck('id', 'nama')->all();
        $mapelIds ??= MataPelajaran::pluck('id', 'nama')->all();

        foreach (self::MAPEL_BY_KELAS as $kelas => $mapelList) {
            $kelasId = $kelasIds[$kelas] ?? null;
            if ($kelasId === null) {
                continue;
            }
            foreach ($mapelList as $mapel) {
                $mapelId = $mapelIds[$mapel] ?? null;
                if ($mapelId === null) {
                    continue;
                }
                KelasMataPelajaran::firstOrCreate([
                    'kelas_id' => $kelasId,
                    'mata_pelajaran_id' => $mapelId,
                ]);
            }
        }
    }

    private function seedAdmin(): User
    {
        return User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'password' => Hash::make('admin123'),
            ]
        );
    }

    /**
     * Each guru teaches one mapel in up to 6 kelas (capped at all
     * eligible kelas). With the current MAPEL_BY_KELAS distribution
     * (6 common × 12 kelas + 8 MIPA-spec × 3 kelas + 6 IPS-spec × 4
     * kelas = 84 guru_mengajar capacity), the balanced selection
     * ensures every siswa has at least 7 nilai.
     *
     * The original "1-4 kelas per guru" spec is overridden here: 20
     * guru × 4 kelas = 80 entries is mathematically insufficient for
     * the 84 entries needed to hit "≥7 per siswa across 12 kelas".
     * Always-max assignment (capped at 6) with balanced selection
     * gives 84 entries distributed evenly = 7 per kelas.
     *
     * @param  array<string, int>|null  $kelasIds  Map of kelas nama => id.
     * @param  array<string, int>|null  $mapelIds  Map of mapel nama => id.
     * @return array<int, Guru>
     */
    private function seedGuru(?array $kelasIds = null, ?array $mapelIds = null): array
    {
        $kelasIds ??= Kelas::pluck('id', 'nama')->all();
        $mapelIds ??= MataPelajaran::pluck('id', 'nama')->all();

        $guruList = [];
        $mapelList = self::MAPEL_LIST;
        $kelasCounts = array_fill_keys(self::KELAS_LIST, 0);

        foreach (self::GURU_LIST as $idx => $nama) {
            $mapel = $mapelList[$idx % count($mapelList)];
            $mapelId = $mapelIds[$mapel] ?? null;
            if ($mapelId === null) {
                continue;
            }

            $availableKelas = $this->getKelasOfferingMapel($mapel);
            $numKelas = min(6, count($availableKelas));

            usort(
                $availableKelas,
                fn (string $a, string $b): int => $kelasCounts[$a] <=> $kelasCounts[$b]
            );
            $selectedKelas = array_slice($availableKelas, 0, $numKelas);

            $username = $this->generateGuruUsername($nama);

            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $nama,
                    'role' => User::ROLE_GURU,
                    'is_active' => true,
                    'password' => Hash::make('guru123'),
                ]
            );

            $guru = Guru::firstOrCreate(
                ['user_id' => $user->id],
                ['nama_guru' => $nama]
            );

            foreach ($selectedKelas as $kelas) {
                $kelasId = $kelasIds[$kelas] ?? null;
                if ($kelasId === null) {
                    continue;
                }
                GuruMengajar::firstOrCreate([
                    'id_guru' => $guru->id,
                    'kelas_id' => $kelasId,
                    'mata_pelajaran_id' => $mapelId,
                ]);
                $kelasCounts[$kelas]++;
            }

            $guruList[] = $guru;
        }

        return $guruList;
    }

    /**
     * Resolve the list of kelas names that offer the given mapel.
     *
     * @return array<int, string>
     */
    private function getKelasOfferingMapel(string $mapel): array
    {
        $result = [];
        foreach (self::MAPEL_BY_KELAS as $kelas => $mapelList) {
            if (in_array($mapel, $mapelList, true)) {
                $result[] = $kelas;
            }
        }

        return $result;
    }

    private function generateGuruUsername(string $namaLengkap): string
    {
        $parts = preg_split('/\s+/', strtolower(trim($namaLengkap)));
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        return implode('', $parts);
    }

    /**
     * @param  array<string, int>|null  $kelasIds  Map of kelas nama => id.
     * @return array<int, Siswa>
     */
    private function seedSiswa(?array $kelasIds = null): array
    {
        $kelasIds ??= Kelas::pluck('id', 'nama')->all();
        $namaDepan = ['Ahmad', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Galih', 'Hana', 'Indra', 'Jihan', 'Kiki', 'Lutfi', 'Mira', 'Nanda', 'Oki', 'Putri', 'Qori', 'Raka', 'Siti', 'Toni', 'Umi', 'Vina', 'Wahyu', 'Xena', 'Yusuf', 'Zara'];
        $namaBelakang = ['Fauzi', 'Santoso', 'Lestari', 'Anggraini', 'Prasetyo', 'Wulandari', 'Pratama', 'Nurhaliza', 'Wijaya', 'Aulia', 'Saputra', 'Handayani', 'Putri', 'Maulana', 'Ramadhan', 'Sari', 'Hidayat', 'Permata', 'Utami', 'Setiawan'];

        $siswaList = [];
        $counter = 1;

        foreach (self::KELAS_LIST as $kls) {
            $kelasId = $kelasIds[$kls] ?? null;
            for ($i = 0; $i < self::SISWA_PER_KELAS; $i++) {
                $nama = $namaDepan[array_rand($namaDepan)].' '.$namaBelakang[array_rand($namaBelakang)];
                $nis = str_pad((string) $counter, 5, '0', STR_PAD_LEFT);
                $username = $nis;

                $user = User::firstOrCreate(
                    ['username' => $username],
                    [
                        'name' => $nama,
                        'role' => User::ROLE_SISWA,
                        'is_active' => true,
                        'password' => Hash::make('siswa123'),
                    ]
                );

                $siswa = Siswa::firstOrCreate(
                    ['nis' => $nis],
                    [
                        'user_id' => $user->id,
                        'nama_siswa' => $nama,
                        'kelas_id' => $kelasId,
                    ]
                );

                $siswaList[] = $siswa;
                $counter++;
            }
        }

        return $siswaList;
    }

    /**
     * @param  array<int, Guru>  $guruList
     * @param  array<int, Siswa>  $siswaList
     */
    private function seedNilai(array $guruList, array $siswaList): void
    {
        $mengajarByKelasId = [];
        foreach ($guruList as $guru) {
            foreach (GuruMengajar::where('id_guru', $guru->id)->get()->all() as $m) {
                $mengajarByKelasId[$m->kelas_id][] = $m;
            }
        }

        foreach ($siswaList as $siswa) {
            $mengajar = $mengajarByKelasId[$siswa->kelas_id] ?? [];
            foreach ($mengajar as $m) {
                $tugas = fake()->numberBetween(50, 100);
                $uts = fake()->numberBetween(50, 100);
                $uas = fake()->numberBetween(50, 100);
                $akhir = Nilai::hitungNilaiAkhir((float) $tugas, (float) $uts, (float) $uas);

                Nilai::firstOrCreate(
                    [
                        'nis' => $siswa->nis,
                        'id_guru' => $m->id_guru,
                        'kelas_id' => $m->kelas_id,
                        'mata_pelajaran_id' => $m->mata_pelajaran_id,
                    ],
                    [
                        'nilai_tugas' => $tugas,
                        'nilai_uts' => $uts,
                        'nilai_uas' => $uas,
                        'nilai_akhir' => $akhir,
                        'status_lulus' => Nilai::tentukanKelulusan((float) $akhir),
                        'status_validasi' => fake()->randomElement([Nilai::STATUS_DRAFT, Nilai::STATUS_FINAL]),
                    ]
                );
            }
        }
    }
}
