<?php
/**
 * Script para generar alertas automáticas
 * Este script debe ejecutarse como tarea programada (cron job)
 * Recomendado: ejecutar cada hora
 */

// Incluir archivos necesarios
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/Alerta.php';
require_once dirname(__DIR__) . '/models/Configuracion.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $alertaModel = new Alerta($db);
    $configModel = new Configuracion($db);
    
    echo "=== GENERADOR DE ALERTAS AUTOMÁTICAS INSERTEL ===\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Obtener configuraciones
    $dias_vencimiento = (int)$configModel->obtenerValor('dias_alerta_vencimiento', 30);
    $horas_solicitud = (int)$configModel->obtenerValor('horas_respuesta_solicitud', 24);
    
    echo "Configuraciones:\n";
    echo "- Días anticipación vencimiento: $dias_vencimiento\n";
    echo "- Horas límite solicitudes: $horas_solicitud\n\n";
    
    // 1. Generar alertas de stock mínimo
    echo "1. Generando alertas de stock mínimo...\n";
    $alertas_stock = $alertaModel->generarAlertasStockMinimo();
    echo "   ✓ Generadas: $alertas_stock alertas\n\n";
    
    // 2. Generar alertas de vencimiento
    echo "2. Generando alertas de vencimiento...\n";
    $alertas_vencimiento = $alertaModel->generarAlertasVencimiento($dias_vencimiento);
    echo "   ✓ Generadas: $alertas_vencimiento alertas\n\n";
    
    // 3. Generar alertas de solicitudes pendientes
    echo "3. Generando alertas de solicitudes pendientes...\n";
    $alertas_solicitudes = $alertaModel->generarAlertasSolicitudesPendientes($horas_solicitud);
    echo "   ✓ Generadas: $alertas_solicitudes alertas\n\n";
    
    // 4. Limpiar alertas antiguas (solo una vez al día)
    $hora_actual = (int)date('H');
    if ($hora_actual == 2) { // Ejecutar a las 2:00 AM
        echo "4. Limpiando alertas antiguas...\n";
        $alertaModel->limpiarAntiguas(30);
        echo "   ✓ Alertas antiguas eliminadas\n\n";
    }
    
    $total_alertas = $alertas_stock + $alertas_vencimiento + $alertas_solicitudes;
    
    echo "=== RESUMEN ===\n";
    echo "Total de alertas generadas: $total_alertas\n";
    echo "- Stock mínimo: $alertas_stock\n";
    echo "- Vencimientos: $alertas_vencimiento\n";
    echo "- Solicitudes pendientes: $alertas_solicitudes\n";
    echo "\nProceso completado exitosamente.\n";
    
    // Registrar en log si es necesario
    if ($total_alertas > 0) {
        $log_message = date('Y-m-d H:i:s') . " - Alertas generadas: $total_alertas (Stock: $alertas_stock, Vencimiento: $alertas_vencimiento, Solicitudes: $alertas_solicitudes)\n";
        file_put_contents(dirname(__DIR__) . '/logs/alertas.log', $log_message, FILE_APPEND | LOCK_EX);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    // Registrar error en log
    $error_message = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    file_put_contents(dirname(__DIR__) . '/logs/alertas_error.log', $error_message, FILE_APPEND | LOCK_EX);
    
    exit(1);
}
?>
