<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Default Fortify action that validates a password-reset payload and
 * updates the `password` column of the affected user.
 *
 * Note: in this project the password-reset feature is disabled (see
 * `config/fortify.php`); admins reset passwords through the dedicated
 * admin endpoint instead.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  User  $user  The user whose password will be updated.
     * @param  array<string, string>  $input  The reset form input, expected to contain the new `password` (and confirmation).
     *
     * @throws ValidationException When validation fails.
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
