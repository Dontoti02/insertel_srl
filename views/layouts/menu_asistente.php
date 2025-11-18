<div class="menu-section">Principal</div>
<a href="<?php echo $base_url; ?>asistente/dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
    <i class="bi bi-speedometer2"></i>
    <span>Dashboard</span>
</a>

<div class="menu-section">Inventario</div>
<a href="<?php echo $base_url; ?>asistente/materiales.php" class="menu-item <?php echo $current_page == 'materiales.php' ? 'active' : ''; ?>">
    <i class="bi bi-box"></i>
    <span>Consultar Materiales</span>
</a>
<a href="<?php echo $base_url; ?>asistente/entradas_materiales.php" class="menu-item <?php echo $current_page == 'entradas_materiales.php' ? 'active' : ''; ?>">
    <i class="bi bi-arrow-down-circle"></i>
    <span>Entradas</span>
</a>
<a href="<?php echo $base_url; ?>asistente/salidas_materiales.php" class="menu-item <?php echo $current_page == 'salidas_materiales.php' ? 'active' : ''; ?>">
    <i class="bi bi-arrow-up-circle"></i>
    <span>Salidas</span>
</a>

<div class="menu-section">Reportes</div>
<a href="<?php echo $base_url; ?>asistente/reportes.php" class="menu-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
    <i class="bi bi-file-earmark-bar-graph"></i>
    <span>Reportes Operativos</span>
</a>

<div class="menu-section">Actas</div>
<a href="<?php echo $base_url; ?>asistente/actas_ver.php" class="menu-item <?php echo $current_page == 'actas_ver.php' ? 'active' : ''; ?>">
    <i class="bi bi-file-text"></i>
    <span>Ver Actas Técnicas</span>
</a>
