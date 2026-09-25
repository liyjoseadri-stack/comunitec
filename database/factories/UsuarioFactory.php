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

            'nombre' => fake()->name(),

            'correo' => fake()->unique()->safeEmail(),

            'correo_verificado_en' => now(),

            'contrasena' => static::$password ??= Hash::make('contrasena'),

            'token_recuerdo' => Str::random(10),

        ];
    }

    /**
     * Genera un usuario con correo sin verificar.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [

            'correo_verificado_en' => null,

        ]);
    }
}
