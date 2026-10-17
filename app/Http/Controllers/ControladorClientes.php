<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorClientes extends Controller
{
    public function listar(Request $solicitud): View
    {
        $busqueda = trim($solicitud->string('buscar')->toString());
        $clientes = Cliente::query()
            ->when($busqueda, fn ($consulta) => $consulta->where(function ($consulta) use ($busqueda) {
                $consulta
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('rfc', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%")
                    ->orWhere('telefono', 'like', "%{$busqueda}%")
                    ->orWhere('direccion', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_postal', 'like', "%{$busqueda}%");
            }))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.listado', compact('clientes'));
    }

    public function crear(): View
    {
        return view('clientes.crear');
    }

    public function editar(Cliente $cliente): View
    {
        return view('clientes.editar', compact('cliente'));
    }

    public function guardar(Request $solicitud)
    {
        Cliente::create($this->datosValidados($solicitud));

        return redirect()->route('clientes.listado');
    }

    public function actualizar(Request $solicitud, Cliente $cliente)
    {
        $cliente->update($this->datosValidados($solicitud, $cliente));

        return redirect()->route('clientes.listado');
    }

    private function datosValidados(Request $solicitud, ?Cliente $cliente = null): array
    {
        return $solicitud->validate([
            'tipo' => [
                'required',
                'in:fisica,moral',
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],
            'rfc' => [
                'required',
                'string',
                'max:13',
                Rule::unique('clientes')->ignore($cliente),
            ],
            'correo' => [
                'required',
                'email',
            ],
            'telefono' => [
                'required',
                'string',
                'max:30',
            ],
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],
            'codigo_postal' => [
                'required',
                'string',
                'max:10',
            ],
        ]);
    }
}
