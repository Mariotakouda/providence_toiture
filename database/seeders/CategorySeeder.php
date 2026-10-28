<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Familles citées dans le cahier des charges (section 2).
        $names = [
            'Tôles bac aluminium',
            'Tôles zinc',
            'Bac acier',
            'Galva',
            'Tôles Metro',
            'Portes de sécurité',
            'Produits d’étanchéité',
        ];

        foreach ($names as $name) {
            Category::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
