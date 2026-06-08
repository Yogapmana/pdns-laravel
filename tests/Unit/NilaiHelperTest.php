<?php

declare(strict_types=1);

use App\Models\Nilai;

test('Hitung nilai akhir: 0.3*80 + 0.3*70 + 0.4*90 = 81', function () {
    expect(Nilai::hitungNilaiAkhir(80, 70, 90))->toBe(81.0);
});

test('Hitung nilai akhir: 0.3*50 + 0.3*60 + 0.4*65 = 59', function () {
    expect(Nilai::hitungNilaiAkhir(50, 60, 65))->toBe(59.0);
});

test('Tentukan kelulusan: >= 70 Lulus, < 70 Tidak Lulus', function () {
    expect(Nilai::tentukanKelulusan(70))->toBe('Lulus');
    expect(Nilai::tentukanKelulusan(80))->toBe('Lulus');
    expect(Nilai::tentukanKelulusan(69.99))->toBe('Tidak Lulus');
    expect(Nilai::tentukanKelulusan(59))->toBe('Tidak Lulus');
});

test('Validasi nilai: 0-100 valid, di luar ditolak', function () {
    expect(Nilai::validasiNilai(0))->toBeTrue();
    expect(Nilai::validasiNilai(100))->toBeTrue();
    expect(Nilai::validasiNilai(50))->toBeTrue();
    expect(Nilai::validasiNilai(-1))->toBeFalse();
    expect(Nilai::validasiNilai(101))->toBeFalse();
    expect(Nilai::validasiNilai(105))->toBeFalse();
});
