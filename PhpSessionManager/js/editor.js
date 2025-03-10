/**
 * Editor.js 
 * Maneja la funcionalidad principal del editor de proyectos
 */

document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let canvas;
    let elementos = [];
    let elementoSeleccionado = null;
    let indiceSeleccionado = -1;
    let fondoColor = '#f8f9fa';
    let zoomLevel = 1;
    let isDragging = false;
    let startX, startY;
    
    // Inicialización
    function inicializar() {
        canvas = document.getElementById('editor-canvas');
        
        if (!canvas) return;
        
        inicializarDropZones();
        inicializarBotonesAgregar();
        inicializarColorFondo();
        inicializarZoom();
        inicializarCanvasEventos();
        inicializarGuardar();
        
        // Cargar elementos si hay datos existentes
        const contenidoInput = document.getElementById('contenido-json');
        if (contenidoInput && contenidoInput.value) {
            try {
                const data = JSON.parse(contenidoInput.value);
                
                if (data.elementos) {
                    elementos = data.elementos;
                }
                
                if (data.configuracion) {
                    if (data.configuracion.fondo) {
                        fondoColor = data.configuracion.fondo;
                        document.getElementById('color-fondo').value = fondoColor;
                        canvas.style.backgroundColor = fondoColor;
                    }
                    
                    if (data.configuracion.ancho && data.configuracion.alto) {
                        canvas.style.width = data.configuracion.ancho + 'px';
                        canvas.style.height = data.configuracion.alto + 'px';
                        
                        document.getElementById('canvas-width').value = data.configuracion.ancho;
                        document.getElementById('canvas-height').value = data.configuracion.alto;
                    }
                }
                
                renderizarElementos();
            } catch (e) {
                console.error('Error al cargar datos existentes:', e);
            }
        }
    }
    
    function inicializarDropZones() {
        const dropZone = document.getElementById('drop-image');
        
        if (!dropZone) return;
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropZone.classList.add('bg-light');
        }
        
        function unhighlight() {
            dropZone.classList.remove('bg-light');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                handleFiles(files);
            }
        }
        
        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                uploadFile(files[i]);
            }
        }
        
        function uploadFile(file) {
            if (!file.type.match('image.*')) {
                mostrarNotificacion('Solo se permiten imágenes', 'error');
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    agregarElementoImagen(e.target.result);
                };
                img.src = e.target.result;
            };
            
            reader.readAsDataURL(file);
        }
        
        // Manejar click en la zona de drop
        dropZone.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            
            input.onchange = function(e) {
                if (e.target.files.length) {
                    handleFiles(e.target.files);
                }
            };
            
            input.click();
        });
    }
    
    function inicializarBotonesAgregar() {
        const btnAgregarTexto = document.getElementById('btn-add-text');
        const btnAgregarForma = document.getElementById('btn-add-shape');
        
        if (btnAgregarTexto) {
            btnAgregarTexto.addEventListener('click', agregarElementoTexto);
        }
        
        if (btnAgregarForma) {
            btnAgregarForma.addEventListener('click', agregarElementoForma);
        }
    }
    
    function inicializarColorFondo() {
        const colorFondo = document.getElementById('color-fondo');
        
        if (!colorFondo) return;
        
        colorFondo.value = fondoColor;
        
        colorFondo.addEventListener('input', function() {
            fondoColor = this.value;
            canvas.style.backgroundColor = fondoColor;
        });
    }
    
    function inicializarZoom() {
        const zoomIn = document.getElementById('zoom-in');
        const zoomOut = document.getElementById('zoom-out');
        const zoomReset = document.getElementById('zoom-reset');
        
        if (!zoomIn || !zoomOut || !zoomReset) return;
        
        zoomIn.addEventListener('click', function() {
            zoomLevel += 0.1;
            aplicarZoom();
        });
        
        zoomOut.addEventListener('click', function() {
            zoomLevel = Math.max(0.1, zoomLevel - 0.1);
            aplicarZoom();
        });
        
        zoomReset.addEventListener('click', function() {
            zoomLevel = 1;
            aplicarZoom();
        });
    }
    
    function aplicarZoom() {
        canvas.style.transform = `scale(${zoomLevel})`;
        
        // Actualizar contador de zoom
        const zoomPercent = document.getElementById('zoom-percent');
        if (zoomPercent) {
            zoomPercent.textContent = Math.round(zoomLevel * 100) + '%';
        }
    }
    
    function inicializarCanvasEventos() {
        if (!canvas) return;
        
        canvas.addEventListener('click', function(e) {
            // Deseleccionar si se hace clic en el canvas y no en un elemento
            if (e.target === canvas) {
                deseleccionarElemento();
            }
        });
    }
    
    function inicializarGuardar() {
        const btnGuardar = document.getElementById('btn-guardar');
        
        if (btnGuardar) {
            btnGuardar.addEventListener('click', guardarProyecto);
        }
    }
    
    function agregarElementoTexto() {
        const elemento = {
            tipo: 'texto',
            contenido: 'Texto de ejemplo',
            estilo: 'font-size: 16px; color: #333;',
            posicion: { x: 50, y: 50 },
            dimensiones: { ancho: 200, alto: 50 },
            zIndex: obtenerSiguienteZIndex()
        };
        
        elementos.push(elemento);
        renderizarElementos();
        
        // Seleccionar el nuevo elemento
        seleccionarElemento(elementos.length - 1);
    }
    
    function agregarElementoImagen(url) {
        if (!validarURLImagen(url)) {
            mostrarNotificacion('URL de imagen inválida', 'error');
            return;
        }
        
        const elemento = {
            tipo: 'imagen',
            url: url,
            estilo: '',
            posicion: { x: 50, y: 50 },
            dimensiones: { ancho: 200, alto: 200 },
            zIndex: obtenerSiguienteZIndex()
        };
        
        const img = new Image();
        img.onload = function() {
            // Mantener proporción
            const proporcion = img.width / img.height;
            elemento.dimensiones.ancho = 200;
            elemento.dimensiones.alto = Math.round(200 / proporcion);
            
            elementos.push(elemento);
            renderizarElementos();
            
            // Seleccionar el nuevo elemento
            seleccionarElemento(elementos.length - 1);
        };
        
        img.onerror = function() {
            mostrarNotificacion('Error al cargar la imagen', 'error');
        };
        
        img.src = url;
    }
    
    function agregarElementoForma() {
        const elemento = {
            tipo: 'forma',
            forma: 'rectangulo',
            estilo: 'background-color: rgba(76, 175, 80, 0.3); border: 2px solid #4CAF50;',
            posicion: { x: 50, y: 50 },
            dimensiones: { ancho: 200, alto: 100 },
            zIndex: obtenerSiguienteZIndex()
        };
        
        elementos.push(elemento);
        renderizarElementos();
        
        // Seleccionar el nuevo elemento
        seleccionarElemento(elementos.length - 1);
    }
    
    function renderizarElementos() {
        // Limpiar canvas (mantener solo los elementos con clase 'controls')
        const controles = Array.from(canvas.querySelectorAll('.controls'));
        canvas.innerHTML = '';
        controles.forEach(control => canvas.appendChild(control));
        
        // Renderizar elementos
        elementos.forEach((elemento, index) => {
            const el = document.createElement('div');
            el.className = 'elemento';
            el.dataset.index = index;
            
            el.style.position = 'absolute';
            el.style.left = elemento.posicion.x + 'px';
            el.style.top = elemento.posicion.y + 'px';
            el.style.width = elemento.dimensiones.ancho + 'px';
            el.style.height = elemento.dimensiones.alto + 'px';
            el.style.zIndex = elemento.zIndex;
            
            // Aplicar estilos específicos según el tipo
            if (elemento.estilo) {
                const estilos = elemento.estilo.split(';').filter(s => s.trim());
                estilos.forEach(estilo => {
                    const [propiedad, valor] = estilo.split(':').map(s => s.trim());
                    if (propiedad && valor) {
                        el.style[propiedad] = valor;
                    }
                });
            }
            
            // Renderizar según tipo
            if (elemento.tipo === 'texto') {
                el.innerHTML = elemento.contenido;
                el.style.overflow = 'hidden';
                el.contentEditable = 'true';
                
                // Prevenir propagación de eventos
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    seleccionarElemento(index);
                });
                
                // Manejar cambios en el texto
                el.addEventListener('input', function() {
                    elemento.contenido = el.innerHTML;
                });
                
                // Evitar selección al arrastrar
                el.addEventListener('mousedown', function(e) {
                    if (e.target === el) {
                        e.preventDefault();
                        iniciarDrag(e, index);
                    }
                });
            } 
            else if (elemento.tipo === 'imagen') {
                el.style.backgroundImage = `url(${elemento.url})`;
                el.style.backgroundSize = 'contain';
                el.style.backgroundRepeat = 'no-repeat';
                el.style.backgroundPosition = 'center';
                
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    seleccionarElemento(index);
                });
                
                el.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    iniciarDrag(e, index);
                });
            } 
            else if (elemento.tipo === 'forma') {
                if (elemento.forma === 'rectangulo') {
                    // Los estilos ya se aplicaron arriba
                }
                else if (elemento.forma === 'circulo') {
                    el.style.borderRadius = '50%';
                }
                
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    seleccionarElemento(index);
                });
                
                el.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    iniciarDrag(e, index);
                });
            }
            
            canvas.appendChild(el);
        });
        
        // Si hay un elemento seleccionado, reseleccionarlo
        if (indiceSeleccionado !== -1) {
            seleccionarElemento(indiceSeleccionado);
        }
    }
    
    function seleccionarElemento(index) {
        deseleccionarElemento();
        
        const elemento = elementos[index];
        const el = canvas.querySelector(`.elemento[data-index="${index}"]`);
        
        if (!elemento || !el) return;
        
        elementoSeleccionado = el;
        indiceSeleccionado = index;
        
        // Añadir controles
        mostrarControles(elemento, index);
        
        // Mostrar panel de configuración
        mostrarConfiguracionElemento(elemento);
    }
    
    function deseleccionarElemento() {
        elementoSeleccionado = null;
        indiceSeleccionado = -1;
        
        ocultarControles();
        
        // Ocultar panel de configuración
        const panelConfig = document.getElementById('elemento-config');
        if (panelConfig) {
            panelConfig.style.display = 'none';
        }
    }
    
    function mostrarControles(elemento, index) {
        // Eliminar controles existentes
        const controlesExistentes = canvas.querySelector('.element-controls');
        if (controlesExistentes) {
            controlesExistentes.remove();
        }
        
        // Crear nuevo control
        const controles = document.createElement('div');
        controles.className = 'element-controls controls';
        controles.style.display = 'block';
        controles.style.left = elemento.posicion.x + 'px';
        controles.style.top = elemento.posicion.y + 'px';
        controles.style.width = elemento.dimensiones.ancho + 'px';
        controles.style.height = elemento.dimensiones.alto + 'px';
        controles.style.zIndex = 9999;
        
        // Agregar manijas de redimensionamiento
        const posiciones = ['tl', 'tr', 'bl', 'br'];
        posiciones.forEach(pos => {
            const handle = document.createElement('div');
            handle.className = 'control-handle ' + pos + ' controls';
            controles.appendChild(handle);
            
            handle.addEventListener('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const startX = e.clientX;
                const startY = e.clientY;
                const startWidth = elemento.dimensiones.ancho;
                const startHeight = elemento.dimensiones.alto;
                const startLeft = elemento.posicion.x;
                const startTop = elemento.posicion.y;
                
                const resize = function(e) {
                    e.preventDefault();
                    
                    let newWidth, newHeight, newLeft, newTop;
                    
                    // Calcular nuevas dimensiones según la manija arrastrada
                    if (pos === 'br') {
                        newWidth = startWidth + (e.clientX - startX) / zoomLevel;
                        newHeight = startHeight + (e.clientY - startY) / zoomLevel;
                        newLeft = startLeft;
                        newTop = startTop;
                    } 
                    else if (pos === 'bl') {
                        newWidth = startWidth - (e.clientX - startX) / zoomLevel;
                        newHeight = startHeight + (e.clientY - startY) / zoomLevel;
                        newLeft = startLeft + (e.clientX - startX) / zoomLevel;
                        newTop = startTop;
                    } 
                    else if (pos === 'tr') {
                        newWidth = startWidth + (e.clientX - startX) / zoomLevel;
                        newHeight = startHeight - (e.clientY - startY) / zoomLevel;
                        newLeft = startLeft;
                        newTop = startTop + (e.clientY - startY) / zoomLevel;
                    } 
                    else if (pos === 'tl') {
                        newWidth = startWidth - (e.clientX - startX) / zoomLevel;
                        newHeight = startHeight - (e.clientY - startY) / zoomLevel;
                        newLeft = startLeft + (e.clientX - startX) / zoomLevel;
                        newTop = startTop + (e.clientY - startY) / zoomLevel;
                    }
                    
                    // Establecer límites mínimos
                    newWidth = Math.max(20, newWidth);
                    newHeight = Math.max(20, newHeight);
                    
                    // Actualizar elemento
                    elemento.dimensiones.ancho = Math.round(newWidth);
                    elemento.dimensiones.alto = Math.round(newHeight);
                    elemento.posicion.x = Math.round(newLeft);
                    elemento.posicion.y = Math.round(newTop);
                    
                    // Actualizar visual
                    renderizarElementos();
                };
                
                const stopResize = function() {
                    document.removeEventListener('mousemove', resize);
                    document.removeEventListener('mouseup', stopResize);
                };
                
                document.addEventListener('mousemove', resize);
                document.addEventListener('mouseup', stopResize);
            });
        });
        
        // Agregar botones de control
        const botonesControl = document.createElement('div');
        botonesControl.className = 'control-buttons controls';
        
        // Botón duplicar
        const btnDuplicar = document.createElement('button');
        btnDuplicar.className = 'btn btn-sm btn-light me-1';
        btnDuplicar.innerHTML = '<i class="fas fa-copy"></i>';
        btnDuplicar.title = 'Duplicar';
        btnDuplicar.addEventListener('click', function() {
            duplicarElemento(index);
        });
        
        // Botón eliminar
        const btnEliminar = document.createElement('button');
        btnEliminar.className = 'btn btn-sm btn-danger me-1';
        btnEliminar.innerHTML = '<i class="fas fa-trash-alt"></i>';
        btnEliminar.title = 'Eliminar';
        btnEliminar.addEventListener('click', function() {
            eliminarElemento(index);
        });
        
        // Botón subir
        const btnSubir = document.createElement('button');
        btnSubir.className = 'btn btn-sm btn-light me-1';
        btnSubir.innerHTML = '<i class="fas fa-arrow-up"></i>';
        btnSubir.title = 'Traer al frente';
        btnSubir.addEventListener('click', function() {
            cambiarZIndex(index, 1);
        });
        
        // Botón bajar
        const btnBajar = document.createElement('button');
        btnBajar.className = 'btn btn-sm btn-light';
        btnBajar.innerHTML = '<i class="fas fa-arrow-down"></i>';
        btnBajar.title = 'Enviar atrás';
        btnBajar.addEventListener('click', function() {
            cambiarZIndex(index, -1);
        });
        
        botonesControl.appendChild(btnDuplicar);
        botonesControl.appendChild(btnEliminar);
        botonesControl.appendChild(btnSubir);
        botonesControl.appendChild(btnBajar);
        controles.appendChild(botonesControl);
        
        canvas.appendChild(controles);
    }
    
    function ocultarControles() {
        const controles = canvas.querySelector('.element-controls');
        if (controles) {
            controles.style.display = 'none';
        }
    }
    
    function mostrarConfiguracionElemento(elemento) {
        const panelConfig = document.getElementById('elemento-config');
        if (!panelConfig) return;
        
        panelConfig.style.display = 'block';
        panelConfig.innerHTML = '';
        
        // Título del panel
        const titulo = document.createElement('h5');
        titulo.className = 'mb-3';
        titulo.textContent = `Editar ${elemento.tipo === 'texto' ? 'Texto' : 
                               elemento.tipo === 'imagen' ? 'Imagen' : 'Forma'}`;
        panelConfig.appendChild(titulo);
        
        // Contenido según tipo de elemento
        if (elemento.tipo === 'texto') {
            // Panel de texto
            const panelTexto = document.createElement('div');
            panelTexto.className = 'mb-3';
            
            // Selector de tamaño de fuente
            const tamanoGrupo = document.createElement('div');
            tamanoGrupo.className = 'mb-2';
            tamanoGrupo.innerHTML = `
                <label class="form-label">Tamaño de fuente</label>
                <select class="form-select form-select-sm" id="font-size">
                    <option value="12px">12px</option>
                    <option value="14px">14px</option>
                    <option value="16px">16px</option>
                    <option value="18px">18px</option>
                    <option value="20px">20px</option>
                    <option value="24px">24px</option>
                    <option value="32px">32px</option>
                    <option value="48px">48px</option>
                </select>
            `;
            panelTexto.appendChild(tamanoGrupo);
            
            // Seleccionar el tamaño actual
            setTimeout(() => {
                const fontSizeSelect = document.getElementById('font-size');
                const currentSize = obtenerValorCSS(elemento.estilo, 'font-size');
                if (fontSizeSelect && currentSize) {
                    fontSizeSelect.value = currentSize;
                }
                
                fontSizeSelect.addEventListener('change', function() {
                    const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'font-size', this.value);
                    elemento.estilo = nuevoEstilo;
                    renderizarElementos();
                });
            }, 0);
            
            // Selector de color
            const colorGrupo = document.createElement('div');
            colorGrupo.className = 'mb-2';
            
            const colorLabel = document.createElement('label');
            colorLabel.className = 'form-label';
            colorLabel.textContent = 'Color de texto';
            
            const colorInput = document.createElement('input');
            colorInput.type = 'color';
            colorInput.className = 'form-control form-control-sm color-pick';
            colorInput.id = 'font-color';
            
            colorGrupo.appendChild(colorLabel);
            colorGrupo.appendChild(colorInput);
            panelTexto.appendChild(colorGrupo);
            
            // Establecer el color actual
            setTimeout(() => {
                const colorPicker = document.getElementById('font-color');
                const currentColor = obtenerColorRGB(elemento.estilo, 'color');
                if (colorPicker && currentColor) {
                    colorPicker.value = currentColor;
                }
                
                colorPicker.addEventListener('input', function() {
                    const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'color', this.value);
                    elemento.estilo = nuevoEstilo;
                    renderizarElementos();
                });
            }, 0);
            
            // Botones de estilo
            const estiloGrupo = document.createElement('div');
            estiloGrupo.className = 'mb-2';
            estiloGrupo.innerHTML = `
                <label class="form-label d-block">Estilo de texto</label>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-bold" title="Negrita">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-italic" title="Cursiva">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-underline" title="Subrayado">
                        <i class="fas fa-underline"></i>
                    </button>
                </div>
            `;
            panelTexto.appendChild(estiloGrupo);
            
            // Eventos de los botones de estilo
            setTimeout(() => {
                const btnBold = document.getElementById('btn-bold');
                const btnItalic = document.getElementById('btn-italic');
                const btnUnderline = document.getElementById('btn-underline');
                
                // Marcar botones según estilo actual
                if (elemento.estilo.includes('font-weight:bold') || elemento.estilo.includes('font-weight: bold')) {
                    btnBold.classList.add('active');
                }
                
                if (elemento.estilo.includes('font-style:italic') || elemento.estilo.includes('font-style: italic')) {
                    btnItalic.classList.add('active');
                }
                
                if (elemento.estilo.includes('text-decoration:underline') || elemento.estilo.includes('text-decoration: underline')) {
                    btnUnderline.classList.add('active');
                }
                
                // Eventos de clic
                btnBold.addEventListener('click', function() {
                    toggleEstiloTexto('font-weight', 'bold', 'normal');
                    this.classList.toggle('active');
                });
                
                btnItalic.addEventListener('click', function() {
                    toggleEstiloTexto('font-style', 'italic', 'normal');
                    this.classList.toggle('active');
                });
                
                btnUnderline.addEventListener('click', function() {
                    toggleEstiloTexto('text-decoration', 'underline', 'none');
                    this.classList.toggle('active');
                });
            }, 0);
            
            // Alineación de texto
            const alineacionGrupo = document.createElement('div');
            alineacionGrupo.className = 'mb-2';
            alineacionGrupo.innerHTML = `
                <label class="form-label d-block">Alineación</label>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="align-left" title="Alinear a la izquierda">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="align-center" title="Centrar">
                        <i class="fas fa-align-center"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="align-right" title="Alinear a la derecha">
                        <i class="fas fa-align-right"></i>
                    </button>
                </div>
            `;
            panelTexto.appendChild(alineacionGrupo);
            
            // Eventos de alineación
            setTimeout(() => {
                const alignLeft = document.getElementById('align-left');
                const alignCenter = document.getElementById('align-center');
                const alignRight = document.getElementById('align-right');
                
                // Marcar botón según alineación actual
                const currentAlign = obtenerValorCSS(elemento.estilo, 'text-align');
                if (currentAlign === 'left') {
                    alignLeft.classList.add('active');
                } else if (currentAlign === 'center') {
                    alignCenter.classList.add('active');
                } else if (currentAlign === 'right') {
                    alignRight.classList.add('active');
                }
                
                // Eventos de clic
                alignLeft.addEventListener('click', function() {
                    aplicarEstiloTexto('text-align', 'left');
                    alignLeft.classList.add('active');
                    alignCenter.classList.remove('active');
                    alignRight.classList.remove('active');
                });
                
                alignCenter.addEventListener('click', function() {
                    aplicarEstiloTexto('text-align', 'center');
                    alignLeft.classList.remove('active');
                    alignCenter.classList.add('active');
                    alignRight.classList.remove('active');
                });
                
                alignRight.addEventListener('click', function() {
                    aplicarEstiloTexto('text-align', 'right');
                    alignLeft.classList.remove('active');
                    alignCenter.classList.remove('active');
                    alignRight.classList.add('active');
                });
            }, 0);
            
            panelConfig.appendChild(panelTexto);
        } 
        else if (elemento.tipo === 'imagen') {
            // Panel de imagen
            const panelImagen = document.createElement('div');
            panelImagen.className = 'mb-3';
            
            // URL de la imagen
            const urlGrupo = document.createElement('div');
            urlGrupo.className = 'mb-3';
            urlGrupo.innerHTML = `
                <label class="form-label">URL de la imagen</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" id="imagen-url" value="${elemento.url}">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-actualizar-imagen">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            `;
            panelImagen.appendChild(urlGrupo);
            
            // Evento de actualización de URL
            setTimeout(() => {
                const urlInput = document.getElementById('imagen-url');
                const btnActualizar = document.getElementById('btn-actualizar-imagen');
                
                btnActualizar.addEventListener('click', function() {
                    const nuevaUrl = urlInput.value.trim();
                    if (validarURLImagen(nuevaUrl)) {
                        elemento.url = nuevaUrl;
                        renderizarElementos();
                    } else {
                        mostrarNotificacion('URL de imagen inválida', 'error');
                    }
                });
            }, 0);
            
            // Opacidad
            const opacidadGrupo = document.createElement('div');
            opacidadGrupo.className = 'mb-3';
            opacidadGrupo.innerHTML = `
                <label class="form-label">Opacidad: <span id="opacidad-valor">100%</span></label>
                <input type="range" class="form-range" min="0" max="100" step="5" id="imagen-opacidad" value="100">
            `;
            panelImagen.appendChild(opacidadGrupo);
            
            // Evento de opacidad
            setTimeout(() => {
                const opacidadInput = document.getElementById('imagen-opacidad');
                const opacidadValor = document.getElementById('opacidad-valor');
                
                // Establecer valor actual
                const opacidadActual = obtenerOpacidad(elemento.estilo, 'opacity');
                if (opacidadActual !== null) {
                    opacidadInput.value = opacidadActual * 100;
                    opacidadValor.textContent = Math.round(opacidadActual * 100) + '%';
                }
                
                opacidadInput.addEventListener('input', function() {
                    const valor = this.value / 100;
                    opacidadValor.textContent = this.value + '%';
                    const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'opacity', valor);
                    elemento.estilo = nuevoEstilo;
                    renderizarElementos();
                });
            }, 0);
            
            // Ajustes de borde
            const bordeGrupo = document.createElement('div');
            bordeGrupo.className = 'mb-3';
            bordeGrupo.innerHTML = `
                <label class="form-label">Borde</label>
                <div class="input-group mb-2">
                    <span class="input-group-text">Grosor</span>
                    <select class="form-select form-select-sm" id="borde-grosor">
                        <option value="0">Sin borde</option>
                        <option value="1px">1px</option>
                        <option value="2px">2px</option>
                        <option value="3px">3px</option>
                        <option value="5px">5px</option>
                    </select>
                </div>
                <div class="input-group">
                    <span class="input-group-text">Color</span>
                    <input type="color" class="form-control form-control-sm color-pick" id="borde-color" value="#000000">
                </div>
            `;
            panelImagen.appendChild(bordeGrupo);
            
            // Evento de borde
            setTimeout(() => {
                const bordeGrosor = document.getElementById('borde-grosor');
                const bordeColor = document.getElementById('borde-color');
                
                // Establecer valores actuales
                const grosorActual = obtenerValorCSS(elemento.estilo, 'border-width');
                if (grosorActual) {
                    bordeGrosor.value = grosorActual;
                }
                
                const colorActual = obtenerColorRGB(elemento.estilo, 'border-color');
                if (colorActual) {
                    bordeColor.value = colorActual;
                }
                
                bordeGrosor.addEventListener('change', function() {
                    if (this.value === '0') {
                        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'border', 'none');
                        elemento.estilo = nuevoEstilo;
                    } else {
                        const color = bordeColor.value;
                        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'border', `${this.value} solid ${color}`);
                        elemento.estilo = nuevoEstilo;
                    }
                    renderizarElementos();
                });
                
                bordeColor.addEventListener('input', function() {
                    const grosor = bordeGrosor.value;
                    if (grosor !== '0') {
                        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'border', `${grosor} solid ${this.value}`);
                        elemento.estilo = nuevoEstilo;
                        renderizarElementos();
                    }
                });
            }, 0);
            
            panelConfig.appendChild(panelImagen);
        } 
        else if (elemento.tipo === 'forma') {
            // Panel de forma
            const panelForma = document.createElement('div');
            panelForma.className = 'mb-3';
            
            // Tipo de forma
            const tipoGrupo = document.createElement('div');
            tipoGrupo.className = 'mb-3';
            tipoGrupo.innerHTML = `
                <label class="form-label">Tipo de forma</label>
                <select class="form-select form-select-sm" id="forma-tipo">
                    <option value="rectangulo">Rectángulo</option>
                    <option value="circulo">Círculo</option>
                </select>
            `;
            panelForma.appendChild(tipoGrupo);
            
            // Evento de tipo de forma
            setTimeout(() => {
                const tipoSelect = document.getElementById('forma-tipo');
                tipoSelect.value = elemento.forma || 'rectangulo';
                
                tipoSelect.addEventListener('change', function() {
                    elemento.forma = this.value;
                    renderizarElementos();
                });
            }, 0);
            
            // Color de fondo
            const fondoGrupo = document.createElement('div');
            fondoGrupo.className = 'mb-3';
            
            const fondoLabel = document.createElement('label');
            fondoLabel.className = 'form-label';
            fondoLabel.textContent = 'Color de fondo';
            
            const fondoInput = document.createElement('input');
            fondoInput.type = 'color';
            fondoInput.className = 'form-control form-control-sm color-pick';
            fondoInput.id = 'forma-color';
            
            const opacidadLabel = document.createElement('label');
            opacidadLabel.className = 'form-label mt-2';
            opacidadLabel.textContent = 'Opacidad: ';
            
            const opacidadValor = document.createElement('span');
            opacidadValor.id = 'forma-opacidad-valor';
            opacidadValor.textContent = '100%';
            opacidadLabel.appendChild(opacidadValor);
            
            const opacidadInput = document.createElement('input');
            opacidadInput.type = 'range';
            opacidadInput.className = 'form-range';
            opacidadInput.min = '0';
            opacidadInput.max = '100';
            opacidadInput.step = '5';
            opacidadInput.id = 'forma-opacidad';
            opacidadInput.value = '100';
            
            fondoGrupo.appendChild(fondoLabel);
            fondoGrupo.appendChild(fondoInput);
            fondoGrupo.appendChild(opacidadLabel);
            fondoGrupo.appendChild(opacidadInput);
            panelForma.appendChild(fondoGrupo);
            
            // Eventos de color y opacidad
            setTimeout(() => {
                const colorPicker = document.getElementById('forma-color');
                const opacidadRange = document.getElementById('forma-opacidad');
                const opacidadValor = document.getElementById('forma-opacidad-valor');
                
                // Obtener color actual
                const bgColor = obtenerColorRGB(elemento.estilo, 'background-color');
                if (bgColor) {
                    colorPicker.value = bgColor;
                }
                
                // Obtener opacidad actual
                const opacidadActual = obtenerOpacidad(elemento.estilo, 'background-color');
                if (opacidadActual !== null) {
                    opacidadRange.value = opacidadActual * 100;
                    opacidadValor.textContent = Math.round(opacidadActual * 100) + '%';
                }
                
                // Función para actualizar el color con opacidad
                const actualizarColorFondo = function() {
                    const color = colorPicker.value;
                    const opacidad = opacidadRange.value / 100;
                    
                    // Convertir color hex a rgba
                    const r = parseInt(color.substring(1, 3), 16);
                    const g = parseInt(color.substring(3, 5), 16);
                    const b = parseInt(color.substring(5, 7), 16);
                    
                    const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'background-color', `rgba(${r}, ${g}, ${b}, ${opacidad})`);
                    elemento.estilo = nuevoEstilo;
                    renderizarElementos();
                };
                
                colorPicker.addEventListener('input', actualizarColorFondo);
                
                opacidadRange.addEventListener('input', function() {
                    opacidadValor.textContent = this.value + '%';
                    actualizarColorFondo();
                });
            }, 0);
            
            // Borde
            const bordeGrupo = document.createElement('div');
            bordeGrupo.className = 'mb-3';
            bordeGrupo.innerHTML = `
                <label class="form-label">Borde</label>
                <div class="input-group mb-2">
                    <span class="input-group-text">Grosor</span>
                    <select class="form-select form-select-sm" id="forma-borde-grosor">
                        <option value="0">Sin borde</option>
                        <option value="1px">1px</option>
                        <option value="2px">2px</option>
                        <option value="3px">3px</option>
                        <option value="5px">5px</option>
                    </select>
                </div>
                <div class="input-group">
                    <span class="input-group-text">Color</span>
                    <input type="color" class="form-control form-control-sm color-pick" id="forma-borde-color" value="#000000">
                </div>
            `;
            panelForma.appendChild(bordeGrupo);
            
            // Eventos de borde
            setTimeout(() => {
                const bordeGrosor = document.getElementById('forma-borde-grosor');
                const bordeColor = document.getElementById('forma-borde-color');
                
                // Establecer valores actuales
                const grosorActual = obtenerValorCSS(elemento.estilo, 'border-width');
                if (grosorActual) {
                    bordeGrosor.value = grosorActual;
                }
                
                const colorActual = obtenerColorRGB(elemento.estilo, 'border-color');
                if (colorActual) {
                    bordeColor.value = colorActual;
                }
                
                bordeGrosor.addEventListener('change', function() {
                    if (this.value === '0') {
                        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'border', 'none');
                        elemento.estilo = nuevoEstilo;
                    } else {
                        const color = bordeColor.value;
                        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'border', `${this.value} solid ${color}`);
                        elemento.estilo = nuevoEstilo;
                    }
                    renderizarElementos();
                });
                
                bordeColor.addEventListener('input', function() {
                    const grosor = bordeGrosor.value;
                    if (grosor !== '0') {
                        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'border', `${grosor} solid ${this.value}`);
                        elemento.estilo = nuevoEstilo;
                        renderizarElementos();
                    }
                });
            }, 0);
            
            // Redondeo de bordes
            const redondeoGrupo = document.createElement('div');
            redondeoGrupo.className = 'mb-3';
            redondeoGrupo.innerHTML = `
                <label class="form-label">Redondeo de bordes: <span id="redondeo-valor">0px</span></label>
                <input type="range" class="form-range" min="0" max="50" step="1" id="forma-redondeo" value="0">
            `;
            panelForma.appendChild(redondeoGrupo);
            
            // Evento de redondeo
            setTimeout(() => {
                const redondeoInput = document.getElementById('forma-redondeo');
                const redondeoValor = document.getElementById('redondeo-valor');
                
                // Establecer valor actual
                const redondeoActual = obtenerValorCSS(elemento.estilo, 'border-radius');
                if (redondeoActual) {
                    const valor = parseInt(redondeoActual);
                    redondeoInput.value = isNaN(valor) ? 0 : valor;
                    redondeoValor.textContent = redondeoActual;
                }
                
                redondeoInput.addEventListener('input', function() {
                    const valor = this.value + 'px';
                    redondeoValor.textContent = valor;
                    const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, 'border-radius', valor);
                    elemento.estilo = nuevoEstilo;
                    renderizarElementos();
                });
            }, 0);
            
            panelConfig.appendChild(panelForma);
        }
        
        // Botón para aplicar cambios
        const btnAplicar = document.createElement('button');
        btnAplicar.className = 'btn btn-success btn-sm w-100';
        btnAplicar.textContent = 'Aplicar Cambios';
        btnAplicar.addEventListener('click', aplicarCambiosElemento);
        
        panelConfig.appendChild(btnAplicar);
    }
    
    function aplicarCambiosElemento() {
        renderizarElementos();
    }
    
    function iniciarDrag(e, index) {
        e.preventDefault();
        
        const elemento = elementos[index];
        
        startX = e.clientX;
        startY = e.clientY;
        indiceSeleccionado = index;
        elementoSeleccionado = e.target;
        isDragging = true;
        
        document.addEventListener('mousemove', moverElemento);
        document.addEventListener('mouseup', finalizarDrag);
    }
    
    function moverElemento(e) {
        if (!isDragging || indiceSeleccionado === -1) return;
        
        const elemento = elementos[indiceSeleccionado];
        
        // Calcular la diferencia y aplicar escala de zoom
        const diffX = (e.clientX - startX) / zoomLevel;
        const diffY = (e.clientY - startY) / zoomLevel;
        
        elemento.posicion.x += diffX;
        elemento.posicion.y += diffY;
        
        // Actualizar puntos de inicio para el próximo movimiento
        startX = e.clientX;
        startY = e.clientY;
        
        renderizarElementos();
    }
    
    function finalizarDrag() {
        isDragging = false;
        document.removeEventListener('mousemove', moverElemento);
        document.removeEventListener('mouseup', finalizarDrag);
    }
    
    function obtenerSiguienteZIndex() {
        let maxZ = 0;
        elementos.forEach(elem => {
            if (elem.zIndex > maxZ) {
                maxZ = elem.zIndex;
            }
        });
        return maxZ + 1;
    }
    
    function eliminarElemento(index) {
        elementos.splice(index, 1);
        renderizarElementos();
        deseleccionarElemento();
    }
    
    function duplicarElemento(index) {
        const original = elementos[index];
        const copia = JSON.parse(JSON.stringify(original));
        
        // Desplazar ligeramente la copia
        copia.posicion.x += 20;
        copia.posicion.y += 20;
        copia.zIndex = obtenerSiguienteZIndex();
        
        elementos.push(copia);
        renderizarElementos();
        
        // Seleccionar el nuevo elemento
        seleccionarElemento(elementos.length - 1);
    }
    
    function cambiarZIndex(index, cambio) {
        const elemento = elementos[index];
        
        if (cambio > 0) {
            // Traer al frente
            elemento.zIndex = obtenerSiguienteZIndex();
        } else {
            // Enviar atrás, mínimo es 0
            elemento.zIndex = Math.max(0, elemento.zIndex - 1);
        }
        
        renderizarElementos();
    }
    
    function aplicarEstiloTexto(propiedad, valor) {
        if (indiceSeleccionado === -1) return;
        
        const elemento = elementos[indiceSeleccionado];
        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, propiedad, valor);
        elemento.estilo = nuevoEstilo;
        renderizarElementos();
    }
    
    function toggleEstiloTexto(propiedad, valorOn, valorOff) {
        if (indiceSeleccionado === -1) return;
        
        const elemento = elementos[indiceSeleccionado];
        const valorActual = obtenerValorCSS(elemento.estilo, propiedad);
        
        const nuevoValor = valorActual === valorOn ? valorOff : valorOn;
        const nuevoEstilo = actualizarEstiloCSS(elemento.estilo, propiedad, nuevoValor);
        elemento.estilo = nuevoEstilo;
        renderizarElementos();
    }
    
    function obtenerValorCSS(estiloCSS, propiedad) {
        if (!estiloCSS) return null;
        
        const regex = new RegExp(propiedad + '\\s*:\\s*([^;]+)', 'i');
        const match = estiloCSS.match(regex);
        
        return match ? match[1].trim() : null;
    }
    
    function actualizarEstiloCSS(estiloCSS, propiedad, nuevoValor) {
        if (!estiloCSS) {
            return propiedad + ': ' + nuevoValor + ';';
        }
        
        const regex = new RegExp(propiedad + '\\s*:\\s*[^;]+;?', 'i');
        
        if (estiloCSS.match(regex)) {
            // Actualizar propiedad existente
            return estiloCSS.replace(regex, propiedad + ': ' + nuevoValor + ';');
        } else {
            // Añadir nueva propiedad
            return estiloCSS + (estiloCSS.endsWith(';') ? ' ' : '; ') + propiedad + ': ' + nuevoValor + ';';
        }
    }
    
    function obtenerColorRGB(estiloCSS, propiedad) {
        const valor = obtenerValorCSS(estiloCSS, propiedad);
        if (!valor) return null;
        
        // Si ya es un valor hexadecimal, retornarlo
        if (valor.startsWith('#')) {
            return valor;
        }
        
        // Si es rgb o rgba, convertir a hex
        const rgbMatch = valor.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*[\d.]+)?\)/);
        if (rgbMatch) {
            const r = parseInt(rgbMatch[1]);
            const g = parseInt(rgbMatch[2]);
            const b = parseInt(rgbMatch[3]);
            return rgbToHex(r, g, b);
        }
        
        return '#000000'; // Valor por defecto
    }
    
    function rgbToHex(r, g, b) {
        return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    }
    
    function obtenerOpacidad(estiloCSS, propiedad) {
        const valor = obtenerValorCSS(estiloCSS, propiedad);
        if (!valor) return null;
        
        // Si es valor directo de opacidad
        if (propiedad === 'opacity') {
            return parseFloat(valor);
        }
        
        // Si es rgba
        const rgbaMatch = valor.match(/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/);
        if (rgbaMatch) {
            return parseFloat(rgbaMatch[4]);
        }
        
        return 1; // Opacidad completa por defecto
    }
    
    function validarURLImagen(url) {
        // Validar URL
        try {
            new URL(url);
        } catch (e) {
            return false;
        }
        
        // Validar que es una imagen (por extensión básica)
        const extensiones = ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp'];
        const tieneExtension = extensiones.some(ext => url.toLowerCase().endsWith(ext));
        
        // También aceptar URLs de data:image
        if (url.startsWith('data:image/')) {
            return true;
        }
        
        return tieneExtension;
    }
    
    function guardarProyecto() {
        // Preparar datos para guardar
        const datosGuardar = {
            elementos: elementos,
            configuracion: {
                fondo: fondoColor,
                ancho: parseInt(canvas.style.width) || 400,
                alto: parseInt(canvas.style.height) || 600
            }
        };
        
        // Actualizar campo oculto con los datos
        const contenidoInput = document.getElementById('contenido-json');
        if (contenidoInput) {
            contenidoInput.value = JSON.stringify(datosGuardar);
            
            // Enviar formulario
            const form = document.getElementById('form-editor');
            if (form) {
                form.submit();
            } else {
                mostrarNotificacion('No se pudo guardar el proyecto', 'error');
            }
        } else {
            mostrarNotificacion('No se pudo guardar el proyecto', 'error');
        }
    }
    
    // Inicializar editor
    inicializar();
});