<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /**
     * Contraseña compartida por los usuarios de prueba.
     */
    protected static ?string $password;

    /**
     * Define los datos iniciales del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'name' => fake()->name(),

            'email' => fake()->unique()->safeEmail(),

            'email_verified_at' => now(),

            'password' => static::$password ??= Hash::make('password'),

            'remember_token' => Str::random(10),

        ];
    }

    /**
     * Genera un usuario con correo sin verificar.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [

            'email_verified_at' => null,

        ]);
    }
}
