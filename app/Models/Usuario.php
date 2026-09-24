<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    protected $table = 'users';

    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory()
    {
        return UsuarioFactory::new();
    }

    public const ROL_ADMINISTRADOR = 'admin';

    public const ROL_COMERCIAL = 'comercial';

    public const ROL_CONSULTA = 'consulta';

    /**
     * Atributos permitidos para asignación masiva.
     *
     * @var list<string>
     */
    protected $fillable = [

        'name',

        'email',

        'role',

        'active',

        'password',

    ];

    public function esAdministrador(): bool
    {
        return $this->role === self::ROL_ADMINISTRADOR;
    }

    public function esComercial(): bool
    {
        return $this->role === self::ROL_COMERCIAL;
    }

    public function esConsulta(): bool
    {
        return $this->role === self::ROL_CONSULTA;
    }

    /**
     * Atributos ocultos al serializar el usuario.
     *
     * @var list<string>
     */
    protected $hidden = [

        'password',

        'remember_token',

    ];

    /**
     * Conversiones de tipos de los atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',

            'active' => 'boolean',

            'password' => 'hashed',

        ];
    }
}
