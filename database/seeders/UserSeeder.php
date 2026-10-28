<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            'admin' => 'Administrateur',
            'direction' => 'Direction',
            'commercial' => 'Commercial',
            'magasinier' => 'Magasinier',
            'comptable' => 'Comptable',
            'livreur' => 'Livreur',
        ];

        foreach ($users as $role => $name) {
            User::updateOrCreate(
                ['email' => "{$role}@providence.test"],
                ['name' => $name, 'password' => 'password', 'is_active' => true]
            )->syncRoles($role);
        }
    }
}
