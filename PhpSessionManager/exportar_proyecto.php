<?php

// Incluir archivos de configuración y funciones
require_once 'config/config.php';
require_once 'includes/funciones.php';
require_once 'config/database.php';

// Verificar que se proporcionó un ID de proyecto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    mostrarMensaje('error', 'No se especificó un proyecto para exportar');
    header('Location: index.php');
    exit;
}

// Verificar el formato de exportación
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'imagen';
if (!in_array($formato, ['imagen', 'pdf', 'html'])) {
    $formato = 'imagen'; // Por defecto exportar como imagen
}

// Conectar a la base de datos
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    mostrarMensaje('error', 'Error al conectar con la base de datos');
    header('Location: index.php');
    exit;
}

// Obtener el ID del proyecto
$id = intval($_GET['id']);
$proyecto = obtenerProyecto($conn, $id);

if (!$proyecto) {
    mostrarMensaje('error', 'El proyecto solicitado no existe o ha sido eliminado');
    header('Location: index.php');
    exit;
}

// Configurar respuesta según el formato
switch ($formato) {
    case 'imagen':
        // Para exportar a imagen, devolvemos una página HTML que usa html2canvas
        $page_title = "Exportar " . $proyecto['nombre'];
        require_once 'includes/header.php';
        ?>
        
        <div class="text-center my-4">
            <h1 class="h2">Exportar proyecto como imagen</h1>
            <p class="text-muted">El proyecto se generará como una imagen que podrá descargar</p>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> 
                            Cuando la imagen esté lista, aparecerá debajo. Puede hacer clic derecho sobre ella y seleccionar "Guardar imagen como..." para descargarla.
                        </div>
                        
                        <div class="d-flex justify-content-center mb-4">
                            <div class="spinner-border text-primary" role="status" id="cargando-spinner">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                        
                        <div id="imagen-generada" class="text-center" style="display:none;">
                            <img id="imagen-proyecto" class="img-fluid border" alt="Proyecto exportado">
                            <div class="mt-3">
                                <a id="descargar-imagen" class="btn btn-success" download="<?= htmlspecialchars($proyecto['nombre']) ?>.png">
                                    <i class="fas fa-download me-2"></i> Descargar imagen
                                </a>
                                <a href="ver_proyecto.php?id=<?= $id ?>" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-arrow-left me-2"></i> Volver al proyecto
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenedor oculto con el proyecto para generar la imagen -->
        <div style="position:absolute; left:-9999px; top:-9999px;">
            <div id="proyecto-canvas" style="width: <?= EDITOR_OPTIONS['canvasWidth'] ?>px; height: <?= EDITOR_OPTIONS['canvasHeight'] ?>px; background-color: <?= htmlspecialchars($proyecto['color']) ?>; position: relative;">
                
                <?php foreach ($proyecto['elementos'] as $elemento): ?>
                    <div style="
                        position: absolute;
                        left: <?= intval($elemento['posicion']['x']) ?>px;
                        top: <?= intval($elemento['posicion']['y']) ?>px;
                        z-index: <?= intval($elemento['zIndex']) ?>;
                        <?= $elemento['estilo'] ?>
                    ">
                        <?php if ($elemento['tipo'] === 'texto'): ?>
                            <?= nl2br(htmlspecialchars($elemento['contenido'])) ?>
                        <?php elseif ($elemento['tipo'] === 'imagen' && validarURLImagen($elemento['contenido'])): ?>
                            <img src="<?= htmlspecialchars($elemento['contenido']) ?>" alt="Imagen del proyecto" style="max-width:100%;max-height:100%;">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </div>
        
        <!-- Incluir html2canvas para la conversión a imagen -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Esperar a que se carguen todas las imágenes
                const imagenes = document.querySelectorAll('#proyecto-canvas img');
                let imagenesListas = 0;
                const totalImagenes = imagenes.length;
                
                // Si no hay imágenes, generar directamente
                if (totalImagenes === 0) {
                    generarImagen();
                    return;
                }
                
                // Esperar a que todas las imágenes estén cargadas
                imagenes.forEach(img => {
                    if (img.complete) {
                        imagenesListas++;
                        if (imagenesListas === totalImagenes) {
                            generarImagen();
                        }
                    } else {
                        img.addEventListener('load', () => {
                            imagenesListas++;
                            if (imagenesListas === totalImagenes) {
                                generarImagen();
                            }
                        });
                        
                        // Si hay error al cargar la imagen
                        img.addEventListener('error', () => {
                            imagenesListas++;
                            img.style.display = 'none'; // Ocultar imagen con error
                            
                            if (imagenesListas === totalImagenes) {
                                generarImagen();
                            }
                        });
                    }
                });
                
                function generarImagen() {
                    const elemento = document.getElementById('proyecto-canvas');
                    
                    // Configuración para mejor calidad
                    const opciones = {
                        scale: 2, // Escala 2x para mejor calidad
                        useCORS: true, // Permitir imágenes de otros dominios
                        allowTaint: true,
                        backgroundColor: null,
                        logging: false
                    };
                    
                    html2canvas(elemento, opciones).then(canvas => {
                        // Ocultar spinner de carga
                        document.getElementById('cargando-spinner').style.display = 'none';
                        
                        // Mostrar la imagen generada
                        const imgGenerada = document.getElementById('imagen-generada');
                        imgGenerada.style.display = 'block';
                        
                        // Convertir canvas a imagen
                        const imgDataUrl = canvas.toDataURL('image/png');
                        const imgElement = document.getElementById('imagen-proyecto');
                        imgElement.src = imgDataUrl;
                        
                        // Configurar enlace de descarga
                        const descargarBtn = document.getElementById('descargar-imagen');
                        descargarBtn.href = imgDataUrl;
                    }).catch(error => {
                        console.error('Error al generar la imagen:', error);
                        document.getElementById('cargando-spinner').style.display = 'none';
                        alert('Ha ocurrido un error al generar la imagen. Por favor, inténtelo de nuevo.');
                    });
                }
            });
        </script>
        
        <?php
        require_once 'includes/footer.php';
        break;
        
    case 'pdf':
        // Implementación futura: exportar a PDF
        mostrarMensaje('info', 'La exportación a PDF estará disponible próximamente');
        header('Location: ver_proyecto.php?id=' . $id);
        exit;
        
    case 'html':
        // Implementación futura: exportar a HTML
        mostrarMensaje('info', 'La exportación a HTML estará disponible próximamente');
        header('Location: ver_proyecto.php?id=' . $id);
        exit;
        
    default:
        // Formato no reconocido
        mostrarMensaje('error', 'Formato de exportación no válido');
        header('Location: ver_proyecto.php?id=' . $id);
        exit;
}
