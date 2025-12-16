# 📧 CONFIGURACIÓN DE MAILTRAP - GUÍA RÁPIDA

## 🔑 Paso 1: Obtener tu API Token

1. **Inicia sesión** en [Mailtrap.io](https://mailtrap.io)
2. Ve a **Settings → API Tokens** (o https://mailtrap.io/api-tokens)
3. Haz clic en **Create Token**
4. Dale un nombre (ej: "INSERTEL Recovery Emails")
5. Copia el token que aparece (se ve como: `abc123def456...`)

## ⚙️ Paso 2: Configurar en tu Sistema

Abre el archivo: **`c:\xampp\htdocs\insertel\config\constants.php`**

Busca la línea 62:
```php
define('MAILTRAP_API_TOKEN', 'TU_API_TOKEN_AQUI');  // ⚠️ CAMBIAR ESTO
```

Reemplázala con tu token real:
```php
define('MAILTRAP_API_TOKEN', 'abc123def456...');  // ✅ Tu token aquí
```

**IMPORTANTE:** Guarda el archivo después de hacer el cambio.

##🎯 Paso 3: Probar el Envío

1. Abre tu navegador
2. Ve a: `http://localhost/insertel/auth/forgot_password.php`
3. Ingresa un email o usuario válido (ej: `admin`)
4. Haz clic en "Enviar Enlace de Recuperación"

### ✅ Si todo salió bien:
Verás el mensaje:
```
✓ Se ha enviado un enlace de recuperación a tu correo electrónico

Revisa tu bandeja de entrada y spam. El enlace es válido por 1 hora.
```

### ❌ Si NO está configurado:
Verás:
```
⚠️ MAILTRAP NO CONFIGURADO

Configura tu API Token en config/constants.php
```

---

## 📨 Paso 4: Ver el Email en Mailtrap

1. Ve a tu cuenta de **Mailtrap.io**
2. Haz clic en **Email Testing → Inboxes**
3. Verás el email recién enviado
4. ¡Ábrelo y haz clic en el botón azul "Restablecer mi Contraseña"!

---

## 🎨 Personalización (Opcional)

### Cambiar el Nombre del Remitente

En `config/constants.php`, línea 64:
```php
define('MAILTRAP_FROM_NAME', 'INSERTEL S.R.L.');  // Cambiar por el nombre deseado
```

### Cambiar el Email del Remitente

En `config/constants.php`, línea 63:
```php
define('MAILTRAP_FROM_EMAIL', 'noreply@insertel.com');  // Cambiar al email deseado
```

---

## 🧪 Probar el Servicio

Puedes crear un archivo de prueba temporal:

**`c:\xampp\htdocs\insertel\test_email.php`**
```php
<?php
require_once 'config/constants.php';
require_once 'services/MailtrapService.php';

$mailtrap = new MailtrapService();

if ($mailtrap->isConfigured()) {
    $result = $mailtrap->sendTestEmail('alopezsa6@ucvvirtual.edu.pe', 'Admin Test');
    
    if ($result['success']) {
        echo "✅ Email de prueba enviado correctamente!<br>";
        echo "Revisa tu inbox en Mailtrap.io";
    } else {
        echo "❌ Error: " . $result['message'];
    }
} else {
    echo "⚠️ Mailtrap no configurado. Revisa config/constants.php";
}
?>
```

Luego visita: `http://localhost/insertel/test_email.php`

---

## 🐛 Problemas Comunes

### Error: "API Token not configured"
**Solución:** Verifica que hayas modificado `constants.php` y guardado el archivo.

### Error: "Authorization failed"
**Solución:** El API Token es incorrecto. Cópialo nuevamente desde Mailtrap.

### Error: "cURL error"
**Solución:** 
1. Verifica que tengas conexión a Internet
2. Verifica que `php_curl` esté habilitado en XAMPP
3. Revisa en `php.ini` que esta línea NO tenga `;` al inicio:
   ```
   extension=curl
   ```

### Los emails no llegan a Mailtrap
**Solución:**
1. Revisa la pestaña "Spam" en Mailtrap
2. Asegúrate de estar viendo el inbox correcto
3. Verifica que el token sea del proyecto correcto

---

## 📊 Monitorear Envíos

Ver logs de envío en la base de datos:
```sql
SELECT * FROM security_audit_log 
WHERE event_type IN ('email_sent_success', 'email_send_failed')
ORDER BY created_at DESC;
```

---

## 🚀 Para Producción

Cuando quieras usar un email real (no Mailtrap):

### Opción 1: Gmail SMTP
Cambiar a PHPMailer con configuración SMTP de Gmail

### Opción 2: Mailtrap Send (Email Real)
Mailtrap también tiene un servicio de envío real.  
Solo cambia estas líneas en `MailtrapService.php`:
```php
// Línea 14, cambiar:
private $apiUrl = 'https://send.api.mailtrap.io/api/send';
// Por:
private $apiUrl = 'https://send.api.mailtrap.io/transport/{transport_id}/send';
```

---

## ✅ Checklist de Configuración

- [ ] Creé cuenta en Mailtrap.io
- [ ] Generé mi API Token
- [ ] Actualicé `constants.php` con mi token
- [ ] Probé enviar un email de recuperación
- [ ] Vi el email en mi inbox de Mailtrap
- [ ] El enlace de recuperación funciona

---

## 📞 ¿Necesitas Ayuda?

Si tienes problemas:

1. Verifica los logs: `SELECT * FROM security_audit_log ORDER BY created_at DESC LIMIT 10;`
2. Revisa que cURL esté habilitado en PHP
3. Asegúrate de tener conexión a Internet
4. Verifica que el token sea correcto (sin espacios extra)

---

**¡Listo!** Ahora tu sistema envía emails de recuperación de forma profesional y segura con Mailtrap.
