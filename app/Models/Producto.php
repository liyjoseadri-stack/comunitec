<?php

namespace App\Models;

use App\Models\Concerns\UsaMarcasTiempoEnEspanol;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use UsaMarcasTiempoEnEspanol;

    public const TIPO_COMPONENTE_PROCESADOR = 'procesador';

    public const TIPO_COMPONENTE_PLACA_MADRE = 'placa_madre';

    public const TIPO_COMPONENTE_MEMORIA_RAM = 'memoria_ram';

    public const TIPO_COMPONENTE_ALMACENAMIENTO = 'almacenamiento';

    public const TIPO_COMPONENTE_TARJETA_GRAFICA = 'tarjeta_grafica';

    public const TIPO_COMPONENTE_FUENTE_PODER = 'fuente_poder';

    public const TIPO_COMPONENTE_GABINETE = 'gabinete';

    public const TIPO_COMPONENTE_ENFRIAMIENTO = 'enfriamiento';

    public const TIPO_COMPONENTE_SISTEMA_OPERATIVO = 'sistema_operativo';

    public const TIPO_COMPONENTE_ACCESORIO = 'accesorio';

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'codigo',
        'marca',
        'modelo',
        'unidad',
        'precio',
        'existencias',
        'requiere_numero_serie',
        'tipo_componente_pc',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'requiere_numero_serie' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public static function tiposComponentesPc(): array
    {
        return [
            self::TIPO_COMPONENTE_PROCESADOR,
            self::TIPO_COMPONENTE_PLACA_MADRE,
            self::TIPO_COMPONENTE_MEMORIA_RAM,
            self::TIPO_COMPONENTE_ALMACENAMIENTO,
            self::TIPO_COMPONENTE_TARJETA_GRAFICA,
            self::TIPO_COMPONENTE_FUENTE_PODER,
            self::TIPO_COMPONENTE_GABINETE,
            self::TIPO_COMPONENTE_ENFRIAMIENTO,
            self::TIPO_COMPONENTE_SISTEMA_OPERATIVO,
            self::TIPO_COMPONENTE_ACCESORIO,
        ];
    }

    public static function etiquetasComponentesPc(): array
    {
        return [
            self::TIPO_COMPONENTE_PROCESADOR => 'Procesador',
            self::TIPO_COMPONENTE_PLACA_MADRE => 'Placa madre',
            self::TIPO_COMPONENTE_MEMORIA_RAM => 'Memoria RAM',
            self::TIPO_COMPONENTE_ALMACENAMIENTO => 'Almacenamiento',
            self::TIPO_COMPONENTE_TARJETA_GRAFICA => 'Tarjeta gráfica',
            self::TIPO_COMPONENTE_FUENTE_PODER => 'Fuente de poder',
            self::TIPO_COMPONENTE_GABINETE => 'Gabinete',
            self::TIPO_COMPONENTE_ENFRIAMIENTO => 'Enfriamiento',
            self::TIPO_COMPONENTE_SISTEMA_OPERATIVO => 'Sistema operativo',
            self::TIPO_COMPONENTE_ACCESORIO => 'Accesorio',
        ];
    }

    public static function etiquetaTipoComponentePc(?string $tipo): string
    {
        return self::etiquetasComponentesPc()[$tipo] ?? 'Sin clasificar';
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function piezasInventario()
    {
        return $this->hasMany(PiezaInventario::class, 'producto_id');
    }

    public function seriesVendidas()
    {
        return $this->piezasInventario()->whereNotNull('partida_venta_id');
    }

    public function partidasCotizacion()
    {
        return $this->hasMany(PartidaCotizacion::class, 'producto_id');
    }

    public function partidasVenta()
    {
        return $this->hasMany(PartidaVenta::class, 'producto_id');
    }

    public function movimientosExistencias()
    {
        return $this->hasMany(MovimientoExistencias::class, 'producto_id');
    }
}
