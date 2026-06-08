<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Aksi Fortify default yang memvalidasi payload registrasi dan membuat
 * baris `User` baru.
 *
 * Menggabungkan trait `profileRules()` dan `passwordRules()` bersama dengan
 * pemanggilan standar Eloquent `User::create()`. Catatan: dalam proyek ini fitur
 * registrasi mandiri dinonaktifkan (lihat `config/fortify.php`), sehingga aksi
 * ini didaftarkan tetapi hanya dipanggil oleh Fortify ketika diaktifkan.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Memvalidasi dan membuat pengguna baru yang terdaftar.
     *
     * @param  array<string, string>  $input  Input form registrasi, diharapkan berisi `name`, `email`, dan `password`.
     * @return User Instance user yang baru disimpan.
     *
     * @throws ValidationException Ketika validasi gagal.
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
