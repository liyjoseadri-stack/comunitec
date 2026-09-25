<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable, UsaMarcasTiempoEnEspanol;

    protected static function newFactory()
    {
        return UsuarioFactory::new();
    }

    public const ROL_ADMINISTRADOR = 'administrador';

    public const ROL_COMERCIAL = 'comercial';

    public const ROL_CONSULTA = 'consulta';

    /**
     * Atributos permitidos para asignación masiva.
     *
     * @var list<string>
     */
    protected $fillable = [

        'nombre',

        'correo',

        'rol',

        'activo',

        'contrasena',

    ];

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esComercial(): bool
    {
        return $this->rol === self::ROL_COMERCIAL;
    }

    public function esConsulta(): bool
    {
        return $this->rol === self::ROL_CONSULTA;
    }

    /**
     * Atributos ocultos al serializar el usuario.
     *
     * @var list<string>
     */
    protected $hidden = [

        'contrasena',

        'token_recuerdo',

    ];

    /**
     * Conversiones de tipos de los atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

            'correo_verificado_en' => 'datetime',

            'activo' => 'boolean',

            'contrasena' => 'hashed',

        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getRememberTokenName(): string
    {
        return 'token_recuerdo';
    }
}
