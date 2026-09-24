<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControladorClientes extends Controller
{
    public function listar(Request $request)
    {
        $customers = Cliente::query()
            ->when(
                $request->string('search')->toString(),
                fn ($query, $search) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('rfc', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->get();

        return view('clientes.listado', compact('customers'));
    }

    public function guardar(Request $request)
    {
        Cliente::create($this->datosValidados($request));

        return redirect()->route('clientes.listado');
    }

    public function actualizar(Request $request, Cliente $customer)
    {
        $customer->update($this->datosValidados($request, $customer));

        return redirect()->route('clientes.listado');
    }

    private function datosValidados(Request $request, ?Cliente $customer = null): array
    {
        return $request->validate([
            'type' => [
                'required',
                'in:fisica,moral',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'rfc' => [
                'required',
                'string',
                'max:13',
                Rule::unique('customers')->ignore($customer),
            ],
            'email' => [
                'required',
                'email',
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
            ],
            'address' => [
                'required',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'required',
                'string',
                'max:10',
            ],
        ]);
    }
}
