<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudGuardarCotizacionCompleta;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Rules\NumeroFinito;
use App\Services\ResultadoEnvioManualCotizacion;
use App\Services\ServicioCreacionCotizacion;
use App\Services\ServicioEnvioManualCotizacion;
use App\Services\ServicioInventarioCotizacion;
use App\Support\CalculosCotizacion;
use App\Support\PlazosHabiles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ControladorCotizaciones extends Controller
{
    public function listar(Request $solicitud): View
    {
        $datos = $solicitud->validate([
            'estado' => ['nullable', Rule::in(Cotizacion::estados())],
        ]);
        $estado = $datos['estado'] ?? null;

        return view('cotizaciones.listado', [
            'cotizaciones' => Cotizacion::with('venta', 'cliente')
                ->when($estado, fn ($consulta, $valor) => $consulta->where('estado', $valor))
                ->latest('creado_en')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'estadoFiltrado' => $estado,
        ]);
    }

    public function crear(): View
    {
        $clientes = Cliente::orderBy('nombre')->get();
        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $servicios = Servicio::with('categoria')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('cotizaciones.crear', [
            'clientes' => $clientes,
            'productos' => $productos,
            'servicios' => $servicios,
            'datosCreador' => [
                'clientes' => $clientes->map(fn (Cliente $cliente): array => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'rfc' => $cliente->rfc,
                ])->values(),
                'catalogo' => $productos->map(fn (Producto $producto): array => [
                    'id' => $producto->id,
                    'tipo' => PartidaCotizacion::TIPO_PRODUCTO,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'categoria' => $producto->categoria?->nombre,
                    'descripcion' => $producto->descripcion,
                    'precio' => (float) $producto->precio,
                ])->concat($servicios->map(fn (Servicio $servicio): array => [
                    'id' => $servicio->id,
                    'tipo' => PartidaCotizacion::TIPO_SERVICIO,
                    'nombre' => $servicio->nombre,
                    'codigo' => $servicio->codigo,
                    'categoria' => $servicio->categoria?->nombre,
                    'descripcion' => $servicio->descripcion,
                    'precio' => (float) $servicio->precio,
                ]))->values(),
            ],
            'puedeEditar' => true,
            'folioInformativo' => 'COT-'.now()->format('Ymd').'-AUTOMÁTICO',
            'vigenciaEstimada' => PlazosHabiles::sumar(now(), 15),
        ]);
    }

    public function mostrar(Cotizacion $cotizacion): View
    {
        $cotizacion->load('cliente', 'partidas.producto', 'partidas.servicio', 'enviosCorreo.usuario', 'venta');
        $productos = $cotizacion->partidas->where('tipo', PartidaCotizacion::TIPO_PRODUCTO)->groupBy('producto_id');
        $disponibles = Producto::whereIn('id', $productos->keys())
            ->pluck('existencias', 'id');
        $faltantes = $productos
            ->map(function ($partidas, $articulo) use ($disponibles): array {
                return [

                    'descripcion' => $partidas->first()->descripcion,

                    'solicitado' => (int) $partidas->sum('cantidad'),

                    'disponible' => (int) ($disponibles[$articulo] ?? 0),

                ];
            })->filter(fn ($faltante) => $faltante['solicitado'] > $faltante['disponible']);

        return view('cotizaciones.detalle', [
            'cotizacion' => $cotizacion,
            'puedeEditar' => $cotizacion->venta === null
                && (auth()->user()->esAdministrador() || auth()->user()->esComercial()),
            'clientes' => Cliente::orderBy('nombre')->get(),
            'productos' => Producto::with('categoria')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
            'servicios' => Servicio::with('categoria')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
            'faltantes' => $faltantes,
        ]);
    }

    public function pdf(Cotizacion $cotizacion)
    {
        return Pdf::loadView('cotizaciones.pdf', [
            'cotizacion' => $cotizacion->load(
                'cliente',
                'partidas.producto',
                'partidas.servicio',
                'responsable'
            ),
        ])->download("{$cotizacion->folio}.pdf");
    }

    public function enviar(Request $solicitud, Cotizacion $cotizacion): RedirectResponse
    {
        return $this->procesarCorreo($solicitud, $cotizacion, Cotizacion::ESTADO_BORRADOR);
    }

    public function enviarCorreo(Request $solicitud, Cotizacion $cotizacion): RedirectResponse
    {
        return $this->procesarCorreo($solicitud, $cotizacion, Cotizacion::ESTADO_PENDIENTE);
    }

    private function procesarCorreo(Request $solicitud, Cotizacion $cotizacion, string $estadoEsperado): RedirectResponse
    {
        $transporte = config('mail.mailers.'.config('mail.default').'.transport');
        if ($transporte === 'log' || ($transporte === 'array' && ! app()->runningUnitTests())) {
            return back()->with('error', 'Configura un servicio de correo real antes de enviar. La cotización conserva su estado.');
        }

        $datos = $solicitud->validate([
            'token_envio' => ['required', 'uuid'],
        ]);
        $resultado = app(ServicioEnvioManualCotizacion::class)->procesar(
            $cotizacion,
            $estadoEsperado,
            $datos['token_envio'],
            (int) auth()->id()
        );

        return match ($resultado->estado) {
            ResultadoEnvioManualCotizacion::ACEPTADO => back()->with('success', 'El servicio de correo aceptó el envío de la cotización al cliente.'),
            ResultadoEnvioManualCotizacion::FALLIDO => back()->with('error', 'No se pudo enviar el correo. La cotización conserva su estado y fechas; abre de nuevo el formulario para realizar otro intento.'),
            ResultadoEnvioManualCotizacion::ESTADO_CAMBIO => back()->with('advertencia', 'El correo fue aceptado, pero la cotización cambió de estado durante el envío. Se conservó el estado más reciente.'),
            default => back()->with('advertencia', 'No se puede confirmar el resultado del intento. No lo repitas: revisa el historial o solicita verificación técnica.'),
        };
    }

    public function rechazar(Cotizacion $cotizacion): RedirectResponse
    {
        DB::transaction(function () use ($cotizacion) {
            $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            abort_unless($actual->estado === Cotizacion::ESTADO_PENDIENTE, 422, 'Solo se pueden rechazar cotizaciones pendientes.');
            $actual->update([
                'estado' => Cotizacion::ESTADO_RECHAZADA,
            ]);
        });

        return back()->with('success', 'La cotización fue rechazada.');
    }

    public function cancelar(Cotizacion $cotizacion): RedirectResponse
    {
        $liberoPiezas = DB::transaction(function () use ($cotizacion): bool {
            $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            abort_if($actual->venta()->exists(), 422, 'Una cotización convertida en venta ya no puede cancelarse.');
            abort_unless(in_array($actual->estado, [
                Cotizacion::ESTADO_BORRADOR,
                Cotizacion::ESTADO_PENDIENTE,
                Cotizacion::ESTADO_ACEPTADA,
            ], true), 422, 'El estado actual de la cotización no permite cancelarla.');

            if ($actual->estado === Cotizacion::ESTADO_ACEPTADA) {
                return app(ServicioInventarioCotizacion::class)->liberar($actual);
            }

            $actual->update([
                'estado' => Cotizacion::ESTADO_CANCELADA,
            ]);

            return false;
        });

        return back()->with('success', $liberoPiezas
            ? 'La cotización fue cancelada y las piezas se liberaron.'
            : 'La cotización fue cancelada.');
    }

    public function aceptar(Cotizacion $cotizacion, ServicioInventarioCotizacion $inventario): RedirectResponse
    {
        try {
            $inventario->reservar($cotizacion);
        } catch (RuntimeException) {
            return back()->with('error', 'No hay piezas disponibles suficientes para aceptar la cotización.');
        }

        return back()->with('success', 'La cotización fue aceptada y las piezas quedaron reservadas por 5 días.');
    }

    public function guardarCompleta(
        SolicitudGuardarCotizacionCompleta $solicitud,
        ServicioCreacionCotizacion $creador
    ): JsonResponse {
        $cotizacion = $creador->crear($solicitud->validated(), $solicitud->user());

        return response()->json([
            'mensaje' => 'La cotización se guardó correctamente.',
            'cotizacion_id' => $cotizacion->id,
            'folio' => $cotizacion->folio,
            'redireccion' => route('cotizaciones.detalle', $cotizacion),
        ], 201);
    }

    private function validarEncabezado(Request $solicitud): array
    {
        $datos = $solicitud->validate([

            'cliente_id' => [
                'required',
                'exists:clientes,id',
            ],

            'area_solicitante' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notas' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'porcentaje_descuento' => [
                'bail',
                'nullable',
                new NumeroFinito,
                'numeric',
                CalculosCotizacion::REGLA_PRECISION,
                function ($atributo,
                    $valor,
                    $fallar) {
                    if ((float) $valor !== 0.0 && ((float) $valor < 5 || (float) $valor > 10)) {
                        $fallar('El descuento debe ser 0 (sin descuento) o estar entre 5% y 10%.');
                    }
                },
            ],

        ]);
        $datos['porcentaje_descuento'] = $datos['porcentaje_descuento'] ?? 0;
        $datos['area_solicitante'] = $datos['area_solicitante'] ?? null;
        $datos['notas'] = $datos['notas'] ?? null;

        return $datos;
    }

    public function actualizar(Request $solicitud, Cotizacion $cotizacion): RedirectResponse
    {
        $datos = $this->validarEncabezado($solicitud);

        DB::transaction(function () use ($cotizacion, $datos) {
            $cotizacion = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            abort_if($cotizacion->venta()->exists(), 422, 'Una cotización convertida en venta ya no admite cambios.');
            abort_unless(in_array($cotizacion->estado, [
                Cotizacion::ESTADO_BORRADOR,
                Cotizacion::ESTADO_PENDIENTE,
                Cotizacion::ESTADO_ACEPTADA,
            ], true), 422, 'Esta cotización ya no admite cambios.');
            $cotizacion->fill($datos);
            if (! $cotizacion->isDirty([
                'cliente_id',
                'area_solicitante',
                'notas',
                'porcentaje_descuento',
            ])) {
                return;
            }

            if ($cotizacion->estado === Cotizacion::ESTADO_ACEPTADA) {
                app(ServicioInventarioCotizacion::class)->liberarParaEdicion($cotizacion);
            }
            $cotizacion->total = CalculosCotizacion::descontar(
                $cotizacion->partidas()->sum('subtotal'),
                $cotizacion->porcentaje_descuento
            );
            $cotizacion->save();
        });

        return back()->with('success', 'Datos guardados. Si modificaste una cotización aceptada, ahora requiere una nueva aceptación.');
    }
}
