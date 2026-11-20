@extends('layouts.aplicacion')

@section('titulo', 'Cotización '.$cotizacion->folio)
@section('clase_pagina', 'detalle-cotizacion')

@section('contexto_carga', 'formulario')
@section('contenido')

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
                    grupo.querySelectorAll('input, select, textarea').forEach((campo) => {
                        campo.disabled = !visible;
                    });
                });
            };

            tipo.addEventListener('change', actualizar);
            actualizar();

            document.querySelectorAll('[data-entero]').forEach((campo) => {
                campo.addEventListener('keydown', (evento) => {
                    if (['.', ',', 'e', 'E', '+', '-'].includes(evento.key)) {
                        evento.preventDefault();
                    }
                });
                campo.addEventListener('paste', (evento) => {
                    const texto = evento.clipboardData?.getData('text').trim() ?? '';
                    if (!/^\d+$/.test(texto)) {
                        evento.preventDefault();
                    }
                });
            });
        })();
    </script>
@endpush
