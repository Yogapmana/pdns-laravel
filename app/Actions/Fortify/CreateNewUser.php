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
 * Default Fortify action that validates a registration payload and creates
 * a new `User` row.
 *
 * Combines the shared `profileRules()` and `passwordRules()` traits with
 * the standard `User::create()` Eloquent call. Note: in this project the
 * self-registration feature is disabled (see `config/fortify.php`), so
 * this action is registered but only invoked by Fortify when enabled.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input  The registration form input, expected to contain `name`, `email`, and `password`.
     * @return User The freshly persisted user instance.
     *
     * @throws ValidationException When validation fails.
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
