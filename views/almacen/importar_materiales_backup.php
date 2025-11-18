<?php
/**
 * Importar Materiales desde Excel/CSV - Jefe de Almacen
 */

require_once '../../config/constants.php';
require_once '../../config/functions.php';
require_once '../../config/database.php';
require_once '../../models/Material.php';

if (!tieneAlgunRol([ROL_JEFE_ALMACEN, ROL_ADMINISTRADOR])) {
    redirigirSegunRol();
}

$database = new Database();
$db = $database->getConnection();
$materialModel = new Material($db);

$page_title = "Importar Materiales";
include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-upload"></i>
                        Importar Materiales desde Excel/CSV
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Instrucciones -->
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Instrucciones:</h6>
                        <ul class="mb-0">
                            <li>El archivo debe ser Excel (.xlsx, .xls) o CSV (.csv)</li>
                            <li>La primera fila debe contener los nombres de las columnas</li>
                            <li>Columnas obligatorias: <strong>codigo</strong>, <strong>nombre</strong></li>
                            <li>Columnas opcionales: descripcion, unidad, categoria_nombre, proveedor_nombre, costo_unitario, stock_actual, stock_minimo, stock_maximo, estado</li>
                            <li>Si un material ya existe (mismo código), se actualizará</li>
                            <li>Las categorías y proveedores se crearán automáticamente si no existen</li>
                        </ul>
                    </div>
                    
                    <!-- Formulario de importación -->
                    <form id="formImportacion" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="archivo_excel" class="form-label">
                                        <i class="bi bi-file-earmark-spreadsheet"></i>
                                        Seleccionar archivo Excel/CSV
                                    </label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="archivo_excel" 
                                           name="archivo_excel" 
                                           accept=".xlsx,.xls,.csv" 
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor seleccione un archivo válido.
                                    </div>
                                    <div class="form-text">
                                        Formatos soportados: Excel (.xlsx, .xls) y CSV (.csv). Tamaño máximo: 10MB
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg" id="btnImportar">
                                            <i class="bi bi-upload"></i>
                                            Importar Materiales
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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
