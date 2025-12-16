# Mejoras para Roles Técnico y Jefe de Almacén

## 📅 Fecha de Implementación
**2025-12-01**

## 🎯 Objetivo
Mejorar las funcionalidades de los roles **Técnico** y **Jefe de Almacén** para optimizar el control de inventario, seguimiento de servicios y análisis de consumo de materiales.

---

## 👷 ROL TÉCNICO - Nuevas Funcionalidades

### 1. ✅ Ver Stock Asignado (Ya existía)
**Ubicación:** `views/tecnico/mi_stock.php`

El técnico puede ver todos los materiales y equipos que le han sido asignados, incluyendo:
- Código del material
- Nombre del material
- Cantidad disponible
- Unidad de medida
- Fecha de asignación
- Valor total asignado

---

### 2. 🆕 Alertas de Equipos Sin Usar
**Ubicación:** `views/tecnico/alertas_equipos.php`

Sistema de alertas inteligente que notifica al técnico sobre materiales/equipos sin actividad:

#### Niveles de Alerta:
- **🟡 Alerta Moderada:** 30-60 días sin usar
- **🔴 Alerta Crítica:** Más de 60 días sin usar

#### Características:
- Muestra días de inactividad
- Fecha de última actividad
- Recomendaciones de acción
- Botón directo para liquidar materiales
- Estadísticas visuales

---

### 3. 🆕 Liquidar Materiales y Equipos
**Ubicación:** `views/tecnico/liquidar_materiales.php`

Permite al técnico registrar el consumo de materiales/equipos utilizados en servicios:

#### Funcionalidades:
- Ver actas pendientes de liquidación
- Seleccionar materiales usados por acta
- Registrar cantidades consumidas
- Validación de stock disponible
- Historial de liquidaciones
- Actualización automática de inventario

#### Proceso:
1. Técnico selecciona acta pendiente
2. Elige materiales utilizados
3. Indica cantidades consumidas
4. Sistema valida stock
5. Reduce stock del técnico
6. Registra movimiento de consumo
7. Actualiza estado del acta

---

### 4. 🆕 Registro de Actas con Foto
**Ubicación:** `views/tecnico/actas.php` (Mejorado)

#### Mejoras Implementadas:
- **Subida de foto del acta:** JPG, JPEG, PNG (Máx. 5MB)
- **Tipos de servicio actualizados:**
  - ✅ Instalación
  - ✅ Mantenimiento
  - ✅ Postventa
- Campo de materiales utilizados
- Estado de liquidación
- Observaciones detalladas

---

## 📊 ROL JEFE DE ALMACÉN - Nuevas Funcionalidades

### 1. 🆕 Estadísticas de Uso de Materiales
**Ubicación:** `views/almacen/estadisticas_uso.php`

Dashboard completo de análisis de consumo de materiales por técnicos:

#### Secciones del Dashboard:

##### 📈 Estadísticas Generales
- Total de materiales utilizados
- Técnicos activos
- Total de liquidaciones
- Items consumidos

##### 🏆 Top 20 Materiales Más Usados
Muestra:
- Ranking de materiales
- Código y nombre
- Categoría
- Total usado
- Número de técnicos que lo usan
- Veces usado
- Última liquidación

##### 👥 Técnicos con Mayor Consumo
Muestra:
- Nombre del técnico
- Total de items usados
- Número de servicios realizados
- Materiales diferentes utilizados
- Última liquidación

##### 🛠️ Uso por Tipo de Servicio
Análisis de consumo por:
- Instalación
- Mantenimiento
- Postventa

Datos mostrados:
- Total de materiales usados
- Número de técnicos
- Cantidad de liquidaciones

##### 🏷️ Consumo por Categoría
- Materiales diferentes por categoría
- Total usado por categoría
- Porcentaje visual con barra de progreso

#### Funcionalidades Adicionales:
- **Impresión de reportes:** Botón para imprimir estadísticas
- **Filtrado automático por sede**
- **Visualización clara con gráficos**

---

## 🗄️ Base de Datos - Cambios Implementados

### Nueva Tabla: `liquidaciones_materiales`
```sql
CREATE TABLE liquidaciones_materiales (
  id INT PRIMARY KEY AUTO_INCREMENT,
  acta_id INT NOT NULL,
  tecnico_id INT NOT NULL,
  material_id INT NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  fecha_liquidacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  sede_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Modificaciones a Tablas Existentes:

#### `actas_tecnicas`
- ➕ `foto_acta` VARCHAR(255) - Ruta de la foto del acta
- ➕ `estado_liquidacion` ENUM('pendiente', 'liquidada') - Estado de liquidación

#### `stock_tecnicos`
- ➕ `updated_at` TIMESTAMP - Última actualización del stock

#### `movimientos_inventario`
- 🔄 `tipo_movimiento` - Agregado valor 'consumo'

---

## 📁 Archivos Creados/Modificados

### Nuevos Archivos:
1. `views/tecnico/liquidar_materiales.php` - Liquidación de materiales
2. `views/tecnico/alertas_equipos.php` - Alertas de equipos sin usar
3. `views/almacen/estadisticas_uso.php` - Estadísticas de uso
4. `migrations/005_mejoras_tecnicos_almacen.sql` - Script de migración

### Archivos Modificados:
1. `views/tecnico/actas.php` - Agregada subida de foto y tipos de servicio
2. `views/layouts/menu_tecnico.php` - Nuevas opciones de menú
3. `views/layouts/menu_jefe.php` - Nueva opción de estadísticas

---

## 🚀 Instalación

### 1. Ejecutar Migración de Base de Datos
```bash
mysql -u root -p insertel < migrations/005_mejoras_tecnicos_almacen.sql
```

### 2. Verificar Permisos de Carpetas
```bash
# Asegurar que la carpeta de actas existe y tiene permisos
mkdir -p uploads/actas
chmod 755 uploads/actas
```

### 3. Verificar Configuración
- Verificar que `ACTAS_PATH` está definido en `config/constants.php`
- Verificar que `MAX_FILE_SIZE` permite archivos de hasta 5MB

---

## 📊 Flujo de Trabajo

### Para Técnicos:

1. **Recibir Asignación de Materiales**
   - Jefe de almacén asigna materiales
   - Técnico ve en "Mi Stock"

2. **Realizar Servicio**
   - Técnico realiza instalación/mantenimiento/postventa
   - Registra acta con foto
   - Indica materiales utilizados (opcional)

3. **Liquidar Materiales**
   - Accede a "Liquidar Materiales"
   - Selecciona acta pendiente
   - Indica cantidades exactas usadas
   - Sistema actualiza inventario

4. **Monitorear Alertas**
   - Revisa "Alertas de Equipos"
   - Actúa sobre materiales sin usar
   - Devuelve o liquida según corresponda

### Para Jefe de Almacén:

1. **Asignar Materiales**
   - Asigna materiales a técnicos según necesidad

2. **Monitorear Consumo**
   - Revisa "Estadísticas de Uso"
   - Identifica materiales más demandados
   - Analiza consumo por técnico

3. **Planificar Compras**
   - Basado en estadísticas de uso
   - Identifica patrones de consumo
   - Optimiza inventario

4. **Generar Reportes**
   - Imprime estadísticas
   - Analiza tendencias
   - Toma decisiones informadas

---

## 🔒 Seguridad

### Validaciones Implementadas:
- ✅ Verificación de rol antes de acceder a páginas
- ✅ Validación de stock antes de liquidar
- ✅ Validación de tipos de archivo para fotos
- ✅ Sanitización de datos de entrada
- ✅ Transacciones de base de datos para integridad
- ✅ Filtrado por sede para multi-tenancy

### Tipos de Archivo Permitidos:
- JPG, JPEG, PNG
- Tamaño máximo: 5MB

---

## 📈 Beneficios

### Para Técnicos:
- ✅ Control preciso de materiales asignados
- ✅ Alertas proactivas de equipos sin usar
- ✅ Proceso simplificado de liquidación
- ✅ Documentación visual con fotos
- ✅ Historial completo de servicios

### Para Jefe de Almacén:
- ✅ Visibilidad completa del consumo
- ✅ Identificación de materiales críticos
- ✅ Análisis por técnico y servicio
- ✅ Datos para planificación de compras
- ✅ Reportes imprimibles

### Para la Empresa:
- ✅ Mejor control de inventario
- ✅ Reducción de desperdicios
- ✅ Optimización de compras
- ✅ Trazabilidad completa
- ✅ Toma de decisiones basada en datos

---

## 🐛 Solución de Problemas

### Error: "No se puede subir la foto"
**Solución:**
```bash
# Verificar permisos de carpeta
chmod 755 uploads/actas
chown www-data:www-data uploads/actas
```

### Error: "Stock insuficiente al liquidar"
**Causa:** El técnico intenta liquidar más de lo que tiene asignado
**Solución:** Verificar stock actual en "Mi Stock" antes de liquidar

### Error: "Tabla liquidaciones_materiales no existe"
**Solución:** Ejecutar migración:
```bash
mysql -u root -p insertel < migrations/005_mejoras_tecnicos_almacen.sql
```

---

## 📞 Soporte

Para cualquier duda o problema con estas funcionalidades, contactar al equipo de desarrollo.

---

## 📝 Notas Adicionales

- Las alertas se calculan automáticamente basadas en la última actividad
- Los reportes se pueden imprimir directamente desde el navegador
- Todas las acciones quedan registradas en el log de actividades
- El sistema mantiene trazabilidad completa de todos los movimientos

---

**Versión:** 1.0.0  
**Fecha:** 2025-12-01  
**Autor:** Sistema INSERTEL S.R.L.
