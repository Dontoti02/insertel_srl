<div class="menu-section">Panel Principal</div>
<a href="<?php echo $base_url; ?>superadmin/dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
    <i class="bi bi-speedometer2"></i>
    <span>Dashboard Global</span>
</a>

<div class="menu-section">Gestión de Sedes</div>
<a href="<?php echo $base_url; ?>superadmin/sedes.php" class="menu-item <?php echo $current_page == 'sedes.php' ? 'active' : ''; ?>">
    <i class="bi bi-building"></i>
    <span>Sedes</span>
</a>
<a href="<?php echo $base_url; ?>superadmin/crear_admin.php" class="menu-item <?php echo $current_page == 'crear_admin.php' ? 'active' : ''; ?>">
    <i class="bi bi-person-plus"></i>
    <span>Crear Administrador</span>
</a>
<a href="<?php echo $base_url; ?>superadmin/usuarios_globales.php" class="menu-item <?php echo $current_page == 'usuarios_globales.php' ? 'active' : ''; ?>">
    <i class="bi bi-people"></i>
    <span>Usuarios Globales</span>
</a>

<div class="menu-section">Reportes y Análisis</div>
<a href="<?php echo $base_url; ?>superadmin/reportes_globales.php" class="menu-item <?php echo $current_page == 'reportes_globales.php' ? 'active' : ''; ?>">
    <i class="bi bi-graph-up"></i>
    <span>Reportes Globales</span>
</a>
<a href="<?php echo $base_url; ?>superadmin/estadisticas_sedes.php" class="menu-item <?php echo $current_page == 'estadisticas_sedes.php' ? 'active' : ''; ?>">
    <i class="bi bi-pie-chart"></i>
    <span>Estadísticas por Sede</span>
</a>
<a href="<?php echo $base_url; ?>superadmin/auditoria_sistema.php" class="menu-item <?php echo $current_page == 'auditoria_sistema.php' ? 'active' : ''; ?>">
    <i class="bi bi-shield-check"></i>
    <span>Auditoría Sistema</span>
</a>

<div class="menu-section">Configuración</div>
<a href="<?php echo $base_url; ?>superadmin/configuracion_sistema.php" class="menu-item <?php echo $current_page == 'configuracion_sistema.php' ? 'active' : ''; ?>">
    <i class="bi bi-gear"></i>
    <span>Configuración Sistema</span>
</a>
<a href="<?php echo $base_url; ?>superadmin/respaldos.php" class="menu-item <?php echo $current_page == 'respaldos.php' ? 'active' : ''; ?>">
    <i class="bi bi-cloud-download"></i>
    <span>Respaldos</span>
</a>
