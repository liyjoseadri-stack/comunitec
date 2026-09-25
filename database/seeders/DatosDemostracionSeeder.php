<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class DatosDemostracionSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = collect([
            'Equipos de cómputo',
            'Componentes',
            'Redes',
            'Videovigilancia',
            'Infraestructura',
            'Accesorios y energía',
        ])->mapWithKeys(function (string $nombre): array {
            $categoria = Categoria::query()->updateOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );

            return [$nombre => $categoria->id];
        });

        foreach ($this->productos() as $datos) {
            $categoria = $datos['categoria'];
            unset($datos['categoria']);

            Producto::query()->updateOrCreate(
                ['codigo' => $datos['codigo']],
                $datos + ['categoria_id' => $categorias[$categoria]]
            );
        }

        foreach ($this->servicios() as $datos) {
            $categoria = $datos['categoria'];
            unset($datos['categoria']);

            Servicio::query()->updateOrCreate(
                ['codigo' => $datos['codigo']],
                $datos + ['categoria_id' => $categorias[$categoria]]
            );
        }

        foreach ($this->clientes() as $datos) {
            Cliente::query()->updateOrCreate(['rfc' => $datos['rfc']], $datos);
        }
    }

    private function productos(): array
    {
        return [
            ['categoria' => 'Equipos de cómputo', 'codigo' => 'COM-LAP-E14G5', 'nombre' => 'Laptop Lenovo ThinkPad E14 Gen 5', 'descripcion' => 'Laptop empresarial de 14 pulgadas, Intel Core i5, 16 GB RAM y SSD de 512 GB.', 'marca' => 'Lenovo', 'modelo' => 'ThinkPad E14 Gen 5', 'unidad' => 'pieza', 'precio' => 18999.00, 'existencias' => 6, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Equipos de cómputo', 'codigo' => 'COM-PC-HP400G9', 'nombre' => 'Computadora HP Pro SFF 400 G9', 'descripcion' => 'Equipo empresarial compacto con Intel Core i5, 16 GB RAM y SSD de 512 GB.', 'marca' => 'HP', 'modelo' => 'Pro SFF 400 G9', 'unidad' => 'pieza', 'precio' => 16750.00, 'existencias' => 4, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'CMP-RAM-KF16D4', 'nombre' => 'Memoria RAM Kingston Fury Beast 16 GB DDR4', 'descripcion' => 'Módulo de memoria DDR4 de 16 GB a 3200 MHz para computadora de escritorio.', 'marca' => 'Kingston', 'modelo' => 'KF432C16BB/16', 'unidad' => 'pieza', 'precio' => 899.00, 'existencias' => 20, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Componentes', 'codigo' => 'CMP-SSD-NV2-1TB', 'nombre' => 'Unidad SSD Kingston NV2 de 1 TB', 'descripcion' => 'Unidad de estado sólido NVMe PCIe 4.0, formato M.2 2280.', 'marca' => 'Kingston', 'modelo' => 'SNV2S/1000G', 'unidad' => 'pieza', 'precio' => 1249.00, 'existencias' => 15, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'RED-AP-U6PLUS', 'nombre' => 'Punto de acceso UniFi U6 Plus', 'descripcion' => 'Punto de acceso Wi-Fi 6 de doble banda para interiores, administrable por UniFi.', 'marca' => 'Ubiquiti', 'modelo' => 'U6+', 'unidad' => 'pieza', 'precio' => 2899.00, 'existencias' => 10, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'RED-SW-TL2428P', 'nombre' => 'Switch administrable PoE+ de 24 puertos', 'descripcion' => 'Switch Gigabit administrable con 24 puertos PoE+ y enlaces SFP.', 'marca' => 'TP-Link', 'modelo' => 'TL-SG2428P', 'unidad' => 'pieza', 'precio' => 8499.00, 'existencias' => 5, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'CCTV-CAM-HIK2MP', 'nombre' => 'Cámara IP Hikvision 2 MP', 'descripcion' => 'Cámara tipo bala para exterior, resolución Full HD, visión nocturna y PoE.', 'marca' => 'Hikvision', 'modelo' => 'DS-2CD1023G2-I', 'unidad' => 'pieza', 'precio' => 1890.00, 'existencias' => 18, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'CCTV-XVR-DAH08', 'nombre' => 'Grabador Dahua XVR de 8 canales', 'descripcion' => 'Grabador pentahíbrido de 8 canales con compresión H.265+.', 'marca' => 'Dahua', 'modelo' => 'XVR1B08-I', 'unidad' => 'pieza', 'precio' => 2650.00, 'existencias' => 5, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'CCTV-HDD-SKY4TB', 'nombre' => 'Disco duro Seagate SkyHawk de 4 TB', 'descripcion' => 'Disco especializado para videovigilancia y operación continua.', 'marca' => 'Seagate', 'modelo' => 'ST4000VX016', 'unidad' => 'pieza', 'precio' => 2299.00, 'existencias' => 8, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'INF-CAB-CAT6-305', 'nombre' => 'Bobina de cable UTP Cat 6 de 305 m', 'descripcion' => 'Cable de red categoría 6, conductor de cobre, caja de 305 metros.', 'marca' => 'LinkedPRO', 'modelo' => 'PRO-CAT6-305', 'unidad' => 'caja', 'precio' => 2950.00, 'existencias' => 12, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'INF-RACK-12U', 'nombre' => 'Gabinete de pared para red de 12U', 'descripcion' => 'Gabinete metálico de 19 pulgadas con puerta frontal y accesorios de montaje.', 'marca' => 'Syscom', 'modelo' => 'SR-12U', 'unidad' => 'pieza', 'precio' => 3850.00, 'existencias' => 4, 'requiere_numero_serie' => false, 'activo' => true],
            ['categoria' => 'Accesorios y energía', 'codigo' => 'ENE-UPS-APC1200', 'nombre' => 'UPS APC de 1200 VA', 'descripcion' => 'Respaldo de energía con regulación automática de voltaje y seis contactos.', 'marca' => 'APC', 'modelo' => 'BVX1200L-LM', 'unidad' => 'pieza', 'precio' => 2690.00, 'existencias' => 7, 'requiere_numero_serie' => true, 'activo' => true],
            ['categoria' => 'Accesorios y energía', 'codigo' => 'ACC-LOG-MK120', 'nombre' => 'Kit de teclado y mouse Logitech MK120', 'descripcion' => 'Teclado y mouse alámbricos USB para oficina.', 'marca' => 'Logitech', 'modelo' => 'MK120', 'unidad' => 'kit', 'precio' => 399.00, 'existencias' => 25, 'requiere_numero_serie' => false, 'activo' => true],
        ];
    }

    private function servicios(): array
    {
        return [
            ['categoria' => 'Equipos de cómputo', 'codigo' => 'SER-MANT-PREV', 'nombre' => 'Mantenimiento preventivo de equipo de cómputo', 'descripcion' => 'Limpieza interna y externa, revisión de componentes, optimización y reporte técnico.', 'unidad' => 'equipo', 'precio' => 650.00, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'SER-INST-AP', 'nombre' => 'Instalación y configuración de punto de acceso', 'descripcion' => 'Montaje, configuración, actualización y pruebas de cobertura del equipo.', 'unidad' => 'servicio', 'precio' => 1450.00, 'activo' => true],
            ['categoria' => 'Redes', 'codigo' => 'SER-CONF-SW', 'nombre' => 'Configuración de switch administrable', 'descripcion' => 'Configuración de VLAN, enlaces troncales, seguridad y documentación básica.', 'unidad' => 'servicio', 'precio' => 2800.00, 'activo' => true],
            ['categoria' => 'Videovigilancia', 'codigo' => 'SER-INST-CAM', 'nombre' => 'Instalación de cámara de videovigilancia', 'descripcion' => 'Montaje, orientación, conexión, configuración y prueba de una cámara.', 'unidad' => 'cámara', 'precio' => 950.00, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'SER-NODO-CAT6', 'nombre' => 'Instalación de nodo de red Cat 6', 'descripcion' => 'Tendido, terminación, etiquetado y prueba de un nodo de cableado estructurado.', 'unidad' => 'nodo', 'precio' => 1250.00, 'activo' => true],
            ['categoria' => 'Infraestructura', 'codigo' => 'SER-LEVANTAMIENTO', 'nombre' => 'Levantamiento técnico de infraestructura', 'descripcion' => 'Visita técnica, identificación de necesidades y elaboración de propuesta de solución.', 'unidad' => 'visita', 'precio' => 1800.00, 'activo' => true],
        ];
    }

    private function clientes(): array
    {
        return [
            ['tipo' => 'moral', 'nombre' => 'Centro Integral de Formación del Sureste, S.A. de C.V.', 'rfc' => 'CIF210315AB4', 'correo' => 'compras@cif-sureste.example', 'telefono' => '961 600 1840', 'direccion' => 'Boulevard Belisario Domínguez 2140, Col. Jardines de Tuxtla, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29020'],
            ['tipo' => 'moral', 'nombre' => 'Servicios Logísticos del Grijalva, S.A. de C.V.', 'rfc' => 'SLG190827KQ2', 'correo' => 'administracion@logistica-grijalva.example', 'telefono' => '961 612 4075', 'direccion' => 'Carretera Panamericana 3450, Col. Plan de Ayala, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29020'],
            ['tipo' => 'moral', 'nombre' => 'Clínica Médica San Marcos, S.C.', 'rfc' => 'CMS1804127L6', 'correo' => 'sistemas@clinica-san-marcos.example', 'telefono' => '961 613 9220', 'direccion' => '5a Norte Poniente 820, Col. Centro, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29000'],
            ['tipo' => 'fisica', 'nombre' => 'María Fernanda López Hernández', 'rfc' => 'LOHF900714M38', 'correo' => 'maria.lopez@example.com', 'telefono' => '961 218 7640', 'direccion' => 'Calle Primavera 118, Col. Las Palmas, Tuxtla Gutiérrez, Chiapas', 'codigo_postal' => '29040'],
        ];
    }
}
