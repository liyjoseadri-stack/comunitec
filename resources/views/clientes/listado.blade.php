@extends('layouts.aplicacion')

@section('titulo', 'Clientes')

@section('contenido')
            <h1 class="text-2xl font-semibold">
                Clientes
            </h1>
            <form class="mt-5 grid gap-3 bg-white p-5" method="POST" action="{{ route('clientes.guardar') }}">
                @csrf
                <select name="tipo">
                    <option value="moral">
                        Persona moral
                    </option>
                    <option value="fisica">
                        Persona física
                    </option>
                </select>
                <input name="nombre" placeholder="Nombre o razón social" required>
                <input name="rfc" placeholder="RFC" required>
                <input name="correo" type="email" placeholder="Correo" required>
                <input name="telefono" placeholder="Teléfono" required>
                <input name="direccion" placeholder="Dirección" required>
                <input name="codigo_postal" placeholder="Código postal" required>
                <button type="submit">
                    Guardar cliente
                </button>
            </form>
            <ul class="mt-5">
                @foreach($clientes as $cliente)
                    <li>
                        {{ $cliente->nombre }} — {{ $cliente->rfc }}
                    </li>
                @endforeach
            </ul>

@endsection
