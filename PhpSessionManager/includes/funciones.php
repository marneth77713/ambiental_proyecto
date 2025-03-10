<?php
/**
 * Funciones generales de la aplicación
 */

/**
 * Limpia y valida una cadena de entrada
 * @param string $data Datos a limpiar
 * @return string Datos limpios
 */
function limpiarEntrada($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Muestra un mensaje en la sesión para ser recuperado más tarde
 * @param string $tipo Tipo de mensaje (success, error, warning, info)
 * @param string $mensaje Contenido del mensaje
 */
function mostrarMensaje($tipo, $mensaje) {
    $_SESSION[$tipo] = $mensaje;
}

/**
 * Valida una URL de imagen para asegurar que proviene de un host permitido
 * @param string $url URL de la imagen a validar
 * @return bool True si la URL es válida, false en caso contrario
 */
function validarURLImagen($url) {
    if (empty($url)) {
        return false;
    }
    
    // Verificar que es una URL válida
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Obtener el host de la URL
    $host = parse_url($url, PHP_URL_HOST);
    
    // Verificar que el host está en la lista de permitidos
    foreach (ALLOWED_IMAGE_HOSTS as $allowed_host) {
        if (strpos($host, $allowed_host) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Validar la estructura de un elemento
 * @param array $elemento Estructura del elemento a validar
 * @return bool True si el elemento es válido, false en caso contrario
 */
function validarElemento($elemento) {
    // Verificar que tiene los campos necesarios
    if (!isset($elemento['tipo']) || !isset($elemento['posicion']) ||
        !isset($elemento['zIndex']) || !isset($elemento['estilo'])) {
        return false;
    }
    
    // Verificar que el tipo es válido
    if (!array_key_exists($elemento['tipo'], ELEMENT_TYPES)) {
        return false;
    }
    
    // Verificar la posición
    if (!isset($elemento['posicion']['x']) || !isset($elemento['posicion']['y']) || 
        !is_numeric($elemento['posicion']['x']) || !is_numeric($elemento['posicion']['y'])) {
        return false;
    }
    
    // Verificar z-index
    if (!is_numeric($elemento['zIndex'])) {
        return false;
    }
    
    // Validación específica para cada tipo
    switch ($elemento['tipo']) {
        case 'texto':
            if (!isset($elemento['contenido']) || strlen($elemento['contenido']) > MAX_TEXT_LENGTH) {
                return false;
            }
            break;
            
        case 'imagen':
            if (!isset($elemento['contenido']) || !validarURLImagen($elemento['contenido'])) {
                return false;
            }
            break;
            
        case 'forma':
            // Las formas no necesitan contenido
            break;
            
        default:
            return false;
    }
    
    return true;
}

/**
 * Obtiene todas las plantillas desde la base de datos
 * @param PDO $conn Conexión a la base de datos
 * @param string|null $categoria Filtrar por categoría (opcional)
 * @return array Lista de plantillas
 */
function obtenerPlantillas($conn, $categoria = null) {
    try {
        $sql = "SELECT * FROM plantillas WHERE activo = 1";
        
        if ($categoria && $categoria !== 'todas') {
            $sql .= " AND categoria = :categoria";
        }
        
        $sql .= " ORDER BY nombre ASC";
        $stmt = $conn->prepare($sql);
        
        if ($categoria && $categoria !== 'todas') {
            $stmt->bindParam(':categoria', $categoria);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener plantillas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene un proyecto específico por su ID
 * @param PDO $conn Conexión a la base de datos
 * @param int $id ID del proyecto
 * @return array|null Datos del proyecto o null si no existe
 */
function obtenerProyecto($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM proyectos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $proyecto = $stmt->fetch();
            
            // Decodificar los elementos JSON
            $proyecto['elementos'] = json_decode($proyecto['elementos'], true);
            
            return $proyecto;
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Error al obtener proyecto: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene todos los proyectos
 * @param PDO $conn Conexión a la base de datos
 * @return array Lista de proyectos
 */
function obtenerProyectos($conn) {
    try {
        $stmt = $conn->query("SELECT id, nombre, descripcion, fecha_modificacion FROM proyectos ORDER BY fecha_modificacion DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener proyectos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene una plantilla por su ID
 * @param PDO $conn Conexión a la base de datos
 * @param int $id ID de la plantilla
 * @return array|null Datos de la plantilla o null si no existe
 */
function obtenerPlantilla($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM plantillas WHERE id = :id AND activo = 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $plantilla = $stmt->fetch();
            
            // Decodificar los elementos JSON
            $plantilla['elementos'] = json_decode($plantilla['elementos'], true);
            
            return $plantilla;
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Error al obtener plantilla: " . $e->getMessage());
        return null;
    }
}

/**
 * Genera una miniatura HTML de una plantilla o proyecto para previsualización
 * @param array $datos Datos de la plantilla o proyecto
 * @param int $ancho Ancho de la miniatura
 * @param int $alto Alto de la miniatura
 * @return string HTML de la miniatura
 */
function generarMiniatura($datos, $ancho = 300, $alto = 200) {
    $escala = $ancho / EDITOR_OPTIONS['canvasWidth'];
    $html = '<div class="miniatura" style="width:' . $ancho . 'px;height:' . $alto . 'px;background-color:' . $datos['color'] . ';position:relative;overflow:hidden;border:1px solid #ddd;border-radius:4px;">';
    
    // Solo procesar hasta 10 elementos para la miniatura (por rendimiento)
    $elementos = array_slice($datos['elementos'], 0, 10);
    
    foreach ($elementos as $elemento) {
        // Ajustar posición y tamaño según escala
        $posX = $elemento['posicion']['x'] * $escala;
        $posY = $elemento['posicion']['y'] * $escala;
        
        // Adaptar estilos para la miniatura
        $estilo = $elemento['estilo'];
        
        // Buscar dimensiones en el estilo y ajustarlas
        preg_match('/width:\s*(\d+)px/', $estilo, $widthMatches);
        if (!empty($widthMatches)) {
            $originalWidth = $widthMatches[1];
            $newWidth = $originalWidth * $escala;
            $estilo = preg_replace('/width:\s*\d+px/', 'width:' . $newWidth . 'px', $estilo);
        }
        
        preg_match('/height:\s*(\d+)px/', $estilo, $heightMatches);
        if (!empty($heightMatches)) {
            $originalHeight = $heightMatches[1];
            $newHeight = $originalHeight * $escala;
            $estilo = preg_replace('/height:\s*\d+px/', 'height:' . $newHeight . 'px', $estilo);
        }
        
        // Reducir tamaño de fuente
        preg_match('/font-size:\s*(\d+)px/', $estilo, $fontMatches);
        if (!empty($fontMatches)) {
            $originalFont = $fontMatches[1];
            $newFont = max(8, $originalFont * $escala); // Mínimo 8px para que sea legible
            $estilo = preg_replace('/font-size:\s*\d+px/', 'font-size:' . $newFont . 'px', $estilo);
        }
        
        $html .= '<div style="position:absolute;left:' . $posX . 'px;top:' . $posY . 'px;z-index:' . $elemento['zIndex'] . ';' . $estilo . '">';
        
        switch ($elemento['tipo']) {
            case 'texto':
                // Limitar texto a 50 caracteres para la miniatura
                $texto = isset($elemento['contenido']) ? substr($elemento['contenido'], 0, 50) : '';
                if (strlen($elemento['contenido']) > 50) {
                    $texto .= '...';
                }
                $html .= htmlspecialchars($texto);
                break;
                
            case 'imagen':
                if (isset($elemento['contenido']) && validarURLImagen($elemento['contenido'])) {
                    $html .= '<img src="' . htmlspecialchars($elemento['contenido']) . '" style="max-width:100%;max-height:100%;" alt="Imagen">';
                }
                break;
                
            case 'forma':
                // La forma se representa solo con el estilo CSS
                break;
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}
