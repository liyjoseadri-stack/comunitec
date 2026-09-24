<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Usuario::factory(10)->create();

        Usuario::factory()->create([

            'name' => 'Test Usuario',

            'email' => 'test@example.com',

        ]);
    }
}
