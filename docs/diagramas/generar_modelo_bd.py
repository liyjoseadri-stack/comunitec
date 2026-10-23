from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


SALIDA = Path(__file__).with_name("modelo-entidad-relacion-comunitec-legible.png")
ANCHO, ALTO = 5200, 3100
COLOR_FONDO = "#eef3f7"
COLOR_CABECERA = "#1769aa"
COLOR_CABECERA_OSCURA = "#0d4775"
COLOR_BORDE = "#6f8798"
COLOR_FILA = "#ffffff"
COLOR_FILA_ALTERNA = "#f6f9fb"
COLOR_TEXTO = "#17242d"
COLOR_TIPO = "#526875"
COLOR_RELACION = "#397da8"
COLOR_PK = "#f4b400"
COLOR_FK = "#d94f4f"
COLOR_UQ = "#7b61a8"
COLORES_RELACION = [
    "#0072b2", "#d55e00", "#009e73", "#cc79a7", "#e69f00",
    "#6f4e9c", "#1b9e77", "#c43c39", "#4d6fb3", "#8a6d1d",
]


def fuente(nombre: str, tamano: int) -> ImageFont.FreeTypeFont:
    rutas = [
        Path("C:/Windows/Fonts") / nombre,
        Path("C:/Windows/Fonts/segoeui.ttf"),
        Path("C:/Windows/Fonts/arial.ttf"),
    ]
    for ruta in rutas:
        if ruta.exists():
            return ImageFont.truetype(str(ruta), tamano)
    return ImageFont.load_default()


FUENTE_TITULO = fuente("segoeuib.ttf", 52)
FUENTE_SUBTITULO = fuente("segoeui.ttf", 25)
FUENTE_TABLA = fuente("segoeuib.ttf", 27)
FUENTE_COLUMNA = fuente("segoeui.ttf", 21)
FUENTE_COLUMNA_NEGRITA = fuente("segoeuib.ttf", 21)
FUENTE_TIPO = fuente("consola.ttf", 18)
FUENTE_LEYENDA = fuente("segoeui.ttf", 21)
FUENTE_CARDINALIDAD = fuente("segoeuib.ttf", 19)


TABLAS = {
    "usuarios": {
        "pos": (70, 210),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("nombre", "VARCHAR(255)", ""),
            ("correo", "VARCHAR(255)", "UQ"),
            ("correo_verificado_en", "TIMESTAMP NULL", ""),
            ("contrasena", "VARCHAR(255)", ""),
            ("rol", "VARCHAR(255)", ""),
            ("activo", "BOOLEAN", ""),
            ("token_recuerdo", "VARCHAR(100) NULL", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "clientes": {
        "pos": (870, 210),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("tipo", "VARCHAR(255)", ""),
            ("nombre", "VARCHAR(255)", ""),
            ("rfc", "VARCHAR(255)", "UQ"),
            ("correo", "VARCHAR(255)", ""),
            ("telefono", "VARCHAR(255)", ""),
            ("direccion", "VARCHAR(255)", ""),
            ("codigo_postal", "VARCHAR(10)", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "categorias": {
        "pos": (1670, 210),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("nombre", "VARCHAR(255)", "UQ"),
            ("activo", "BOOLEAN", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "productos": {
        "pos": (2470, 90),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("categoria_id", "BIGINT NULL", "FK"),
            ("nombre", "VARCHAR(255)", ""),
            ("descripcion", "TEXT NULL", ""),
            ("codigo", "VARCHAR(255)", "UQ"),
            ("marca", "VARCHAR(255) NULL", ""),
            ("modelo", "VARCHAR(255) NULL", ""),
            ("unidad", "VARCHAR(255)", ""),
            ("precio", "DECIMAL(12,2)", ""),
            ("existencias", "UNSIGNED INT", ""),
            ("requiere_numero_serie", "BOOLEAN", ""),
            ("activo", "BOOLEAN", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "servicios": {
        "pos": (3270, 170),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("categoria_id", "BIGINT NULL", "FK"),
            ("nombre", "VARCHAR(255)", ""),
            ("descripcion", "TEXT NULL", ""),
            ("codigo", "VARCHAR(255)", "UQ"),
            ("unidad", "VARCHAR(255)", ""),
            ("precio", "DECIMAL(12,2)", ""),
            ("activo", "BOOLEAN", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "cotizaciones": {
        "pos": (500, 1010),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("folio", "VARCHAR(255)", "UQ"),
            ("cliente_id", "BIGINT", "FK"),
            ("usuario_id", "BIGINT", "FK"),
            ("area_solicitante", "VARCHAR(255) NULL", ""),
            ("notas", "TEXT NULL", ""),
            ("estado", "VARCHAR(255)", ""),
            ("enviada_en", "TIMESTAMP NULL", ""),
            ("aceptada_en", "TIMESTAMP NULL", ""),
            ("vence_en", "TIMESTAMP NULL", ""),
            ("entrega_limite_en", "TIMESTAMP NULL", ""),
            ("porcentaje_descuento", "DECIMAL(5,2)", ""),
            ("total", "DECIMAL(12,2)", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "partidas_cotizacion": {
        "pos": (1360, 1050),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("cotizacion_id", "BIGINT", "FK"),
            ("producto_id", "BIGINT NULL", "FK"),
            ("servicio_id", "BIGINT NULL", "FK"),
            ("tipo", "VARCHAR(255)", ""),
            ("descripcion", "VARCHAR(255)", ""),
            ("cantidad", "DECIMAL(10,2)", ""),
            ("precio_unitario", "DECIMAL(12,2)", ""),
            ("porcentaje_descuento", "DECIMAL(5,2)", ""),
            ("subtotal", "DECIMAL(12,2)", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "envios_correo_cotizacion": {
        "pos": (70, 2100),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("cotizacion_id", "BIGINT", "FK"),
            ("usuario_id", "BIGINT", "FK"),
            ("destinatario", "VARCHAR(255)", ""),
            ("tipo", "VARCHAR(255)", ""),
            ("resultado", "VARCHAR(255)", ""),
            ("mensaje", "VARCHAR(255) NULL", ""),
            ("intentado_en", "TIMESTAMP", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "ventas": {
        "pos": (2260, 1030),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("folio", "VARCHAR(255)", "UQ"),
            ("cotizacion_id", "BIGINT", "FK UQ"),
            ("cliente_id", "BIGINT", "FK"),
            ("usuario_id", "BIGINT", "FK"),
            ("tipo_cliente", "VARCHAR(255)", ""),
            ("nombre_cliente", "VARCHAR(255)", ""),
            ("rfc_cliente", "VARCHAR(255)", ""),
            ("correo_cliente", "VARCHAR(255)", ""),
            ("telefono_cliente", "VARCHAR(255)", ""),
            ("direccion_cliente", "VARCHAR(255)", ""),
            ("codigo_postal_cliente", "VARCHAR(10)", ""),
            ("nombre_responsable", "VARCHAR(255)", ""),
            ("correo_responsable", "VARCHAR(255)", ""),
            ("vendida_en", "TIMESTAMP", ""),
            ("metodo_pago", "VARCHAR(255)", ""),
            ("detalle_metodo_pago", "VARCHAR(255) NULL", ""),
            ("subtotal", "DECIMAL(12,2)", ""),
            ("porcentaje_descuento", "DECIMAL(5,2)", ""),
            ("total", "DECIMAL(12,2)", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "partidas_venta": {
        "pos": (3140, 1120),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("venta_id", "BIGINT", "FK"),
            ("partida_cotizacion_id", "BIGINT", "FK UQ"),
            ("producto_id", "BIGINT NULL", "FK"),
            ("servicio_id", "BIGINT NULL", "FK"),
            ("tipo", "VARCHAR(255)", ""),
            ("descripcion", "VARCHAR(255)", ""),
            ("cantidad", "DECIMAL(10,2)", ""),
            ("precio_unitario", "DECIMAL(12,2)", ""),
            ("subtotal", "DECIMAL(12,2)", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
    "piezas_inventario": {
        "pos": (4030, 1060),
        "columnas": [
            ("id", "BIGINT", "PK"),
            ("producto_id", "BIGINT", "FK"),
            ("cotizacion_id", "BIGINT NULL", "FK"),
            ("partida_venta_id", "BIGINT NULL", "FK"),
            ("numero_serie", "VARCHAR(255)", "UQ"),
            ("estado", "VARCHAR(255)", ""),
            ("creado_en", "TIMESTAMP NULL", ""),
            ("actualizado_en", "TIMESTAMP NULL", ""),
        ],
    },
}


RELACIONES = [
    ("usuarios", "id", "cotizaciones", "usuario_id", "1", "N"),
    ("clientes", "id", "cotizaciones", "cliente_id", "1", "N"),
    ("categorias", "id", "productos", "categoria_id", "1", "N"),
    ("categorias", "id", "servicios", "categoria_id", "1", "N"),
    ("cotizaciones", "id", "partidas_cotizacion", "cotizacion_id", "1", "N"),
    ("productos", "id", "partidas_cotizacion", "producto_id", "1", "N"),
    ("servicios", "id", "partidas_cotizacion", "servicio_id", "1", "N"),
    ("cotizaciones", "id", "envios_correo_cotizacion", "cotizacion_id", "1", "N"),
    ("usuarios", "id", "envios_correo_cotizacion", "usuario_id", "1", "N"),
    ("cotizaciones", "id", "ventas", "cotizacion_id", "1", "0..1"),
    ("clientes", "id", "ventas", "cliente_id", "1", "N"),
    ("usuarios", "id", "ventas", "usuario_id", "1", "N"),
    ("ventas", "id", "partidas_venta", "venta_id", "1", "N"),
    ("partidas_cotizacion", "id", "partidas_venta", "partida_cotizacion_id", "1", "0..1"),
    ("productos", "id", "partidas_venta", "producto_id", "1", "N"),
    ("servicios", "id", "partidas_venta", "servicio_id", "1", "N"),
    ("productos", "id", "piezas_inventario", "producto_id", "1", "N"),
    ("cotizaciones", "id", "piezas_inventario", "cotizacion_id", "1", "N"),
    ("partidas_venta", "id", "piezas_inventario", "partida_venta_id", "1", "N"),
]


ANCHO_TABLA = 720
ALTO_CABECERA = 52
ALTO_FILA = 38


def caja(tabla: dict) -> tuple[int, int, int, int]:
    x, y = tabla["pos"]
    alto = ALTO_CABECERA + len(tabla["columnas"]) * ALTO_FILA
    return x, y, x + ANCHO_TABLA, y + alto


def centro_fila(tabla: dict, columna: str) -> int:
    indice = next(i for i, campo in enumerate(tabla["columnas"]) if campo[0] == columna)
    return tabla["pos"][1] + ALTO_CABECERA + indice * ALTO_FILA + ALTO_FILA // 2


def punto_conexion(origen: dict, columna_origen: str, destino: dict, columna_destino: str) -> tuple[tuple[int, int], tuple[int, int]]:
    ox1, oy1, ox2, oy2 = caja(origen)
    dx1, dy1, dx2, dy2 = caja(destino)
    ocx, ocy = (ox1 + ox2) // 2, centro_fila(origen, columna_origen)
    dcx, dcy = (dx1 + dx2) // 2, centro_fila(destino, columna_destino)

    if abs(dcx - ocx) >= abs(dcy - ocy):
        if dcx > ocx:
            return (ox2, ocy), (dx1, dcy)
        return (ox1, ocy), (dx2, dcy)

    if dcy > ocy:
        return (ocx, oy2), (dcx, dy1)
    return (ocx, oy1), (dcx, dy2)


def dibujar_relacion(
    dibujo: ImageDraw.ImageDraw,
    origen: dict,
    columna_origen: str,
    destino: dict,
    columna_destino: str,
    cardinalidad_origen: str,
    cardinalidad_destino: str,
    indice: int,
) -> None:
    inicio, fin = punto_conexion(origen, columna_origen, destino, columna_destino)
    desplazamiento = (indice % 9 - 4) * 18
    color = COLORES_RELACION[indice % len(COLORES_RELACION)]
    etiqueta = f"R{indice + 1:02d}"

    if inicio[0] in (caja(origen)[0], caja(origen)[2]):
        medio_x = (inicio[0] + fin[0]) // 2 + desplazamiento
        puntos = [inicio, (medio_x, inicio[1]), (medio_x, fin[1]), fin]
    else:
        medio_y = (inicio[1] + fin[1]) // 2 + desplazamiento
        puntos = [inicio, (inicio[0], medio_y), (fin[0], medio_y), fin]

    dibujo.line(puntos, fill="#ffffff", width=14, joint="curve")
    dibujo.line(puntos, fill=color, width=5, joint="curve")

    radio = 7
    dibujo.ellipse(
        (inicio[0] - radio, inicio[1] - radio, inicio[0] + radio, inicio[1] + radio),
        fill="#ffffff",
        outline=color,
        width=3,
    )
    dibujo.ellipse(
        (fin[0] - radio, fin[1] - radio, fin[0] + radio, fin[1] + radio),
        fill=color,
    )
    for punto, cardinalidad in ((inicio, cardinalidad_origen), (fin, cardinalidad_destino)):
        dibujo.rounded_rectangle((punto[0] + 8, punto[1] - 30, punto[0] + 88, punto[1] - 4), radius=6, fill="#ffffff", outline=color, width=2)
        dibujo.text((punto[0] + 14, punto[1] - 28), f"{etiqueta} {cardinalidad}", font=FUENTE_CARDINALIDAD, fill=color)


def dibujar_tabla(dibujo: ImageDraw.ImageDraw, nombre: str, tabla: dict) -> None:
    x1, y1, x2, y2 = caja(tabla)
    dibujo.rounded_rectangle((x1 + 10, y1 + 12, x2 + 16, y2 + 18), radius=13, fill="#9aabb5")
    dibujo.rounded_rectangle((x1, y1, x2, y2), radius=11, fill=COLOR_FILA, outline=COLOR_BORDE, width=3)
    dibujo.rounded_rectangle((x1, y1, x2, y1 + ALTO_CABECERA), radius=10, fill=COLOR_CABECERA)
    dibujo.rectangle((x1, y1 + ALTO_CABECERA - 10, x2, y1 + ALTO_CABECERA), fill=COLOR_CABECERA)
    dibujo.text((x1 + 20, y1 + 9), nombre, font=FUENTE_TABLA, fill="#ffffff")

    for indice, (columna, tipo, clave) in enumerate(tabla["columnas"]):
        fy1 = y1 + ALTO_CABECERA + indice * ALTO_FILA
        fy2 = fy1 + ALTO_FILA
        if indice % 2:
            dibujo.rectangle((x1 + 2, fy1, x2 - 2, fy2), fill=COLOR_FILA_ALTERNA)
        dibujo.line((x1, fy2, x2, fy2), fill="#dbe3e8", width=1)

        color_clave = None
        if "PK" in clave:
            color_clave = COLOR_PK
        elif "FK" in clave:
            color_clave = COLOR_FK
        elif "UQ" in clave:
            color_clave = COLOR_UQ

        if color_clave:
            dibujo.rounded_rectangle((x1 + 12, fy1 + 10, x1 + 28, fy1 + 26), radius=4, fill=color_clave)

        fuente_columna = FUENTE_COLUMNA_NEGRITA if clave else FUENTE_COLUMNA
        dibujo.text((x1 + 40, fy1 + 7), columna, font=fuente_columna, fill=COLOR_TEXTO)
        ancho_tipo = dibujo.textlength(tipo, font=FUENTE_TIPO)
        dibujo.text((x2 - ancho_tipo - 18, fy1 + 9), tipo, font=FUENTE_TIPO, fill=COLOR_TIPO)

        if clave:
            texto_clave = clave.replace(" ", "/")
            dibujo.text((x1 + 410, fy1 + 9), texto_clave, font=FUENTE_TIPO, fill=color_clave or COLOR_TIPO)


imagen = Image.new("RGB", (ANCHO, ALTO), COLOR_FONDO)
dibujo = ImageDraw.Draw(imagen)

dibujo.rectangle((0, 0, ANCHO, 132), fill="#263b4a")
dibujo.text((70, 28), "Modelo entidad-relación - COMUN&TEC", font=FUENTE_TITULO, fill="#ffffff")
dibujo.text(
    (70, 150),
    "Sistema administrativo para el control de cotizaciones, inventario y ventas · Esquema lógico MySQL",
    font=FUENTE_SUBTITULO,
    fill=COLOR_TIPO,
)

for indice, (origen, campo_origen, destino, campo_destino, card_origen, card_destino) in enumerate(RELACIONES):
    dibujar_relacion(
        dibujo,
        TABLAS[origen],
        campo_origen,
        TABLAS[destino],
        campo_destino,
        card_origen,
        card_destino,
        indice,
    )

for nombre, tabla in TABLAS.items():
    dibujar_tabla(dibujo, nombre, tabla)

leyenda_y = 2960
dibujo.rounded_rectangle((70, leyenda_y, 1510, leyenda_y + 86), radius=12, fill="#ffffff", outline=COLOR_BORDE, width=2)
leyenda = [
    (COLOR_PK, "PK llave primaria"),
    (COLOR_FK, "FK llave foránea"),
    (COLOR_UQ, "UQ valor único"),
]
x = 100
for color, texto_leyenda in leyenda:
    dibujo.rounded_rectangle((x, leyenda_y + 29, x + 20, leyenda_y + 49), radius=4, fill=color)
    dibujo.text((x + 32, leyenda_y + 24), texto_leyenda, font=FUENTE_LEYENDA, fill=COLOR_TEXTO)
    x += 430

dibujo.text(
    (3650, leyenda_y + 25),
    "1 = uno   ·   N = muchos   ·   0..1 = relación opcional única",
    font=FUENTE_LEYENDA,
    fill=COLOR_TIPO,
)

# Índice inequívoco de relaciones: permite seguir cada línea aunque se cruce con otra.
indice_x, indice_y = 1600, 2440
dibujo.rounded_rectangle((indice_x, indice_y, 5100, 2905), radius=12, fill="#ffffff", outline=COLOR_BORDE, width=2)
dibujo.text((indice_x + 24, indice_y + 16), "Relaciones entre campos", font=FUENTE_TABLA, fill=COLOR_CABECERA_OSCURA)
for i, (origen, campo_origen, destino, campo_destino, card_origen, card_destino) in enumerate(RELACIONES):
    columna = i // 7
    fila = i % 7
    x = indice_x + 28 + columna * 1140
    y = indice_y + 72 + fila * 52
    color = COLORES_RELACION[i % len(COLORES_RELACION)]
    dibujo.line((x, y + 12, x + 46, y + 12), fill=color, width=6)
    dibujo.text((x + 58, y), f"R{i + 1:02d}  {origen}.{campo_origen}  →  {destino}.{campo_destino}  ({card_origen}:{card_destino})", font=FUENTE_LEYENDA, fill=COLOR_TEXTO)

SALIDA.parent.mkdir(parents=True, exist_ok=True)
imagen.save(SALIDA, "PNG", optimize=True)
print(SALIDA.resolve())
