<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo) ? $titulo : 'Editor de Proyectos Ambientales' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #1565c0;
            --accent-color: #ff9800;
            --text-color: #333;
            --light-text: #6c757d;
            --light-bg: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1 0 auto;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
        }
        
        .dropdown-item:active {
            background-color: var(--primary-color);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2c3e50 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }
        
        .btn-outline-success {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-success:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .template-card {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        
        .template-preview {
            position: relative;
            overflow: hidden;
            border-radius: 0.25rem 0.25rem 0 0;
        }
        
        .template-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .template-card:hover .template-overlay {
            opacity: 1;
        }
        
        .footer {
            background-color: #1b5e20;
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
        }
        
        .footer-link {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-link:hover {
            color: white;
            text-decoration: none;
        }
        
        .text-light-50 {
            color: rgba(255,255,255,0.7) !important;
        }
        
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            font-size: 1.5rem;
        }
        
        /* Editor specific styles */
        .canvas-container {
            position: relative;
            overflow: hidden;
            background-color: #fff;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .element-controls {
            position: absolute;
            display: none;
            border: 2px dashed var(--primary-color);
            pointer-events: none;
        }
        
        .control-handle {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: var(--primary-color);
            border: 1px solid white;
            pointer-events: auto;
            cursor: pointer;
        }
        
        .control-handle.tl { top: -5px; left: -5px; cursor: nw-resize; }
        .control-handle.tr { top: -5px; right: -5px; cursor: ne-resize; }
        .control-handle.bl { bottom: -5px; left: -5px; cursor: sw-resize; }
        .control-handle.br { bottom: -5px; right: -5px; cursor: se-resize; }
        
        .control-buttons {
            position: absolute;
            top: -35px;
            right: 0;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            padding: 2px;
            pointer-events: auto;
        }
        
        .dropzone {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .dropzone:hover {
            background-color: #f5f5f5;
        }
        
        .color-pick {
            width: 30px;
            height: 30px;
            padding: 0;
            border: none;
            cursor: pointer;
        }
        
        .zoom-container {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            padding: 5px 10px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
    </style>
    
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="<?= BASE_URL ?>">
                    <i class="fas fa-leaf me-2 text-success"></i>Editor Ambiental
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= !isset($pagina) || $pagina === 'inicio' ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>">
                                <i class="fas fa-home me-1"></i>Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isset($pagina) && $pagina === 'crear' ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>crear_proyecto.php">
                                <i class="fas fa-plus-circle me-1"></i>Nuevo Proyecto
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" 
                               data-bs-toggle="dropdown">
                                <i class="fas fa-th-large me-1"></i>Plantillas
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>crear_proyecto.php?categoria=todas">Todas las plantillas</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php foreach (PLANTILLA_CATEGORIES as $key => $name): ?>
                                    <?php if ($key !== 'todas'): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>crear_proyecto.php?categoria=<?= $key ?>"><?= $name ?></a></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <a href="<?= BASE_URL ?>crear_proyecto.php" class="btn btn-success">
                            <i class="fas fa-pencil-alt me-1"></i>Comenzar a Editar
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main>
        <div class="container py-4">
            <?php 
            // Mostrar mensajes flash si existen
            mostrarMensajes(); 
            ?>