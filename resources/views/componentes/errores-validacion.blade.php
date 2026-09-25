@if ($errors->any())
    <section class="alerta alerta--peligro mensaje-error" role="alert" aria-labelledby="titulo-errores-validacion">
        <h2 id="titulo-errores-validacion">Revisa los filtros</h2>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif
