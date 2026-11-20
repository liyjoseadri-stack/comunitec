<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\PcArmada;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Services\ServicioConfiguracionPc;
use App\Support\CalculosCotizacion;
use App\Support\PlazosHabiles;
use Illuminate\Database\Seeder;

class DatosDemostracionSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = collect([
            'Equipos de cómputo',
            'Componentes',
            'Redes',
            'Videovigilancia',
            'Infraestructura',
            'Accesorios y energía',
        ])->mapWithKeys(function (string $nombre): array {
            $categoria = Categoria::query()->updateOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );

            return [$nombre => $categoria->id];
        });

        foreach ($this->productos() as $datos) {
            $categoria = $datos['categoria'];
            unset($datos['categoria']);

            Producto::query()->updateOrCreate(
                ['codigo' => $datos['codigo']],
                $datos + ['categoria_id' => $categorias[$categoria]]
            );
        }

        Producto::query()
            ->where('codigo', 'ACC-LOG-MK120')
            ->update(['tipo_componente_pc' => Producto::TIPO_COMPONENTE_ACCESORIO]);

        foreach ($this->servicios() as $datos) {
            $categoria = $datos['categoria'];
            unset($datos['categoria']);

            Servicio::query()->updateOrCreate(
                ['codigo' => $datos['codigo']],
                $datos + ['categoria_id' => $categorias[$categoria]]
            );
        }

        foreach ($this->clientes() as $datos) {
            Cliente::query()->updateOrCreate(['rfc' => $datos['rfc']], $datos);
        }

        $this->sembrarCotizaciones();
        $this->sembrarPcParaPruebaDeCorreo();
    }

    private function sembrarCotizaciones(): void
    {
        $usuario = Usuario::query()
            ->where('activo', true)
            ->orderBy('id')
            ->first();

        if ($usuario === null) {
            $usuario = Usuario::query()->create([
                'nombre' => 'Comercial de demostración',
                'correo' => 'demo.comercial@comunitec.test',
                'contrasena' => 'DemoLocal123!',
                'rol' => Usuario::ROL_COMERCIAL,
                'activo' => true,
            ]);
        }

        $clientes = Cliente::query()
            ->whereIn('rfc', collect($this->clientes())->pluck('rfc'))
            ->get()
            ->keyBy('rfc');
        $productos = Producto::query()
            ->whereIn('codigo', [
                'COM-LAP-E14G5',
                'CMP-SSD-NV2-1TB',
                'RED-AP-U6PLUS',
                'CCTV-CAM-HIK2MP',
                'ACC-LOG-MK120',
            ])
            ->get()
            ->keyBy('codigo');
        $servicios = Servicio::query()
            ->whereIn('codigo', [
                'SER-MANT-PREV',
                'SER-INST-AP',
                'SER-INST-CAM',
                'SER-NODO-CAT6',
            ])
            ->get()
            ->keyBy('codigo');

        $ahora = now();
        $enviada = $ahora->copy()->subDays(2)->setTime(10, 0);
        $siguienteDiaHabil = PlazosHabiles::sumar($ahora, 1)->setTime(16, 0);

        $cotizaciones = [
            [
                'folio' => 'DEMO-BORRADOR-01',
                'cliente' => 'CIF210315AB4',
                'estado' => Cotizacion::ESTADO_BORRADOR,
                'enviada_en' => null,
                'aceptada_en' => null,
                'vence_en' => null,
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['COM-LAP-E14G5'], 1),
                    $this->partidaServicio($servicios['SER-MANT-PREV'], 1),
                ],
            ],
            [
                'folio' => 'DEMO-BORRADOR-02',
                'cliente' => 'LOHF900714M38',
                'estado' => Cotizacion::ESTADO_BORRADOR,
                'enviada_en' => null,
                'aceptada_en' => null,
                'vence_en' => null,
                'entrega_limite_en' => null,
                'porcentaje_descuento' => 5,
                'partidas' => [
                    $this->partidaProducto($productos['ACC-LOG-MK120'], 2),
                    $this->partidaProducto($productos['CMP-SSD-NV2-1TB'], 1),
                ],
            ],
            [
                'folio' => 'DEMO-PENDIENTE-MANANA',
                'cliente' => 'SLG190827KQ2',
                'estado' => Cotizacion::ESTADO_PENDIENTE,
                'enviada_en' => $enviada,
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->addDay()->setTime(17, 0),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['CMP-SSD-NV2-1TB'], 3),
                    $this->partidaServicio($servicios['SER-NODO-CAT6'], 2),
                ],
            ],
            [
                'folio' => 'DEMO-PENDIENTE-2-DIAS',
                'cliente' => 'CMS1804127L6',
                'estado' => Cotizacion::ESTADO_PENDIENTE,
                'enviada_en' => $enviada,
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->addDays(2)->setTime(12, 0),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['RED-AP-U6PLUS'], 2),
                    $this->partidaServicio($servicios['SER-INST-AP'], 2),
                ],
            ],
            [
                'folio' => 'DEMO-PENDIENTE-3-DIAS',
                'cliente' => 'CIF210315AB4',
                'estado' => Cotizacion::ESTADO_PENDIENTE,
                'enviada_en' => $ahora->copy()->subDay()->setTime(9, 30),
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->addDays(3)->setTime(16, 0),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['CCTV-CAM-HIK2MP'], 4),
                    $this->partidaServicio($servicios['SER-INST-CAM'], 4),
                ],
            ],
            [
                'folio' => 'DEMO-PENDIENTE-7-DIAS',
                'cliente' => 'LOHF900714M38',
                'estado' => Cotizacion::ESTADO_PENDIENTE,
                'enviada_en' => $ahora->copy()->subDay()->setTime(14, 0),
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->addDays(7)->setTime(18, 0),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['COM-LAP-E14G5'], 2),
                    $this->partidaServicio($servicios['SER-MANT-PREV'], 2),
                ],
            ],
            [
                'folio' => 'DEMO-VENCIDA-01',
                'cliente' => 'CMS1804127L6',
                'estado' => Cotizacion::ESTADO_VENCIDA,
                'enviada_en' => $ahora->copy()->subDays(10),
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->subDay()->setTime(18, 0),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['RED-AP-U6PLUS'], 1),
                ],
            ],
            [
                'folio' => 'DEMO-RECHAZADA-01',
                'cliente' => 'SLG190827KQ2',
                'estado' => Cotizacion::ESTADO_RECHAZADA,
                'enviada_en' => $ahora->copy()->subDays(8),
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->subDays(2),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['CMP-SSD-NV2-1TB'], 5),
                    $this->partidaServicio($servicios['SER-INST-AP'], 1),
                ],
            ],
            [
                'folio' => 'DEMO-CANCELADA-01',
                'cliente' => 'LOHF900714M38',
                'estado' => Cotizacion::ESTADO_CANCELADA,
                'enviada_en' => $ahora->copy()->subDays(9),
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->subDays(3),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['CCTV-CAM-HIK2MP'], 2),
                ],
            ],
            [
                'folio' => 'DEMO-CANCELADA-02',
                'cliente' => 'CIF210315AB4',
                'estado' => Cotizacion::ESTADO_CANCELADA,
                'enviada_en' => $ahora->copy()->subDays(6),
                'aceptada_en' => null,
                'vence_en' => $ahora->copy()->addDays(4),
                'entrega_limite_en' => null,
                'partidas' => [
                    $this->partidaProducto($productos['ACC-LOG-MK120'], 4),
                    $this->partidaServicio($servicios['SER-MANT-PREV'], 1),
                ],
            ],
            [
                'folio' => 'DEMO-ACEPTADA-PROXIMA',
                'cliente' => 'CIF210315AB4',
                'estado' => Cotizacion::ESTADO_ACEPTADA,
                'enviada_en' => $enviada,
                'aceptada_en' => $ahora->copy()->subDay(),
                'vence_en' => $ahora->copy()->addDays(10),
                'entrega_limite_en' => $siguienteDiaHabil,
                'partidas' => [
                    $this->partidaServicio($servicios['SER-INST-AP'], 1),
                    $this->partidaServicio($servicios['SER-CONF-SW'] ?? $servicios['SER-INST-AP'], 1),
                ],
            ],
            [
                'folio' => 'DEMO-ACEPTADA-2-DIAS',
                'cliente' => 'CMS1804127L6',
                'estado' => Cotizacion::ESTADO_ACEPTADA,
                'enviada_en' => $enviada,
                'aceptada_en' => $ahora->copy()->subDays(2),
                'vence_en' => $ahora->copy()->addDays(9),
                'entrega_limite_en' => PlazosHabiles::sumar($ahora, 2)->setTime(16, 0),
                'partidas' => [
                    $this->partidaServicio($servicios['SER-INST-CAM'], 3),
                ],
            ],
            [
                'folio' => 'DEMO-ACEPTADA-5-DIAS',
                'cliente' => 'SLG190827KQ2',
                'estado' => Cotizacion::ESTADO_ACEPTADA,
                'enviada_en' => $enviada,
                'aceptada_en' => $ahora->copy()->subDays(3),
                'vence_en' => $ahora->copy()->addDays(12),
                'entrega_limite_en' => PlazosHabiles::sumar($ahora, 5)->setTime(16, 0),
                'partidas' => [
                    $this->partidaProducto($productos['ACC-LOG-MK120'], 3),
                    $this->partidaServicio($servicios['SER-MANT-PREV'], 3),
                ],
            ],
        ];

        foreach ($cotizaciones as $datos) {
            $this->guardarCotizacionDemostracion($datos, $clientes, $usuario);
        }
    }

    private function guardarCotizacionDemostracion(array $datos, $clientes, Usuario $usuario): void
    {
        $cotizacion = Cotizacion::query()->updateOrCreate(
            ['folio' => $datos['folio']],
            [
                'cliente_id' => $clientes[$datos['cliente']]->id,
                'usuario_id' => $usuario->id,
                'area_solicitante' => 'Demostración',
                'estado' => $datos['estado'],
                'enviada_en' => $datos['enviada_en'],
                'aceptada_en' => $datos['aceptada_en'],
                'vence_en' => $datos['vence_en'],
                'entrega_limite_en' => $datos['entrega_limite_en'],
                'porcentaje_descuento' => $datos['porcentaje_descuento'] ?? 0,
                'total' => 0,
            ]
        );

        $cotizacion->partidas()->delete();
        $subtotal = 0;

        foreach ($datos['partidas'] as $partida) {
            $cotizacion->partidas()->create($partida);
            $subtotal += (float) $partida['subtotal'];
        }

        $cotizacion->update([
            'total' => CalculosCotizacion::descontar(
                $subtotal,
                (float) ($datos['porcentaje_descuento'] ?? 0)
            ),
        ]);
    }

    private function partidaProducto(Producto $producto, int $cantidad, float $descuento = 0): array
    {
        return [
            ...$this->snapshotProducto($producto),
            'tipo' => PartidaCotizacion::TIPO_PRODUCTO,
            'producto_id' => $producto->id,
            'servicio_id' => null,
            'descripcion' => $producto->descripcion ?: $producto->nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $producto->precio,
            'porcentaje_descuento' => $descuento,
            'subtotal' => CalculosCotizacion::descontar($cantidad * (float) $producto->precio, $descuento),
        ];
    }

    private function partidaServicio(Servicio $servicio, int $cantidad, float $descuento = 0): array
    {
        return [
            ...$this->snapshotServicio($servicio),
            'tipo' => PartidaCotizacion::TIPO_SERVICIO,
            'producto_id' => null,
            'servicio_id' => $servicio->id,
            'descripcion' => $servicio->descripcion ?: $servicio->nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $servicio->precio,
            'porcentaje_descuento' => $descuento,
            'subtotal' => CalculosCotizacion::descontar($cantidad * (float) $servicio->precio, $descuento),
        ];
    }

    private function snapshotProducto(Producto $producto): array
    {
        return [
            'nombre_concepto' => $producto->nombre,
            'codigo_concepto' => $producto->codigo,
            'marca_concepto' => $producto->marca,
            'modelo_concepto' => $producto->modelo,
            'unidad_concepto' => $producto->unidad,
            'requiere_numero_serie' => $producto->requiere_numero_serie,
        ];
    }

    private function snapshotServicio(Servicio $servicio): array
    {
        return [
            'nombre_concepto' => $servicio->nombre,
            'codigo_concepto' => $servicio->codigo,
            'marca_concepto' => null,
            'modelo_concepto' => null,
            'unidad_concepto' => $servicio->unidad,
            'requiere_numero_serie' => false,
        ];
    }

    private function sembrarPcParaPruebaDeCorreo(): void
    {
        $componentes = Producto::query()
            ->whereIn('codigo', ['PC-DEMO-CPU', 'PC-DEMO-MB', 'PC-DEMO-RAM', 'PC-DEMO-SSD', 'PC-DEMO-CASE', 'ACC-LOG-MK120'])
            ->get()
            ->keyBy('codigo');

        if ($componentes->count() < 6) {
            return;
        }

        $plantilla = PcArmada::query()->updateOrCreate(
            ['nombre' => 'PC Demo Oficina'],
            [
                'descripcion' => 'Plantilla de demostración para probar PC armadas y correo con PDF.',
                'activo' => true,
            ]
        );
        $plantilla->componentes()->delete();

        foreach ([
            ['codigo' => 'PC-DEMO-CPU', 'tipo' => Producto::TIPO_COMPONENTE_PROCESADOR, 'cantidad' => 1],
            ['codigo' => 'PC-DEMO-MB', 'tipo' => Producto::TIPO_COMPONENTE_PLACA_MADRE, 'cantidad' => 1],
            ['codigo' => 'PC-DEMO-RAM', 'tipo' => Producto::TIPO_COMPONENTE_MEMORIA_RAM, 'cantidad' => 2],
            ['codigo' => 'PC-DEMO-SSD', 'tipo' => Producto::TIPO_COMPONENTE_ALMACENAMIENTO, 'cantidad' => 1],
            ['codigo' => 'PC-DEMO-CASE', 'tipo' => Producto::TIPO_COMPONENTE_GABINETE, 'cantidad' => 1],
            ['codigo' => 'ACC-LOG-MK120', 'tipo' => Producto::TIPO_COMPONENTE_ACCESORIO, 'cantidad' => 1],
        ] as $orden => $componente) {
            $plantilla->componentes()->create([
                'producto_id' => $componentes[$componente['codigo']]->id,
                'tipo_componente_pc' => $componente['tipo'],
                'cantidad' => $componente['cantidad'],
                'orden' => $orden,
            ]);
        }

        $usuario = Usuario::query()->where('activo', true)->orderBy('id')->first();
        $cliente = Cliente::query()->updateOrCreate(
            ['rfc' => 'PCD261119PRU'],
            [
                'tipo' => 'fisica',
                'nombre' => 'Cliente de prueba PC y correo',
                'correo' => env('DEMO_CORREO_PRUEBA', 'demo.pc@comunitec.test'),
                'telefono' => '961 000 0000',
                'direccion' => 'Dirección de prueba',
                'codigo_postal' => '29000',
            ]
        );
        if ($usuario === null) {
            return;
        }

        $cotizacion = Cotizacion::query()->updateOrCreate(
            ['folio' => 'DEMO-PC-PARA-CORREO'],
            [
                'cliente_id' => $cliente->id,
                'usuario_id' => $usuario->id,
                'area_solicitante' => 'Prueba de PC armada y correo',
                'estado' => Cotizacion::ESTADO_BORRADOR,
                'enviada_en' => null,
                'aceptada_en' => null,
                'vence_en' => null,
                'entrega_limite_en' => null,
                'porcentaje_descuento' => 0,
                'total' => 0,
            ]
        );
        $cotizacion->configuracionesPc()->delete();
        $cotizacion->partidas()->delete();

        app(ServicioConfiguracionPc::class)->crearConfiguracionEnCotizacion(
            $cotizacion,
            [
                'nombre' => 'PC personalizada para prueba de correo',
                'descripcion' => 'PC de oficina armada con procesador Ryzen 5, memoria RAM, SSD y gabinete.',
                'componentes' => $plantilla->fresh('componentes')->componentes->map(fn ($componente): array => [
                    'producto_id' => $componente->producto_id,
                    'tipo_componente_pc' => $componente->tipo_componente_pc,
                    'cantidad' => $componente->cantidad,
                ])->all(),
            ]
        );
    }

    private function productos(): array
    {
        $productos = [
            ['categoria' => 'Componentes', 'codigo' => 'PC-DEMO-CPU', 'nombre' => 'Procesador AMD Ryzen 5 5600G', 'descripcion' => 'Procesador de demostración para PC armada.', 'marca' => 'AMD', 'modelo' => 'Ryzen 5 5600G', 'unidad' => 'pieza', 'precio' => 2399.00, 'existencias' => 8, 'requiere_numero_serie' => false, 'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PROCESADOR, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'PC-DEMO-MB', 'nombre' => 'Placa madre ASUS Prime B550M', 'descripcion' => 'Placa madre de demostración para PC armada.', 'marca' => 'ASUS', 'modelo' => 'Prime B550M-A', 'unidad' => 'pieza', 'precio' => 1999.00, 'existencias' => 6, 'requiere_numero_serie' => false, 'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'PC-DEMO-RAM', 'nombre' => 'Memoria RAM Kingston 8 GB DDR4', 'descripcion' => 'Módulo de memoria de demostración para PC armada.', 'marca' => 'Kingston', 'modelo' => 'KVR32N22S8/8', 'unidad' => 'pieza', 'precio' => 449.00, 'existencias' => 20, 'requiere_numero_serie' => false, 'tipo_componente_pc' => Producto::TIPO_COMPONENTE_MEMORIA_RAM, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'PC-DEMO-SSD', 'nombre' => 'SSD Kingston NV2 500 GB', 'descripcion' => 'Almacenamiento de demostración para PC armada.', 'marca' => 'Kingston', 'modelo' => 'SNV2S/500G', 'unidad' => 'pieza', 'precio' => 799.00, 'existencias' => 12, 'requiere_numero_serie' => false, 'tipo_componente_pc' => Producto::TIPO_COMPONENTE_ALMACENAMIENTO, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'PC-DEMO-CASE', 'nombre' => 'Gabinete Acteck básico', 'descripcion' => 'Gabinete de demostración para PC armada.', 'marca' => 'Acteck', 'modelo' => 'Babel', 'unidad' => 'pieza', 'precio' => 899.00, 'existencias' => 5, 'requiere_numero_serie' => false, 'tipo_componente_pc' => Producto::TIPO_COMPONENTE_GABINETE, 'activo' => true],
            ['categoria' => 'Equipos de cómputo', 'codigo' => 'COM-LAP-E14G5', 'nombre' => 'Laptop Lenovo ThinkPad E14 Gen 5', 'descripcion' => 'Laptop empresarial de 14 pulgadas, Intel Core i5, 16 GB RAM y SSD de 512 GB.', 'marca' => 'Lenovo', 'modelo' => 'ThinkPad E14 Gen 5', 'unidad' => 'pieza', 'precio' => 18999.00, 'existencias' => 6, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Equipos de cómputo', 'codigo' => 'COM-PC-HP400G9', 'nombre' => 'Computadora HP Pro SFF 400 G9', 'descripcion' => 'Equipo empresarial compacto con Intel Core i5, 16 GB RAM y SSD de 512 GB.', 'marca' => 'HP', 'modelo' => 'Pro SFF 400 G9', 'unidad' => 'pieza', 'precio' => 16750.00, 'existencias' => 4, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'CMP-RAM-KF16D4', 'nombre' => 'Memoria RAM Kingston Fury Beast 16 GB DDR4', 'descripcion' => 'Módulo de memoria DDR4 de 16 GB a 3200 MHz para computadora de escritorio.', 'marca' => 'Kingston', 'modelo' => 'KF432C16BB/16', 'unidad' => 'pieza', 'precio' => 899.00, 'existencias' => 20, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'CMP-SSD-NV2-1TB', 'nombre' => 'Unidad SSD Kingston NV2 de 1 TB', 'descripcion' => 'Unidad de estado sólido NVMe PCIe 4.0, formato M.2 2280.', 'marca' => 'Kingston', 'modelo' => 'SNV2S/1000G', 'unidad' => 'pieza', 'precio' => 1249.00, 'existencias' => 15, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'RED-AP-U6PLUS', 'nombre' => 'Punto de acceso UniFi U6 Plus', 'descripcion' => 'Punto de acceso Wi-Fi 6 de doble banda para interiores, administrable por UniFi.', 'marca' => 'Ubiquiti', 'modelo' => 'U6+', 'unidad' => 'pieza', 'precio' => 2899.00, 'existencias' => 10, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'RED-SW-TL2428P', 'nombre' => 'Switch administrable PoE+ de 24 puertos', 'descripcion' => 'Switch Gigabit administrable con 24 puertos PoE+ y enlaces SFP.', 'marca' => 'TP-Link', 'modelo' => 'TL-SG2428P', 'unidad' => 'pieza', 'precio' => 8499.00, 'existencias' => 5, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'CCTV-CAM-HIK2MP', 'nombre' => 'Cámara IP Hikvision 2 MP', 'descripcion' => 'Cámara tipo bala para exterior, resolución Full HD, visión nocturna y PoE.', 'marca' => 'Hikvision', 'modelo' => 'DS-2CD1023G2-I', 'unidad' => 'pieza', 'precio' => 1890.00, 'existencias' => 18, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'CCTV-XVR-DAH08', 'nombre' => 'Grabador Dahua XVR de 8 canales', 'descripcion' => 'Grabador pentahíbrido de 8 canales con compresión H.265+.', 'marca' => 'Dahua', 'modelo' => 'XVR1B08-I', 'unidad' => 'pieza', 'precio' => 2650.00, 'existencias' => 5, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'CCTV-HDD-SKY4TB', 'nombre' => 'Disco duro Seagate SkyHawk de 4 TB', 'descripcion' => 'Disco especializado para videovigilancia y operación continua.', 'marca' => 'Seagate', 'modelo' => 'ST4000VX016', 'unidad' => 'pieza', 'precio' => 2299.00, 'existencias' => 8, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'INF-CAB-CAT6-305', 'nombre' => 'Bobina de cable UTP Cat 6 de 305 m', 'descripcion' => 'Cable de red categoría 6, conductor de cobre, caja de 305 metros.', 'marca' => 'LinkedPRO', 'modelo' => 'PRO-CAT6-305', 'unidad' => 'caja', 'precio' => 2950.00, 'existencias' => 12, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'INF-RACK-12U', 'nombre' => 'Gabinete de pared para red de 12U', 'descripcion' => 'Gabinete metálico de 19 pulgadas con puerta frontal y accesorios de montaje.', 'marca' => 'Syscom', 'modelo' => 'SR-12U', 'unidad' => 'pieza', 'precio' => 3850.00, 'existencias' => 4, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Accesorios y energía', 'codigo' => 'ENE-UPS-APC1200', 'nombre' => 'UPS APC de 1200 VA', 'descripcion' => 'Respaldo de energía con regulación automática de voltaje y seis contactos.', 'marca' => 'APC', 'modelo' => 'BVX1200L-LM', 'unidad' => 'pieza', 'precio' => 2690.00, 'existencias' => 7, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Accesorios y energía', 'codigo' => 'ACC-LOG-MK120', 'nombre' => 'Kit de teclado y mouse Logitech MK120', 'descripcion' => 'Teclado y mouse alámbricos USB para oficina.', 'marca' => 'Logitech', 'modelo' => 'MK120', 'unidad' => 'kit', 'precio' => 399.00, 'existencias' => 25, 'requiere_numero_serie' => false, 'activo' => true],
        ];

        $familias = [
            ['categoria' => 'Componentes', 'prefijo' => 'CMP', 'tipo' => Producto::TIPO_COMPONENTE_PROCESADOR, 'nombre' => 'Procesador', 'marca' => 'AMD'],
            ['categoria' => 'Componentes', 'prefijo' => 'CMP', 'tipo' => Producto::TIPO_COMPONENTE_PLACA_MADRE, 'nombre' => 'Placa madre', 'marca' => 'ASUS'],
            ['categoria' => 'Componentes', 'prefijo' => 'CMP', 'tipo' => Producto::TIPO_COMPONENTE_MEMORIA_RAM, 'nombre' => 'Memoria RAM', 'marca' => 'Kingston'],
            ['categoria' => 'Componentes', 'prefijo' => 'CMP', 'tipo' => Producto::TIPO_COMPONENTE_ALMACENAMIENTO, 'nombre' => 'Unidad SSD', 'marca' => 'Western Digital'],
            ['categoria' => 'Componentes', 'prefijo' => 'CMP', 'tipo' => Producto::TIPO_COMPONENTE_TARJETA_GRAFICA, 'nombre' => 'Tarjeta gráfica', 'marca' => 'Gigabyte'],
            ['categoria' => 'Componentes', 'prefijo' => 'CMP', 'tipo' => Producto::TIPO_COMPONENTE_FUENTE_PODER, 'nombre' => 'Fuente de poder', 'marca' => 'Corsair'],
            ['categoria' => 'Componentes', 'prefijo' => 'CMP', 'tipo' => Producto::TIPO_COMPONENTE_GABINETE, 'nombre' => 'Gabinete', 'marca' => 'NZXT'],
            ['categoria' => 'Redes', 'prefijo' => 'RED', 'tipo' => null, 'nombre' => 'Equipo de red', 'marca' => 'TP-Link'],
            ['categoria' => 'Videovigilancia', 'prefijo' => 'CCTV', 'tipo' => null, 'nombre' => 'Equipo de videovigilancia', 'marca' => 'Hikvision'],
            ['categoria' => 'Accesorios y energía', 'prefijo' => 'ACC', 'tipo' => Producto::TIPO_COMPONENTE_ACCESORIO, 'nombre' => 'Accesorio de cómputo', 'marca' => 'Logitech'],
        ];

        for ($indice = 1; $indice <= 182; $indice++) {
            $familia = $familias[($indice - 1) % count($familias)];
            $numero = str_pad((string) $indice, 3, '0', STR_PAD_LEFT);
            $productos[] = [
                'categoria' => $familia['categoria'],
                'codigo' => $familia['prefijo'].'-DEMO-'.$numero,
                'nombre' => $familia['nombre'].' demo '.$numero,
                'descripcion' => $familia['nombre'].' de demostración para pruebas del inventario y cotizaciones.',
                'marca' => $familia['marca'],
                'modelo' => 'Modelo Demo '.$numero,
                'unidad' => 'pieza',
                'precio' => 299 + (($indice * 137) % 9700),
                'existencias' => 5 + ($indice % 20),
                'requiere_numero_serie' => in_array($familia['categoria'], ['Equipos de cómputo', 'Redes', 'Videovigilancia'], true),
                'tipo_componente_pc' => $familia['tipo'],
                'activo' => true,
            ];
        }

        return $productos;
    }

    private function servicios(): array
    {
        return [
            ['categoria' => 'Equipos de cómputo', 'codigo' => 'SER-MANT-PREV', 'nombre' => 'Mantenimiento preventivo de equipo de cómputo', 'descripcion' => 'Limpieza interna y externa, revisión de componentes, optimización y reporte técnico.', 'unidad' => 'equipo', 'precio' => 650.00, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'SER-INST-AP', 'nombre' => 'Instalación y configuración de punto de acceso', 'descripcion' => 'Montaje, configuración, actualización y pruebas de cobertura del equipo.', 'unidad' => 'servicio', 'precio' => 1450.00, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'SER-CONF-SW', 'nombre' => 'Configuración de switch administrable', 'descripcion' => 'Configuración de VLAN, enlaces troncales, seguridad y documentación básica.', 'unidad' => 'servicio', 'precio' => 2800.00, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'SER-INST-CAM', 'nombre' => 'Instalación de cámara de videovigilancia', 'descripcion' => 'Montaje, orientación, conexión, configuración y prueba de una cámara.', 'unidad' => 'cámara', 'precio' => 950.00, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'SER-NODO-CAT6', 'nombre' => 'Instalación de nodo de red Cat 6', 'descripcion' => 'Tendido, terminación, etiquetado y prueba de un nodo de cableado estructurado.', 'unidad' => 'nodo', 'precio' => 1250.00, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'SER-LEVANTAMIENTO', 'nombre' => 'Levantamiento técnico de infraestructura', 'descripcion' => 'Visita técnica, identificación de necesidades y elaboración de propuesta de solución.', 'unidad' => 'visita', 'precio' => 1800.00, 'activo' => true],
        ];
    }

    private function clientes(): array
    {
        return [
            ['tipo' => 'moral', 'nombre' => 'Centro Integral de Formación del Sureste, S.A. de C.V.', 'rfc' => 'CIF210315AB4', 'correo' => 'compras@cif-sureste.example', 'telefono' => '961 600 1840', 'direccion' => 'Boulevard Belisario Domínguez 2140, Col. Jardines de Tuxtla, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29020'],
            ['tipo' => 'moral', 'nombre' => 'Servicios Logísticos del Grijalva, S.A. de C.V.', 'rfc' => 'SLG190827KQ2', 'correo' => 'administracion@logistica-grijalva.example', 'telefono' => '961 612 4075', 'direccion' => 'Carretera Panamericana 3450, Col. Plan de Ayala, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29020'],
            ['tipo' => 'moral', 'nombre' => 'Clínica Médica San Marcos, S.C.', 'rfc' => 'CMS1804127L6', 'correo' => 'sistemas@clinica-san-marcos.example', 'telefono' => '961 613 9220', 'direccion' => '5a Norte Poniente 820, Col. Centro, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29000'],
            ['tipo' => 'fisica', 'nombre' => 'María Fernanda López Hernández', 'rfc' => 'LOHF900714M38', 'correo' => 'maria.lopez@example.com', 'telefono' => '961 218 7640', 'direccion' => 'Calle Primavera 118, Col. Las Palmas, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29040'],
        ];
    }
}
