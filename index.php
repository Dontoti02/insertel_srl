<?php

/**
 * Punto de entrada principal
 * INSERTEL S.R.L. - Sistema de Gestión de Inventario
 */

require_once 'config/constants.php';
require_once 'config/functions.php';
require_once 'config/database.php';

// Verificar si hay sesión activa
if (estaAutenticado()) {
    // Sincronizar datos de sesión con la base de datos
    sincronizarDatosSesion();
    redirigirSegunRol();
} else {
    redirigir('auth/login.php');
}
