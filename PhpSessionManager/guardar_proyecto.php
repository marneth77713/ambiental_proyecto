<?php

// Incluir archivos de configuración y funciones
require_once 'config/config.php';
require_once 'includes/funciones.php';
require_once 'includes/mensajes.php';
require_once 'config/database.php';

// Conectar a la base de datos
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    mostrarMensaje('error', 'Error al conectar con la base de datos');
    header('Location: index.php');
    exit;
}

// Verificar si se recibieron datos del proyecto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proyecto_data'])) {
    
    try {
        // Decodificar JSON de los datos del proyecto
        $proyecto_data = json_decode($_POST['proyecto_data'], true);
        
        // Verificar si la decodificación fue exitosa
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al procesar los datos del proyecto: ' . json_last_error_msg());
        }
        
        // Validar datos mínimos requeridos
        if (empty($proyecto_data['nombre'])) {
            throw new Exception('El nombre del proyecto es obligatorio');
        }
        
        // Verificar que hay elementos en el proyecto
        if (!isset($proyecto_data['elementos']) || !is_array($proyecto_data['elementos']) || empty($proyecto_data['elementos'])) {
            throw new Exception('El proyecto debe contener al menos un elemento');
        }
        
        // Limpiar y validar datos de entrada
        $nombre = limpiarEntrada($proyecto_data['nombre']);
        $descripcion = isset($proyecto_data['descripcion']) ? limpiarEntrada($proyecto_data['descripcion']) : '';
        $color = isset($proyecto_data['color']) ? limpiarEntrada($proyecto_data['color']) : '#ffffff';
        
        // Validar la sintaxis del color (debe ser hex o color CSS válido)
        if (!preg_match('/^#[a-f0-9]{6}$/i', $color) && !preg_match('/^#[a-f0-9]{3}$/i', $color)) {
            $color = '#ffffff'; // Si no es válido, usar blanco por defecto
        }
        
        // Validar cada elemento del proyecto
        foreach ($proyecto_data['elementos'] as $key => $elemento) {
            if (!validarElemento($elemento)) {
                throw new Exception('Uno o más elementos del proyecto no son válidos');
            }
        }
        
        // Convertir los elementos a JSON para almacenar
        $elementos_json = json_encode($proyecto_data['elementos'], JSON_UNESCAPED_UNICODE);
        
        // Verificar si estamos actualizando un proyecto existente o creando uno nuevo
        if (isset($proyecto_data['id']) && !empty($proyecto_data['id'])) {
            // Actualizar proyecto existente
            $proyecto_id = intval($proyecto_data['id']);
            
            // Verificar si el proyecto existe
            $stmt = $conn->prepare("SELECT id FROM proyectos WHERE id = :id");
            $stmt->bindParam(':id', $proyecto_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('El proyecto que intenta actualizar no existe');
            }
            
            // Actualizar en la base de datos
            $stmt = $conn->prepare("UPDATE proyectos SET 
                nombre = :nombre, 
                descripcion = :descripcion, 
                color = :color, 
                elementos = :elementos,
                fecha_modificacion = NOW()
                WHERE id = :id");
                
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':elementos', $elementos_json);
            $stmt->bindParam(':id', $proyecto_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                mostrarMensaje('success', 'Proyecto actualizado correctamente');
                header('Location: editar_proyecto.php?id=' . $proyecto_id);
                exit;
            } else {
                throw new Exception('Error al actualizar el proyecto');
            }
            
        } else {
            // Crear nuevo proyecto
            $stmt = $conn->prepare("INSERT INTO proyectos (
                nombre, descripcion, color, elementos
            ) VALUES (
                :nombre, :descripcion, :color, :elementos
            )");
            
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':elementos', $elementos_json);
            
            if ($stmt->execute()) {
                $nuevo_id = $conn->lastInsertId();
                mostrarMensaje('success', 'Proyecto creado correctamente');
                header('Location: editar_proyecto.php?id=' . $nuevo_id);
                exit;
            } else {
                throw new Exception('Error al crear el proyecto');
            }
        }
        
    } catch (Exception $e) {
        // Registrar error
        error_log('Error en guardar_proyecto.php: ' . $e->getMessage());
        
        // Mostrar mensaje de error al usuario
        mostrarMensaje('error', 'Error: ' . $e->getMessage());
        
        // Redirigir, preservando los datos para evitar pérdida
        if (isset($proyecto_data['id']) && !empty($proyecto_data['id'])) {
            header('Location: editar_proyecto.php?id=' . intval($proyecto_data['id']));
        } else {
            header('Location: editar_proyecto.php');
        }
        exit;
    }
    
} else {
    // No se recibieron datos correctamente
    mostrarMensaje('error', 'No se recibieron datos del proyecto');
    header('Location: index.php');
    exit;
}
