<?php
/**
 * Configuración global de la aplicación
 */

// Ruta base de la aplicación
define('BASE_URL', '/');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Categorías de plantillas ambientales
define('PLANTILLA_CATEGORIES', [
    'todas' => 'Todas las plantillas',
    'conservacion' => 'Conservación',
    'energia' => 'Energías Renovables',
    'educacion' => 'Educación Ambiental',
    'ambiente' => 'Biodiversidad'
]);

/**
 * Muestra un mensaje al usuario
 * @param string $mensaje Mensaje a mostrar
 * @param string $tipo Tipo de mensaje: success, danger, warning, info
 */
function mostrarMensaje($mensaje, $tipo) {
    // Almacenar mensaje en sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['tipo_mensaje'] = $tipo;
    $_SESSION['mensaje'] = $mensaje;
}

/**
 * Muestra mensajes almacenados en sesión
 */
function mostrarMensajes() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        $tipo = $_SESSION['tipo_mensaje'];
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                mostrarNotificacion('{$mensaje}', '{$tipo}');
            });
        </script>";
        
        // Limpiar mensajes
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
    }
}

/**
 * Sanea entradas para prevenir XSS
 * @param string $dato Dato a sanear
 * @return string Dato saneado
 */
function sanitizar($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

/**
 * Verifica si una solicitud es de tipo POST
 * @return bool True si es POST, false si no
 */
function esPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Redirige a una URL
 * @param string $url URL a la que redirigir
 */
function redirigir($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

/**
 * Genera un ID único basado en timestamp
 * @return string ID único
 */
function generarId() {
    return uniqid('proyecto_', true);
}

/**
 * Obtiene fecha actual formateada
 * @return string Fecha actual formateada
 */
function obtenerFechaActual() {
    return date('Y-m-d H:i:s');
}

// Activar gestión de errores en desarrollo
ini_set('display_errors', 1);
error_reporting(E_ALL);