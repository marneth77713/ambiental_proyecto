<?php

// Incluir archivos de configuración y funciones
require_once 'config/config.php';
require_once 'includes/funciones.php';
require_once 'includes/mensajes.php';
require_once 'config/database.php';

// Verificar que se proporcionó un ID de proyecto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    mostrarMensaje('error', 'No se especificó un proyecto para eliminar');
    header('Location: index.php');
    exit;
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

try {
    // Verificar si el proyecto existe
    $stmt = $conn->prepare("SELECT id, nombre FROM proyectos WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('El proyecto que intenta eliminar no existe');
    }
    
    $proyecto = $stmt->fetch();
    $nombre_proyecto = $proyecto['nombre'];
    
    // Eliminar el proyecto
    $stmt = $conn->prepare("DELETE FROM proyectos WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        mostrarMensaje('success', 'Proyecto "' . htmlspecialchars($nombre_proyecto) . '" eliminado correctamente');
    } else {
        throw new Exception('Error al eliminar el proyecto');
    }
    
} catch (Exception $e) {
    // Registrar error
    error_log('Error en eliminar_proyecto.php: ' . $e->getMessage());
    
    // Mostrar mensaje de error al usuario
    mostrarMensaje('error', 'Error: ' . $e->getMessage());
}

// Redirigir a la página principal
header('Location: index.php');
exit;
