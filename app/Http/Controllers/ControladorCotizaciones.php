<?php

namespace App\Http\Controllers;

use App\Mail\CorreoCotizacion;
use App\Models\ArticuloCatalogo;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PiezaInventario;
use App\Services\ServicioInventarioCotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ControladorCotizaciones extends Controller
{
    public function listar(): View
    {
        return view('cotizaciones.listado', [
            'customers' => Cliente::orderBy('name')->get(),
            'quotes' => Cotizacion::latest()->get(),
            'puedeEditar' => auth()->user()->esAdministrador() || auth()->user()->esComercial(),
        ]);
    }

    public function mostrar(Cotizacion $quote): View
    {
        $quote->load('customer', 'lines');
        $productos = $quote->lines->where('type', 'product')->groupBy('catalog_item_id');
        $disponibles = PiezaInventario::whereIn('catalog_item_id', $productos->keys())
            ->where('status', 'available')->selectRaw('catalog_item_id, COUNT(*) AS cantidad')
            ->groupBy('catalog_item_id')->pluck('cantidad', 'catalog_item_id');
        $shortages = $productos
            ->map(function ($partidas, $articulo) use ($disponibles): array {
                return [

                    'description' => $partidas->first()->description,

                    'requested' => (int) $partidas->sum('quantity'),

                    'available' => (int) ($disponibles[$articulo] ?? 0),

                ];
            })->filter(fn ($faltante) => $faltante['requested'] > $faltante['available']);

        return view('cotizaciones.detalle', [
            'quote' => $quote,
            'puedeEditar' => auth()->user()->esAdministrador() || auth()->user()->esComercial(),
            'clientes' => Cliente::orderBy('name')->get(),
            'items' => ArticuloCatalogo::where('active',
                true)->get(),
            'shortages' => $shortages,
        ]);
    }

    public function pdf(Cotizacion $quote)
    {
        return Pdf::loadView('cotizaciones.pdf', [
            'quote' => $quote->load('customer',
                'lines'),
        ])->download("{$quote->folio}.pdf");
    }

    public function enviar(Cotizacion $quote): RedirectResponse
    {
        return $this->procesarCorreo($quote, 'draft');
    }

    public function enviarCorreo(Cotizacion $quote): RedirectResponse
    {
        return $this->procesarCorreo($quote, 'pending');
    }

    private function procesarCorreo(Cotizacion $cotizacion, string $estadoEsperado): RedirectResponse
    {
        $transporte = config('mail.mailers.'.config('mail.default').'.transport');
        if ($transporte === 'log' || ($transporte === 'array' && ! app()->runningUnitTests())) {
            return back()->with('error', 'Configura un servicio de correo real antes de enviar. La cotización conserva su estado.');
        }
        try {
            DB::transaction(function () use ($cotizacion, $estadoEsperado) {
                $actual = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
                abort_unless($actual->status === $estadoEsperado, 422, 'El estado de la cotización no permite este envío.');
                $actual->load('customer', 'lines');
                if ($estadoEsperado === 'draft') {
                    $actual->fill([
                        'status' => 'pending',
                        'sent_at' => now(),
                        'expires_at' => now()->addDays(15),
                    ]);
                }
                Mail::to($actual->customer->email)->send(new CorreoCotizacion($actual));
                $actual->save();
            });
        } catch (TransportExceptionInterface $excepcion) {
            report($excepcion);

            return back()->with('error', 'No se pudo enviar el correo. La cotización conserva su estado y fechas; puedes volver a intentarlo.');
        }

        return back()->with('success', 'El servicio de correo aceptó el envío de la cotización al cliente.');
    }

    public function rechazar(Cotizacion $quote): RedirectResponse
    {
        DB::transaction(function () use ($quote) {
            $actual = Cotizacion::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            abort_unless($actual->status === 'pending', 422, 'Solo se pueden rechazar cotizaciones pendientes.');
            $actual->update([
                'status' => 'rejected',
            ]);
        });

        return back()->with('success', 'La cotización fue rechazada.');
    }

    public function cancelar(Cotizacion $quote): RedirectResponse
    {
        $liberoPiezas = DB::transaction(function () use ($quote): bool {
            $actual = Cotizacion::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($actual->status, [
                'draft',
                'pending',
                'accepted',
            ], true), 422, 'El estado actual de la cotización no permite cancelarla.');

            if ($actual->status === 'accepted') {
                return app(ServicioInventarioCotizacion::class)->liberar($actual);
            }

            $actual->update([
                'status' => 'cancelled',
            ]);

            return false;
        });

        return back()->with('success', $liberoPiezas
            ? 'La cotización fue cancelada y las piezas se liberaron.'
            : 'La cotización fue cancelada.');
    }

    public function aceptar(Cotizacion $quote, ServicioInventarioCotizacion $inventory): RedirectResponse
    {
        abort_unless($quote->status === 'pending', 422);

        try {
            $inventory->reservar($quote);
        } catch (RuntimeException) {
            return back()->with('error', 'No hay piezas disponibles suficientes para aceptar la cotización.');
        }

        return back()->with('success', 'La cotización fue aceptada y las piezas quedaron reservadas por 5 días.');
    }

    public function guardar(Request $request): RedirectResponse
    {
        $data = $this->validarEncabezado($request);
        Cotizacion::create([
            ...$data,
            'user_id' => $request->user()->id,
            'folio' => 'COT-'.now()->format('Ymd').'-'.Str::ulid(),
            'status' => 'draft',
            'total' => 0,
        ]);

        return redirect()->route('cotizaciones.listado')->with('success', 'Se creó la cotización en borrador.');
    }

    private function validarEncabezado(Request $solicitud): array
    {
        $datos = $solicitud->validate([

            'customer_id' => [
                'required',
                'exists:customers,id',
            ],

            'area_requesting' => [
                'nullable',
                'string',
                'max:255',
            ],

            'discount_percent' => [
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
        $datos['discount_percent'] = $datos['discount_percent'] ?? 0;
        $datos['area_requesting'] = $datos['area_requesting'] ?? null;

        return $datos;
    }

    public function actualizar(Request $solicitud, Cotizacion $quote): RedirectResponse
    {
        $datos = $this->validarEncabezado($solicitud);

        DB::transaction(function () use ($quote, $datos) {
            $cotizacion = Cotizacion::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($cotizacion->status, [
                'draft',
                'pending',
                'accepted',
            ], true), 422, 'Esta cotización ya no admite cambios.');
            $cotizacion->fill($datos);
            if (! $cotizacion->isDirty([
                'customer_id',
                'area_requesting',
                'discount_percent',
            ])) {
                return;
            }

            if ($cotizacion->status === 'accepted') {
                PiezaInventario::where('quote_id', $cotizacion->id)->where('status', 'reserved')->update([
                    'status' => 'available',
                    'quote_id' => null,
                ]);
                $cotizacion->fill([
                    'status' => 'pending',
                    'accepted_at' => null,
                    'expires_at' => now()->addDays(15),
                ]);
            }
            $cotizacion->total = round($cotizacion->lines()->sum('subtotal') * (1 - $cotizacion->discount_percent / 100), 2);
            $cotizacion->save();
        });

        return back()->with('success', 'Datos guardados. Si modificaste una cotización aceptada, ahora requiere una nueva aceptación.');
    }
}
