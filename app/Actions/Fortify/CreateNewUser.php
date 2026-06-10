<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'profil' => ['required', 'string', 'min:20'],
            'budget' => ['nullable', 'decimal'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ],
            messages:[
                'name.required' => 'Le nom est requis.',
                'name.string' => 'Le nom doit être une chaîne de caractères.',
                'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
                'profil.required' => 'Le profil est requis.',
                'profil.string' => 'Le profil doit être une chaîne de caractères.',
                'profil.min' => 'Le profil doit faire au moins 20 caractères.',
                'email.required' => 'L\'adresse email est requise.',
                'email.string' => 'L\'adresse email doit être une chaîne de caractères.',
                'email.email' => 'L\'adresse email doit être valide.',
                'email.max' => 'L\'adresse email ne doit pas dépasser 255 caractères.',
                'email.unique' => 'Cette adresse email est déjà utilisée.',
                'password.required' => 'Le mot de passe est requis.',
                'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            ]
        )->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'profil' => $input['profil'],
        ]);
    }
}
