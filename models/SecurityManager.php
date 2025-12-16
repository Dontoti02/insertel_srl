<?php

/**
 * Gestor de Seguridad
 * Maneja rate limiting, auditoría y protección contra ataques
 */

class SecurityManager
{
    private $conn;
    private $table_rate_limit = "security_rate_limit";
    private $table_audit = "security_audit_log";

    // Configuraciones por defecto
    private $max_login_attempts = 5;
    private $login_lockout_minutes = 15;
    private $max_recovery_attempts = 3;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->loadSecurityConfig();
    }

    /**
     * Cargar configuraciones de seguridad desde BD
     */
    private function loadSecurityConfig()
    {
        try {
            $query = "SELECT clave, valor FROM configuracion_sistema 
                      WHERE categoria = 'seguridad' AND clave IN ('max_login_attempts', 'login_lockout_minutes', 'max_recovery_attempts')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->{$row['clave']} = (int)$row['valor'];
            }
        } catch (Exception $e) {
            // Usar valores por defecto si hay error
        }
    }

    /**
     * Verificar rate limit para una acción
     * Retorna array con: allowed (bool), remaining_attempts (int), retry_after (int segundos)
     */
    public function checkRateLimit($identifier, $action_type = 'login')
    {
        try {
            // Limpiar bloqueos expirados
            $this->cleanupExpiredBlocks();

            $query = "SELECT attempts, blocked_until, last_attempt 
                      FROM " . $this->table_rate_limit . " 
                      WHERE identifier = :identifier 
                      AND action_type = :action_type";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->bindParam(':action_type', $action_type);
            $stmt->execute();

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si está bloqueado
            if ($record && $record['blocked_until']) {
                $blocked_until = strtotime($record['blocked_until']);
                $now = time();

                if ($blocked_until > $now) {
                    return [
                        'allowed' => false,
                        'remaining_attempts' => 0,
                        'retry_after' => $blocked_until - $now,
                        'blocked_until' => $record['blocked_until']
                    ];
                }
            }

            // Determinar límite según tipo de acción
            $max_attempts = $action_type === 'login' ? $this->max_login_attempts : $this->max_recovery_attempts;
            $current_attempts = $record ? (int)$record['attempts'] : 0;

            return [
                'allowed' => true,
                'remaining_attempts' => max(0, $max_attempts - $current_attempts),
                'retry_after' => 0,
                'blocked_until' => null
            ];
        } catch (Exception $e) {
            // En caso de error, permitir pero registrar
            $this->logSecurityEvent(
                'rate_limit_error',
                'high',
                null,
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                false,
                $e->getMessage()
            );
            return ['allowed' => true, 'remaining_attempts' => 1, 'retry_after' => 0];
        }
    }

    /**
     * Registrar intento fallido
     */
    public function recordFailedAttempt($identifier, $action_type = 'login')
    {
        try {
            $now = date('Y-m-d H:i:s');

            $query = "INSERT INTO " . $this->table_rate_limit . " 
                      (identifier, action_type, attempts, last_attempt) 
                      VALUES (:identifier, :action_type, 1, :now)
                      ON DUPLICATE KEY UPDATE 
                      attempts = attempts + 1, 
                      last_attempt = :now";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->bindParam(':action_type', $action_type);
            $stmt->bindParam(':now', $now);
            $stmt->execute();

            // Verificar si debe bloquearse
            $check = $this->checkRateLimit($identifier, $action_type);
            if ($check['remaining_attempts'] <= 0) {
                $this->blockIdentifier($identifier, $action_type);
            }
        } catch (Exception $e) {
            // Log del error
            error_log("SecurityManager: Error recording failed attempt - " . $e->getMessage());
        }
    }

    /**
     * Bloquear identificador
     */
    private function blockIdentifier($identifier, $action_type)
    {
        $lockout_minutes = $action_type === 'login' ? $this->login_lockout_minutes : 60; // 1 hora para recovery
        $blocked_until = date('Y-m-d H:i:s', strtotime("+{$lockout_minutes} minutes"));

        $query = "UPDATE " . $this->table_rate_limit . " 
                  SET blocked_until = :blocked_until 
                  WHERE identifier = :identifier 
                  AND action_type = :action_type";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':blocked_until', $blocked_until);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->bindParam(':action_type', $action_type);
        $stmt->execute();

        // Registrar evento de bloqueo
        $this->logSecurityEvent(
            'account_locked',
            'high',
            null,
            null,
            null,
            $identifier,
            false,
            "Account locked for {$lockout_minutes} minutes"
        );
    }

    /**
     * Resetear intentos tras éxito
     */
    public function resetAttempts($identifier, $action_type = 'login')
    {
        try {
            $query = "DELETE FROM " . $this->table_rate_limit . " 
                      WHERE identifier = :identifier 
                      AND action_type = :action_type";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->bindParam(':action_type', $action_type);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("SecurityManager: Error resetting attempts - " . $e->getMessage());
        }
    }

    /**
     * Limpiar bloqueos expirados
     */
    private function cleanupExpiredBlocks()
    {
        try {
            $now = date('Y-m-d H:i:s');
            $query = "UPDATE " . $this->table_rate_limit . " 
                      SET blocked_until = NULL, attempts = 0 
                      WHERE blocked_until IS NOT NULL 
                      AND blocked_until < :now";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':now', $now);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("SecurityManager: Error cleaning up blocks - " . $e->getMessage());
        }
    }

    /**
     * Registrar evento de seguridad en audit log
     */
    public function logSecurityEvent(
        $event_type,
        $severity,
        $user_id,
        $username,
        $email,
        $ip_address,
        $success = false,
        $error_message = null,
        $metadata = null
    ) {
        try {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $metadata_json = $metadata ? json_encode($metadata) : null;

            $query = "INSERT INTO " . $this->table_audit . " 
                      (event_type, severity, user_id, username, email, ip_address, user_agent, 
                       success, error_message, metadata) 
                      VALUES (:event_type, :severity, :user_id, :username, :email, :ip_address, 
                              :user_agent, :success, :error_message, :metadata)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':event_type', $event_type);
            $stmt->bindParam(':severity', $severity);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':success', $success, PDO::PARAM_BOOL);
            $stmt->bindParam(':error_message', $error_message);
            $stmt->bindParam(':metadata', $metadata_json);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("SecurityManager: Error logging security event - " . $e->getMessage());
        }
    }

    /**
     * Validar dirección IP
     */
    public function isValidIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Obtener IP del cliente (considerando proxies)
     */
    public function getClientIP()
    {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                // Si hay múltiples IPs (proxy chain), tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if ($this->isValidIP($ip)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Detectar posibles ataques basados en patrones
     */
    public function detectSuspiciousActivity($input)
    {
        $suspicious_patterns = [
            '/(<script|javascript:|onerror=)/i',  // XSS
            '/(union|select|from|where|drop|insert|update|delete)/i',  // SQL Injection
            '/(\.\.|\/etc\/|\/var\/)/i',  // Path traversal
            '/(exec|eval|system|passthru)/i',  // Command injection
        ];

        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limpiar logs antiguos (mantener últimos 90 días)
     */
    public function cleanupOldLogs($days = 90)
    {
        try {
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            $query = "DELETE FROM " . $this->table_audit . " 
                      WHERE created_at < :cutoff_date 
                      AND severity NOT IN ('high', 'critical')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cutoff_date', $cutoff_date);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("SecurityManager: Error cleaning old logs - " . $e->getMessage());
            return 0;
        }
    }
}
