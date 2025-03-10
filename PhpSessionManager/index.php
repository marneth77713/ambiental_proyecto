<?php
// Incluir configuración global
require_once 'config/config.php';

// Incluir conexión a base de datos
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Inicializar base de datos si no existe
$database->setupDatabase();

// Título de la página
$titulo = "Editor de Proyectos Ambientales - Inicio";

// Incluir cabecera
require_once 'includes/header.php';

// Obtener proyectos recientes
$proyectos = [];
try {
    if ($conn) {
        // Crear la tabla si no existe
        $conn->exec("
            CREATE TABLE IF NOT EXISTS proyectos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                descripcion TEXT,
                contenido TEXT,
                config TEXT,
                categoria TEXT,
                plantilla_id INTEGER,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $conn->prepare("
            SELECT id, nombre, descripcion, categoria, fecha_creacion
            FROM proyectos
            ORDER BY fecha_modificacion DESC
            LIMIT 6
        ");
        $stmt->execute();
        $proyectos = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error al obtener proyectos: " . $e->getMessage());
}

// Obtener categorías con conteo
$categorias = [];
try {
    if ($conn) {
        $stmt = $conn->prepare("
            SELECT categoria, COUNT(*) as conteo
            FROM proyectos
            WHERE categoria IS NOT NULL AND categoria != ''
            GROUP BY categoria
        ");
        $stmt->execute();
        $categorias = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error al obtener categorías: " . $e->getMessage());
}

// Plantillas destacadas
$plantillas = [];
try {
    if ($conn) {
        // Crear la tabla si no existe
        $conn->exec("
            CREATE TABLE IF NOT EXISTS plantillas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                descripcion TEXT,
                preview TEXT,
                contenido TEXT,
                categoria TEXT,
                activo INTEGER DEFAULT 1,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $conn->prepare("
            SELECT id, nombre, descripcion, preview, categoria
            FROM plantillas
            WHERE activo = 1
            ORDER BY id
            LIMIT 4
        ");
        $stmt->execute();
        $plantillas = $stmt->fetchAll();
        
        // Si no hay plantillas, insertar plantillas de ejemplo
        if (count($plantillas) === 0) {
            // Reinicializar base de datos
            $database->setupDatabase();
            
            // Volver a obtener plantillas
            $stmt->execute();
            $plantillas = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log("Error al obtener plantillas: " . $e->getMessage());
}
?>

<!-- Hero Section -->
<section class="hero-section py-5 text-center text-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="display-4 fw-bold mb-4">Editor de Proyectos Ambientales</h1>
                <p class="lead mb-4">Crea y comparte proyectos interactivos con temáticas ecológicas y ambientales. Utiliza nuestras plantillas personalizables o comienza desde cero.</p>
                <div class="mt-5">
                    <a href="crear_proyecto.php" class="btn btn-success btn-lg me-3 mb-3">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Proyecto
                    </a>
                    <a href="crear_proyecto.php?categoria=todas" class="btn btn-outline-light btn-lg mb-3">
                        <i class="fas fa-th-large me-2"></i>Ver Plantillas
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Proyectos Recientes -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-0">Proyectos Recientes</h2>
                <p class="text-muted">Explora nuestros proyectos ambientales más recientes</p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if (count($proyectos) > 0): ?>
                <a href="#" class="btn btn-outline-success">
                    <i class="fas fa-th-list me-2"></i>Ver todos
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <?php if (count($proyectos) > 0): ?>
                <?php foreach ($proyectos as $proyecto): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($proyecto['nombre']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars(truncarTexto($proyecto['descripcion'] ?? '', 80)) ?></p>
                            <?php if (!empty($proyecto['categoria'])): ?>
                            <span class="badge bg-success mb-2"><?= htmlspecialchars($proyecto['categoria']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?= date('d M Y', strtotime($proyecto['fecha_creacion'])) ?>
                                </small>
                                <div>
                                    <a href="ver_proyecto.php?id=<?= $proyecto['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="far fa-eye me-1"></i>Ver
                                    </a>
                                    <a href="editar_proyecto.php?id=<?= $proyecto['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="far fa-edit me-1"></i>Editar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="p-4 rounded-3 bg-light">
                        <i class="fas fa-seedling fa-3x text-success mb-3"></i>
                        <h3>Aún no hay proyectos</h3>
                        <p class="text-muted">Comienza a crear tu primer proyecto ambiental</p>
                        <a href="crear_proyecto.php" class="btn btn-success mt-2">
                            <i class="fas fa-plus-circle me-2"></i>Crear proyecto
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Plantillas Destacadas -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-0">Plantillas Ambientales</h2>
                <p class="text-muted">Comienza con una de nuestras plantillas prediseñadas</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="crear_proyecto.php?categoria=todas" class="btn btn-outline-success">
                    <i class="fas fa-th me-2"></i>Ver todas
                </a>
            </div>
        </div>
        
        <div class="row">
            <?php if (count($plantillas) > 0): ?>
                <?php foreach ($plantillas as $plantilla): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100 border-0 shadow-sm template-card" data-id="<?= $plantilla['id'] ?>">
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
                            <p class="card-text text-muted template-description"><?= htmlspecialchars(truncarTexto($plantilla['descripcion'] ?? '', 60)) ?></p>
                            <?php if (!empty($plantilla['categoria'])): ?>
                            <span class="badge bg-primary"><?= htmlspecialchars($plantilla['categoria']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="p-4 rounded-3 bg-white">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h3>No se encontraron plantillas</h3>
                        <p class="text-muted">Por favor, inténtalo de nuevo más tarde</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Características -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-10 mx-auto">
                <h2>¿Por qué usar nuestro Editor de Proyectos Ambientales?</h2>
                <p class="lead text-muted">Una herramienta diseñada para crear proyectos ambientales atractivos y educativos</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-gradient text-white rounded-circle mb-3">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h4>Plantillas Ambientales</h4>
                        <p class="text-muted">Elige entre varias plantillas con temáticas de conservación, energías renovables y más.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <h4>Editor Intuitivo</h4>
                        <p class="text-muted">Interfaz sencilla de arrastrar y soltar para crear diseños sin necesidad de conocimientos técnicos.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info bg-gradient text-white rounded-circle mb-3">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <h4>Compartir Fácilmente</h4>
                        <p class="text-muted">Exporta tus proyectos en diferentes formatos o compártelos directamente con un enlace.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Llamada a la acción -->
<section class="py-5 bg-success text-white text-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="mb-3">Comienza tu proyecto ambiental ahora</h2>
                <p class="lead mb-4">Utiliza nuestras plantillas prediseñadas o comienza desde cero</p>
                <a href="crear_proyecto.php" class="btn btn-light btn-lg">
                    <i class="fas fa-rocket me-2"></i>Crear Proyecto
                </a>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar efectos en las plantillas
    const templateCards = document.querySelectorAll('.template-card');
    
    templateCards.forEach(card => {
        card.addEventListener('click', function() {
            const plantillaId = this.dataset.id;
            window.location.href = `crear_proyecto.php?plantilla_id=${plantillaId}`;
        });
    });
});
</script>

<?php
// Incluir pie de página
require_once 'includes/footer.php';
?>