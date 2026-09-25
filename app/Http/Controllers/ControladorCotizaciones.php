<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudGuardarCotizacionCompleta;
use App\Mail\CorreoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EnvioCotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use App\Services\ServicioCreacionCotizacion;
use App\Services\ServicioInventarioCotizacion;
use App\Support\PlazosHabiles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ControladorCotizaciones extends Controller
{
    public function listar(Request $solicitud): View
    {
        $datos = $solicitud->validate([
            'estado' => ['nullable', Rule::in(Cotizacion::estados())],
        ]);
        $estado = $datos['estado'] ?? null;
        $clientes = Cliente::orderBy('nombre')->get();
        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $servicios = Servicio::with('categoria')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('cotizaciones.listado', [
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
                    'tipo' => 'producto',
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'categoria' => $producto->categoria?->nombre,
                    'descripcion' => $producto->descripcion,
                    'precio' => (float) $producto->precio,
                ])->concat($servicios->map(fn (Servicio $servicio): array => [
                    'id' => $servicio->id,
                    'tipo' => 'servicio',
                    'nombre' => $servicio->nombre,
                    'codigo' => $servicio->codigo,
                    'categoria' => $servicio->categoria?->nombre,
                    'descripcion' => $servicio->descripcion,
                    'precio' => (float) $servicio->precio,
                ]))->values(),
            ],
            'cotizaciones' => Cotizacion::with('venta', 'cliente')
                ->when($estado, fn ($consulta, $valor) => $consulta->where('estado', $valor))
                ->latest('creado_en')
                ->get(),
            'estadoFiltrado' => $estado,
            'puedeEditar' => auth()->user()->esAdministrador() || auth()->user()->esComercial(),
            'folioInformativo' => 'COT-'.now()->format('Ymd').'-AUTOMÁTICO',
            'vigenciaEstimada' => PlazosHabiles::sumar(now(), 15),
        ]);
    }

    public function mostrar(Cotizacion $cotizacion): View
    {
        $cotizacion->load('cliente', 'partidas.producto', 'partidas.servicio', 'enviosCorreo.usuario', 'venta');
        $productos = $cotizacion->partidas->where('tipo', 'producto')->groupBy('producto_id');
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

    public function enviar(Cotizacion $cotizacion): RedirectResponse
    {
        return $this->procesarCorreo($cotizacion, 'borrador');
    }

    public function enviarCorreo(Cotizacion $cotizacion): RedirectResponse
    {
        return $this->procesarCorreo($cotizacion, 'pendiente');
    }

    private function procesarCorreo(Cotizacion $cotizacion, string $estadoEsperado): RedirectResponse
    {
        $transporte = config('mail.mailers.'.config('mail.default').'.transport');
        if ($transporte === 'log' || ($transporte === 'array' && ! app()->runningUnitTests())) {
            return back()->with('error', 'Configura un servicio de correo real antes de enviar. La cotización conserva su estado.');
        }

        $destinatario = $cotizacion->cliente()->value('correo');
        $usuarioId = (int) auth()->id();

        try {
            DB::transaction(function () use ($cotizacion, $destinatario, $estadoEsperado, $usuarioId) {
                $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
                abort_unless($actual->estado === $estadoEsperado, 422, 'El estado de la cotización no permite este envío.');
                $actual->load('cliente', 'partidas', 'responsable');
                if ($estadoEsperado === 'borrador') {
                    $actual->fill([
                        'estado' => 'pendiente',
                        'enviada_en' => now(),
                        'vence_en' => PlazosHabiles::sumar(now(), 15),
                    ]);
                }
                Mail::to($destinatario)->send(new CorreoCotizacion($actual));
                $actual->save();
                $actual->enviosCorreo()->create([
                    'usuario_id' => $usuarioId,
                    'destinatario' => $destinatario,
                    'tipo' => EnvioCotizacion::TIPO_COTIZACION,
                    'resultado' => EnvioCotizacion::RESULTADO_ACEPTADO,
                    'mensaje' => 'El servicio de correo aceptó el mensaje para su envío.',
                    'intentado_en' => now(),
                ]);
            });
        } catch (TransportExceptionInterface $excepcion) {
            report($excepcion);
            $cotizacion->enviosCorreo()->create([
                'usuario_id' => $usuarioId,
                'destinatario' => $destinatario,
                'tipo' => EnvioCotizacion::TIPO_COTIZACION,
                'resultado' => EnvioCotizacion::RESULTADO_FALLIDO,
                'mensaje' => 'El servicio de correo no aceptó el mensaje.',
                'intentado_en' => now(),
            ]);

            return back()->with('error', 'No se pudo enviar el correo. La cotización conserva su estado y fechas; puedes volver a intentarlo.');
        }

        return back()->with('success', 'El servicio de correo aceptó el envío de la cotización al cliente.');
    }

    public function rechazar(Cotizacion $cotizacion): RedirectResponse
    {
        DB::transaction(function () use ($cotizacion) {
            $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            abort_unless($actual->estado === 'pendiente', 422, 'Solo se pueden rechazar cotizaciones pendientes.');
            $actual->update([
                'estado' => 'rechazada',
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
                'borrador',
                'pendiente',
                'aceptada',
            ], true), 422, 'El estado actual de la cotización no permite cancelarla.');

            if ($actual->estado === 'aceptada') {
                return app(ServicioInventarioCotizacion::class)->liberar($actual);
            }

            $actual->update([
                'estado' => 'cancelada',
            ]);

            return false;
        });

        return back()->with('success', $liberoPiezas
            ? 'La cotización fue cancelada y las piezas se liberaron.'
            : 'La cotización fue cancelada.');
    }

    public function aceptar(Cotizacion $cotizacion, ServicioInventarioCotizacion $inventario): RedirectResponse
    {
        abort_unless($cotizacion->estado === 'pendiente', 422);

        try {
            $inventario->reservar($cotizacion);
        } catch (RuntimeException) {
            return back()->with('error', 'No hay piezas disponibles suficientes para aceptar la cotización.');
        }

        return back()->with('success', 'La cotización fue aceptada y las piezas quedaron reservadas por 5 días.');
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        $datos = $this->validarEncabezado($solicitud);
        Cotizacion::create([
            ...$datos,
            'usuario_id' => $solicitud->user()->id,
            'folio' => 'COT-'.now()->format('Ymd').'-'.Str::ulid(),
            'estado' => 'borrador',
            'total' => 0,
        ]);

        return redirect()->route('cotizaciones.listado')->with('success', 'Se creó la cotización en borrador.');
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
                'nullable',
                'numeric',
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
                'borrador',
                'pendiente',
                'aceptada',
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

            if ($cotizacion->estado === 'aceptada') {
                app(ServicioInventarioCotizacion::class)->liberarParaEdicion($cotizacion);
            }
            $cotizacion->total = round($cotizacion->partidas()->sum('subtotal') * (1 - $cotizacion->porcentaje_descuento / 100), 2);
            $cotizacion->save();
        });

        return back()->with('success', 'Datos guardados. Si modificaste una cotización aceptada, ahora requiere una nueva aceptación.');
    }
}
