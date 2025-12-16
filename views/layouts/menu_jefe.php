<div class="menu-section">Principal</div>
<a href="<?php echo $base_url; ?>almacen/dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
    <i class="bi bi-speedometer2"></i>
    <span>Dashboard</span>
</a>

<div class="menu-section">Inventario</div>
<a href="<?php echo $base_url; ?>almacen/materiales.php" class="menu-item <?php echo $current_page == 'materiales.php' ? 'active' : ''; ?>">
    <i class="bi bi-box"></i>
    <span>Materiales</span>
</a>
<a href="<?php echo $base_url; ?>almacen/entradas_materiales.php" class="menu-item <?php echo $current_page == 'entradas_materiales.php' ? 'active' : ''; ?>">
    <i class="bi bi-arrow-down-circle"></i>
    <span>Entradas</span>
</a>
<a href="<?php echo $base_url; ?>almacen/salidas_materiales.php" class="menu-item <?php echo $current_page == 'salidas_materiales.php' ? 'active' : ''; ?>">
    <i class="bi bi-arrow-up-circle"></i>
    <span>Salidas</span>
</a>
<a href="<?php echo $base_url; ?>almacen/asignar_tecnicos.php" class="menu-item <?php echo $current_page == 'asignar_tecnicos.php' ? 'active' : ''; ?>">
    <i class="bi bi-person-plus"></i>
    <span>Asignar a Técnicos</span>
</a>
<a href="<?php echo $base_url; ?>almacen/actas_tecnicos.php" class="menu-item <?php echo $current_page == 'actas_tecnicos.php' ? 'active' : ''; ?>">
    <i class="bi bi-file-text"></i>
    <span>Actas Técnicas</span>
</a>
<a href="<?php echo $base_url; ?>almacen/alertas_inventario.php" class="menu-item <?php echo $current_page == 'alertas_inventario.php' ? 'active' : ''; ?>">
    <i class="bi bi-exclamation-triangle"></i>
    <span>Alertas</span>
</a>

<div class="menu-section">Análisis</div>
<a href="<?php echo $base_url; ?>almacen/estadisticas_uso.php" class="menu-item <?php echo $current_page == 'estadisticas_uso.php' ? 'active' : ''; ?>">
    <i class="bi bi-graph-up"></i>
    <span>Estadísticas de Uso</span>
</a>

<div class="menu-section">Reportes</div>
<a href="<?php echo $base_url; ?>almacen/reportes.php" class="menu-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
    <i class="bi bi-file-earmark-bar-graph"></i>
    <span>Reportes</span>
</a>