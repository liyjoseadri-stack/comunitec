# PC armadas y Personalizar PC

## Operación

1. Clasifica los productos desde Inventario con el apartado correspondiente de PC. Solo los productos activos y con existencias aparecen en el configurador.
2. En Inventario > PC armadas registra una plantilla pública con nombre, descripción y componentes. Una plantilla puede editarse o desactivarse sin cambiar cotizaciones ya guardadas.
3. En una nueva cotización usa Agregar plantilla para cargar una PC armada o Personalizar PC para crear una configuración privada. Cada componente se conserva como una partida individual dentro del grupo de la PC.
4. Al aceptar la cotización, el inventario reserva cada producto del grupo dentro de la misma transacción. Si la existencia cambió, la aceptación se rechaza completa y no se reserva parcialmente.
5. Al convertir en venta, solo se solicitan series de los componentes que las requieren. La venta conserva el nombre de la PC y el apartado de cada componente.

Las configuraciones privadas nunca aparecen en el listado global de PC armadas. Los precios, nombres, códigos, marcas, modelos, unidades, requisito de serie y tipo se copian como snapshot en las partidas; los cambios posteriores al catálogo o a una plantilla no alteran operaciones existentes.
