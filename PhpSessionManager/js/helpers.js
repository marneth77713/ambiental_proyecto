/**
 * Helpers.js
 * Funciones auxiliares reutilizables en todo el sitio
 */

/**
 * Crea una notificación toast
 * @param {string} mensaje Mensaje a mostrar
 * @param {string} tipo Tipo de notificación: success, error, warning, info
 * @param {number} duracion Duración en milisegundos
 */
function mostrarNotificacion(mensaje, tipo = 'info', duracion = 3000) {
    // Mapear tipo a clase Bootstrap
    const tipoBootstrap = {
        'success': 'success',
        'error': 'danger',
        'warning': 'warning',
        'info': 'info'
    }[tipo] || 'info';
    
    // Crear elemento toast
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${tipoBootstrap} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    // Crear contenido del toast
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    `;
    
    // Añadir al contenedor de toasts
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toastEl);
    
    // Inicializar y mostrar el toast
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: duracion
    });
    
    toast.show();
    
    // Eliminar el toast del DOM cuando se oculte
    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

/**
 * Valida formularios y muestra errores
 * @param {HTMLFormElement} formulario Formulario a validar
 * @returns {boolean} True si el formulario es válido
 */
function validarFormulario(formulario) {
    let esValido = true;
    
    // Buscar campos requeridos
    const camposRequeridos = formulario.querySelectorAll('[required]');
    
    camposRequeridos.forEach(campo => {
        // Limpiar mensajes de error previos
        const contenedorCampo = campo.closest('.form-group, .mb-3');
        if (contenedorCampo) {
            const mensajeError = contenedorCampo.querySelector('.invalid-feedback');
            if (mensajeError) {
                mensajeError.remove();
            }
            campo.classList.remove('is-invalid');
        }
        
        // Validar campo vacío
        if (!campo.value.trim()) {
            mostrarErrorCampo(campo, 'Este campo es requerido');
            esValido = false;
            return;
        }
        
        // Validar emails
        if (campo.type === 'email' && !validarEmail(campo.value)) {
            mostrarErrorCampo(campo, 'Ingrese un email válido');
            esValido = false;
            return;
        }
        
        // Validar URLs
        if (campo.type === 'url' && !validarURL(campo.value)) {
            mostrarErrorCampo(campo, 'Ingrese una URL válida');
            esValido = false;
            return;
        }
    });
    
    return esValido;
}

/**
 * Muestra un mensaje de error para un campo específico
 * @param {HTMLElement} campo Campo con error
 * @param {string} mensaje Mensaje de error
 */
function mostrarErrorCampo(campo, mensaje) {
    // Marcar campo como inválido
    campo.classList.add('is-invalid');
    
    // Crear mensaje de error
    const mensajeError = document.createElement('div');
    mensajeError.className = 'invalid-feedback';
    mensajeError.textContent = mensaje;
    
    // Añadir después del campo
    const contenedorCampo = campo.closest('.form-group, .mb-3');
    if (contenedorCampo) {
        contenedorCampo.appendChild(mensajeError);
    } else {
        campo.parentNode.insertBefore(mensajeError, campo.nextSibling);
    }
}

/**
 * Valida una dirección de email
 * @param {string} email Email a validar
 * @returns {boolean} True si el email es válido
 */
function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

/**
 * Valida una URL
 * @param {string} url URL a validar
 * @returns {boolean} True si la URL es válida
 */
function validarURL(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * Confirmación personalizada
 * @param {string} mensaje Mensaje a mostrar
 * @param {string} titulo Título del diálogo
 * @returns {Promise} Promise que se resuelve con true (confirmar) o false (cancelar)
 */
function confirmar(mensaje, titulo = 'Confirmar') {
    return new Promise((resolve) => {
        // Crear modal de confirmación
        const modalId = 'modalConfirm' + Date.now();
        const modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.id = modalId;
        modalEl.setAttribute('tabindex', '-1');
        modalEl.setAttribute('aria-hidden', 'true');
        
        modalEl.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${titulo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p>${mensaje}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-confirm">Confirmar</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modalEl);
        
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        
        // Manejar botones
        const btnConfirm = modalEl.querySelector('.btn-confirm');
        btnConfirm.addEventListener('click', () => {
            modal.hide();
            resolve(true);
        });
        
        modalEl.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modalEl);
            resolve(false);
        });
    });
}

/**
 * Formatea una fecha en formato legible
 * @param {string} fechaStr Fecha en formato ISO
 * @returns {string} Fecha formateada
 */
function formatearFecha(fechaStr) {
    const fecha = new Date(fechaStr);
    return fecha.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Copia texto al portapapeles
 * @param {string} texto Texto a copiar
 * @returns {Promise} Promise que se resuelve cuando se completa la copia
 */
function copiarAlPortapapeles(texto) {
    return navigator.clipboard.writeText(texto)
        .then(() => {
            mostrarNotificacion('Texto copiado al portapapeles', 'success');
            return true;
        })
        .catch(err => {
            console.error('Error al copiar: ', err);
            mostrarNotificacion('No se pudo copiar el texto', 'error');
            return false;
        });
}

/**
 * Trunca un texto a cierta longitud
 * @param {string} texto Texto original
 * @param {number} longitud Longitud máxima
 * @returns {string} Texto truncado
 */
function truncarTexto(texto, longitud = 100) {
    if (texto.length <= longitud) return texto;
    return texto.substring(0, longitud) + '...';
}