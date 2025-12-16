<?php

/**
 * Modelo para recuperación de contraseñas - VERSION SEGURA
 * Implementa patrón Selector/Verifier para prevenir timing attacks
 * Los tokens nunca se guardan en texto plano, solo sus hashes
 */

class PasswordRecovery
{
    private $conn;
    private $table_recovery = "password_recovery_tokens";
    private $table_remember = "remember_tokens";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Generar selector aleatorio (parte pública del token)
     */
    private function generateSelector($length = 16)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generar verifier aleatorio (parte secreta del token)
     */
    private function generateVerifier($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash del verifier para almacenamiento seguro
     */
    private function hashVerifier($verifier)
    {
        return hash('sha256', $verifier);
    }

    /**
     * Crear token de recuperación con patrón Selector/Verifier
     * Retorna: selector:verifier (solo el verifier se hashea en BD)
     */
    public function createRecoveryToken($user_id)
    {
        try {
            // Limpiar tokens anteriores del usuario
            $this->cleanupUserTokens($user_id);

            // Generar selector y verifier
            $selector = $this->generateSelector();
            $verifier = $this->generateVerifier();
            $verifier_hash = $this->hashVerifier($verifier);

            // Obtener validez del token desde configuración
            $validity_hours = $this->getTokenValidityHours();
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$validity_hours} hours"));

            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $query = "INSERT INTO " . $this->table_recovery . " 
                      (user_id, selector, token_hash, expires_at, ip_address, user_agent) 
                      VALUES (:user_id, :selector, :token_hash, :expires_at, :ip_address, :user_agent)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':selector', $selector);
            $stmt->bindParam(':token_hash', $verifier_hash);
            $stmt->bindParam(':expires_at', $expires_at);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);

            if ($stmt->execute()) {
                // Retornar token completo: selector:verifier
                // Este es el único momento donde el verifier está disponible
                return $selector . ':' . $verifier;
            }
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error creating token - " . $e->getMessage());
        }

        return false;
    }

    /**
     * Validar token de recuperación usando timing-safe comparison
     */
    public function validateRecoveryToken($token)
    {
        try {
            // Separar selector y verifier
            $parts = explode(':', $token);
            if (count($parts) !== 2) {
                return false;
            }

            list($selector, $verifier) = $parts;

            // Buscar por selector (parte pública)
            $query = "SELECT r.*, u.id as user_id, u.username, u.email 
                      FROM " . $this->table_recovery . " r
                      INNER JOIN usuarios u ON r.user_id = u.id
                      WHERE r.selector = :selector 
                      AND r.expires_at > NOW() 
                      AND r.used = FALSE
                      AND u.estado = 'activo'
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':selector', $selector);
            $stmt->execute();

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return false;
            }

            // Verificar hash del verifier usando timing-safe comparison
            $verifier_hash = $this->hashVerifier($verifier);

            if (hash_equals($record['token_hash'], $verifier_hash)) {
                return $record;
            }
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error validating token - " . $e->getMessage());
        }

        return false;
    }

    /**
     * Marcar token como usado
     */
    public function markTokenAsUsed($token)
    {
        try {
            $parts = explode(':', $token);
            if (count($parts) !== 2) {
                return false;
            }

            $selector = $parts[0];

            $query = "UPDATE " . $this->table_recovery . " 
                      SET used = TRUE 
                      WHERE selector = :selector";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':selector', $selector);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error marking token as used - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpiar tokens del usuario (todos los tokens de recuperación)
     */
    private function cleanupUserTokens($user_id)
    {
        try {
            $query = "DELETE FROM " . $this->table_recovery . " 
                      WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error cleaning user tokens - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear token de "recordar sesión" con patrón Selector/Verifier
     */
    public function createRememberToken($user_id)
    {
        try {
            $selector = $this->generateSelector();
            $verifier = $this->generateVerifier();
            $verifier_hash = $this->hashVerifier($verifier);

            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

            $query = "INSERT INTO " . $this->table_remember . " 
                      (user_id, selector, token_hash, expires_at, user_agent, ip_address) 
                      VALUES (:user_id, :selector, :token_hash, :expires_at, :user_agent, :ip_address)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':selector', $selector);
            $stmt->bindParam(':token_hash', $verifier_hash);
            $stmt->bindParam(':expires_at', $expires_at);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':ip_address', $ip_address);

            if ($stmt->execute()) {
                return $selector . ':' . $verifier;
            }
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error creating remember token - " . $e->getMessage());
        }

        return false;
    }

    /**
     * Validar token de "recordar sesión"
     */
    public function validateRememberToken($token)
    {
        try {
            $parts = explode(':', $token);
            if (count($parts) !== 2) {
                return false;
            }

            list($selector, $verifier) = $parts;

            $query = "SELECT r.*, u.* 
                      FROM " . $this->table_remember . " r
                      INNER JOIN usuarios u ON r.user_id = u.id
                      WHERE r.selector = :selector 
                      AND r.expires_at > NOW()
                      AND u.estado = 'activo'
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':selector', $selector);
            $stmt->execute();

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return false;
            }

            // Verificar hash del verifier
            $verifier_hash = $this->hashVerifier($verifier);

            if (hash_equals($record['token_hash'], $verifier_hash)) {
                // Actualizar último uso
                $this->updateRememberTokenUsage($selector);
                return $record;
            }
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error validating remember token - " . $e->getMessage());
        }

        return false;
    }

    /**
     * Actualizar último uso del token
     */
    private function updateRememberTokenUsage($selector)
    {
        try {
            $query = "UPDATE " . $this->table_remember . " 
                      SET last_used = NOW() 
                      WHERE selector = :selector";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':selector', $selector);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error updating token usage - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar token de "recordar sesión"
     */
    public function deleteRememberToken($token)
    {
        try {
            $parts = explode(':', $token);
            if (count($parts) === 0) {
                return false;
            }

            $selector = $parts[0];

            $query = "DELETE FROM " . $this->table_remember . " 
                      WHERE selector = :selector";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':selector', $selector);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error deleting remember token - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar todos los tokens de remember de un usuario
     */
    public function deleteAllRememberTokens($user_id)
    {
        try {
            $query = "DELETE FROM " . $this->table_remember . " 
                      WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error deleting all remember tokens - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpiar tokens expirados
     */
    public function cleanupExpiredTokens()
    {
        try {
            // Limpiar tokens de recuperación expirados o usados
            $query1 = "DELETE FROM " . $this->table_recovery . " 
                       WHERE expires_at < NOW() OR used = TRUE";
            $this->conn->exec($query1);

            // Limpiar tokens de remember expirados
            $query2 = "DELETE FROM " . $this->table_remember . " 
                       WHERE expires_at < NOW()";
            $this->conn->exec($query2);

            return true;
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error cleaning expired tokens - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener usuario por email
     */
    public function getUserByEmail($email)
    {
        try {
            $query = "SELECT * FROM usuarios 
                      WHERE email = :email 
                      AND estado = 'activo'
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error getting user by email - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener usuario por username
     */
    public function getUserByUsername($username)
    {
        try {
            $query = "SELECT * FROM usuarios 
                      WHERE username = :username 
                      AND estado = 'activo'
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("PasswordRecovery: Error getting user by username - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar contraseña del usuario
     */
    public function updateUserPassword($user_id, $new_password)
    {
        try {
            // Validar requisitos de contraseña
            if (!$this->validatePasswordStrength($new_password)) {
                return false;
            }

            $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);

            $query = "UPDATE usuarios 
                      SET password = :password, 
                          updated_at = CURRENT_TIMESTAMP 
                      WHERE id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':user_id', $user_id);

            return $stmt->execute();
        } catch (Exception $e) {
            // Si Argon2id no está disponible, usar bcrypt
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $query = "UPDATE usuarios 
                          SET password = :password, 
                              updated_at = CURRENT_TIMESTAMP 
                          WHERE id = :user_id";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':user_id', $user_id);

                return $stmt->execute();
            } catch (Exception $e2) {
                error_log("PasswordRecovery: Error updating password - " . $e2->getMessage());
                return false;
            }
        }
    }

    /**
     * Validar fortaleza de la contraseña
     */
    private function validatePasswordStrength($password)
    {
        $min_length = $this->getMinPasswordLength();

        if (strlen($password) < $min_length) {
            return false;
        }

        // Verificar requisitos según configuración
        $config = $this->getPasswordRequirements();

        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($config['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if ($config['require_special'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Obtener longitud mínima de contraseña desde configuración
     */
    private function getMinPasswordLength()
    {
        try {
            $query = "SELECT valor FROM configuracion_sistema WHERE clave = 'password_min_length' LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['valor'] : 8;
        } catch (Exception $e) {
            return 8; // Default
        }
    }

    /**
     * Obtener requisitos de contraseña desde configuración
     */
    private function getPasswordRequirements()
    {
        try {
            $query = "SELECT clave, valor FROM configuracion_sistema 
                      WHERE clave IN ('password_require_uppercase', 'password_require_numbers', 'password_require_special')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $config = [
                'require_uppercase' => true,
                'require_numbers' => true,
                'require_special' => true
            ];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = str_replace('password_', '', $row['clave']);
                $config[$key] = (bool)$row['valor'];
            }

            return $config;
        } catch (Exception $e) {
            return [
                'require_uppercase' => true,
                'require_numbers' => true,
                'require_special' => true
            ];
        }
    }

    /**
     * Obtener validez del token desde configuración
     */
    private function getTokenValidityHours()
    {
        try {
            $query = "SELECT valor FROM configuracion_sistema WHERE clave = 'recovery_token_validity_hours' LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['valor'] : 1;
        } catch (Exception $e) {
            return 1; // Default to 1 hour
        }
    }
}
