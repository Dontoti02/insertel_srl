<?php
/**
 * Movimientos de Inventario - Administrador
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

// El administrador puede usar la misma vista que el jefe de almacén
include '../almacen/movimientos.php';
