@extends('layouts.aplicacion')
@section('titulo', $articulo->nombre)
@section('contenido')
<x-encabezado-pagina :titulo="$articulo->nombre" :descripcion="'SKU '.$articulo->codigo"><x-slot:acciones><x-boton variante="contorno" :href="route('inventario.listado')">Regresar</x-boton></x-slot:acciones></x-encabezado-pagina>
@include('componentes.errores-validacion') @if(session('success'))<p class="mensaje-exito">{{session('success')}}</p>@endif
<section class="bloque-administrativo"><h2>Editar artículo</h2><form class="formulario-administrativo formulario-catalogo" method="POST" action="{{route('inventario.productos.actualizar',$articulo)}}">@csrf @method('PUT')
<div class="campo-formulario"><label for="tipo">Tipo</label><select id="tipo" name="tipo"><option value="producto" @selected(old('tipo',$articulo->tipo)==='producto')>Producto</option><option value="servicio" @selected(old('tipo',$articulo->tipo)==='servicio')>Servicio</option></select></div>
<div class="campo-formulario"><label for="categoria_id">Categoría</label><select id="categoria_id" name="categoria_id" required>@foreach($categorias as $categoria)<option value="{{$categoria->id}}" @selected(old('categoria_id',$articulo->categoria_id)==$categoria->id)>{{$categoria->nombre}}</option>@endforeach</select></div>
@foreach(['nombre'=>'Nombre','codigo'=>'Código/SKU','marca'=>'Marca','modelo'=>'Modelo','unidad'=>'Unidad'] as $campo=>$etiqueta)<div class="campo-formulario"><label for="{{$campo}}">{{$etiqueta}}</label><input id="{{$campo}}" name="{{$campo}}" value="{{old($campo,$articulo->$campo)}}" @required(in_array($campo,['nombre','codigo','unidad']))></div>@endforeach
<div class="campo-formulario campo-formulario--ancho"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion">{{old('descripcion',$articulo->descripcion)}}</textarea></div>
<div class="campo-formulario"><label for="precio">Precio</label><input id="precio" name="precio" type="number" step="0.01" min="0" value="{{old('precio',$articulo->precio)}}" required></div><div class="campo-formulario"><label for="existencias">Existencias</label><input id="existencias" name="existencias" type="number" min="0" value="{{old('existencias',$articulo->existencias)}}"></div>
<label class="campo-formulario"><span>Números de serie</span><span><input type="checkbox" name="requiere_numero_serie" value="1" @checked(old('requiere_numero_serie',$articulo->requiere_numero_serie))> Requiere serie al vender</span></label><x-boton tipo="submit">Guardar cambios</x-boton></form></section>
@if($articulo->seriesVendidas->isNotEmpty())<section class="bloque-administrativo"><h2>Series vendidas</h2><ul>@foreach($articulo->seriesVendidas as $serie)<li>{{$serie->numero_serie}}</li>@endforeach</ul></section>@endif
@endsection
