<?php

namespace App\Console\Commands;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    protected $signature = 'escm:create-admin
                            {--name= : Nom complet}
                            {--email= : Adresse e-mail}
                            {--password= : Mot de passe}
                            {--role=administrateur : Rôle (super_admin|administrateur)}';

    protected $description = 'Créer un compte administrateur pour la production';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nom complet');
        $email = $this->option('email') ?: $this->ask('E-mail');
        $password = $this->option('password') ?: $this->secret('Mot de passe');
        $role = $this->option('role') ?: 'administrateur';

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'in:super_admin,administrateur'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'departement_id' => Departement::query()->where('slug', 'communication')->value('id'),
            'email_verified_at' => now(),
        ]);

        $this->info("Utilisateur créé : {$user->email} ({$user->role})");

        return self::SUCCESS;
    }
}
