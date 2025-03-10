<?php
// Incluir configuración global
require_once 'config/config.php';

// Incluir conexión a base de datos
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Obtener ID del proyecto
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    mostrarMensaje("ID de proyecto no válido", "error");
    redirigir("");
}

// Obtener datos del proyecto
$proyecto = null;
try {
    $stmt = $conn->prepare("SELECT * FROM proyectos WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $proyecto = $stmt->fetch();
    
    if (!$proyecto) {
        mostrarMensaje("El proyecto no existe", "error");
        redirigir("");
    }
} catch (PDOException $e) {
    error_log("Error al obtener proyecto: " . $e->getMessage());
    mostrarMensaje("Error al cargar el proyecto", "error");
    redirigir("");
}

// Verificar si se envió el formulario
$mensaje = '';
if (esPost()) {
    try {
        // Obtener datos del formulario
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $categoria = sanitizar($_POST['categoria'] ?? '');
        $contenido = $_POST['contenido_json'] ?? '{}';
        
        // Validar datos
        if (empty($nombre)) {
            throw new Exception("El nombre del proyecto es obligatorio.");
        }
        
        // Actualizar proyecto
        $stmt = $conn->prepare("
            UPDATE proyectos 
            SET nombre = :nombre, descripcion = :descripcion, categoria = :categoria, 
                contenido = :contenido, fecha_modificacion = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':contenido', $contenido);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            mostrarMensaje("Proyecto actualizado correctamente", "success");
            
            // Volver a cargar el proyecto
            $stmt = $conn->prepare("SELECT * FROM proyectos WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $proyecto = $stmt->fetch();
        } else {
            throw new Exception("Error al actualizar el proyecto.");
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        mostrarMensaje($mensaje, "error");
    }
}

// Configurar variables para la vista
$titulo = "Editar Proyecto: " . htmlspecialchars($proyecto['nombre']);
$pagina = "editar";

// CSS adicional
$extra_css = <<<CSS
<style>
.editor-container {
    position: relative;
    margin: 0 auto;
}

.editor-tools {
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.editor-sidebar {
    height: calc(100vh - 200px);
    overflow-y: auto;
}

.element-config-panel {
    display: none;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    margin-top: 1rem;
}

.elemento {
    user-select: none;
}

.elemento[data-tipo="texto"] {
    user-select: text;
}
</style>
CSS;

// JavaScript adicional
$extra_js = <<<JS
<script src="{$BASE_URL}js/editor.js"></script>
JS;

// Incluir cabecera
require_once 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="mb-0">Editor de Proyecto</h1>
        <p class="text-muted">Personalizando: <?= htmlspecialchars($proyecto['nombre']) ?></p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="ver_proyecto.php?id=<?= $id ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-eye me-1"></i>Vista previa
        </a>
        <a href="exportar_proyecto.php?id=<?= $id ?>" class="btn btn-outline-success me-2">
            <i class="fas fa-download me-1"></i>Exportar
        </a>
        <button id="btn-guardar" class="btn btn-success">
            <i class="fas fa-save me-1"></i>Guardar cambios
        </button>
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-danger mb-4"><?= $mensaje ?></div>
<?php endif; ?>

<div class="row">
    <!-- Herramientas de edición -->
    <div class="col-md-3">
        <div class="editor-sidebar">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Información</h5>
                </div>
                <div class="card-body">
                    <form id="form-editor" method="post" action="editar_proyecto.php?id=<?= $id ?>">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del proyecto *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($proyecto['nombre']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($proyecto['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Sin categoría</option>
                                <?php foreach (PLANTILLA_CATEGORIES as $key => $name): ?>
                                    <?php if ($key !== 'todas'): ?>
                                    <option value="<?= $key ?>" <?= $key === $proyecto['categoria'] ? 'selected' : '' ?>>
                                        <?= $name ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" id="contenido-json" name="contenido_json" 
                               value="<?= htmlspecialchars($proyecto['contenido'] ?? '{}') ?>">
                    </form>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Elementos</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button id="btn-add-text" class="btn btn-outline-primary">
                            <i class="fas fa-font me-1"></i>Añadir Texto
                        </button>
                        <button id="btn-add-shape" class="btn btn-outline-success">
                            <i class="fas fa-shapes me-1"></i>Añadir Forma
                        </button>
                        <div class="dropzone" id="drop-image">
                            <i class="fas fa-image me-1"></i>
                            <span>Arrastrar imagen o hacer clic</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Configuración</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="color-fondo" class="form-label">Color de fondo</label>
                        <input type="color" class="form-control" id="color-fondo" value="#f8f9fa">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dimensiones</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text">Ancho</span>
                            <input type="number" class="form-control" id="canvas-width" value="400" min="100" step="10">
                            <span class="input-group-text">px</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">Alto</span>
                            <input type="number" class="form-control" id="canvas-height" value="600" min="100" step="10">
                            <span class="input-group-text">px</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="elemento-config" class="element-config-panel">
                <!-- Panel de configuración de elementos -->
            </div>
        </div>
    </div>
    
    <!-- Área de edición -->
    <div class="col-md-9">
        <div class="editor-container">
            <div id="editor-canvas" class="canvas-container"></div>
            
            <div class="zoom-container">
                <button id="zoom-out" class="btn btn-sm btn-light" title="Reducir zoom">
                    <i class="fas fa-search-minus"></i>
                </button>
                <span id="zoom-percent" class="mx-2">100%</span>
                <button id="zoom-in" class="btn btn-sm btn-light" title="Aumentar zoom">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button id="zoom-reset" class="btn btn-sm btn-light ms-2" title="Restablecer zoom">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
require_once 'includes/footer.php';
?>