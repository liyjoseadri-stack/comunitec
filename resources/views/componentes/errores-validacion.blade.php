@if ($errors->any())
    <section
        class="alerta alerta--peligro mensaje-error"
        id="resumen-errores-validacion"
        role="alert"
        aria-labelledby="titulo-errores-validacion"
        tabindex="-1"
    >
        <h2 id="titulo-errores-validacion">Revisa los datos ingresados</h2>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif
