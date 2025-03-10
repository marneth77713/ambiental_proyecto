<?php
/**
 * Archivo con funciones auxiliares para todo el sistema
 */

/**
 * Obtiene proyectos de la base de datos
 * @param PDO $conn Conexión a la base de datos
 * @param int|null $limite Límite de proyectos a obtener, null para todos
 * @param array $opciones Opciones adicionales de filtrado
 * @return array Array de proyectos
 */
function obtenerProyectos($conn, $limite = null, $opciones = []) {
    $proyectos = [];
    
    try {
        $sql = "SELECT * FROM proyectos ORDER BY fecha_modificacion DESC";
        
        if ($limite !== null) {
            $sql .= " LIMIT " . intval($limite);
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $proyectos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener proyectos: " . $e->getMessage());
    }
    
    return $proyectos;
}

/**
 * Obtiene un proyecto específico por ID
 * @param PDO $conn Conexión a la base de datos
 * @param int $id ID del proyecto
 * @return array|null Datos del proyecto o null si no existe
 */
function obtenerProyectoPorId($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM proyectos WHERE id = ?");
        $stmt->execute([$id]);
        $proyecto = $stmt->fetch();
        return $proyecto ? $proyecto : null;
    } catch (PDOException $e) {
        error_log("Error al obtener proyecto: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene plantillas de la base de datos
 * @param PDO $conn Conexión a la base de datos
 * @param string|null $categoria Categoría de plantillas a filtrar, null para todas
 * @return array Array de plantillas
 */
function obtenerPlantillas($conn, $categoria = null) {
    $plantillas = [];
    
    try {
        $sql = "SELECT * FROM plantillas WHERE activo = 1";
        
        if ($categoria !== null && $categoria !== 'todas') {
            $sql .= " AND categoria = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$categoria]);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }
        
        $plantillas = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener plantillas: " . $e->getMessage());
    }
    
    return $plantillas;
}

/**
 * Obtiene una plantilla específica por ID
 * @param PDO $conn Conexión a la base de datos
 * @param int $id ID de la plantilla
 * @return array|null Datos de la plantilla o null si no existe
 */
function obtenerPlantillaPorId($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM plantillas WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $plantilla = $stmt->fetch();
        return $plantilla ? $plantilla : null;
    } catch (PDOException $e) {
        error_log("Error al obtener plantilla: " . $e->getMessage());
        return null;
    }
}

/**
 * Muestra un mensaje en la siguiente página después de redireccionar
 * @param string $mensaje Mensaje a mostrar
 * @param string $tipo Tipo de mensaje (success, danger, warning, info)
 */
function mostrarMensaje($tipo, $mensaje) {
    $_SESSION['tipo_mensaje'] = $tipo;
    $_SESSION['mensaje'] = $mensaje;
}

/**
 * Recupera un mensaje almacenado en la sesión y lo elimina
 * @return array|null Array con tipo y mensaje, o null si no hay mensaje
 */
function obtenerMensaje() {
    if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
        $mensaje = [
            'tipo' => $_SESSION['tipo_mensaje'],
            'texto' => $_SESSION['mensaje']
        ];
        
        // Eliminar mensaje de la sesión
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
        
        return $mensaje;
    }
    
    return null;
}