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
    public function run(): void
    {
        $kelas = $this->seedKelas();
        $mapel = $this->seedMataPelajaran();
        $this->seedKelasMataPelajaran($kelas, $mapel);
        $this->seedAdmin();
        $guru = $this->seedGuru();
        $siswa = $this->seedSiswa();
        $this->seedNilai($guru, $siswa);
    }

    /**
     * @return array<int, string>
     */
    private function seedKelas(): array
    {
        $daftarKelas = ['X-A', 'X-B', 'XI-A', 'XI-B', 'XII-A', 'XII-B'];

        foreach ($daftarKelas as $nama) {
            Kelas::firstOrCreate(['nama' => $nama]);
        }

        return $daftarKelas;
    }

    /**
     * @return array<int, string>
     */
    private function seedMataPelajaran(): array
    {
        $daftarMapel = [
            'Matematika',
            'Bahasa Indonesia',
            'IPA',
            'IPS',
            'Bahasa Inggris',
            'PKN',
            'Penjaskes',
            'Seni Budaya',
            'Sejarah',
            'Bahasa Jawa',
        ];

        foreach ($daftarMapel as $nama) {
            MataPelajaran::firstOrCreate(['nama' => $nama]);
        }

        return $daftarMapel;
    }

    /**
     * Populate the `kelas_mata_pelajaran` pivot for every default (kelas, mapel)
     * combination. Mirrors the test trait so the seeded demo dataset lets
     * the admin guru form accept every default kombinasi.
     *
     * @param  array<int, string>  $kelasList
     * @param  array<int, string>  $mapelList
     */
    private function seedKelasMataPelajaran(array $kelasList, array $mapelList): void
    {
        foreach ($kelasList as $k) {
            foreach ($mapelList as $m) {
                KelasMataPelajaran::firstOrCreate(['kelas' => $k, 'mata_pelajaran' => $m]);
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
     * @return array<int, Guru>
     */
    private function seedGuru(): array
    {
        $daftarGuru = [
            [
                'nama' => 'Ibu Sari Wahyuni',
                'mengajar' => [
                    ['kelas' => 'X-A', 'mapel' => 'Matematika'],
                    ['kelas' => 'X-B', 'mapel' => 'Matematika'],
                ],
            ],
            [
                'nama' => 'Pak Budi Hartono',
                'mengajar' => [
                    ['kelas' => 'X-A', 'mapel' => 'Bahasa Indonesia'],
                    ['kelas' => 'XI-A', 'mapel' => 'Bahasa Indonesia'],
                ],
            ],
            [
                'nama' => 'Bu Rini Astuti',
                'mengajar' => [
                    ['kelas' => 'X-B', 'mapel' => 'IPA'],
                    ['kelas' => 'XI-B', 'mapel' => 'IPA'],
                ],
            ],
            [
                'nama' => 'Pak Joko Santoso',
                'mengajar' => [
                    ['kelas' => 'XI-A', 'mapel' => 'IPS'],
                    ['kelas' => 'XI-B', 'mapel' => 'IPS'],
                ],
            ],
        ];

        $guruList = [];
        foreach ($daftarGuru as $g) {
            $username = $this->generateGuruUsername($g['nama']);

            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $g['nama'],
                    'role' => User::ROLE_GURU,
                    'is_active' => true,
                    'password' => Hash::make('guru123'),
                ]
            );

            $guru = Guru::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_guru' => $g['nama'],
                ]
            );

            foreach ($g['mengajar'] as $m) {
                GuruMengajar::firstOrCreate(
                    [
                        'id_guru' => $guru->id,
                        'kelas' => $m['kelas'],
                        'mata_pelajaran' => $m['mapel'],
                    ]
                );
            }

            $guruList[] = $guru;
        }

        return $guruList;
    }

    private function generateGuruUsername(string $namaLengkap): string
    {
        $honorifics = ['ibu', 'pak', 'bu', 'bpk', 'bapak', 'ibu.'];
        $parts = preg_split('/\s+/', strtolower(trim($namaLengkap)));
        $parts = array_values(array_filter($parts, fn ($p) => ! in_array(rtrim($p, '.'), $honorifics, true) && $p !== ''));

        return implode('', $parts);
    }

    /**
     * @return array<int, Siswa>
     */
    private function seedSiswa(): array
    {
        $kelas = ['X-A', 'X-B', 'XI-A', 'XI-B'];
        $namaDepan = ['Ahmad', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Galih', 'Hana', 'Indra', 'Jihan', 'Kiki', 'Lutfi', 'Mira', 'Nanda', 'Oki', 'Putri', 'Qori', 'Raka', 'Siti', 'Toni', 'Umi', 'Vina', 'Wahyu', 'Xena', 'Yusuf', 'Zara'];
        $namaBelakang = ['Fauzi', 'Santoso', 'Lestari', 'Anggraini', 'Prasetyo', 'Wulandari', 'Pratama', 'Nurhaliza', 'Wijaya', 'Aulia', 'Saputra', 'Handayani', 'Putri', 'Maulana', 'Ramadhan', 'Sari', 'Hidayat', 'Permata', 'Utami', 'Setiawan'];

        $siswaList = [];
        $counter = 1;

        foreach ($kelas as $kls) {
            $jumlahSiswa = $kls === 'X-A' ? 8 : 7;
            for ($i = 0; $i < $jumlahSiswa; $i++) {
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
                        'kelas' => $kls,
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
        $mengajarByGuru = [];
        foreach ($guruList as $guru) {
            $mengajarByGuru[$guru->id] = GuruMengajar::where('id_guru', $guru->id)->get()->all();
        }

        foreach ($siswaList as $siswa) {
            foreach ($guruList as $guru) {
                foreach ($mengajarByGuru[$guru->id] as $m) {
                    if ($m->kelas !== $siswa->kelas) {
                        continue;
                    }

                    $tugas = fake()->numberBetween(50, 100);
                    $uts = fake()->numberBetween(50, 100);
                    $uas = fake()->numberBetween(50, 100);

                    $akhir = Nilai::hitungNilaiAkhir(
                        (float) $tugas,
                        (float) $uts,
                        (float) $uas
                    );

                    Nilai::firstOrCreate(
                        [
                            'nis' => $siswa->nis,
                            'id_guru' => $guru->id,
                            'kelas' => $m->kelas,
                            'mata_pelajaran' => $m->mata_pelajaran,
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
}
