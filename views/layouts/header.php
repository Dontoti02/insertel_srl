<?php
if (!estaAutenticado()) {
    redirigir('auth/login.php');
}

// Sincronizar datos de sesión con la base de datos
sincronizarDatosSesion();

// Si se marcó para cerrar sesión (usuario desactivado), cerrar ahora
if (isset($_SESSION['debe_cerrar_sesion']) && $_SESSION['debe_cerrar_sesion']) {
    cerrarSesion();
}

$mensaje = getMensaje();
$nombre_empresa = obtenerNombreEmpresa();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo $nombre_empresa; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            /* Deep Koamaru Palette */
            --koamaru-50: #f1f5ff;
            --koamaru-100: #e5ebff;
            --koamaru-200: #cfddff;
            --koamaru-300: #a8bcff;
            --koamaru-400: #7791ff;
            --koamaru-500: #415cff;
            --koamaru-600: #1a2cff;
            --koamaru-700: #091df8;
            --koamaru-800: #0718d0;
            --koamaru-900: #0816aa;
            --koamaru-950: #01107e;

            /* Sistema de colores basado en Deep Koamaru */
            --color-primary: var(--koamaru-600);
            --color-primary-light: var(--koamaru-400);
            --color-primary-dark: var(--koamaru-800);
            --color-secondary: var(--koamaru-300);
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-info: var(--koamaru-500);
            --color-dark: var(--koamaru-950);
            --color-light: var(--koamaru-50);
            --color-purple: var(--koamaru-700);
            --color-accent: var(--koamaru-400);
            --color-muted: var(--koamaru-200);

            --sidebar-width: 260px;

            /* Gradientes con Deep Koamaru */
            --gradient-primary: linear-gradient(135deg, var(--koamaru-600) 0%, var(--koamaru-800) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--koamaru-300) 0%, var(--koamaru-500) 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, var(--koamaru-400) 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, var(--koamaru-400) 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, var(--koamaru-600) 100%);
            --gradient-accent: linear-gradient(135deg, var(--koamaru-400) 0%, var(--koamaru-600) 100%);
            --gradient-light: linear-gradient(135deg, var(--koamaru-50) 0%, var(--koamaru-100) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-light);
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--gradient-primary);
            color: white;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            /* Scroll personalizado */
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
            scroll-behavior: smooth;
        }

        /* Webkit scrollbar (Chrome, Safari, Edge) */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        /* Scroll suave solo cuando sea necesario */
        .sidebar:hover::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Scroll más visible al hacer hover en el sidebar */
        .sidebar:not(:hover)::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Animación suave del scroll ya incluida en .sidebar principal */

        /* Ocultar scroll en Firefox cuando no se usa */
        @-moz-document url-prefix() {
            .sidebar {
                scrollbar-width: thin;
                scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
            }
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .sidebar-header h4 {
            font-size: 20px;
            font-weight: 700;
            margin: 10px 0 5px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .sidebar-header small {
            opacity: 0.9;
            font-weight: 500;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--koamaru-300);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .menu-item:hover::before {
            transform: scaleY(1);
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .menu-item.active::before {
            transform: scaleY(1);
            background: var(--koamaru-400);
        }

        .menu-item i {
            margin-right: 15px;
            font-size: 20px;
            width: 28px;
            text-align: center;
        }

        .menu-section {
            padding: 20px 25px 8px;
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.7;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Top Navbar */
        .topbar {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--color-primary);
        }

        .topbar h5 {
            margin: 0;
            color: var(--color-dark);
            font-weight: 700;
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--gradient-accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(26, 44, 255, 0.3);
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 700;
            color: var(--color-dark);
            font-size: 16px;
        }

        .user-role {
            font-size: 13px;
            color: var(--color-primary);
            font-weight: 600;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card .icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card .icon.blue {
            background: var(--gradient-primary);
            color: white;
        }

        .stat-card .icon.green {
            background: var(--gradient-success);
            color: white;
        }

        .stat-card .icon.orange {
            background: var(--gradient-warning);
            color: white;
        }

        .stat-card .icon.red {
            background: var(--gradient-danger);
            color: white;
        }

        .stat-card .icon.purple {
            background: linear-gradient(135deg, var(--koamaru-700) 0%, var(--koamaru-500) 100%);
            color: white;
        }

        .stat-card .icon.teal {
            background: var(--gradient-secondary);
            color: white;
        }

        .stat-card .icon.koamaru {
            background: linear-gradient(135deg, var(--koamaru-400) 0%, var(--koamaru-600) 100%);
            color: white;
        }

        .stat-card .icon.koamaru-light {
            background: linear-gradient(135deg, var(--koamaru-300) 0%, var(--koamaru-500) 100%);
            color: white;
        }

        .stat-card h3 {
            font-size: 36px;
            font-weight: 800;
            margin: 0;
            color: var(--color-dark);
        }

        .stat-card p {
            margin: 8px 0 0 0;
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .content-card .card-header {
            border-bottom: 3px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .content-card .card-header h5 {
            margin: 0;
            color: var(--color-dark);
            font-weight: 700;
            font-size: 20px;
        }

        /* Enhanced Buttons */
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(26, 44, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 44, 255, 0.4);
            background: var(--gradient-primary);
        }

        .btn-success {
            background: var(--gradient-success);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: var(--gradient-warning);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: var(--gradient-danger);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-outline-primary {
            border: 2px solid var(--color-primary);
            color: var(--color-primary);
            background: transparent;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--gradient-primary);
            border-color: var(--color-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 44, 255, 0.3);
        }

        .btn-outline-success {
            border: 2px solid var(--color-success);
            color: var(--color-success);
            background: transparent;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-success:hover {
            background: var(--gradient-success);
            border-color: var(--color-success);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-outline-danger {
            border: 2px solid var(--color-danger);
            color: var(--color-danger);
            background: transparent;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background: var(--gradient-danger);
            border-color: var(--color-danger);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        /* Enhanced Table */
        .table {
            margin: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background: var(--gradient-primary);
            color: white;
            font-weight: 700;
            border: none;
            padding: 18px 15px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Enhanced Badges */
        .badge {
            padding: 8px 16px;
            font-weight: 600;
            font-size: 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.bg-success {
            background: var(--gradient-success) !important;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .badge.bg-warning {
            background: var(--gradient-warning) !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .badge.bg-danger {
            background: var(--gradient-danger) !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .badge.bg-primary {
            background: var(--gradient-primary) !important;
            box-shadow: 0 2px 8px rgba(26, 44, 255, 0.3);
        }

        .badge.bg-secondary {
            background: var(--gradient-secondary) !important;
            box-shadow: 0 2px 8px rgba(168, 188, 255, 0.3);
        }

        /* Form Controls */
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 44, 255, 0.25);
        }

        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 44, 255, 0.25);
        }

        /* Toast Container */
        .toast-container {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            border: none;
        }

        .toast-header.bg-success {
            background: var(--gradient-success) !important;
        }

        .toast-header.bg-danger {
            background: var(--gradient-danger) !important;
        }

        .toast-header.bg-warning {
            background: var(--gradient-warning) !important;
        }

        .toast-header.bg-primary {
            background: var(--gradient-primary) !important;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        /* Pagination */
        .pagination .page-link {
            color: var(--color-primary);
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            margin: 0 3px;
            padding: 10px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination .page-item.active .page-link {
            background: var(--gradient-primary);
            border-color: var(--color-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(26, 44, 255, 0.3);
        }

        .pagination .page-link:hover {
            color: white;
            background: var(--gradient-primary);
            border-color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 44, 255, 0.3);
        }

        .pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background-color: #f1f5f9;
            border-color: #e2e8f0;
        }

        /* Checkbox styling */
        .form-check-input {
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            width: 1.2em;
            height: 1.2em;
        }

        .form-check-input:checked {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            box-shadow: 0 2px 8px rgba(26, 44, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }

            .main-content {
                margin-left: 0;
            }

            .content-area {
                padding: 20px 15px;
            }

            .topbar {
                padding: 15px 20px;
            }

            .stat-card {
                padding: 20px;
            }

            .content-card {
                padding: 20px;
            }
        }

        /* Clases específicas Deep Koamaru */
        .bg-koamaru-50 {
            background-color: var(--koamaru-50) !important;
        }

        .bg-koamaru-100 {
            background-color: var(--koamaru-100) !important;
        }

        .bg-koamaru-200 {
            background-color: var(--koamaru-200) !important;
        }

        .bg-koamaru-300 {
            background-color: var(--koamaru-300) !important;
        }

        .bg-koamaru-400 {
            background-color: var(--koamaru-400) !important;
        }

        .bg-koamaru-500 {
            background-color: var(--koamaru-500) !important;
        }

        .bg-koamaru-600 {
            background-color: var(--koamaru-600) !important;
        }

        .bg-koamaru-700 {
            background-color: var(--koamaru-700) !important;
        }

        .bg-koamaru-800 {
            background-color: var(--koamaru-800) !important;
        }

        .bg-koamaru-900 {
            background-color: var(--koamaru-900) !important;
        }

        .bg-koamaru-950 {
            background-color: var(--koamaru-950) !important;
        }

        .text-koamaru-50 {
            color: var(--koamaru-50) !important;
        }

        .text-koamaru-100 {
            color: var(--koamaru-100) !important;
        }

        .text-koamaru-200 {
            color: var(--koamaru-200) !important;
        }

        .text-koamaru-300 {
            color: var(--koamaru-300) !important;
        }

        .text-koamaru-400 {
            color: var(--koamaru-400) !important;
        }

        .text-koamaru-500 {
            color: var(--koamaru-500) !important;
        }

        .text-koamaru-600 {
            color: var(--koamaru-600) !important;
        }

        .text-koamaru-700 {
            color: var(--koamaru-700) !important;
        }

        .text-koamaru-800 {
            color: var(--koamaru-800) !important;
        }

        .text-koamaru-900 {
            color: var(--koamaru-900) !important;
        }

        .text-koamaru-950 {
            color: var(--koamaru-950) !important;
        }

        .border-koamaru-300 {
            border-color: var(--koamaru-300) !important;
        }

        .border-koamaru-400 {
            border-color: var(--koamaru-400) !important;
        }

        .border-koamaru-500 {
            border-color: var(--koamaru-500) !important;
        }

        .border-koamaru-600 {
            border-color: var(--koamaru-600) !important;
        }

        /* Gradientes adicionales */
        .bg-gradient-koamaru {
            background: var(--gradient-primary) !important;
        }

        .bg-gradient-koamaru-light {
            background: var(--gradient-secondary) !important;
        }

        .bg-gradient-koamaru-accent {
            background: var(--gradient-accent) !important;
        }

        /* Badges personalizados para estados de materiales */
        .badge.bg-success.badge-activo-override {
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
            display: inline-block !important;
        }

        .badge.bg-danger.badge-inactivo-override {
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
            display: inline-block !important;
        }

        /* Fallback para badges individuales */
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
            display: inline-block !important;
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
            display: inline-block !important;
        }
    </style>
</head>

<body>
    <!-- Toast Container for Alerts -->
    <div class="toast-container">
        <?php if ($mensaje): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-<?php echo $mensaje['tipo']; ?> text-white">
                    <i class="bi bi-<?php echo $mensaje['tipo'] == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <strong class="me-auto">Notificación</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    <?php echo $mensaje['texto']; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['datos_actualizados']) && $_SESSION['datos_actualizados']): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
                <div class="toast-header bg-primary text-white">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong class="me-auto">Actualización de Datos</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    <strong>¡Atención!</strong> Tus datos de sesión han sido actualizados por el administrador del sistema.
                    <?php if (isset($_SESSION['sede_nombre'])): ?>
                        <br><small class="text-muted">Sede actual: <strong><?php echo $_SESSION['sede_nombre']; ?></strong></small>
                    <?php endif; ?>
                    <br><small class="text-muted">Por favor, verifica que todo esté correcto.</small>
                </div>
            </div>
            <?php
            // Limpiar la bandera después de mostrarla
            unset($_SESSION['datos_actualizados']);
            ?>
        <?php endif; ?>
    </div>


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-box-seam" style="font-size: 36px;"></i>
            <h4><?php echo $nombre_empresa; ?></h4>
            <small>Sistema de Inventario</small>
        </div>

        <div class="sidebar-menu">
            <?php
            $base_url = BASE_URL . 'views/';
            $current_page = basename($_SERVER['PHP_SELF']);

            // Menú según rol
            if (tieneRol(ROL_SUPERADMIN)) {
                include 'menu_superadmin.php';
            } elseif (tieneRol(ROL_ADMINISTRADOR)) {
                include 'menu_admin.php';
            } elseif (tieneRol(ROL_JEFE_ALMACEN)) {
                include 'menu_jefe.php';
            } elseif (tieneRol(ROL_ASISTENTE_ALMACEN)) {
                include 'menu_asistente.php';
            } elseif (tieneRol(ROL_TECNICO)) {
                include 'menu_tecnico.php';
            }
            ?>

            <div class="menu-section">Sistema</div>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <h5><?php echo $page_title ?? 'Dashboard'; ?></h5>
            <div class="user-info">
                <!-- Alertas de Stock Bajo -->
                <?php include __DIR__ . '/../../components/alertas_stock.php'; ?>

                <div class="dropdown">
                    <div class="d-flex align-items-center cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-details me-3">
                            <div class="user-name"><?php echo $_SESSION['nombre_completo']; ?></div>
                            <div class="user-role"><?php echo $_SESSION['rol_nombre']; ?></div>
                            <?php if (!tieneRol(ROL_SUPERADMIN) && isset($_SESSION['sede_nombre'])): ?>
                                <div class="text-muted" style="font-size: 12px;">
                                    Sede: <strong><?php echo $_SESSION['sede_nombre']; ?></strong>
                                    <?php if (!empty($_SESSION['sede_codigo'])): ?>
                                        (<code><?php echo $_SESSION['sede_codigo']; ?></code>)
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['nombre_completo'], 0, 1)); ?>
                        </div>
                        <i class="bi bi-chevron-down ms-2"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>views/perfil.php">
                                <i class="bi bi-person me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>views/configuracion.php">
                                <i class="bi bi-gear me-2"></i>Configuración
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">