<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Aksi Fortify default yang memvalidasi payload reset password dan
 * memperbarui kolom `password` dari user yang bersangkutan.
 *
 * Catatan: dalam proyek ini fitur reset password dinonaktifkan (lihat
 * `config/fortify.php`); sebagai gantinya, admin mereset password melalui
 * endpoint admin khusus.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Memvalidasi dan mereset password pengguna yang lupa.
     *
     * @param  User  $user  Pengguna yang password-nya akan diperbarui.
     * @param  array<string, string>  $input  Input form reset, diharapkan berisi `password` baru (dan konfirmasinya).
     *
     * @throws ValidationException Ketika validasi gagal.
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
