# Implementación de Eliminación Múltiple de Usuarios

## Resumen
Este documento describe cómo agregar la funcionalidad de eliminación múltiple de usuarios en la página `usuarios_globales.php`.

## Cambios Necesarios

### 1. Agregar manejo de eliminación múltiple en PHP (después de la línea 124)

```php
if ($accion === 'eliminar_multiple') {
    $user_ids = $_POST['user_ids'] ?? [];
    
    if (empty($user_ids)) {
        setMensaje('warning', 'No se seleccionaron usuarios para eliminar');
        redirigir('views/superadmin/usuarios_globales.php');
    }

    $eliminados = 0;
    $errores = [];
    $usuarios_no_eliminables = [];

    foreach ($user_ids as $user_id) {
        $user_id = (int)$user_id;

        // Validar que no sea el usuario actual
        if ($user_id == $_SESSION['usuario_id']) {
            $usuarios_no_eliminables[] = 'Tu propia cuenta';
            continue;
        }

        // Validar que no sea un SUPERADMIN
        $usuario_eliminar = $userModel->obtenerPorId($user_id);
        if (!$usuario_eliminar) {
            continue;
        }

        if ($usuario_eliminar['rol_id'] == ROL_SUPERADMIN) {
            $usuarios_no_eliminables[] = $usuario_eliminar['username'] . ' (Superadministrador)';
            continue;
        }

        // Intentar eliminar
        try {
            if ($userModel->eliminar($user_id)) {
                registrarActividad($_SESSION['usuario_id'], 'eliminar_global', 'usuarios', "Usuario eliminado: {$usuario_eliminar['username']}");
                $eliminados++;
            } else {
                $errores[] = $usuario_eliminar['username'];
            }
        } catch (Exception $e) {
            error_log("Error al eliminar usuario ID $user_id: " . $e->getMessage());
            $errores[] = $usuario_eliminar['username'] . ' (Error: ' . $e->getMessage() . ')';
        }
    }

    // Mostrar mensajes de resultado
    if ($eliminados > 0) {
        setMensaje('success', "Se eliminaron $eliminados usuario(s) exitosamente");
    }
    if (!empty($usuarios_no_eliminables)) {
        setMensaje('warning', 'No se pueden eliminar: ' . implode(', ', $usuarios_no_eliminables));
    }
    if (!empty($errores)) {
        setMensaje('danger', 'Error al eliminar: ' . implode(', ', $errores));
    }

    redirigir('views/superadmin/usuarios_globales.php');
}
```

### 2. Agregar botón de eliminación múltiple en la barra de acciones (línea 157)

Reemplazar:
```php
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
    <i class="bi bi-plus-circle me-2"></i> Nuevo Usuario
</button>
```

Con:
```php
<div class="d-flex gap-2">
    <button type="button" id="btnEliminarMultiple" class="btn btn-outline-danger" style="display: none;" onclick="confirmarEliminacionMultiple()">
        <i class="bi bi-trash me-2"></i>Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
    </button>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
        <i class="bi bi-plus-circle me-2"></i> Nuevo Usuario
    </button>
</div>
```

### 3. Agregar columna de checkbox en la tabla (línea 183)

Agregar como primera columna del `<thead>`:
```php
<th width="40">
    <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll()">
</th>
```

Y actualizar el colspan de "No se encontraron usuarios" de 7 a 8.

### 4. Agregar checkbox en cada fila de usuario (línea 200)

Agregar como primera celda del `<tr>`:
```php
<td>
    <?php if ($usuario['id'] != $_SESSION['usuario_id'] && $usuario['rol_id'] != ROL_SUPERADMIN): ?>
        <input type="checkbox" class="form-check-input user-checkbox" value="<?php echo $usuario['id']; ?>" onchange="updateSelectionCount()">
    <?php endif; ?>
</td>
```

### 5. Agregar JavaScript al final del archivo (antes del cierre de PHP, línea 333)

```javascript
<script>
    // Funciones para eliminación múltiple
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.user-checkbox');

        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });

        updateSelectionCount();
    }

    function updateSelectionCount() {
        const checkboxes = document.querySelectorAll('.user-checkbox:checked');
        const count = checkboxes.length;
        const btnEliminar = document.getElementById('btnEliminarMultiple');
        const contador = document.getElementById('contadorSeleccionados');

        contador.textContent = count;

        if (count > 0) {
            btnEliminar.style.display = 'inline-block';
        } else {
            btnEliminar.style.display = 'none';
        }

        // Actualizar estado del checkbox "Seleccionar todo"
        const totalCheckboxes = document.querySelectorAll('.user-checkbox').length;
        const selectAll = document.getElementById('selectAll');

        if (count === 0) {
            selectAll.indeterminate = false;
            selectAll.checked = false;
        } else if (count === totalCheckboxes) {
            selectAll.indeterminate = false;
            selectAll.checked = true;
        } else {
            selectAll.indeterminate = true;
            selectAll.checked = false;
        }
    }

    function confirmarEliminacionMultiple() {
        const checkboxes = document.querySelectorAll('.user-checkbox:checked');
        const count = checkboxes.length;

        if (count === 0) {
            alert('Seleccione al menos un usuario para eliminar');
            return;
        }

        const mensaje = `¿Está seguro de que desea eliminar ${count} usuario(s) seleccionado(s)?\\n\\n` +
            'Esta acción no se puede deshacer.';

        if (confirm(mensaje)) {
            // Crear formulario dinámico
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            // Agregar acción
            const accionInput = document.createElement('input');
            accionInput.type = 'hidden';
            accionInput.name = 'accion';
            accionInput.value = 'eliminar_multiple';
            form.appendChild(accionInput);

            // Agregar IDs seleccionados
            checkboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = checkbox.value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Inicializar contadores al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        updateSelectionCount();
    });
</script>
```

### 6. Agregar estilos CSS (después de la línea 149, antes del HTML)

```php
<style>
    .user-checkbox {
        cursor: pointer;
    }

    #selectAll {
        cursor: pointer;
    }

    .table th:first-child,
    .table td:first-child {
        text-align: center;
        vertical-align: middle;
    }

    #btnEliminarMultiple {
        transition: all 0.3s ease;
    }

    #btnEliminarMultiple:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
</style>
```

## Funcionalidad

La implementación permite:
- ✅ Seleccionar usuarios individuales mediante checkboxes
- ✅ Seleccionar/deseleccionar todos los usuarios con un checkbox maestro
- ✅ Ver contador de usuarios seleccionados
- ✅ Botón de eliminación que solo aparece cuando hay usuarios seleccionados
- ✅ Validación para no eliminar el usuario actual
- ✅ Validación para no eliminar superadministradores
- ✅ Confirmación antes de eliminar
- ✅ Mensajes de resultado detallados
- ✅ Registro de actividad para cada eliminación

## Notas
- Los checkboxes solo aparecen para usuarios que no son el usuario actual ni superadministradores
- El sistema maneja errores individuales y muestra mensajes apropiados
- Cada eliminación se registra en el historial de actividades
