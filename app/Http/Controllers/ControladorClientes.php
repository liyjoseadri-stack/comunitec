<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControladorClientes extends Controller
{
    public function listar(Request $solicitud)
    {
        $clientes = Cliente::query()
            ->when(
                $solicitud->string('search')->toString(),
                fn ($consulta, $busqueda) => $consulta
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('rfc', 'like', "%{$busqueda}%")
            )
            ->orderBy('nombre')
            ->get();

        return view('clientes.listado', compact('clientes'));
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
