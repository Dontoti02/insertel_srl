# INSERTEL S.R.L. - Guía de Inicio Rápido

Este documento proporciona instrucciones esenciales para configurar y acceder al sistema INSERTEL.

## 1. Acceso al Sistema

Para acceder a la aplicación web, asegúrate de que tu servidor XAMPP esté en funcionamiento y luego navega a la siguiente URL en tu navegador:

`http://localhost/insertel/index.php`

## 2. Credenciales de Acceso

A continuación, se detallan los usuarios y contraseñas predeterminados para las diferentes sedes y roles.

### 2.1. Acceso Superadministrador

El usuario Superadministrador es responsable de la creación de sedes y la asignación de administradores para cada una. Cada sede opera con su propia base de datos.

- **Usuario:** `superadmin1`
- **Contraseña:** `12345678`

### 2.2. Usuarios de la Sede Central

Estos usuarios están preconfigurados para la "Sede Central".

- **Administrador:**
    - **Usuario:** `insertel25`
    - **Contraseña:** `12345678`
- **Jefe de Almacén:**
    - **Usuario:** `jefealm1`
    - **Contraseña:** `12345678`
- **Asistente de Almacén:**
    - **Usuario:** `asistalm1`
    - **Contraseña:** `12345678`
- **Técnico:**
    - **Usuario:** `tecnico1`
    - **Contraseña:** `12345678`

**Nota Importante:** Para acceder a otras sedes, primero debes crearlas y asignar un administrador utilizando la cuenta de Superadministrador.

## 3. Importación de la Base de Datos

Sigue estos pasos para configurar la base de datos:

1.  Accede a `phpMyAdmin` a través de XAMPP.
2.  Crea una nueva base de datos con el nombre `insertel_db`.
3.  Selecciona la base de datos `insertel_db` recién creada.
4.  Dirígete a la pestaña "Importar" en `phpMyAdmin`.
5.  Importa el archivo `insertel_db.sql` que se encuentra en la raíz del proyecto.

## 4. Plantilla de Materiales

La plantilla de materiales (`PLANTILLA DE MATERIALES.xlsx`) debe ser utilizada exclusivamente por el rol de Administrador para la importación de datos.