<?php
$page_title = "Gestionar Plantillas";

// Ruta relativa para incluir archivos
$base_path = '../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'config/database.php';

// Conectar a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Verificar conexión
if (!$conn) {
    mostrarMensaje('error', 'Error al conectar con la base de datos');
    require_once $base_path . 'includes/footer.php';
    exit;
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Acción para agregar/editar plantilla
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_plantilla') {
        try {
            // Recoger datos del formulario
            $nombre = isset($_POST['nombre']) ? limpiarEntrada($_POST['nombre']) : '';
            $categoria = isset($_POST['categoria']) ? limpiarEntrada($_POST['categoria']) : 'general';
            $color = isset($_POST['color']) ? limpiarEntrada($_POST['color']) : '#ffffff';
            $elementos = isset($_POST['elementos']) ? $_POST['elementos'] : '[]';
            
            // Validar datos
            if (empty($nombre)) {
                throw new Exception('El nombre de la plantilla es obligatorio');
            }
            
            // Validar y ajustar la categoría
            if (!array_key_exists($categoria, PLANTILLA_CATEGORIES)) {
                $categoria = 'general';
            }
            
            // Decodificar elementos para validar
            $elementos_array = json_decode($elementos, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en el formato de los elementos');
            }
            
            // Validar cada elemento
            foreach ($elementos_array as $elemento) {
                if (!validarElemento($elemento)) {
                    throw new Exception('Uno o más elementos no son válidos');
                }
            }
            
            // Verificar si es edición o nueva plantilla
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Editar plantilla existente
                $id = intval($_POST['id']);
                
                $stmt = $conn->prepare("UPDATE plantillas SET 
                    nombre = :nombre, 
                    categoria = :categoria, 
                    color = :color, 
                    elementos = :elementos 
                    WHERE id = :id");
                    
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':categoria', $categoria);
                $stmt->bindParam(':color', $color);
                $stmt->bindParam(':elementos', $elementos);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    mostrarMensaje('success', 'Plantilla actualizada correctamente');
                } else {
                    throw new Exception('Error al actualizar la plantilla');
                }
                
            } else {
                // Nueva plantilla
                $stmt = $conn->prepare("INSERT INTO plantillas (
                    nombre, categoria, color, elementos
                ) VALUES (
                    :nombre, :categoria, :color, :elementos
                )");
                
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':categoria', $categoria);
                $stmt->bindParam(':color', $color);
                $stmt->bindParam(':elementos', $elementos);
                
                if ($stmt->execute()) {
                    mostrarMensaje('success', 'Plantilla creada correctamente');
                } else {
                    throw new Exception('Error al crear la plantilla');
                }
            }
            
        } catch (Exception $e) {
            mostrarMensaje('error', 'Error: ' . $e->getMessage());
        }
    }
    
    // Acción para cambiar estado (activar/desactivar)
    elseif (isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $estado = isset($_POST['estado']) ? (intval($_POST['estado']) ? 1 : 0) : 0;
            
            if ($id <= 0) {
                throw new Exception('ID de plantilla no válido');
            }
            
            $stmt = $conn->prepare("UPDATE plantillas SET activo = :estado WHERE id = :id");
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                mostrarMensaje('success', 'Estado de la plantilla actualizado');
            } else {
                throw new Exception('Error al actualizar el estado de la plantilla');
            }
            
        } catch (Exception $e) {
            mostrarMensaje('error', 'Error: ' . $e->getMessage());
        }
    }
    
    // Acción para eliminar plantilla
    elseif (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_plantilla') {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                throw new Exception('ID de plantilla no válido');
            }
            
            $stmt = $conn->prepare("DELETE FROM plantillas WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                mostrarMensaje('success', 'Plantilla eliminada correctamente');
            } else {
                throw new Exception('Error al eliminar la plantilla');
            }
            
        } catch (Exception $e) {
            mostrarMensaje('error', 'Error: ' . $e->getMessage());
        }
    }
    
    // Acción para guardar plantilla desde proyecto
    elseif (isset($_POST['accion']) && $_POST['accion'] === 'guardar_desde_proyecto') {
        try {
            $proyecto_id = isset($_POST['proyecto_id']) ? intval($_POST['proyecto_id']) : 0;
            $nombre = isset($_POST['nombre']) ? limpiarEntrada($_POST['nombre']) : '';
            $categoria = isset($_POST['categoria']) ? limpiarEntrada($_POST['categoria']) : 'general';
            
            if ($proyecto_id <= 0) {
                throw new Exception('ID de proyecto no válido');
            }
            
            if (empty($nombre)) {
                throw new Exception('El nombre de la plantilla es obligatorio');
            }
            
            // Obtener datos del proyecto
            $proyecto = obtenerProyecto($conn, $proyecto_id);
            
            if (!$proyecto) {
                throw new Exception('El proyecto no existe');
            }
            
            // Insertar nueva plantilla
            $stmt = $conn->prepare("INSERT INTO plantillas (
                nombre, categoria, color, elementos
            ) VALUES (
                :nombre, :categoria, :color, :elementos
            )");
            
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':color', $proyecto['color']);
            
            // Elementos ya está en formato JSON en la base de datos
            $elementos_json = $proyecto['elementos_json'] ?? json_encode($proyecto['elementos']);
            $stmt->bindParam(':elementos', $elementos_json);
            
            if ($stmt->execute()) {
                mostrarMensaje('success', 'Proyecto convertido a plantilla correctamente');
            } else {
                throw new Exception('Error al crear la plantilla desde el proyecto');
            }
            
        } catch (Exception $e) {
            mostrarMensaje('error', 'Error: ' . $e->getMessage());
        }
    }
}

// Obtener plantillas para mostrar en la tabla
$plantillas = [];
try {
    $stmt = $conn->query("SELECT * FROM plantillas ORDER BY nombre ASC");
    $plantillas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error al obtener plantillas: ' . $e->getMessage());
}

// Obtener categorías disponibles
$categorias = PLANTILLA_CATEGORIES;

// Añadir CSS y JS adicionales
$extra_css = '
<style>
    .miniatura-admin {
        height: 120px;
        overflow: hidden;
        margin-bottom: 10px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table th, .table td {
        vertical-align: middle;
    }
    
    .badge-estado {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 5px;
    }
    
    .badge-estado.activo {
        background-color: var(--success-color);
    }
    
    .badge-estado.inactivo {
        background-color: var(--gray-500);
    }
    
    .plantilla-filtro {
        margin-bottom: 1rem;
    }
</style>
';

$extra_js = '
<script src="' . $base_path . 'js/helpers.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Filtrar plantillas
        const filtroInput = document.getElementById("filtro-plantillas");
        const filtroCategoria = document.getElementById("filtro-categoria");
        const filtroEstado = document.getElementById("filtro-estado");
        
        if (filtroInput) {
            filtroInput.addEventListener("input", filtrarPlantillas);
        }
        
        if (filtroCategoria) {
            filtroCategoria.addEventListener("change", filtrarPlantillas);
        }
        
        if (filtroEstado) {
            filtroEstado.addEventListener("change", filtrarPlantillas);
        }
        
        function filtrarPlantillas() {
            const termino = filtroInput ? filtroInput.value.toLowerCase() : "";
            const categoria = filtroCategoria ? filtroCategoria.value : "todas";
            const estado = filtroEstado ? filtroEstado.value : "todos";
            
            const filas = document.querySelectorAll("table tbody tr");
            
            filas.forEach(fila => {
                const nombre = fila.querySelector("[data-nombre]").getAttribute("data-nombre").toLowerCase();
                const categoriaFila = fila.querySelector("[data-categoria]").getAttribute("data-categoria");
                const estadoFila = fila.querySelector("[data-estado]").getAttribute("data-estado");
                
                let mostrarFila = true;
                
                // Filtrar por término
                if (termino && !nombre.includes(termino)) {
                    mostrarFila = false;
                }
                
                // Filtrar por categoría
                if (categoria !== "todas" && categoriaFila !== categoria) {
                    mostrarFila = false;
                }
                
                // Filtrar por estado
                if (estado !== "todos" && estadoFila !== estado) {
                    mostrarFila = false;
                }
                
                fila.style.display = mostrarFila ? "" : "none";
            });
            
            // Mostrar mensaje si no hay resultados
            const sinResultados = document.getElementById("sin-resultados");
            const hayResultadosVisibles = Array.from(filas).some(fila => fila.style.display !== "none");
            
            if (sinResultados) {
                sinResultados.style.display = hayResultadosVisibles ? "none" : "";
            }
        }
        
        // Confirmar eliminación
        const formsEliminar = document.querySelectorAll(".form-eliminar");
        
        formsEliminar.forEach(form => {
            form.addEventListener("submit", function(e) {
                e.preventDefault();
                
                const nombrePlantilla = this.getAttribute("data-nombre");
                
                confirmar(`¿Está seguro de que desea eliminar la plantilla "${nombrePlantilla}"? Esta acción no se puede deshacer.`, "Confirmar eliminación")
                    .then(confirmado => {
                        if (confirmado) {
                            this.submit();
                        }
                    });
            });
        });
        
        // Previsualización de plantilla en modal
        const btnsPreview = document.querySelectorAll(".btn-preview");
        
        btnsPreview.forEach(btn => {
            btn.addEventListener("click", function() {
                const id = this.getAttribute("data-id");
                const nombre = this.getAttribute("data-nombre");
                const categoria = this.getAttribute("data-categoria-nombre");
                const miniatura = document.querySelector(`.miniatura-${id}`).innerHTML;
                
                document.getElementById("modal-plantilla-titulo").textContent = nombre;
                document.getElementById("modal-plantilla-categoria").textContent = categoria;
                document.getElementById("modal-plantilla-preview").innerHTML = miniatura;
                
                // Mostrar modal usando Bootstrap
                const modal = new bootstrap.Modal(document.getElementById("modalPreview"));
                modal.show();
            });
        });
    });
</script>
';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="fas fa-th-large text-success me-2"></i> Gestión de Plantillas</h1>
        <p class="text-muted">Administre las plantillas disponibles para los proyectos</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group">
            <a href="<?= $base_path ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al inicio
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevaPlantilla">
                <i class="fas fa-plus-circle me-1"></i> Nueva plantilla
            </button>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-4 plantilla-filtro">
                <label for="filtro-plantillas" class="form-label">Buscar:</label>
                <input type="text" id="filtro-plantillas" class="form-control" placeholder="Filtrar por nombre...">
            </div>
            <div class="col-md-4 plantilla-filtro">
                <label for="filtro-categoria" class="form-label">Categoría:</label>
                <select id="filtro-categoria" class="form-select">
                    <option value="todas">Todas las categorías</option>
                    <?php foreach ($categorias as $key => $name): ?>
                    <option value="<?= $key ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 plantilla-filtro">
                <label for="filtro-estado" class="form-label">Estado:</label>
                <select id="filtro-estado" class="form-select">
                    <option value="todos">Todos los estados</option>
                    <option value="1">Activas</option>
                    <option value="0">Inactivas</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px">#</th>
                        <th style="width: 150px">Vista previa</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th style="width: 180px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plantillas)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <p class="text-muted mb-0">No hay plantillas disponibles.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    
                    <?php foreach ($plantillas as $plantilla): ?>
                    <tr>
                        <td><?= $plantilla['id'] ?></td>
                        <td>
                            <div class="miniatura-admin miniatura-<?= $plantilla['id'] ?>">
                                <?= generarMiniatura(
                                    [
                                        'color' => $plantilla['color'],
                                        'elementos' => json_decode($plantilla['elementos'], true)
                                    ], 
                                    130, 
                                    120
                                ) ?>
                            </div>
                        </td>
                        <td data-nombre="<?= htmlspecialchars($plantilla['nombre']) ?>">
                            <strong><?= htmlspecialchars($plantilla['nombre']) ?></strong>
                        </td>
                        <td data-categoria="<?= htmlspecialchars($plantilla['categoria']) ?>">
                            <?php
                            $categoria_nombre = isset(PLANTILLA_CATEGORIES[$plantilla['categoria']]) 
                                ? PLANTILLA_CATEGORIES[$plantilla['categoria']] 
                                : ucfirst($plantilla['categoria']);
                            ?>
                            <span class="badge bg-light text-dark"><?= $categoria_nombre ?></span>
                        </td>
                        <td data-estado="<?= $plantilla['activo'] ? '1' : '0' ?>">
                            <?php if ($plantilla['activo']): ?>
                            <span><span class="badge-estado activo"></span> Activa</span>
                            <?php else: ?>
                            <span><span class="badge-estado inactivo"></span> Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-preview" 
                                    data-id="<?= $plantilla['id'] ?>" 
                                    data-nombre="<?= htmlspecialchars($plantilla['nombre']) ?>"
                                    data-categoria-nombre="<?= $categoria_nombre ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <a href="<?= $base_path ?>editar_proyecto.php?plantilla=<?= $plantilla['id'] ?>" class="btn btn-outline-success">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="id" value="<?= $plantilla['id'] ?>">
                                    <input type="hidden" name="estado" value="<?= $plantilla['activo'] ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-outline-secondary" title="<?= $plantilla['activo'] ? 'Desactivar' : 'Activar' ?>">
                                        <i class="fas fa-<?= $plantilla['activo'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                    </button>
                                </form>
                                
                                <form method="post" class="d-inline form-eliminar" data-nombre="<?= htmlspecialchars($plantilla['nombre']) ?>">
                                    <input type="hidden" name="accion" value="eliminar_plantilla">
                                    <input type="hidden" name="id" value="<?= $plantilla['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div id="sin-resultados" class="text-center py-4" style="display: none;">
                <p class="text-muted mb-0">No se encontraron plantillas con los filtros seleccionados.</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Información sobre plantillas</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Categorías disponibles</h6>
                <ul class="list-group">
                    <?php foreach ($categorias as $key => $name): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= $name ?>
                        <?php 
                        // Contar plantillas por categoría
                        $count = 0;
                        foreach ($plantillas as $p) {
                            if ($p['categoria'] === $key) $count++;
                        }
                        ?>
                        <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Estadísticas</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total de plantillas
                        <span class="badge bg-success rounded-pill"><?= count($plantillas) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Plantillas activas
                        <?php 
                        $activas = 0;
                        foreach ($plantillas as $p) {
                            if ($p['activo']) $activas++;
                        }
                        ?>
                        <span class="badge bg-primary rounded-pill"><?= $activas ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Plantillas inactivas
                        <span class="badge bg-secondary rounded-pill"><?= count($plantillas) - $activas ?></span>
                    </li>
                </ul>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb me-2"></i> <strong>Consejo:</strong> Puede crear plantillas a partir de proyectos existentes. Esto le permite reutilizar diseños ya creados.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de previsualización de plantilla -->
<div class="modal fade" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPreviewLabel">Vista previa de plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <h4 id="modal-plantilla-titulo"></h4>
                <p class="text-muted">Categoría: <span id="modal-plantilla-categoria"></span></p>
                
                <div class="text-center my-3" id="modal-plantilla-preview">
                    <!-- Aquí se insertará la vista previa de la plantilla -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nueva plantilla -->
<div class="modal fade" id="modalNuevaPlantilla" tabindex="-1" aria-labelledby="modalNuevaPlantillaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaPlantillaLabel">Crear nueva plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p>Para crear una nueva plantilla, puede:</p>
                    <ul>
                        <li>Crear una desde cero en el editor</li>
                        <li>Convertir un proyecto existente en plantilla</li>
                    </ul>
                </div>
                
                <div class="d-grid gap-3">
                    <a href="<?= $base_path ?>editar_proyecto.php" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i> Crear desde cero en el editor
                    </a>
                    
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="collapse" data-bs-target="#collapseProyectos">
                        <i class="fas fa-exchange-alt me-2"></i> Convertir proyecto existente
                    </button>
                </div>
                
                <div class="collapse mt-3" id="collapseProyectos">
                    <div class="card card-body">
                        <h6>Seleccione un proyecto</h6>
                        
                        <?php
                        // Obtener lista de proyectos
                        $proyectos = obtenerProyectos($conn);
                        ?>
                        
                        <?php if (empty($proyectos)): ?>
                        <p class="text-muted">No hay proyectos disponibles para convertir.</p>
                        <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="accion" value="guardar_desde_proyecto">
                            
                            <div class="mb-3">
                                <label for="proyecto_id" class="form-label">Proyecto:</label>
                                <select name="proyecto_id" id="proyecto_id" class="form-select" required>
                                    <option value="">Seleccionar proyecto...</option>
                                    <?php foreach ($proyectos as $proy): ?>
                                    <option value="<?= $proy['id'] ?>"><?= htmlspecialchars($proy['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la plantilla:</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría:</label>
                                <select name="categoria" id="categoria" class="form-select">
                                    <?php foreach ($categorias as $key => $name): ?>
                                    <option value="<?= $key ?>"><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Crear plantilla a partir del proyecto</button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
