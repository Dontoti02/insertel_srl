# Guía de Importación de Materiales - INSERTEL

## Descripción
El sistema permite importar materiales masivamente desde archivos Excel (XLS, XLSX) o CSV para facilitar la carga inicial de inventario o actualizaciones masivas.

## Permisos Requeridos
- **Administrador** (ROL_ADMINISTRADOR = 1)
- **Jefe de Almacén** (ROL_JEFE_ALMACEN = 2)

## Formatos Soportados
- **CSV** (Comma Separated Values)
- **XLS** (Excel 97-2003)
- **XLSX** (Excel 2007+)

## Estructura de Datos

### Campos Obligatorios
- `codigo`: Código único del material
- `nombre`: Nombre descriptivo del material

### Campos Opcionales
- `descripcion`: Descripción detallada
- `categoria_id`: ID numérico de categoría existente
- `categoria_nombre`: Nombre de categoría (se crea automáticamente si no existe)
- `unidad`: Unidad de medida (unidad, metro, kit)
- `stock_actual`: Cantidad actual en inventario
- `stock_minimo`: Cantidad mínima para alertas
- `costo_unitario`: Costo por unidad del material
- `proveedor_id`: ID numérico de proveedor existente
- `proveedor_nombre`: Nombre de proveedor (se crea automáticamente si no existe)
- `ubicacion`: Ubicación física en almacén
- `estado`: Estado del material (activo/inactivo)

## Proceso de Importación

### 1. Preparación
1. Descargar plantilla desde el sistema
2. Completar datos siguiendo el formato
3. Guardar como CSV, XLS o XLSX

### 2. Validaciones Automáticas
- Verificación de campos obligatorios
- Validación de formato de archivo
- Verificación de tamaño (máximo 5MB)
- Detección de códigos duplicados

### 3. Procesamiento
- **Materiales nuevos**: Se crean automáticamente
- **Materiales existentes**: Se actualizan con nuevos datos
- **Categorías nuevas**: Se crean automáticamente
- **Proveedores nuevos**: Se crean automáticamente

### 4. Resultados
- Reporte de materiales importados
- Reporte de materiales actualizados
- Lista de errores encontrados
- Registro en auditoría del sistema

## Ejemplos de Uso

### Ejemplo 1: Material Básico
```csv
codigo,nombre,descripcion,categoria_nombre,unidad,stock_actual,estado
MAT001,Cable UTP,Cable de red categoria 6,Cables,metros,100,activo
```

### Ejemplo 2: Material Completo
```csv
codigo,nombre,descripcion,categoria_nombre,unidad,stock_actual,stock_minimo,costo_unitario,proveedor_nombre,ubicacion,estado
MAT002,Router WiFi,Equipo de red inalambrico,Equipos,unidades,20,5,89.99,Tech Solutions,Almacen C1,activo
```

## Consideraciones Importantes

### Códigos de Material
- Deben ser únicos en el sistema
- Se recomienda usar un formato consistente (ej: MAT001, MAT002)
- No se permiten códigos vacíos

### Categorías y Proveedores
- Si usa `categoria_id`, debe existir en el sistema
- Si usa `categoria_nombre`, se creará automáticamente si no existe
- Mismo comportamiento para proveedores

### Estados Válidos
- `activo`: Material disponible para uso
- `inactivo`: Material deshabilitado

### Unidades Válidas
- `unidad`: Para elementos contables
- `metro`: Para cables y materiales lineales
- `kit`: Para conjuntos o paquetes

## Solución de Problemas

### Error: "Código y nombre son obligatorios"
- Verificar que las columnas `codigo` y `nombre` tengan datos
- Revisar que no haya filas vacías

### Error: "El nombre de usuario ya está en uso"
- El código del material ya existe en el sistema
- Cambiar el código o actualizar el material existente

### Error: "Formato de archivo no válido"
- Verificar que el archivo sea CSV, XLS o XLSX
- Guardar nuevamente en el formato correcto

### Error: "El archivo es demasiado grande"
- Dividir el archivo en partes más pequeñas
- Máximo permitido: 5MB

## Acceso al Sistema
- **URL**: `/views/almacen/importar_materiales.php` (Jefe de Almacén)
- **URL**: `/views/admin/importar_materiales.php` (Administrador)
- **Plantillas**: Disponibles para descarga en la página de importación

## Auditoría
Todas las importaciones quedan registradas en el sistema con:
- Usuario que realizó la importación
- Fecha y hora
- Cantidad de materiales procesados
- Detalles de la operación
