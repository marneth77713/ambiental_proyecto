        </div><!-- /.container -->
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-light mb-3">Editor de Proyectos Ambientales</h5>
                    <p class="text-light-50">Una herramienta para crear y compartir proyectos interactivos con temática ambiental. Diseñado para educadores, estudiantes y entusiastas del medio ambiente.</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h5 class="text-light mb-3">Enlaces</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?= BASE_URL ?>" class="footer-link"><i class="fas fa-home me-2"></i>Inicio</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>crear_proyecto.php" class="footer-link"><i class="fas fa-plus-circle me-2"></i>Nuevo proyecto</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>crear_proyecto.php?categoria=todas" class="footer-link"><i class="fas fa-th-large me-2"></i>Plantillas</a></li>
                    </ul>
                </div>
                <div class="col-md-5">
                    <h5 class="text-light mb-3">Categorías Ambientales</h5>
                    <div class="row">
                        <?php foreach (PLANTILLA_CATEGORIES as $key => $name): ?>
                        <div class="col-6 mb-2">
                            <a href="<?= BASE_URL ?>crear_proyecto.php?categoria=<?= $key ?>" class="footer-link">
                                <i class="fas fa-folder me-2"></i><?= $name ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-light opacity-25">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0 text-light-50">&copy; <?= date('Y') ?> Editor de Proyectos Ambientales. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="footer-link me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="footer-link me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="footer-link me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="footer-link"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    
    <!-- Helpers JS -->
    <script src="<?= BASE_URL ?>js/helpers.js"></script>
    
    <?php if (isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
    
    <script>
    // Inicializar tooltips y popovers Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
    </script>
</body>
</html>