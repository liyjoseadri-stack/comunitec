            @if ($cotizacion->enviosCorreo->isNotEmpty())
                <section class="bloque-administrativo" aria-labelledby="titulo-historial-correo">
                    <h2 id="titulo-historial-correo">
                        Historial de correo
                    </h2>
                    <p>
                        Cada registro indica si el servicio de correo aceptó el mensaje. La aceptación técnica no confirma que el cliente lo haya leído.
                    </p>
                    <x-tabla-responsive etiqueta="Historial de envíos por correo">
                        <table class="tabla-registros">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        Fecha
                                    </th>
                                    <th scope="col">
                                        Destinatario
                                    </th>
                                    <th scope="col">
                                        Tipo
                                    </th>
                                    <th scope="col">
                                        Resultado
                                    </th>
                                    <th scope="col">
                                        Usuario
                                    </th>
                                    <th scope="col">
                                        Detalle
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cotizacion->enviosCorreo as $envio)
                                    <tr>
                                        <th scope="row">
                                            {{ $envio->intentado_en->format('d/m/Y H:i') }}
                                        </th>
                                        <td>
                                            {{ $envio->destinatario }}
                                        </td>
                                        <td>
                                            {{ $envio->etiquetaTipo() }}
                                        </td>
                                        <td>
                                            {{ $envio->etiquetaResultado() }}
                                        </td>
                                        <td>
                                            {{ $envio->usuario?->nombre ?? 'Usuario no disponible' }}
                                        </td>
                                        <td>
                                            {{ $envio->mensaje }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-tabla-responsive>
                </section>
            @endif
