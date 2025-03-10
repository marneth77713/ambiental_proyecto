<?php
// Incluir configuración global
require_once 'config/config.php';

// Incluir conexión a base de datos
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Título de la página
$titulo = "Crear Nuevo Proyecto Ambiental";
$pagina = "crear";

// Verificar si se envió el formulario
$mensaje = '';
if (esPost()) {
    try {
        // Obtener datos del formulario
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $categoria = sanitizar($_POST['categoria'] ?? '');
        $plantilla_id = isset($_POST['plantilla_id']) ? (int)$_POST['plantilla_id'] : null;
        
        // Validar datos
        if (empty($nombre)) {
            throw new Exception("El nombre del proyecto es obligatorio.");
        }
        
        // Contenido inicial (puede ser desde una plantilla o vacío)
        $contenido = '{"elementos":[],"configuracion":{"fondo":"#f8f9fa","ancho":400,"alto":600}}';
        $config = '{}';
        
        // Si se seleccionó una plantilla, obtener su contenido
        if (!empty($plantilla_id)) {
            $stmt = $conn->prepare("SELECT contenido FROM plantillas WHERE id = :id");
            $stmt->bindParam(':id', $plantilla_id);
            $stmt->execute();
            
            if ($plantilla = $stmt->fetch()) {
                $contenido = $plantilla['contenido'];
            }
        }
        
        // Guardar proyecto
        $stmt = $conn->prepare("
            INSERT INTO proyectos (nombre, descripcion, contenido, config, categoria, plantilla_id) 
            VALUES (:nombre, :descripcion, :contenido, :config, :categoria, :plantilla_id)
        ");
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':contenido', $contenido);
        $stmt->bindParam(':config', $config);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':plantilla_id', $plantilla_id);
        
        if ($stmt->execute()) {
            $id = $conn->lastInsertId();
            mostrarMensaje("Proyecto creado correctamente.", "success");
            redirigir("editar_proyecto.php?id=" . $id);
        } else {
            throw new Exception("Error al crear el proyecto.");
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        mostrarMensaje($mensaje, "error");
    }
}

// Obtener categoría y plantilla_id de la URL si existen
$categoria_url = sanitizar($_GET['categoria'] ?? 'todas');
$plantilla_id_url = isset($_GET['plantilla_id']) ? (int)$_GET['plantilla_id'] : null;

// Obtener todas las plantillas
$plantillas = [];
try {
    if ($conn) {
        $sql = "SELECT id, nombre, descripcion, preview, categoria FROM plantillas WHERE activo = 1";
        
        // Filtrar por categoría si no es 'todas'
        if ($categoria_url !== 'todas') {
            $sql .= " AND categoria = :categoria";
        }
        
        $sql .= " ORDER BY nombre";
        
        $stmt = $conn->prepare($sql);
        
        if ($categoria_url !== 'todas') {
            $stmt->bindParam(':categoria', $categoria_url);
        }
        
        $stmt->execute();
        $plantillas = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error al obtener plantillas: " . $e->getMessage());
    mostrarMensaje("Error al cargar las plantillas.", "error");
}

// Si se especificó un ID de plantilla, obtener sus datos
$plantilla_seleccionada = null;
if ($plantilla_id_url) {
    try {
        $stmt = $conn->prepare("SELECT * FROM plantillas WHERE id = :id");
        $stmt->bindParam(':id', $plantilla_id_url);
        $stmt->execute();
        $plantilla_seleccionada = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener plantilla seleccionada: " . $e->getMessage());
    }
}

// Incluir cabecera
require_once 'includes/header.php';

// Si hay una plantilla seleccionada, mostrar formulario de creación
if ($plantilla_seleccionada) {
?>
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-4">Crear proyecto con plantilla</h1>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-4 mb-md-0">
                        <div class="template-preview mb-3">
                            <img src="<?= !empty($plantilla_seleccionada['preview']) ? $plantilla_seleccionada['preview'] : 'img/plantilla-default.png' ?>" 
                                 class="img-fluid rounded" alt="<?= htmlspecialchars($plantilla_seleccionada['nombre']) ?>">
                        </div>
                        <h4><?= htmlspecialchars($plantilla_seleccionada['nombre']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($plantilla_seleccionada['descripcion'] ?? '') ?></p>
                        <?php if (!empty($plantilla_seleccionada['categoria'])): ?>
                        <span class="badge bg-primary"><?= htmlspecialchars($plantilla_seleccionada['categoria']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h3 class="mb-3">Información del proyecto</h3>
                        
                        <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-danger"><?= $mensaje ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="crear_proyecto.php" id="form-crear-proyecto">
                            <input type="hidden" name="plantilla_id" value="<?= $plantilla_seleccionada['id'] ?>">
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del proyecto *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                                <div class="form-text">Elige un nombre descriptivo para tu proyecto.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                                <div class="form-text">Describe brevemente el propósito de tu proyecto.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria">
                                    <option value="">Sin categoría</option>
                                    <?php foreach (PLANTILLA_CATEGORIES as $key => $name): ?>
                                        <?php if ($key !== 'todas'): ?>
                                        <option value="<?= $key ?>" <?= $key === $plantilla_seleccionada['categoria'] ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Selecciona una categoría para organizar tus proyectos.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="crear_proyecto.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Ver otras plantillas
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-1"></i>Crear proyecto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php } else { // Mostrar selección de plantillas ?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="mb-3">Selecciona una plantilla</h1>
        <p class="lead text-muted">Comienza tu proyecto con una de nuestras plantillas prediseñadas o desde cero.</p>
    </div>
    <div class="col-md-4 d-flex justify-content-end align-items-center">
        <a href="crear_proyecto.php" class="btn btn-outline-success">
            <i class="fas fa-sync-alt me-1"></i>Reiniciar
        </a>
    </div>
</div>

<!-- Categorías de Plantillas -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-pills">
            <?php foreach (PLANTILLA_CATEGORIES as $key => $name): ?>
            <li class="nav-item me-2 mb-2">
                <a class="nav-link categoria-link <?= $categoria_url === $key ? 'active' : '' ?>" 
                   href="crear_proyecto.php?categoria=<?= $key ?>"
                   data-categoria="<?= $key ?>">
                    <?= $name ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Búsqueda de Plantillas -->
<div class="row mb-4">
    <div class="col-md-6 mx-auto">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0" id="busqueda-plantilla" 
                   placeholder="Buscar plantillas...">
        </div>
    </div>
</div>

<!-- Plantillas -->
<div class="row templates-container">
    <!-- Opción de Proyecto en Blanco -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100 border-0 shadow-sm template-card" data-categoria="blank">
            <div class="template-preview">
                <div class="bg-light text-center py-5">
                    <i class="fas fa-file fa-3x text-muted my-4"></i>
                </div>
                <div class="template-overlay">
                    <button class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Usar plantilla
                    </button>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title template-name">Proyecto en Blanco</h5>
                <p class="card-text text-muted template-description">Comienza un proyecto desde cero sin plantilla predefinida.</p>
                <form method="post" action="crear_proyecto.php" id="form-proyecto-blanco">
                    <input type="hidden" name="nombre" value="Nuevo Proyecto">
                    <input type="hidden" name="descripcion" value="Proyecto creado desde cero">
                    <button type="submit" class="btn btn-outline-success btn-sm mt-2">
                        <i class="fas fa-plus-circle me-1"></i>Crear en blanco
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Plantillas disponibles -->
    <?php foreach ($plantillas as $plantilla): ?>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100 border-0 shadow-sm template-card" 
             data-id="<?= $plantilla['id'] ?>"
             data-categoria="<?= $plantilla['categoria'] ?>">
            <div class="template-preview">
                <img src="<?= !empty($plantilla['preview']) ? $plantilla['preview'] : 'img/plantilla-default.png' ?>" 
                     class="card-img-top" alt="<?= htmlspecialchars($plantilla['nombre']) ?>">
                <div class="template-overlay">
                    <button class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Usar plantilla
                    </button>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title template-name"><?= htmlspecialchars($plantilla['nombre']) ?></h5>
                <p class="card-text text-muted template-description">
                    <?= htmlspecialchars(truncarTexto($plantilla['descripcion'] ?? '', 60)) ?>
                </p>
                <?php if (!empty($plantilla['categoria'])): ?>
                <span class="badge bg-primary"><?= htmlspecialchars($plantilla['categoria']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- JavaScript para la selección de plantillas -->
<script src="<?= BASE_URL ?>js/templates.js"></script>

<?php } ?>

<?php
// Incluir pie de página
require_once 'includes/footer.php';
?>