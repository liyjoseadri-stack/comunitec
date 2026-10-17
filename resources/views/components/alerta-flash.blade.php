@foreach (['estado' => 'exito', 'success' => 'exito', 'error' => 'peligro', 'advertencia' => 'advertencia'] as $clave => $variante)
    @if (session()->has($clave))
        <div class="alerta alerta-flash alerta--{{ $variante }}" role="{{ $variante === 'exito' ? 'status' : 'alert' }}">
            {{ session($clave) }}
        </div>
    @endif
@endforeach
