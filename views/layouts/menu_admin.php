<div class="menu-section">Principal</div>
<a href="<?php echo $base_url; ?>admin/dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
    <i class="bi bi-speedometer2"></i>
    <span>Dashboard</span>
</a>

<div class="menu-section">Gestión</div>
<a href="<?php echo $base_url; ?>admin/usuarios.php" class="menu-item <?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>">
    <i class="bi bi-people"></i>
    <span>Usuarios</span>
</a>
<a href="<?php echo $base_url; ?>admin/sedes.php" class="menu-item <?php echo $current_page == 'sedes.php' ? 'active' : ''; ?>">
    <i class="bi bi-building"></i>
    <span>Sedes</span>
</a>
<a href="<?php echo $base_url; ?>admin/materiales.php" class="menu-item <?php echo $current_page == 'materiales.php' ? 'active' : ''; ?>">
    <i class="bi bi-box"></i>
    <span>Materiales</span>
</a>

<div class="menu-section">Configuración</div>
<a href="<?php echo $base_url; ?>admin/categorias.php" class="menu-item <?php echo $current_page == 'categorias.php' ? 'active' : ''; ?>">
    <i class="bi bi-tags"></i>
    <span>Categorías</span>
</a>
<a href="<?php echo $base_url; ?>admin/unidades.php" class="menu-item <?php echo $current_page == 'unidades.php' ? 'active' : ''; ?>">
    <i class="bi bi-rulers"></i>
    <span>Unidades</span>
</a>
<a href="<?php echo $base_url; ?>admin/proveedores.php" class="menu-item <?php echo $current_page == 'proveedores.php' ? 'active' : ''; ?>">
    <i class="bi bi-truck"></i>
    <span>Proveedores</span>
</a>

<div class="menu-section">Reportes</div>
<a href="<?php echo $base_url; ?>admin/reportes.php" class="menu-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
    <i class="bi bi-file-earmark-bar-graph"></i>
    <span>Reportes</span>
</a>
<a href="<?php echo $base_url; ?>admin/historial.php" class="menu-item <?php echo $current_page == 'historial.php' ? 'active' : ''; ?>">
    <i class="bi bi-clock-history"></i>
    <span>Historial</span>
</a>

<div class="menu-section">Configuración</div>
<a href="<?php echo $base_url; ?>admin/configuracion_avanzada.php" class="menu-item <?php echo $current_page == 'configuracion_avanzada.php' ? 'active' : ''; ?>">
    <i class="bi bi-gear"></i>
    <span>Config. Avanzada</span>
</a>
