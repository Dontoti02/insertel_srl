<?php

/**
 * Script de Mantenimiento de Seguridad
 * Ejecutar periódicamente (diario recomendado) via cron o task scheduler
 * 
 * Windows Task Scheduler:
 * php C:\xampp\htdocs\insertel\scripts\security_maintenance.php
 * 
 * Linux Cron (diario a las 3 AM):
 * 0 3 * * * php /path/to/insertel/scripts/security_maintenance.php
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/PasswordRecovery.php';
require_once dirname(__DIR__) . '/models/SecurityManager.php';

echo "===========================================\n";
echo "  MANTENIMIENTO DE SEGURIDAD - INSERTEL\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    $recovery = new PasswordRecovery($db);
    $security = new SecurityManager($db);

    // 1. Limpiar tokens expirados
    echo "1. Limpiando tokens expirados...\n";
    $recovery->cleanupExpiredTokens();
    echo "   ✓ Tokens de recuperación y sesión limpiados\n\n";

    // 2. Limpiar bloqueos expirados
    echo "2. Limpiando bloqueos de rate limit expirados...\n";
    $query = "DELETE FROM security_rate_limit 
              WHERE blocked_until IS NOT NULL 
              AND blocked_until < NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "   ✓ {$deleted} bloqueos expirados eliminados\n\n";

    // 3. Limpiar logs antiguos (mantener últimos 90 días)
    echo "3. Limpiando logs de seguridad antiguos (>90 días)...\n";
    $deleted_logs = $security->cleanupOldLogs(90);
    echo "   ✓ {$deleted_logs} logs antiguos eliminados\n\n";

    // 4. Estadísticas
    echo "4. Generando estadísticas...\n";

    // Total de intentos de recuperación hoy
    $query = "SELECT COUNT(*) as total FROM security_audit_log 
              WHERE event_type LIKE 'password_recovery%' 
              AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recovery_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Intentos bloqueados hoy
    $query = "SELECT COUNT(*) as total FROM security_audit_log 
              WHERE event_type = 'recovery_blocked_rate_limit' 
              AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $blocked_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Bots detectados hoy
    $query = "SELECT COUNT(*) as total FROM security_audit_log 
              WHERE event_type = 'bot_detection' 
              AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $bots_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Inyecciones detectadas hoy
    $query = "SELECT COUNT(*) as total FROM security_audit_log 
              WHERE event_type = 'injection_attempt' 
              AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $injections_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "\n";
    echo "   ESTADÍSTICAS DEL DÍA:\n";
    echo "   ───────────────────────────\n";
    echo "   Intentos de recuperación: {$recovery_today}\n";
    echo "   Intentos bloqueados:      {$blocked_today}\n";
    echo "   Bots detectados:          {$bots_today}\n";
    echo "   Inyecciones detectadas:   {$injections_today}\n";
    echo "\n";

    // 5. Alertas de seguridad
    echo "5. Verificando alertas de seguridad...\n";

    // IPs con muchos intentos fallidos en últimas 24 horas
    $query = "SELECT ip_address, COUNT(*) as intentos
              FROM security_audit_log
              WHERE success = 0
              AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
              GROUP BY ip_address
              HAVING intentos > 10
              ORDER BY intentos DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $suspicious_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($suspicious_ips)) {
        echo "\n   ⚠️ ALERTA: IPs sospechosas detectadas:\n";
        foreach ($suspicious_ips as $ip) {
            echo "   • {$ip['ip_address']}: {$ip['intentos']} intentos fallidos\n";
        }
    } else {
        echo "   ✓ No se detectaron IPs sospechosas\n";
    }

    echo "\n===========================================\n";
    echo "  MANTENIMIENTO COMPLETADO EXITOSAMENTE\n";
    echo "===========================================\n";
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
