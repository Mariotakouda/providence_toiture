<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email : Adresse e-mail de l\'administrateur} {--name=Administrateur : Nom affiché}';

    protected $description = 'Crée un compte administrateur (mot de passe saisi de façon masquée).';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->secret('Mot de passe (12 caractères min., lettres et chiffres)');
        $confirmation = (string) $this->secret('Confirmez le mot de passe');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => (string) $this->option('name'),
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ]);
        $user->syncRoles('admin');

        $this->info("Administrateur créé : {$email}");

        return self::SUCCESS;
    }
}
