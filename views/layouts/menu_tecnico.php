<div class="menu-section">Principal</div>
<a href="<?php echo $base_url; ?>tecnico/dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
    <i class="bi bi-speedometer2"></i>
    <span>Mi Dashboard</span>
</a>

<div class="menu-section">Mi Inventario</div>
<a href="<?php echo $base_url; ?>tecnico/mi_stock.php" class="menu-item <?php echo $current_page == 'mi_stock.php' ? 'active' : ''; ?>">
    <i class="bi bi-box-seam"></i>
    <span>Mi Stock</span>
</a>

<div class="menu-section">Actas</div>
<a href="<?php echo $base_url; ?>tecnico/actas.php" class="menu-item <?php echo $current_page == 'actas.php' ? 'active' : ''; ?>">
    <i class="bi bi-file-text"></i>
    <span>Mis Actas</span>
</a>
