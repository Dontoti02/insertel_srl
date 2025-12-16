<?php

/**
 * Gestión de Materiales - Almacén (Jefe, Asistente y Administrador)
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR, ROL_ASISTENTE_ALMACEN])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$materialModel = new Material($db);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'eliminar_masa' && !empty($_POST['materiales_seleccionados'])) {
            // Eliminación en masa
            $materiales_eliminados = 0;
            $errores_eliminacion = [];

            foreach ($_POST['materiales_seleccionados'] as $material_id) {
                $material_id = (int)$material_id;

                try {
                    $resultado = $materialModel->eliminarSeguro($material_id);

                    if ($resultado['success']) {
                        $materiales_eliminados++;
                        if ($resultado['action'] === 'desactivado') {
                            $errores_eliminacion[] = "Material ID $material_id: " . $resultado['message'];
                        }
                    } else {
                        $errores_eliminacion[] = "Material ID $material_id: " . $resultado['message'];
                    }
                } catch (Exception $e) {
                    $errores_eliminacion[] = "Error al procesar material ID $material_id: " . $e->getMessage();
                }
            }

            if ($materiales_eliminados > 0) {
                setMensaje('success', "Se eliminaron/desactivaron $materiales_eliminados materiales correctamente");
                registrarActividad($_SESSION['usuario_id'], 'eliminar_masa', 'materiales', "Eliminados: $materiales_eliminados materiales");
            }

            if (!empty($errores_eliminacion)) {
                foreach ($errores_eliminacion as $error) {
                    setMensaje('danger', $error);
                }
            }
        } elseif ($_POST['accion'] === 'crear') {
            $materialModel->codigo = sanitizar($_POST['codigo']);
            $materialModel->nombre = sanitizar($_POST['nombre']);
            $materialModel->descripcion = sanitizar($_POST['descripcion']);
            $materialModel->categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
            $materialModel->unidad = sanitizar($_POST['unidad']);
            $materialModel->proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
            $materialModel->costo_unitario = (float)$_POST['costo_unitario'];
            $materialModel->stock_actual = (int)$_POST['stock_actual'];
            $materialModel->stock_minimo = (int)$_POST['stock_minimo'];
            $materialModel->stock_maximo = (int)$_POST['stock_maximo'];
            $materialModel->ubicacion = sanitizar($_POST['ubicacion']);
            $materialModel->estado = 'activo';

            if ($materialModel->existeCodigo($materialModel->codigo)) {
                setMensaje('danger', 'El código de material ya existe');
            } else {
                $id = $materialModel->crear();
                if ($id) {
                    registrarActividad($_SESSION['usuario_id'], 'crear', 'materiales', "Material creado: {$materialModel->nombre}");
                    setMensaje('success', 'Material registrado exitosamente');
                    redirigir('views/almacen/materiales.php');
                } else {
                    setMensaje('danger', 'Error al registrar el material');
                }
            }
        } elseif ($_POST['accion'] === 'actualizar') {
            $materialModel->id = (int)$_POST['id'];
            $materialModel->codigo = sanitizar($_POST['codigo']);
            $materialModel->nombre = sanitizar($_POST['nombre']);
            $materialModel->descripcion = sanitizar($_POST['descripcion']);
            $materialModel->categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
            $materialModel->unidad = sanitizar($_POST['unidad']);
            $materialModel->proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
            $materialModel->costo_unitario = (float)$_POST['costo_unitario'];
            $materialModel->stock_actual = (int)$_POST['stock_actual'];
            $materialModel->stock_minimo = (int)$_POST['stock_minimo'];
            $materialModel->stock_maximo = (int)$_POST['stock_maximo'];
            $materialModel->ubicacion = sanitizar($_POST['ubicacion']);
            $materialModel->estado = sanitizar($_POST['estado']);

            if ($materialModel->actualizar()) {
                registrarActividad($_SESSION['usuario_id'], 'actualizar', 'materiales', "Material actualizado: {$materialModel->nombre}");
                setMensaje('success', 'Material actualizado exitosamente');
                redirigir('views/almacen/materiales.php');
            } else {
                setMensaje('danger', 'Error al actualizar el material');
            }
        }
    }
}

// Obtener filtros
$filtros = [];
if (!empty($_GET['categoria_id'])) {
    $filtros['categoria_id'] = (int)$_GET['categoria_id'];
}
if (!empty($_GET['buscar'])) {
    $filtros['buscar'] = sanitizar($_GET['buscar']);
}
if (isset($_GET['stock_bajo'])) {
    $filtros['stock_bajo'] = true;
}
if (!empty($_GET['estado'])) {
    $filtros['estado'] = sanitizar($_GET['estado']);
} elseif (tieneRol(ROL_ADMINISTRADOR)) {
    // Para admin, no filtrar por estado por defecto para que vea todo
    $filtros['estado'] = '';
}

// Configuración de paginación
$materiales_por_pagina = REGISTROS_POR_PAGINA;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $materiales_por_pagina;

// Agregar paginación a filtros
$filtros['limit'] = $materiales_por_pagina;
$filtros['offset'] = $offset;

// Obtener materiales y total
// Obtener materiales y total
$materiales = $materialModel->obtenerTodos($filtros);
$total_materiales = $materialModel->contarTodos($filtros);
$total_paginas = ceil($total_materiales / $materiales_por_pagina);

// Función para generar URLs de paginación
function generarUrlPaginacion($pagina)
{
    $params = $_GET;
    $params['pagina'] = $pagina;
    return '?' . http_build_query($params);
}

// Obtener categorías y proveedores
$query_cat = "SELECT * FROM categorias_materiales ORDER BY nombre";
$categorias = $db->query($query_cat)->fetchAll(PDO::FETCH_ASSOC);

$query_prov = "SELECT * FROM proveedores WHERE estado = 'activo' ORDER BY nombre";
$proveedores = $db->query($query_prov)->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gestión de Materiales";
include '../layouts/header.php';
?>

<style>
    .pagination .page-link {
        color: #0d3b66;
        border-color: #dee2e6;
    }

    .pagination .page-item.active .page-link {
        background-color: #0d3b66;
        border-color: #0d3b66;
    }

    .pagination .page-link:hover {
        color: #0a2a4a;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }

    /* Estilos para selección en masa */
    .material-checkbox {
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

    #btnEliminarMasa {
        transition: all 0.3s ease;
    }

    #btnEliminarMasa:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
</style>

<!-- Barra de acciones -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">
                        <i class="bi bi-box me-2"></i>
                        Inventario de Materiales
                    </h5>
                    <small class="text-muted">Total: <?php echo $total_materiales; ?> materiales</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="btnEliminarMasa" class="btn btn-outline-danger" style="display: none;" onclick="confirmarEliminacionMasa()">
                        <i class="bi bi-trash me-2"></i>Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
                    </button>
                    <a href="importar_materiales.php" class="btn btn-outline-success">
                        <i class="bi bi-upload me-2"></i>Importar Excel
                    </a>
                    <a href="movimientos.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left-right me-2"></i>Ver Movimientos
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoMaterial">
                        <i class="bi bi-plus-circle me-2"></i>Nuevo Material
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Código, nombre o descripción..." value="<?php echo $_GET['buscar'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['categoria_id']) && $_GET['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo $cat['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" id="filtro-estado">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'activo') ? 'selected' : ''; ?> style="background-color: #d4edda; color: #155724;">
                            ✅ Activos
                        </option>
                        <option value="inactivo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'inactivo') ? 'selected' : ''; ?> style="background-color: #f8d7da; color: #721c24;">
                            ❌ Inactivos
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de materiales -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover" id="tablaMateriales">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll()">
                            </th>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mín/Máx</th>
                            <th>Ubicación</th>
                            <th>Costo Unit.</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materiales)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No se encontraron materiales
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($materiales as $material): ?>
                                <?php
                                $alerta_stock = '';
                                if ($material['stock_actual'] <= $material['stock_minimo']) {
                                    $alerta_stock = 'table-danger';
                                } elseif ($material['stock_actual'] >= $material['stock_maximo']) {
                                    $alerta_stock = 'table-warning';
                                }
                                ?>
                                <tr class="<?php echo $alerta_stock; ?>" data-estado="<?php echo $material['estado']; ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input material-checkbox" value="<?php echo $material['id']; ?>" onchange="updateSelectionCount()">
                                    </td>
                                    <td><code><?php echo $material['codigo']; ?></code></td>
                                    <td>
                                        <strong><?php echo $material['nombre']; ?></strong>
                                        <?php if ($material['descripcion']): ?>
                                            <br><small class="text-muted"><?php echo substr($material['descripcion'], 0, 50); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $material['categoria_nombre'] ?? '-'; ?></td>
                                    <td>
                                        <?php if ($material['stock_actual'] <= $material['stock_minimo']): ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                <?php echo $material['stock_actual']; ?> <?php echo $material['unidad']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <?php echo $material['stock_actual']; ?> <?php echo $material['unidad']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo $material['stock_minimo']; ?> / <?php echo $material['stock_maximo']; ?> <?php echo $material['unidad']; ?></small>
                                    </td>
                                    <td><?php echo $material['ubicacion'] ?? '-'; ?></td>
                                    <td><?php echo formatearMoneda($material['costo_unitario']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getBadgeEstado($material['estado']); ?>">
                                            <?php echo ucfirst($material['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" onclick="editarMaterial(<?php echo htmlspecialchars(json_encode($material)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="movimientos.php?material_id=<?php echo $material['id']; ?>" class="btn btn-outline-info">
                                                <i class="bi bi-list"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Información de paginación -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="text-muted">
                    Mostrando <?php echo count($materiales); ?> de <?php echo $total_materiales; ?> materiales
                    <?php if ($total_paginas > 1): ?>
                        (Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>)
                    <?php endif; ?>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de materiales">
                        <ul class="pagination mb-0">
                            <!-- Página anterior -->
                            <?php if ($pagina_actual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo generarUrlPaginacion($pagina_actual - 1); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                                </li>
                            <?php endif; ?>

                            <!-- Páginas -->
                            <?php
                            $inicio = max(1, $pagina_actual - 2);
                            $fin = min($total_paginas, $pagina_actual + 2);

                            if ($inicio > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo generarUrlPaginacion(1); ?>">1</a>
                                </li>
                                <?php if ($inicio > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                                <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo generarUrlPaginacion($i); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($fin < $total_paginas): ?>
                                <?php if ($fin < $total_paginas - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo generarUrlPaginacion($total_paginas); ?>"><?php echo $total_paginas; ?></a>
                                </li>
                            <?php endif; ?>

                            <!-- Página siguiente -->
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo generarUrlPaginacion($pagina_actual + 1); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Material -->
<div class="modal fade" id="modalNuevoMaterial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Registrar Nuevo Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Código *</label>
                            <input type="text" name="codigo" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre del Material *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-select">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unidad de Medida *</label>
                            <select name="unidad" class="form-select" required>
                                <option value="unidad">unidad</option>
                                <option value="metro">metro</option>
                                <option value="kit">kit</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id" class="form-select">
                                <option value="">Sin proveedor</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?php echo $prov['id']; ?>"><?php echo $prov['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Costo Unitario</label>
                            <input type="number" name="costo_unitario" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Inicial *</label>
                            <input type="number" name="stock_actual" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Mínimo *</label>
                            <input type="number" name="stock_minimo" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Máximo *</label>
                            <input type="number" name="stock_maximo" class="form-control" value="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ubicación en Almacén</label>
                        <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Estante A-3">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Material -->
<div class="modal fade" id="modalEditarMaterial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Código *</label>
                            <input type="text" name="codigo" id="edit_codigo" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre del Material *</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" id="edit_categoria_id" class="form-select">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unidad de Medida *</label>
                            <select name="unidad" id="edit_unidad" class="form-select" required>
                                <option value="unidad">unidad</option>
                                <option value="metro">metro</option>
                                <option value="kit">kit</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id" id="edit_proveedor_id" class="form-select">
                                <option value="">Sin proveedor</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?php echo $prov['id']; ?>"><?php echo $prov['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Costo Unitario</label>
                            <input type="number" name="costo_unitario" id="edit_costo_unitario" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Actual *</label>
                            <input type="number" name="stock_actual" id="edit_stock_actual" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Mínimo *</label>
                            <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Máximo *</label>
                            <input type="number" name="stock_maximo" id="edit_stock_maximo" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Ubicación en Almacén</label>
                            <input type="text" name="ubicacion" id="edit_ubicacion" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado *</label>
                            <select name="estado" id="edit_estado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editarMaterial(material) {
        document.getElementById('edit_id').value = material.id;
        document.getElementById('edit_codigo').value = material.codigo;
        document.getElementById('edit_nombre').value = material.nombre;
        document.getElementById('edit_descripcion').value = material.descripcion || '';
        document.getElementById('edit_categoria_id').value = material.categoria_id || '';
        document.getElementById('edit_unidad').value = material.unidad;
        document.getElementById('edit_proveedor_id').value = material.proveedor_id || '';
        document.getElementById('edit_costo_unitario').value = material.costo_unitario;
        document.getElementById('edit_stock_actual').value = material.stock_actual;
        document.getElementById('edit_stock_minimo').value = material.stock_minimo;
        document.getElementById('edit_stock_maximo').value = material.stock_maximo;
        document.getElementById('edit_ubicacion').value = material.ubicacion || '';
        document.getElementById('edit_estado').value = material.estado;

        const modal = new bootstrap.Modal(document.getElementById('modalEditarMaterial'));
        modal.show();
    }

    // Funciones para eliminación en masa
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.material-checkbox');

        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });

        updateSelectionCount();
    }

    function updateSelectionCount() {
        const checkboxes = document.querySelectorAll('.material-checkbox:checked');
        const count = checkboxes.length;
        const btnEliminar = document.getElementById('btnEliminarMasa');
        const contador = document.getElementById('contadorSeleccionados');

        contador.textContent = count;

        if (count > 0) {
            btnEliminar.style.display = 'inline-block';
        } else {
            btnEliminar.style.display = 'none';
        }

        // Actualizar estado del checkbox "Seleccionar todo"
        const totalCheckboxes = document.querySelectorAll('.material-checkbox').length;
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

    function confirmarEliminacionMasa() {
        const checkboxes = document.querySelectorAll('.material-checkbox:checked');
        const count = checkboxes.length;

        if (count === 0) {
            alert('Seleccione al menos un material para eliminar');
            return;
        }

        const mensaje = `¿Está seguro de que desea eliminar ${count} material(es) seleccionado(s)?\n\n` +
            'Nota: Los materiales con movimientos de inventario serán desactivados en lugar de eliminados.';

        if (confirm(mensaje)) {
            // Crear formulario dinámico
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            // Agregar acción
            const accionInput = document.createElement('input');
            accionInput.type = 'hidden';
            accionInput.name = 'accion';
            accionInput.value = 'eliminar_masa';
            form.appendChild(accionInput);

            // Agregar IDs seleccionados
            checkboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'materiales_seleccionados[]';
                input.value = checkbox.value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Mejorar visualización del filtro de estado
    document.addEventListener('DOMContentLoaded', function() {
        const filtroEstado = document.getElementById('filtro-estado');
        if (filtroEstado) {
            filtroEstado.addEventListener('change', function() {
                // Cambiar color del select según la opción seleccionada
                const valor = this.value;
                this.className = 'form-select';

                if (valor === 'activo') {
                    this.style.backgroundColor = '#d4edda';
                    this.style.color = '#155724';
                    this.style.borderColor = '#c3e6cb';
                } else if (valor === 'inactivo') {
                    this.style.backgroundColor = '#f8d7da';
                    this.style.color = '#721c24';
                    this.style.borderColor = '#f5c6cb';
                } else {
                    this.style.backgroundColor = '';
                    this.style.color = '';
                    this.style.borderColor = '';
                }
            });

            // Aplicar estilo inicial si hay un valor seleccionado
            filtroEstado.dispatchEvent(new Event('change'));
        }
    });

    // Inicializar contadores al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        updateSelectionCount();
    });
</script>

<style>
    /* Estilos para el filtro de estado */
    #filtro-estado option[value="activo"] {
        background-color: #d4edda !important;
        color: #155724 !important;
    }

    #filtro-estado option[value="inactivo"] {
        background-color: #f8d7da !important;
        color: #721c24 !important;
    }

    /* Badges personalizados para estados de materiales */
    .badge-activo {
        background: linear-gradient(135deg, #28a745, #20c997) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        padding: 6px 12px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3) !important;
        border: none !important;
    }

    .badge-inactivo {
        background: linear-gradient(135deg, #dc3545, #c82333) !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        padding: 6px 12px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3) !important;
        border: none !important;
    }

    /* Mejorar visualización de badges de estado (fallback) */
    .badge.bg-success {
        background: linear-gradient(135deg, #28a745, #20c997) !important;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    }

    .badge.bg-secondary {
        background: linear-gradient(135deg, #6c757d, #495057) !important;
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
    }

    /* Efecto hover en filas según estado */
    tbody tr[data-estado="activo"]:hover {
        background-color: rgba(212, 237, 218, 0.1) !important;
    }

    tbody tr[data-estado="inactivo"]:hover {
        background-color: rgba(248, 215, 218, 0.1) !important;
    }

    /* Indicador visual sutil en filas */
    tbody tr[data-estado="activo"] {
        border-left: 3px solid #28a745;
    }

    tbody tr[data-estado="inactivo"] {
        border-left: 3px solid #dc3545;
        opacity: 0.8;
    }
</style>

<?php include '../layouts/footer.php'; ?>