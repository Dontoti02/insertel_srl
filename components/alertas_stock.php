<?php
/**
 * Componente de Alertas de Stock Bajo
 * Muestra alertas para roles autorizados
 */

// Solo mostrar alertas a roles que manejan inventario
if (!tieneAlgunRol([ROL_ADMINISTRADOR, ROL_JEFE_ALMACEN, ROL_ASISTENTE_ALMACEN])) {
    return;
}

require_once __DIR__ . '/../models/Material.php';

try {
    $material = new Material($db);
    $materiales_stock_bajo = $material->obtenerStockBajo();
    $total_alertas = count($materiales_stock_bajo);
    
    // Solo mostrar si hay alertas
    if ($total_alertas > 0):
?>

<div class="dropdown me-3">
    <button class="btn btn-link position-relative p-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Alertas de Stock Bajo">
        <i class="bi bi-bell-fill text-warning" style="font-size: 1.5rem;"></i>
        <?php if ($total_alertas > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?php echo $total_alertas > 99 ? '99+' : $total_alertas; ?>
            <span class="visually-hidden">alertas de stock bajo</span>
        </span>
        <?php endif; ?>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end shadow-lg" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                Alertas de Stock Bajo
            </h6>
            <span class="badge bg-danger"><?php echo $total_alertas; ?></span>
        </div>
        <div class="dropdown-divider"></div>
        
        <?php foreach ($materiales_stock_bajo as $material_bajo): ?>
        <div class="dropdown-item-text px-3 py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1 text-dark"><?php echo htmlspecialchars($material_bajo['nombre']); ?></h6>
                    <p class="mb-1 text-muted small">
                        <strong>Código:</strong> <?php echo htmlspecialchars($material_bajo['codigo']); ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-danger small">
                            <i class="bi bi-box-seam me-1"></i>
                            Stock: <strong><?php echo $material_bajo['stock_actual']; ?></strong>
                        </span>
                        <span class="text-warning small">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            Mín: <strong><?php echo $material_bajo['stock_minimo']; ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="dropdown-divider"></div>
        <div class="dropdown-item text-center">
            <?php 
            $materiales_url = BASE_URL . 'views/';
            if (tieneRol(ROL_ADMINISTRADOR)) {
                $materiales_url .= 'admin/materiales.php';
            } else {
                $materiales_url .= 'almacen/materiales.php';
            }
            ?>
            <a href="<?php echo $materiales_url; ?>?stock_bajo=1" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye me-1"></i>
                Ver Todos los Materiales
            </a>
        </div>
    </div>
</div>

<style>
/* Animación de campanita */
@keyframes bell-shake {
    0%, 100% { transform: rotate(0deg); }
    10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
    20%, 40%, 60%, 80% { transform: rotate(10deg); }
}

@keyframes pulse-glow {
    0%, 100% { 
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
    }
    50% { 
        box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
    }
}

.btn-link:hover .bi-bell-fill {
    animation: bell-shake 0.8s ease-in-out;
    color: var(--koamaru-500) !important;
}

.bi-bell-fill {
    filter: drop-shadow(0 2px 4px rgba(255, 193, 7, 0.3));
}

.badge.bg-danger {
    animation: pulse-glow 2s infinite;
    font-weight: 700;
}

/* Estilos para el dropdown de alertas */
.dropdown-menu {
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15) !important;
}

.dropdown-header {
    background: var(--gradient-light);
    border-radius: 12px 12px 0 0;
    padding: 15px 20px;
    border-bottom: 2px solid var(--koamaru-200);
}

.dropdown-item-text:hover {
    background-color: var(--koamaru-50);
}

.dropdown-item-text:last-of-type {
    border-bottom: none !important;
}

/* Badge de contador */
.badge {
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php 
    endif;
} catch (Exception $e) {
    // Error silencioso - no mostrar alertas si hay problemas
    error_log("Error en alertas de stock: " . $e->getMessage());
}
?>
