<?php

/**
 * Script de diagnóstico para verificar permisos de eliminación de sedes
 * Este archivo debe ser eliminado después de resolver el problema
 */

require_once '../config/constants.php';
require_once '../config/functions.php';
require_once '../config/database.php';

// Iniciar sesión
iniciarSesion();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Permisos - INSERTEL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }

        .diagnostic-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .status-ok {
            color: #28a745;
            font-weight: bold;
        }

        .status-error {
            color: #dc3545;
            font-weight: bold;
        }

        .status-warning {
            color: #ffc107;
            font-weight: bold;
        }

        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="mb-4">🔍 Diagnóstico de Permisos de Eliminación de Sedes</h1>

        <div class="diagnostic-card">
            <h3>📋 Información de Sesión</h3>
            <table class="table table-bordered">
                <tr>
                    <th>Usuario ID</th>
                    <td><?php echo $_SESSION['usuario_id'] ?? '<span class="status-error">NO DEFINIDO</span>'; ?></td>
                </tr>
                <tr>
                    <th>Nombre de Usuario</th>
                    <td><?php echo $_SESSION['nombre_completo'] ?? '<span class="status-error">NO DEFINIDO</span>'; ?></td>
                </tr>
                <tr>
                    <th>Rol ID (valor)</th>
                    <td><?php echo $_SESSION['rol_id'] ?? '<span class="status-error">NO DEFINIDO</span>'; ?></td>
                </tr>
                <tr>
                    <th>Rol ID (tipo)</th>
                    <td><?php echo isset($_SESSION['rol_id']) ? gettype($_SESSION['rol_id']) : '<span class="status-error">NO DEFINIDO</span>'; ?></td>
                </tr>
                <tr>
                    <th>Sede ID</th>
                    <td><?php echo $_SESSION['sede_id'] ?? '<span class="status-warning">NO DEFINIDO (normal para superadmin)</span>'; ?></td>
                </tr>
            </table>
        </div>

        <div class="diagnostic-card">
            <h3>🔐 Constantes de Roles del Sistema</h3>
            <table class="table table-bordered">
                <tr>
                    <th>ROL_SUPERADMIN</th>
                    <td><?php echo ROL_SUPERADMIN; ?> (tipo: <?php echo gettype(ROL_SUPERADMIN); ?>)</td>
                </tr>
                <tr>
                    <th>ROL_ADMINISTRADOR</th>
                    <td><?php echo ROL_ADMINISTRADOR; ?> (tipo: <?php echo gettype(ROL_ADMINISTRADOR); ?>)</td>
                </tr>
                <tr>
                    <th>ROL_JEFE_ALMACEN</th>
                    <td><?php echo ROL_JEFE_ALMACEN; ?> (tipo: <?php echo gettype(ROL_JEFE_ALMACEN); ?>)</td>
                </tr>
                <tr>
                    <th>ROL_ASISTENTE_ALMACEN</th>
                    <td><?php echo ROL_ASISTENTE_ALMACEN; ?> (tipo: <?php echo gettype(ROL_ASISTENTE_ALMACEN); ?>)</td>
                </tr>
                <tr>
                    <th>ROL_TECNICO</th>
                    <td><?php echo ROL_TECNICO; ?> (tipo: <?php echo gettype(ROL_TECNICO); ?>)</td>
                </tr>
            </table>
        </div>

        <div class="diagnostic-card">
            <h3>✅ Verificación de Permisos</h3>
            <table class="table table-bordered">
                <tr>
                    <th>¿Está autenticado?</th>
                    <td>
                        <?php
                        $autenticado = estaAutenticado();
                        echo $autenticado ? '<span class="status-ok">✓ SÍ</span>' : '<span class="status-error">✗ NO</span>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>¿Es Superadmin? (esSuperAdmin())</th>
                    <td>
                        <?php
                        $es_superadmin = esSuperAdmin();
                        echo $es_superadmin ? '<span class="status-ok">✓ SÍ</span>' : '<span class="status-error">✗ NO</span>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>¿Tiene rol Superadmin? (tieneRol(ROL_SUPERADMIN))</th>
                    <td>
                        <?php
                        $tiene_rol = tieneRol(ROL_SUPERADMIN);
                        echo $tiene_rol ? '<span class="status-ok">✓ SÍ</span>' : '<span class="status-error">✗ NO</span>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Comparación directa ($_SESSION['rol_id'] === ROL_SUPERADMIN)</th>
                    <td>
                        <?php
                        $comparacion_estricta = isset($_SESSION['rol_id']) && $_SESSION['rol_id'] === ROL_SUPERADMIN;
                        echo $comparacion_estricta ? '<span class="status-ok">✓ SÍ</span>' : '<span class="status-error">✗ NO</span>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Comparación con casting ((int)$_SESSION['rol_id'] === (int)ROL_SUPERADMIN)</th>
                    <td>
                        <?php
                        $comparacion_casting = isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === (int)ROL_SUPERADMIN;
                        echo $comparacion_casting ? '<span class="status-ok">✓ SÍ</span>' : '<span class="status-error">✗ NO</span>';
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="diagnostic-card">
            <h3>🎯 Conclusión</h3>
            <?php if ($autenticado && $es_superadmin && $tiene_rol): ?>
                <div class="alert alert-success">
                    <h5>✅ Todo está correcto</h5>
                    <p>Tienes todos los permisos necesarios para eliminar sedes. Si aún tienes problemas:</p>
                    <ul>
                        <li>Verifica los logs de PHP en: <code><?php echo ini_get('error_log'); ?></code></li>
                        <li>Revisa la consola del navegador para errores JavaScript</li>
                        <li>Verifica que el archivo AJAX <code>ajax/obtener_datos_sede.php</code> sea accesible</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h5>❌ Problema detectado</h5>
                    <p>No tienes los permisos adecuados. Posibles causas:</p>
                    <ul>
                        <?php if (!$autenticado): ?>
                            <li><strong>No estás autenticado</strong> - Inicia sesión nuevamente</li>
                        <?php endif; ?>
                        <?php if ($autenticado && !$es_superadmin): ?>
                            <li><strong>Tu rol no es Superadmin</strong> - Rol actual: <?php echo $_SESSION['rol_id'] ?? 'N/A'; ?> (esperado: <?php echo ROL_SUPERADMIN; ?>)</li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['rol_id']) && gettype($_SESSION['rol_id']) !== 'integer'): ?>
                            <li><strong>Tipo de dato incorrecto</strong> - El rol_id está almacenado como <?php echo gettype($_SESSION['rol_id']); ?> en lugar de integer</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="diagnostic-card">
            <h3>🔧 Datos de Sesión Completos (para debugging)</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>

        <div class="text-center mt-4">
            <a href="sedes.php" class="btn btn-primary">← Volver a Gestión de Sedes</a>
            <a href="dashboard.php" class="btn btn-secondary">← Volver al Dashboard</a>
        </div>

        <div class="alert alert-warning mt-4">
            <strong>⚠️ Importante:</strong> Este archivo es solo para diagnóstico. Elimínalo después de resolver el problema por seguridad.
        </div>
    </div>
</body>

</html>