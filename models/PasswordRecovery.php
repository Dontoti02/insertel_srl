<?php
/**
 * Modelo para recuperación de contraseñas
 */

class PasswordRecovery {
    private $conn;
    private $table_recovery = "password_recovery_tokens";
    private $table_remember = "remember_tokens";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Generar token seguro
     */
    private function generateSecureToken($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Crear token de recuperación
     */
    public function createRecoveryToken($user_id) {
        // Eliminar tokens anteriores del usuario
        $this->cleanupUserTokens($user_id);
        
        $token = $this->generateSecureToken();
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora
        
        $query = "INSERT INTO " . $this->table_recovery . " 
                  (user_id, token, expires_at) 
                  VALUES (:user_id, :token, :expires_at)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);
        
        if ($stmt->execute()) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Validar token de recuperación
     */
    public function validateRecoveryToken($token) {
        $query = "SELECT r.*, u.id as user_id, u.username, u.email 
                  FROM " . $this->table_recovery . " r
                  INNER JOIN usuarios u ON r.user_id = u.id
                  WHERE r.token = :token 
                  AND r.expires_at > NOW() 
                  AND r.used = FALSE
                  AND u.estado = 'activo'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marcar token como usado
     */
    public function markTokenAsUsed($token) {
        $query = "UPDATE " . $this->table_recovery . " 
                  SET used = TRUE 
                  WHERE token = :token";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        
        return $stmt->execute();
    }
    
    /**
     * Limpiar tokens del usuario
     */
    private function cleanupUserTokens($user_id) {
        $query = "DELETE FROM " . $this->table_recovery . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Crear token de "recordar sesión"
     */
    public function createRememberToken($user_id) {
        $token = $this->generateSecureToken();
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days')); // Token válido por 30 días
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $query = "INSERT INTO " . $this->table_remember . " 
                  (user_id, token, expires_at, user_agent, ip_address) 
                  VALUES (:user_id, :token, :expires_at, :user_agent, :ip_address)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':user_agent', $user_agent);
        $stmt->bindParam(':ip_address', $ip_address);
        
        if ($stmt->execute()) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Validar token de "recordar sesión"
     */
    public function validateRememberToken($token) {
        $query = "SELECT r.*, u.* 
                  FROM " . $this->table_remember . " r
                  INNER JOIN usuarios u ON r.user_id = u.id
                  WHERE r.token = :token 
                  AND r.expires_at > NOW()
                  AND u.estado = 'activo'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Actualizar último uso
            $this->updateRememberTokenUsage($token);
        }
        
        return $result;
    }
    
    /**
     * Actualizar último uso del token
     */
    private function updateRememberTokenUsage($token) {
        $query = "UPDATE " . $this->table_remember . " 
                  SET last_used = NOW() 
                  WHERE token = :token";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar token de "recordar sesión"
     */
    public function deleteRememberToken($token) {
        $query = "DELETE FROM " . $this->table_remember . " 
                  WHERE token = :token";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar todos los tokens de remember de un usuario
     */
    public function deleteAllRememberTokens($user_id) {
        $query = "DELETE FROM " . $this->table_remember . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Limpiar tokens expirados
     */
    public function cleanupExpiredTokens() {
        // Limpiar tokens de recuperación
        $query1 = "DELETE FROM " . $this->table_recovery . " 
                   WHERE expires_at < NOW() OR used = TRUE";
        $this->conn->exec($query1);
        
        // Limpiar tokens de remember
        $query2 = "DELETE FROM " . $this->table_remember . " 
                   WHERE expires_at < NOW()";
        $this->conn->exec($query2);
        
        return true;
    }
    
    /**
     * Obtener usuario por email
     */
    public function getUserByEmail($email) {
        $query = "SELECT * FROM usuarios 
                  WHERE email = :email 
                  AND estado = 'activo'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener usuario por username
     */
    public function getUserByUsername($username) {
        $query = "SELECT * FROM usuarios 
                  WHERE username = :username 
                  AND estado = 'activo'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualizar contraseña del usuario
     */
    public function updateUserPassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE usuarios 
                  SET password = :password, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
}
?>
