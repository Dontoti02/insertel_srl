# 🔒 Sistema Seguro de Recuperación de Contraseñas
## INSERTEL S.R.L. - Implementación de Seguridad Robusta

### ✨ Características Implementadas

Este sistema implementa las mejores prácticas de seguridad para la recuperación de contraseñas, protegiendo contra ataques comunes de hackers.

---

## 🛡️ Medidas de Seguridad Implementadas

### 1. **Tokens Hasheados con Patrón Selector/Verifier**
- ✅ Los tokens **NUNCA** se guardan en texto plano en la base de datos
- ✅ Se usa Hash SHA-256 para almacenamiento seguro
- ✅ Patrón Selector/Verifier previene timing attacks
- ✅ Tokens de un solo uso (se marcan como usados tras el cambio)
- ✅ Expiración automática (1 hora por defecto, configurable)

**¿Qué previene?**
- ❌ Robo de tokens de la base de datos
- ❌ Timing attacks para adivinar tokens
- ❌ Reutilización de enlaces de recuperación

---

### 2. **Rate Limiting (Límite de Intentos)**
- ✅ Máximo 3 intentos de recuperación por hora por IP
- ✅ Máximo 5 intentos de login antes de bloqueo
- ✅ Bloqueo temporal de 15 minutos tras exceder límite
- ✅ Mensajes progresivos de advertencia

**¿Qué previene?**
- ❌ Ataques de fuerza bruta
- ❌ Enumeración de usuarios
- ❌ Spam de solicitudes de recuperación

---

### 3. **Validación Robusta de Contraseñas**
- ✅ Longitud mínima configurable (8 caracteres por defecto)
- ✅ Requiere mayúsculas, minúsculas, números y símbolos
- ✅ Hash Argon2ID (o Bcrypt como fallback)
- ✅ Validación en servidor Y cliente

**¿Qué previene?**
- ❌ Contraseñas débiles
- ❌ Diccionarios comunes de contraseñas

---

### 4. **Protección Anti-Bot**
- ✅ Campo honeypot invisible para detectar bots
- ✅ Validación de entrada para detectar inyecciones
- ✅ Detección de patrones sospechosos (XSS, SQL Injection)

**¿Qué previene?**
- ❌ Bots automatizados
- ❌ Scripts de ataque
- ❌ Intentos de inyección SQL/XSS

---

### 5. **Auditoría Completa de Seguridad**
- ✅ Log de TODOS los eventos de seguridad
- ✅ Registro de IPs, User Agents y timestamps
- ✅ Niveles de severidad (low, medium, high, critical)
- ✅ Metadata en formato JSON para análisis

**Eventos Registrados:**
- 🔍 Solicitudes de recuperación (exitosas y fallidas)
- 🔍 Intentos con tokens inválidos
- 🔍 Detección de bots
- 🔍 Intentos de inyección
- 🔍 Bloqueos por rate limit

---

### 6. **Protección de Sesiones**
- ✅ Cookies HttpOnly (no accesibles por JavaScript)
- ✅ Tokens de "Recordar sesión" hasheados
- ✅ Invalidación automática de sesiones tras cambio de contraseña
- ✅ Tracking de último uso

---

## 📊 Tablas de Base de Datos

### `password_recovery_tokens`
Almacena tokens de recuperación de forma segura.

```sql
- id: INT (PK)
- user_id: INT (FK a usuarios)
- selector: VARCHAR(32) - Parte pública del token
- token_hash: VARCHAR(255) - Hash SHA256 del verifier
- expires_at: DATETIME - Fecha de expiración
- used: BOOLEAN - Si fue usado o no
- ip_address: VARCHAR(45) - IP del solicitante
- user_agent: TEXT - Navegador del solicitante
- created_at: TIMESTAMP
```

### `security_rate_limit`
Control de intentos para prevenir ataques.

```sql
- id: INT (PK)
- identifier: VARCHAR(255) - IP, email o username
- action_type: ENUM (login, password_recovery, api_request)
- attempts: INT - Número de intentos
- last_attempt: DATETIME - Último intento
- blocked_until: DATETIME - Bloqueado hasta
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

### `security_audit_log`
Registro completo de eventos de seguridad.

```sql
- id: INT (PK)
- event_type: VARCHAR(50) - Tipo de evento
- severity: ENUM (low, medium, high, critical)
- user_id: INT - ID del usuario (si aplica)
- username: VARCHAR - Username intentado
- email: VARCHAR - Email intentado
- ip_address: VARCHAR(45) - IP del cliente
- user_agent: TEXT - Navegador
- success: BOOLEAN - Si fue exitoso
- error_message: TEXT - Mensaje de error
- metadata: JSON - Datos adicionales
- created_at: TIMESTAMP
```

---

## 🔧 Configuraciones del Sistema

Todas las configuraciones se manejan desde la tabla `configuracion_sistema`:

| Clave | Valor Default | Descripción |
|-------|---------------|-------------|
| `max_login_attempts` | 5 | Intentos máximos de login |
| `login_lockout_minutes` | 15 | Minutos de bloqueo tras exceder intentos |
| `max_recovery_attempts` | 3 | Intentos máximos de recuperación por hora |
| `password_min_length` | 8 | Longitud mínima de contraseña |
| `password_require_special` | 1 | Requerir caracteres especiales |
| `password_require_numbers` | 1 | Requerir números |
| `password_require_uppercase` | 1 | Requerir mayúsculas |
| `recovery_token_validity_hours` | 1 | Horas de validez del token |
| `enable_email_notifications` | 1 | Activar notificaciones por email |

Para cambiar estos valores, editar directamente en la BD o desde el panel de administración.

---

## 📝 Uso del Sistema

### Para Solicitar Recuperación:
1. Usuario ingresa a `/auth/forgot_password.php`
2. Ingresa su email o username
3. El sistema valida:
   - ✅ Rate limit no excedido
   - ✅ No detección de bot
   - ✅ Entrada válida (sin inyecciones)
4. Si el usuario existe, se genera un token seguro
5. Se muestra el enlace (en desarrollo) o se envía por email (en producción)

### Para Restablecer Contraseña:
1. Usuario hace clic en el enlace recibido
2. Token se valida (selector + verifier hash)
3. Usuario ingresa nueva contraseña
4. Sistema valida requisitos de seguridad
5. Contraseña se actualiza con hash Argon2ID
6. Token se marca como usado
7. Todas las sesiones activas se invalidan

---

## 🚀 Integración con Email (Producción)

Para entorno de producción, integrar con un servicio de email como:

### PHPMailer (Recomendado)
```php
use PHPMailer\PHPMailer\PHPMailer;

function sendRecoveryEmail($to, $recovery_link) {
    $mail = new PHPMailer(true);
    
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Tu servidor SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'tu-email@empresa.com';
    $mail->Password = 'tu-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Destinatario
    $mail->setFrom('noreply@insertel.com', 'INSERTEL S.R.L.');
    $mail->addAddress($to);
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de Contraseña - INSERTEL';
    $mail->Body = "
        <h2>Recuperación de Contraseña</h2>
        <p>Has solicitado restablecer tu contraseña.</p>
        <p><a href='$recovery_link'>Haz clic aquí para crear una nueva contraseña</a></p>
        <p><small>Este enlace es válido por 1 hora.</small></p>
    ";
    
    $mail->send();
}
```

**Cambiar en `forgot_password.php` línea ~130:**
```php
// Comentar/eliminar el bloque de desarrollo
// Descomentar:
sendRecoveryEmail($user['email'], $recovery_link);
```

---

## 🔍 Monitoreo y Análisis

### Ver Intentos de Ataque:
```sql
SELECT * FROM security_audit_log 
WHERE severity IN ('high', 'critical') 
ORDER BY created_at DESC 
LIMIT 100;
```

### Ver IPs Bloqueadas:
```sql
SELECT * FROM security_rate_limit 
WHERE blocked_until > NOW() 
ORDER BY blocked_until DESC;
```

### Estadísticas de Recuperación:
```sql
SELECT 
    DATE(created_at) as fecha,
    COUNT(*) as total_intentos,
    SUM(success) as exitosos,
    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as fallidos
FROM security_audit_log
WHERE event_type LIKE 'password_%'
GROUP BY DATE(created_at)
ORDER BY fecha DESC;
```

---

## ⚙️ Mantenimiento

### Limpiar Logs Antiguos (ejecutar periódicamente):
```php
$security->cleanupOldLogs(90); // Mantener últimos 90 días
```

### Limpiar Tokens Expirados:
```php
$recovery->cleanupExpiredTokens();
```

### Desbloquear Manualmente una IP:
```sql
DELETE FROM security_rate_limit 
WHERE identifier = '192.168.1.100';
```

---

## 📈 Mejoras Futuras Recomendadas

1. **2FA (Autenticación de Dos Factores)**
   - Google Authenticator
   - SMS verification
   
2. **reCAPTCHA v3 de Google**
   - Protección adicional contra bots sofisticados
   
3. **Notificaciones de Seguridad**
   - Email al cambiar contraseña
   - Alertas de login desde nueva ubicación
   
4. **Análisis Avanzado**
   - Dashboard de seguridad
   - Gráficos de intentos de ataque
   - Alertas automáticas
   
5. **Whitelist/Blacklist de IPs**
   - Bloqueo permanente de IPs maliciosas
   - Whitelist para IPs confiables

---

## 🐛 Troubleshooting

### Error: "Constante ENVIRONMENT no definida"
**Solución:** Verificar que `config/constants.php` tenga:
```php
define('ENVIRONMENT', 'development');
```

### Error: "Tabla security_rate_limit no existe"
**Solución:** Ejecutar migración:
```bash
mysql -u root insertel_db < migrations/004_security_password_recovery.sql
```

### Los tokens no funcionan
**Solución:** Verificar que las columnas `selector` y `token_hash` existan en la tabla

---

## 📞 Soporte

Para preguntas o problemas, contactar al equipo de desarrollo de INSERTEL S.R.L.

---

**Versión:** 1.0  
**Fecha:** Noviembre 2025  
**Autor:** Sistema de Seguridad INSERTEL  
