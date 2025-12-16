<?php

/**
 * Entradas de Materiales - Administrador
 * Reutiliza la vista de almacén
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

// Incluir la vista de almacén
include '../almacen/entradas_materiales.php';
