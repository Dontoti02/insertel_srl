# 🧪 GUÍA DE PRUEBAS - Nuevas Funcionalidades

## 📋 Preparación

### 1. Verificar que la migración se ejecutó correctamente
```sql
-- Conectar a MySQL
mysql -u root insertel_db

-- Verificar tabla liquidaciones_materiales
SHOW TABLES LIKE 'liquidaciones_materiales';

-- Verificar campos nuevos en actas_tecnicas
DESCRIBE actas_tecnicas;

-- Verificar índices
SHOW INDEX FROM stock_tecnicos;
SHOW INDEX FROM actas_tecnicas;
```

### 2. Verificar permisos de carpetas
```bash
# En PowerShell o CMD
cd c:\xampp\htdocs\insertel
mkdir uploads\actas -Force
icacls uploads\actas /grant Everyone:F
```

---

## 🧪 PRUEBAS - ROL TÉCNICO

### Prueba 1: Ver Stock Asignado
**Objetivo:** Verificar que el técnico puede ver sus materiales asignados

**Pasos:**
1. Iniciar sesión como técnico
2. Ir a **Mi Inventario → Mi Stock**
3. Verificar que se muestran los materiales asignados
4. Verificar que se muestra:
   - Código del material
   - Nombre
   - Cantidad
   - Unidad
   - Fecha de asignación

**Resultado Esperado:** ✅ Lista de materiales asignados visible

---

### Prueba 2: Alertas de Equipos Sin Usar
**Objetivo:** Verificar el sistema de alertas

**Pasos:**
1. Iniciar sesión como técnico
2. Ir a **Mi Inventario → Alertas de Equipos**
3. Verificar que se muestran alertas (si hay materiales sin usar)
4. Verificar niveles de alerta:
   - 🟡 Amarilla: 30-60 días
   - 🔴 Roja: >60 días

**Resultado Esperado:** ✅ Sistema de alertas funcionando

**Nota:** Si no hay alertas, es porque todos los materiales tienen actividad reciente (esto es bueno).

---

### Prueba 3: Registrar, Ver y Eliminar Acta
**Objetivo:** Verificar gestión completa de actas

**Pasos:**
1. Iniciar sesión como técnico
2. Ir a **Servicios → Mis Actas**
3. **Registrar:**
   - Clic en "Nueva Acta"
   - Llenar formulario y subir foto
   - Guardar
4. **Ver Detalle:**
   - Clic en el botón "Ver" (ojo) del acta creada
   - Verificar que se muestran todos los datos
   - Verificar que la foto se ve correctamente
5. **Eliminar:**
   - Clic en el botón "Eliminar" (basurero)
   - Confirmar en el modal
   - Verificar que el acta desaparece de la lista

**Resultado Esperado:** 
- ✅ Registro exitoso con foto
- ✅ Vista previa muestra datos y foto
- ✅ Eliminación exitosa (registro y archivo de foto)

**Verificar:**
```bash
# Ver archivos en carpeta actas (debe estar vacío tras eliminar)
dir uploads\actas
```

---

### Prueba 4: Liquidar Materiales
**Objetivo:** Verificar el proceso de liquidación

**Preparación:**
1. Asegurarse de tener un acta registrada (de la prueba 3)
2. Asegurarse de tener materiales asignados

**Pasos:**
1. Iniciar sesión como técnico
2. Ir a **Servicios → Liquidar Materiales**
3. Verificar que aparece el acta pendiente
4. Clic en "Liquidar" en el acta
5. En el modal:
   - Seleccionar materiales usados
   - Indicar cantidades (no exceder stock disponible)
6. Confirmar liquidación

**Resultado Esperado:**
- ✅ Liquidación exitosa
- ✅ Stock del técnico reducido
- ✅ Acta marcada como "liquidada"
- ✅ Registro en historial de liquidaciones

**Verificar en BD:**
```sql
-- Ver liquidaciones registradas
SELECT * FROM liquidaciones_materiales ORDER BY id DESC LIMIT 5;

-- Ver stock actualizado del técnico
SELECT * FROM stock_tecnicos WHERE tecnico_id = [ID_TECNICO];
```

---

## 🧪 PRUEBAS - ROL JEFE DE ALMACÉN

### Prueba 5: Estadísticas de Uso
**Objetivo:** Verificar dashboard de estadísticas

**Preparación:**
1. Asegurarse de que hay liquidaciones registradas (de la prueba 4)

**Pasos:**
1. Iniciar sesión como jefe de almacén
2. Ir a **Análisis → Estadísticas de Uso**
3. Verificar secciones:
   - ✅ Estadísticas generales (4 tarjetas)
   - ✅ Top 20 materiales más usados
   - ✅ Técnicos con mayor consumo
   - ✅ Uso por tipo de servicio
   - ✅ Consumo por categoría

**Resultado Esperado:**
- ✅ Dashboard completo visible
- ✅ Datos correctos
- ✅ Gráficos de progreso funcionando

---

### Prueba 6: Imprimir Reporte
**Objetivo:** Verificar función de impresión

**Pasos:**
1. En la página de Estadísticas de Uso
2. Clic en "Imprimir Reporte"
3. Verificar vista previa de impresión

**Resultado Esperado:**
- ✅ Vista de impresión limpia
- ✅ Sin menús ni botones
- ✅ Solo contenido relevante

---

## 🧪 PRUEBAS DE INTEGRACIÓN

### Prueba 7: Flujo Completo Técnico
**Objetivo:** Probar el flujo completo desde asignación hasta liquidación

**Pasos:**
1. **Como Jefe de Almacén:**
   - Asignar materiales a un técnico
   - Ir a **Inventario → Asignar a Técnicos**
   - Seleccionar técnico
   - Asignar 5 unidades de un material

2. **Como Técnico:**
   - Verificar en "Mi Stock" que aparecen los materiales
   - Registrar nueva acta con foto
   - Tipo de servicio: "Mantenimiento"
   - Liquidar 3 unidades del material asignado

3. **Como Jefe de Almacén:**
   - Ir a "Estadísticas de Uso"
   - Verificar que aparece:
     - El material en "Más Usados"
     - El técnico en "Mayor Consumo"
     - "Mantenimiento" en "Uso por Servicio"

**Resultado Esperado:**
- ✅ Flujo completo funciona
- ✅ Datos se reflejan en estadísticas
- ✅ Stock actualizado correctamente

---

### Prueba 8: Sistema de Alertas
**Objetivo:** Verificar que las alertas se generan correctamente

**Nota:** Esta prueba requiere datos antiguos o modificar fechas en BD

**Opción 1 - Modificar fechas en BD (solo para pruebas):**
```sql
-- Hacer que un material parezca que tiene 70 días sin usar
UPDATE stock_tecnicos 
SET fecha_asignacion = DATE_SUB(NOW(), INTERVAL 70 DAY)
WHERE tecnico_id = [ID_TECNICO]
LIMIT 1;
```

**Pasos:**
1. Como técnico, ir a "Alertas de Equipos"
2. Verificar que aparece alerta roja para el material

**Resultado Esperado:**
- ✅ Alerta roja visible
- ✅ Muestra días de inactividad
- ✅ Botón de liquidar funciona

**Restaurar:**
```sql
-- Restaurar fecha original
UPDATE stock_tecnicos 
SET fecha_asignacion = NOW()
WHERE tecnico_id = [ID_TECNICO];
```

---

## 🧪 PRUEBAS DE SEGURIDAD

### Prueba 9: Validación de Roles
**Objetivo:** Verificar que los roles están protegidos

**Pasos:**
1. Iniciar sesión como técnico
2. Intentar acceder directamente a:
   ```
   http://localhost/insertel/views/almacen/estadisticas_uso.php
   ```

**Resultado Esperado:**
- ✅ Redirige al dashboard del técnico
- ✅ No permite acceso

3. Iniciar sesión como jefe de almacén
4. Intentar acceder a:
   ```
   http://localhost/insertel/views/tecnico/liquidar_materiales.php
   ```

**Resultado Esperado:**
- ✅ Redirige al dashboard del jefe
- ✅ No permite acceso

---

### Prueba 10: Validación de Stock
**Objetivo:** Verificar que no se puede liquidar más de lo disponible

**Pasos:**
1. Como técnico con 5 unidades de un material
2. Ir a "Liquidar Materiales"
3. Intentar liquidar 10 unidades

**Resultado Esperado:**
- ✅ Error: "Stock insuficiente"
- ✅ No se realiza la liquidación
- ✅ Stock permanece igual

---

### Prueba 11: Validación de Archivos
**Objetivo:** Verificar que solo se aceptan imágenes

**Pasos:**
1. Como técnico, registrar nueva acta
2. Intentar subir un archivo PDF como foto

**Resultado Esperado:**
- ✅ No permite subir el archivo
- ✅ Solo acepta JPG, JPEG, PNG

---

## 📊 VERIFICACIÓN DE DATOS

### Consultas SQL Útiles:

```sql
-- Ver todas las liquidaciones
SELECT 
    l.id,
    a.codigo_acta,
    u.nombre_completo as tecnico,
    m.nombre as material,
    l.cantidad,
    l.fecha_liquidacion
FROM liquidaciones_materiales l
INNER JOIN actas_tecnicas a ON l.acta_id = a.id
INNER JOIN usuarios u ON l.tecnico_id = u.id
INNER JOIN materiales m ON l.material_id = m.id
ORDER BY l.fecha_liquidacion DESC;

-- Ver stock actual de técnicos
SELECT 
    u.nombre_completo as tecnico,
    m.nombre as material,
    st.cantidad,
    st.fecha_asignacion,
    st.updated_at
FROM stock_tecnicos st
INNER JOIN usuarios u ON st.tecnico_id = u.id
INNER JOIN materiales m ON st.material_id = m.id
WHERE st.cantidad > 0;

-- Ver actas con fotos
SELECT 
    codigo_acta,
    cliente,
    tipo_servicio,
    foto_acta,
    estado_liquidacion
FROM actas_tecnicas
WHERE foto_acta IS NOT NULL;

-- Estadísticas generales
SELECT 
    COUNT(DISTINCT tecnico_id) as total_tecnicos,
    COUNT(DISTINCT material_id) as materiales_usados,
    SUM(cantidad) as items_liquidados
FROM liquidaciones_materiales;
```

---

## ✅ CHECKLIST DE PRUEBAS

### Funcionalidades Técnico:
- [ ] Ver stock asignado
- [ ] Ver alertas de equipos
- [ ] Registrar acta sin foto
- [ ] Registrar acta con foto
- [ ] Liquidar materiales
- [ ] Ver historial de liquidaciones
- [ ] Tipos de servicio (Instalación, Mantenimiento, Postventa)

### Funcionalidades Jefe de Almacén:
- [ ] Ver estadísticas generales
- [ ] Ver materiales más usados
- [ ] Ver técnicos con mayor consumo
- [ ] Ver uso por tipo de servicio
- [ ] Ver consumo por categoría
- [ ] Imprimir reporte

### Seguridad:
- [ ] Validación de roles
- [ ] Validación de stock
- [ ] Validación de archivos
- [ ] Filtrado por sede

### Base de Datos:
- [ ] Tabla liquidaciones_materiales creada
- [ ] Campos en actas_tecnicas agregados
- [ ] Índices creados
- [ ] Datos se guardan correctamente

---

## 🐛 PROBLEMAS COMUNES Y SOLUCIONES

### Error: "No se puede subir la foto"
**Solución:**
```bash
# Verificar permisos
icacls uploads\actas /grant Everyone:F
```

### Error: "Tabla no existe"
**Solución:**
```bash
# Ejecutar migración
mysql -u root insertel_db < migrations/005_mejoras_tecnicos_almacen.sql
```

### Error: "Stock insuficiente"
**Solución:**
- Verificar stock actual en "Mi Stock"
- Asignar más materiales desde jefe de almacén

### No aparecen estadísticas
**Solución:**
- Asegurarse de que hay liquidaciones registradas
- Verificar que el técnico pertenece a la misma sede

---

## 📝 NOTAS FINALES

1. **Datos de Prueba:** Todas las pruebas se pueden hacer con datos reales o de prueba
2. **Backup:** Antes de probar, hacer backup de la BD
3. **Restauración:** Si algo sale mal, restaurar desde backup
4. **Logs:** Revisar logs de actividad en la tabla `actividades`

---

**¡Listo para probar!** 🚀

Si encuentras algún problema, revisa:
1. Logs de PHP en `c:\xampp\php\logs\php_error_log`
2. Logs de Apache en `c:\xampp\apache\logs\error.log`
3. Consola del navegador (F12)
