@extends('layouts.aplicacion')

@section('titulo', 'Cotización '.$cotizacion->folio)
@section('clase_pagina', 'detalle-cotizacion')

@section('contenido')
            <x-encabezado-pagina
                :titulo="'Cotización '.$cotizacion->folio"
            >
                <x-slot:acciones>
                    <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
                    <x-boton variante="contorno" :href="route('cotizaciones.pdf', $cotizacion)">Generar PDF</x-boton>
                </x-slot:acciones>
            </x-encabezado-pagina>

            @include('cotizaciones.parciales.detalle.resumen')
            @include('cotizaciones.parciales.detalle.seguimiento-acciones')
            @include('cotizaciones.parciales.detalle.notas')
            @include('cotizaciones.parciales.detalle.historial-correo')
            @include('cotizaciones.parciales.detalle.formulario-concepto')
            @include('cotizaciones.parciales.detalle.conceptos')

@endsection

@push('scripts')
    <script>
        (() => {
            const tipo = document.querySelector('#tipo-partida');
            const grupos = document.querySelectorAll('[data-catalogo-tipo]');

            if (!tipo || grupos.length === 0) {
                return;
            }

            const actualizar = () => {
                grupos.forEach((grupo) => {
                    const visible = grupo.dataset.catalogoTipo === tipo.value;
                    grupo.hidden = !visible;
                    grupo.querySelector('select').disabled = !visible;
                });
            };

            tipo.addEventListener('change', actualizar);
            actualizar();
        })();
    </script>
@endpush
