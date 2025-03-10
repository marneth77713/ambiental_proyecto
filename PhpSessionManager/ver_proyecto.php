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

// Configurar variables para la vista
$titulo = "Visualizar: " . htmlspecialchars($proyecto['nombre']);
$pagina = "ver";

// Parsear contenido JSON
$contenido = json_decode($proyecto['contenido'], true);
if (!$contenido) {
    $contenido = [
        'elementos' => [],
        'configuracion' => [
            'fondo' => '#f8f9fa',
            'ancho' => 400,
            'alto' => 600
        ]
    ];
}

// CSS adicional
$extra_css = <<<CSS
<style>
.proyecto-container {
    position: relative;
    margin: 0 auto;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.proyecto-elemento {
    position: absolute;
    pointer-events: none;
}

@media print {
    header, footer, .no-print {
        display: none !important;
    }
    
    body {
        background-color: white !important;
    }
    
    .container {
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .proyecto-container {
        box-shadow: none !important;
        margin: 0 auto !important;
    }
}
</style>
CSS;

// Incluir cabecera
require_once 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="mb-0"><?= htmlspecialchars($proyecto['nombre']) ?></h1>
        <?php if (!empty($proyecto['descripcion'])): ?>
        <p class="text-muted"><?= htmlspecialchars($proyecto['descripcion']) ?></p>
        <?php endif; ?>
    </div>
    <div class="col-md-6 text-md-end no-print">
        <button onclick="window.print()" class="btn btn-outline-dark me-2">
            <i class="fas fa-print me-1"></i>Imprimir
        </button>
        <a href="exportar_proyecto.php?id=<?= $id ?>" class="btn btn-outline-success me-2">
            <i class="fas fa-download me-1"></i>Exportar
        </a>
        <a href="editar_proyecto.php?id=<?= $id ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i>Editar proyecto
        </a>
    </div>
</div>

<div class="row justify-content-center mb-5">
    <div class="col-md-12 text-center">
        <div class="proyecto-container" 
             style="width: <?= $contenido['configuracion']['ancho'] ?>px; 
                    height: <?= $contenido['configuracion']['alto'] ?>px; 
                    background-color: <?= $contenido['configuracion']['fondo'] ?>;">
             
            <?php foreach ($contenido['elementos'] as $elemento): ?>
                <div class="proyecto-elemento" 
                     style="left: <?= $elemento['posicion']['x'] ?>px; 
                            top: <?= $elemento['posicion']['y'] ?>px; 
                            width: <?= $elemento['dimensiones']['ancho'] ?>px; 
                            height: <?= $elemento['dimensiones']['alto'] ?>px; 
                            z-index: <?= $elemento['zIndex'] ?? 1 ?>; 
                            <?= $elemento['estilo'] ?>">
                             
                    <?php if ($elemento['tipo'] === 'texto'): ?>
                        <?= $elemento['contenido'] ?>
                    <?php elseif ($elemento['tipo'] === 'imagen'): ?>
                        <div style="width: 100%; height: 100%; 
                                    background-image: url('<?= $elemento['url'] ?>'); 
                                    background-size: contain; 
                                    background-repeat: no-repeat; 
                                    background-position: center;"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
        </div>
    </div>
</div>

<div class="row mt-4 no-print">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Detalles del proyecto</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width: 30%">Nombre:</th>
                            <td><?= htmlspecialchars($proyecto['nombre']) ?></td>
                        </tr>
                        <?php if (!empty($proyecto['descripcion'])): ?>
                        <tr>
                            <th>Descripción:</th>
                            <td><?= htmlspecialchars($proyecto['descripcion']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($proyecto['categoria'])): ?>
                        <tr>
                            <th>Categoría:</th>
                            <td>
                                <span class="badge bg-success">
                                    <?= htmlspecialchars(PLANTILLA_CATEGORIES[$proyecto['categoria']] ?? $proyecto['categoria']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Fecha de creación:</th>
                            <td><?= date('d/m/Y H:i', strtotime($proyecto['fecha_creacion'])) ?></td>
                        </tr>
                        <tr>
                            <th>Última modificación:</th>
                            <td><?= date('d/m/Y H:i', strtotime($proyecto['fecha_modificacion'])) ?></td>
                        </tr>
                        <tr>
                            <th>Dimensiones:</th>
                            <td><?= $contenido['configuracion']['ancho'] ?> × <?= $contenido['configuracion']['alto'] ?> px</td>
                        </tr>
                        <tr>
                            <th>Número de elementos:</th>
                            <td><?= count($contenido['elementos']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Acciones</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="editar_proyecto.php?id=<?= $id ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-edit me-2 text-primary"></i>Editar este proyecto
                    </a>
                    <a href="exportar_proyecto.php?id=<?= $id ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-download me-2 text-success"></i>Exportar como imagen
                    </a>
                    <a href="#" onclick="window.print(); return false;" class="list-group-item list-group-item-action">
                        <i class="fas fa-print me-2 text-dark"></i>Imprimir proyecto
                    </a>
                    <a href="crear_proyecto.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2 text-success"></i>Crear nuevo proyecto
                    </a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#compartirModal" class="list-group-item list-group-item-action">
                        <i class="fas fa-share-alt me-2 text-info"></i>Compartir proyecto
                    </a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#eliminarModal" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-trash-alt me-2"></i>Eliminar proyecto
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Compartir -->
<div class="modal fade" id="compartirModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Compartir proyecto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Comparte el enlace a este proyecto:</p>
                
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="shareLink" value="<?= BASE_URL ?>ver_proyecto.php?id=<?= $id ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copiarAlPortapapeles(document.getElementById('shareLink').value)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-center">
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BASE_URL . 'ver_proyecto.php?id=' . $id) ?>&text=<?= urlencode('Mira mi proyecto ambiental: ' . $proyecto['nombre']) ?>" target="_blank" class="btn btn-outline-info mx-2">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                    
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL . 'ver_proyecto.php?id=' . $id) ?>" target="_blank" class="btn btn-outline-primary mx-2">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    
                    <a href="mailto:?subject=<?= urlencode('Proyecto ambiental: ' . $proyecto['nombre']) ?>&body=<?= urlencode('Mira mi proyecto ambiental: ' . BASE_URL . 'ver_proyecto.php?id=' . $id) ?>" class="btn btn-outline-secondary mx-2">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar proyecto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>¿Estás seguro de que deseas eliminar este proyecto?</p>
                <p>Esta acción no se puede deshacer. Se eliminará permanentemente el proyecto <strong><?= htmlspecialchars($proyecto['nombre']) ?></strong>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="eliminar_proyecto.php?id=<?= $id ?>" class="btn btn-danger">Eliminar definitivamente</a>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
require_once 'includes/footer.php';
?>