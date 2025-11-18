<?php
/**
 * Importar Materiales - Administrador
 * Redirige a la vista de almacén con permisos de admin
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';

if (!tieneRol(ROL_ADMINISTRADOR)) {
    redirigirSegunRol();
}

// El administrador puede usar la misma vista que el jefe de almacén
// ya que tiene todos los permisos
include '../almacen/importar_materiales.php';
