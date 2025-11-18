<?php
/**
 * Importar Materiales desde Excel/CSV - Jefe de Almacen
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';
require_once '../../vendor/autoload.php'; // Composer autoload

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$materialModel = new Material($db);

$errores = [];
$exito = '';
$materiales_importados = 0;
$materiales_actualizados = 0;

// Procesar importación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    $archivo = $_FILES['archivo_excel'];
    
    // Obtener sede actual del usuario
    $sede_id = obtenerSedeActual();
    if (!$sede_id) {
        $errores[] = 'Error: No se pudo determinar la sede del usuario. Por favor, contacte al administrador.';
    }
    
    // Validar archivo
    $errores_validacion = validarArchivoExcel($archivo);
    if (!empty($errores_validacion)) {
        $errores = array_merge($errores, $errores_validacion);
    } else if (empty($errores)) {
        try {
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $datos_procesados = [];
            
            if ($extension === 'csv') {
                // Procesar CSV
                $datos_procesados = procesarArchivoCSV($archivo['tmp_name']);
            } else {
                // Procesar Excel con PhpSpreadsheet
                $spreadsheet = IOFactory::load($archivo['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                
                // Obtener headers de la primera fila
                $headers = [];
                $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1')[0];
                foreach ($headerRow as $header) {
                    $headers[] = trim($header);
                }
                
                // Procesar datos desde la fila 2
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row)[0];
                    
                    // Verificar que la fila no esté vacía
                    if (array_filter($rowData)) {
                        $material_data = [];
                        for ($col = 0; $col < count($headers) && $col < count($rowData); $col++) {
                            $value = $rowData[$col];
                            
                            // Convertir fechas de Excel si es necesario
                            if (is_numeric($value) && Date::isDateTime($worksheet->getCell(chr(65 + $col) . $row))) {
                                $value = Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
                            }
                            
                            $material_data[$headers[$col]] = trim($value);
                        }
                        $datos_procesados[] = $material_data;
                    }
                }
            }
            
            // Procesar cada material
            foreach ($datos_procesados as $index => $material_data) {
                $fila = $index + 2; // +2 porque empezamos desde la fila 2 y el índice es 0-based
                
                // Validar campos obligatorios
                if (empty($material_data['codigo']) || empty($material_data['nombre'])) {
                    $errores[] = "Fila $fila: Código y nombre son obligatorios";
                    continue;
                }
                
                try {
                    // Verificar si el material ya existe en esta sede
                    $query_existe = "SELECT id FROM materiales WHERE codigo = :codigo AND sede_id = :sede_id";
                    $stmt_existe = $db->prepare($query_existe);
                    $stmt_existe->bindParam(':codigo', $material_data['codigo']);
                    $stmt_existe->bindParam(':sede_id', $sede_id);
                    $stmt_existe->execute();
                    $material_existente = $stmt_existe->fetch(PDO::FETCH_ASSOC);
                    
                    // Obtener o crear categoría
                    $categoria_id = null;
                    if (!empty($material_data['categoria_nombre'])) {
                        $query_cat = "SELECT id FROM categorias_materiales WHERE nombre = :nombre";
                        $stmt_cat = $db->prepare($query_cat);
                        $stmt_cat->bindParam(':nombre', $material_data['categoria_nombre']);
                        $stmt_cat->execute();
                        $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$categoria) {
                            // Crear nueva categoría
                            $query_new_cat = "INSERT INTO categorias_materiales (nombre, descripcion) VALUES (:nombre, :descripcion)";
                            $stmt_new_cat = $db->prepare($query_new_cat);
                            $descripcion = "Categoría creada automáticamente durante importación";
                            $stmt_new_cat->bindParam(':nombre', $material_data['categoria_nombre']);
                            $stmt_new_cat->bindParam(':descripcion', $descripcion);
                            $stmt_new_cat->execute();
                            $categoria_id = $db->lastInsertId();
                        } else {
                            $categoria_id = $categoria['id'];
                        }
                    } elseif (!empty($material_data['categoria_id']) && is_numeric($material_data['categoria_id'])) {
                        $categoria_id = (int)$material_data['categoria_id'];
                    }
                    
                    // Obtener o crear proveedor
                    $proveedor_id = null;
                    if (!empty($material_data['proveedor_nombre'])) {
                        $query_prov = "SELECT id FROM proveedores WHERE nombre = :nombre";
                        $stmt_prov = $db->prepare($query_prov);
                        $stmt_prov->bindParam(':nombre', $material_data['proveedor_nombre']);
                        $stmt_prov->execute();
                        $proveedor = $stmt_prov->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$proveedor) {
                            // Crear nuevo proveedor
                            $query_new_prov = "INSERT INTO proveedores (nombre, contacto, telefono, email, estado) VALUES (:nombre, :contacto, :telefono, :email, 'activo')";
                            $stmt_new_prov = $db->prepare($query_new_prov);
                            $contacto = "Contacto automático";
                            $telefono = "";
                            $email = "";
                            $stmt_new_prov->bindParam(':nombre', $material_data['proveedor_nombre']);
                            $stmt_new_prov->bindParam(':contacto', $contacto);
                            $stmt_new_prov->bindParam(':telefono', $telefono);
                            $stmt_new_prov->bindParam(':email', $email);
                            $stmt_new_prov->execute();
                            $proveedor_id = $db->lastInsertId();
                        } else {
                            $proveedor_id = $proveedor['id'];
                        }
                    } elseif (!empty($material_data['proveedor_id']) && is_numeric($material_data['proveedor_id'])) {
                        $proveedor_id = (int)$material_data['proveedor_id'];
                    }
                    
                    // Preparar datos para inserción/actualización
                    $datos = [
                        'nombre' => $material_data['nombre'],
                        'descripcion' => $material_data['descripcion'] ?? '',
                        'categoria_id' => $categoria_id,
                        'unidad' => $material_data['unidad'] ?? 'unidad',
                        'stock_actual' => is_numeric($material_data['stock_actual'] ?? 0) ? (int)$material_data['stock_actual'] : 0,
                        'stock_minimo' => is_numeric($material_data['stock_minimo'] ?? 0) ? (int)$material_data['stock_minimo'] : 0,
                        'costo_unitario' => is_numeric($material_data['precio_unitario'] ?? $material_data['costo_unitario'] ?? 0) ? (float)($material_data['precio_unitario'] ?? $material_data['costo_unitario'] ?? 0) : 0,
                        'proveedor_id' => $proveedor_id,
                        'ubicacion' => $material_data['ubicacion'] ?? '',
                        'estado' => in_array($material_data['estado'] ?? 'activo', ['activo', 'inactivo']) ? $material_data['estado'] : 'activo',
                        'sede_id' => $sede_id
                    ];
                    
                    if ($material_existente) {
                        // Actualizar material existente
                        $query_update = "UPDATE materiales SET 
                            nombre = :nombre,
                            descripcion = :descripcion,
                            categoria_id = :categoria_id,
                            unidad = :unidad,
                            stock_actual = :stock_actual,
                            stock_minimo = :stock_minimo,
                            costo_unitario = :costo_unitario,
                            proveedor_id = :proveedor_id,
                            ubicacion = :ubicacion,
                            estado = :estado,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE id = :id";
                        
                        $stmt_update = $db->prepare($query_update);
                        $update_params = $datos;
                        unset($update_params['sede_id']); // No se actualiza la sede
                        $update_params['id'] = $material_existente['id'];
                        
                        if ($stmt_update->execute($update_params)) {
                            $materiales_actualizados++;
                        }
                    } else {
                        // Crear nuevo material
                        $query_insert = "INSERT INTO materiales 
                            (codigo, nombre, descripcion, categoria_id, unidad, stock_actual, stock_minimo, costo_unitario, proveedor_id, ubicacion, estado, sede_id) 
                            VALUES (:codigo, :nombre, :descripcion, :categoria_id, :unidad, :stock_actual, :stock_minimo, :costo_unitario, :proveedor_id, :ubicacion, :estado, :sede_id)";
                        
                        $stmt_insert = $db->prepare($query_insert);
                        $insert_params = $datos;
                        $insert_params['codigo'] = $material_data['codigo'];

                        if ($stmt_insert->execute($insert_params)) {
                            $materiales_importados++;
                        }
                    }
                    
                } catch (Exception $e) {
                    $errores[] = "Fila $fila: Error al procesar - " . $e->getMessage();
                }
            }
            
            if (empty($errores)) {
                $exito = "Importación completada exitosamente: $materiales_importados materiales nuevos, $materiales_actualizados actualizados";
                registrarActividad($_SESSION['usuario_id'], 'importar', 'materiales', "Importados: $materiales_importados, Actualizados: $materiales_actualizados");
            } else {
                if ($materiales_importados > 0 || $materiales_actualizados > 0) {
                    $exito = "Importación parcial: $materiales_importados materiales nuevos, $materiales_actualizados actualizados. Revisar errores.";
                }
            }
            
        } catch (Exception $e) {
            $errores[] = 'Error al procesar el archivo: ' . $e->getMessage();
        }
    }
}

$page_title = "Importar Materiales";
include '../layouts/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-upload me-2"></i>Importar Materiales desde Excel/CSV</h5>
                <a href="materiales.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Materiales
                </a>
            </div>
            
            <!-- Mensajes -->
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Errores encontrados:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($exito)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?php echo $exito; ?>
                </div>
            <?php endif; ?>
            
            <!-- Instrucciones -->
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="bi bi-info-circle me-2"></i>Instrucciones</h6>
                    <ol>
                        <li>Descarga la plantilla Excel/CSV</li>
                        <li>Completa los datos de los materiales</li>
                        <li>Guarda el archivo en formato CSV, XLS o XLSX</li>
                        <li>Sube el archivo usando el formulario</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <strong>Campos obligatorios:</strong> código, nombre<br>
                        <strong>Campos opcionales:</strong> descripción, categoría, unidad, stock, costo_unitario, proveedor, ubicación, estado<br>
                        <strong>Nota:</strong> Use <code>costo_unitario</code> para el precio del material
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Sistema mejorado:</strong> Ahora soporta archivos Excel nativos (.xls, .xlsx) usando PhpSpreadsheet
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6><i class="bi bi-download me-2"></i>Descargar Plantillas</h6>
                    <p>Descarga la plantilla con ejemplos para facilitar la importación:</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="../../templates/plantilla_materiales.csv" class="btn btn-success btn-sm" download>
                            <i class="bi bi-file-earmark-text me-2"></i>Plantilla CSV
                        </a>
                        <a href="../../templates/plantilla_materiales.xls" class="btn btn-success btn-sm" download>
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Plantilla Excel
                        </a>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Tip:</strong> La plantilla Excel incluye una hoja de instrucciones detalladas.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario de importación -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h6><i class="bi bi-cloud-upload me-2"></i>Subir Archivo</h6>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-4">
                <div class="mb-3">
                    <label for="archivo_excel" class="form-label">
                        <i class="bi bi-file-earmark me-1"></i>Seleccionar archivo Excel/CSV *
                    </label>
                    <input type="file" class="form-control" id="archivo_excel" name="archivo_excel" 
                           accept=".xlsx,.xls,.csv" required>
                    <div class="form-text">
                        Formatos permitidos: CSV, XLS, XLSX (máximo <?php echo number_format(MAX_FILE_SIZE / 1024 / 1024, 1); ?>MB)
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="materiales.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Importar Materiales
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('archivo_excel').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileSize = file.size;
        const maxSize = <?php echo MAX_FILE_SIZE; ?>;
        
        if (fileSize > maxSize) {
            alert('El archivo es demasiado grande. Máximo permitido: ' + (maxSize / 1024 / 1024).toFixed(1) + 'MB');
            e.target.value = '';
        }
    }
});
</script>

<?php include '../layouts/footer.php'; ?>
