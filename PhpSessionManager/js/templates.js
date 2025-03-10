/**
 * Templates.js 
 * Gestiona la visualización y selección de plantillas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el módulo de plantillas
    const Templates = (function() {
        // Variables privadas
        let plantillasContainer;
        let busquedaInput;
        let categoriasLinks;
        let plantillasItems;
        let categoriaActual = 'todas';
        
        /**
         * Filtra plantillas por término de búsqueda
         * @param {string} termino Término de búsqueda
         */
        function filtrarPlantillas(termino) {
            termino = termino.toLowerCase().trim();
            
            plantillasItems.forEach(item => {
                const nombre = item.querySelector('.template-name').textContent.toLowerCase();
                const descripcion = item.querySelector('.template-description').textContent.toLowerCase();
                const categoria = item.dataset.categoria;
                
                const coincideTermino = termino === '' || 
                    nombre.includes(termino) || 
                    descripcion.includes(termino);
                    
                const coincideCategoria = categoriaActual === 'todas' || 
                    categoria === categoriaActual;
                
                if (coincideTermino && coincideCategoria) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            const hayResultados = Array.from(plantillasItems).some(item => item.style.display !== 'none');
            
            let mensajeNoResultados = document.getElementById('no-results');
            if (!hayResultados) {
                if (!mensajeNoResultados) {
                    mensajeNoResultados = document.createElement('div');
                    mensajeNoResultados.id = 'no-results';
                    mensajeNoResultados.className = 'col-12 text-center py-5';
                    mensajeNoResultados.innerHTML = `
                        <div class="text-muted">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <h4>No se encontraron plantillas</h4>
                            <p>Intenta con otros términos de búsqueda o selecciona otra categoría.</p>
                        </div>
                    `;
                    plantillasContainer.appendChild(mensajeNoResultados);
                }
            } else if (mensajeNoResultados) {
                mensajeNoResultados.remove();
            }
        }
        
        /**
         * Inicializa la previsualización de plantillas
         */
        function inicializarPreview() {
            plantillasItems.forEach(item => {
                item.addEventListener('click', function() {
                    const plantillaId = this.dataset.id;
                    window.location.href = `crear_proyecto.php?plantilla_id=${plantillaId}`;
                });
                
                // Efecto hover
                item.addEventListener('mouseenter', function() {
                    this.classList.add('animate__animated', 'animate__pulse');
                });
                
                item.addEventListener('mouseleave', function() {
                    this.classList.remove('animate__animated', 'animate__pulse');
                });
            });
        }
        
        /**
         * Inicializa las miniaturas y selección de categorías
         */
        function inicializarMiniaturasCategorias() {
            if (categoriasLinks) {
                categoriasLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Eliminar clase activa de todos los links
                        categoriasLinks.forEach(l => l.classList.remove('active'));
                        
                        // Añadir clase activa al link actual
                        this.classList.add('active');
                        
                        // Actualizar categoría actual
                        categoriaActual = this.dataset.categoria;
                        
                        // Filtrar plantillas
                        filtrarPlantillas(busquedaInput.value);
                    });
                });
            }
        }
        
        /**
         * Anima las plantillas al cargar la página
         */
        function animarPlantillas() {
            plantillasItems.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('animate__animated', 'animate__fadeInUp');
                    
                    // Eliminar clases después de la animación
                    setTimeout(() => {
                        item.classList.remove('animate__animated', 'animate__fadeInUp');
                    }, 1000);
                }, index * 100);
            });
        }
        
        /**
         * Inicializa el módulo
         */
        function inicializar() {
            // Obtener referencias a elementos del DOM
            plantillasContainer = document.querySelector('.templates-container');
            busquedaInput = document.getElementById('busqueda-plantilla');
            categoriasLinks = document.querySelectorAll('.categoria-link');
            plantillasItems = document.querySelectorAll('.template-card');
            
            if (plantillasContainer && plantillasItems.length > 0) {
                // Inicializar búsqueda
                if (busquedaInput) {
                    busquedaInput.addEventListener('input', function() {
                        filtrarPlantillas(this.value);
                    });
                }
                
                // Inicializar categorías
                inicializarMiniaturasCategorias();
                
                // Inicializar previsualización
                inicializarPreview();
                
                // Animar plantillas
                animarPlantillas();
                
                // Establecer categoría desde URL si existe
                const urlParams = new URLSearchParams(window.location.search);
                const categoriaParam = urlParams.get('categoria');
                
                if (categoriaParam) {
                    const categoriaLink = document.querySelector(`.categoria-link[data-categoria="${categoriaParam}"]`);
                    if (categoriaLink) {
                        categoriaLink.click();
                    }
                }
            }
        }
        
        // API pública
        return {
            init: inicializar
        };
    })();
    
    // Inicializar módulo de plantillas
    Templates.init();
});