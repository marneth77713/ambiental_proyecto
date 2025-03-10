<?php
/**
 * Sistema de mensajes para el usuario
 * Maneja la visualización de mensajes de éxito, error, advertencia e información
 */

/**
 * Muestra mensajes almacenados en la sesión
 * Elimina los mensajes después de mostrarlos
 */
function mostrarMensajesGuardados() {
    $tipos = ['success', 'error', 'warning', 'info'];
    $iconos = [
        'success' => 'check-circle',
        'error' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle'
    ];
    
    $hayMensajes = false;
    
    echo '<div id="sistema-mensajes">';
    
    foreach ($tipos as $tipo) {
        if (isset($_SESSION[$tipo])) {
            $hayMensajes = true;
            echo '<div class="mensaje mensaje-' . $tipo . ' animate__animated animate__fadeIn">';
            echo '<i class="fas fa-' . $iconos[$tipo] . ' mensaje-icono"></i>';
            echo '<div class="mensaje-contenido">' . $_SESSION[$tipo] . '</div>';
            echo '<button type="button" class="mensaje-cerrar" onclick="this.parentNode.remove();">&times;</button>';
            echo '</div>';
            unset($_SESSION[$tipo]);
        }
    }
    
    echo '</div>';
    
    if ($hayMensajes) {
        echo '
        <script>
            // Auto-ocultar mensajes después de 5 segundos
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    var mensajes = document.querySelectorAll(".mensaje");
                    mensajes.forEach(function(mensaje) {
                        mensaje.classList.add("animate__fadeOut");
                        setTimeout(function() {
                            if (mensaje.parentNode) {
                                mensaje.parentNode.removeChild(mensaje);
                            }
                        }, 300);
                    });
                }, 5000);
            });
        </script>';
    }
}
