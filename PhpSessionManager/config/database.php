<?php
/**
 * Configuración y conexión a la base de datos
 */

class Database {
    private $host = "localhost";
    private $db_name = "editorambiental";
    private $sqlite_path = "database/editorambiental.db";
    private $conn;
    
    /**
     * Obtiene la conexión a la base de datos
     * @return PDO Conexión PDO a la base de datos
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Usar SQLite para compatibilidad con Replit
            $this->conn = new PDO("sqlite:" . $this->sqlite_path);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("PRAGMA foreign_keys = ON");
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    /**
     * Configura la base de datos si no existe
     * @return bool True si se configuró correctamente, false en caso contrario
     */
    public function setupDatabase() {
        try {
            // Verificar si el directorio database existe, si no, crearlo
            if (!is_dir('database')) {
                mkdir('database', 0755, true);
            }
            
            // Verificar si la base de datos existe
            $dbExists = file_exists($this->sqlite_path);
            
            // Si no existe, crear las tablas
            if (!$dbExists) {
                // Tabla de proyectos
                $this->conn->exec("
                    CREATE TABLE IF NOT EXISTS proyectos (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        nombre TEXT NOT NULL,
                        descripcion TEXT,
                        contenido TEXT,
                        config TEXT,
                        categoria TEXT,
                        plantilla_id INTEGER,
                        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Tabla de plantillas
                $this->conn->exec("
                    CREATE TABLE IF NOT EXISTS plantillas (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        nombre TEXT NOT NULL,
                        descripcion TEXT,
                        preview TEXT,
                        contenido TEXT,
                        categoria TEXT,
                        activo INTEGER DEFAULT 1,
                        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Insertar plantillas de muestra
                $this->insertarPlantillasMuestra();
                
                return true;
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Error al configurar la base de datos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Inserta plantillas de muestra en la base de datos
     */
    private function insertarPlantillasMuestra() {
        $plantillas = [
            [
                'nombre' => 'Conservación Forestal',
                'descripcion' => 'Plantilla para proyectos de conservación de bosques y áreas forestales',
                'preview' => 'img/plantillas/conservacion_forestal.png',
                'contenido' => json_encode([
                    'elementos' => [
                        [
                            'tipo' => 'texto',
                            'contenido' => 'Conservación Forestal',
                            'estilo' => 'font-size:32px;font-weight:bold;color:#2e7d32;text-align:center;',
                            'posicion' => ['x' => 50, 'y' => 50],
                            'dimensiones' => ['ancho' => 300, 'alto' => 50],
                            'zIndex' => 1
                        ],
                        [
                            'tipo' => 'texto',
                            'contenido' => 'Protegiendo nuestros bosques para las generaciones futuras',
                            'estilo' => 'font-size:18px;color:#555;text-align:center;font-style:italic;',
                            'posicion' => ['x' => 50, 'y' => 110],
                            'dimensiones' => ['ancho' => 300, 'alto' => 40],
                            'zIndex' => 2
                        ],
                        [
                            'tipo' => 'forma',
                            'forma' => 'rectangulo',
                            'estilo' => 'background-color:rgba(76, 175, 80, 0.1);border:2px solid #4CAF50;border-radius:10px;',
                            'posicion' => ['x' => 50, 'y' => 160],
                            'dimensiones' => ['ancho' => 300, 'alto' => 200],
                            'zIndex' => 0
                        ]
                    ],
                    'configuracion' => [
                        'fondo' => '#f8f9fa',
                        'ancho' => 400,
                        'alto' => 600
                    ]
                ]),
                'categoria' => 'conservacion'
            ],
            [
                'nombre' => 'Energías Renovables',
                'descripcion' => 'Plantilla para proyectos de energías limpias y renovables',
                'preview' => 'img/plantillas/energias_renovables.png',
                'contenido' => json_encode([
                    'elementos' => [
                        [
                            'tipo' => 'texto',
                            'contenido' => 'Energías Renovables',
                            'estilo' => 'font-size:32px;font-weight:bold;color:#1565c0;text-align:center;',
                            'posicion' => ['x' => 50, 'y' => 50],
                            'dimensiones' => ['ancho' => 300, 'alto' => 50],
                            'zIndex' => 1
                        ],
                        [
                            'tipo' => 'texto',
                            'contenido' => 'El futuro de la energía limpia y sostenible',
                            'estilo' => 'font-size:18px;color:#555;text-align:center;font-style:italic;',
                            'posicion' => ['x' => 50, 'y' => 110],
                            'dimensiones' => ['ancho' => 300, 'alto' => 40],
                            'zIndex' => 2
                        ],
                        [
                            'tipo' => 'forma',
                            'forma' => 'rectangulo',
                            'estilo' => 'background-color:rgba(33, 150, 243, 0.1);border:2px solid #2196F3;border-radius:10px;',
                            'posicion' => ['x' => 50, 'y' => 160],
                            'dimensiones' => ['ancho' => 300, 'alto' => 200],
                            'zIndex' => 0
                        ]
                    ],
                    'configuracion' => [
                        'fondo' => '#f8f9fa',
                        'ancho' => 400,
                        'alto' => 600
                    ]
                ]),
                'categoria' => 'energia'
            ],
            [
                'nombre' => 'Educación Ambiental',
                'descripcion' => 'Plantilla para proyectos educativos sobre medio ambiente',
                'preview' => 'img/plantillas/educacion_ambiental.png',
                'contenido' => json_encode([
                    'elementos' => [
                        [
                            'tipo' => 'texto',
                            'contenido' => 'Educación Ambiental',
                            'estilo' => 'font-size:32px;font-weight:bold;color:#ff9800;text-align:center;',
                            'posicion' => ['x' => 50, 'y' => 50],
                            'dimensiones' => ['ancho' => 300, 'alto' => 50],
                            'zIndex' => 1
                        ],
                        [
                            'tipo' => 'texto',
                            'contenido' => 'Aprendiendo a cuidar nuestro planeta',
                            'estilo' => 'font-size:18px;color:#555;text-align:center;font-style:italic;',
                            'posicion' => ['x' => 50, 'y' => 110],
                            'dimensiones' => ['ancho' => 300, 'alto' => 40],
                            'zIndex' => 2
                        ],
                        [
                            'tipo' => 'forma',
                            'forma' => 'rectangulo',
                            'estilo' => 'background-color:rgba(255, 152, 0, 0.1);border:2px solid #FF9800;border-radius:10px;',
                            'posicion' => ['x' => 50, 'y' => 160],
                            'dimensiones' => ['ancho' => 300, 'alto' => 200],
                            'zIndex' => 0
                        ]
                    ],
                    'configuracion' => [
                        'fondo' => '#f8f9fa',
                        'ancho' => 400,
                        'alto' => 600
                    ]
                ]),
                'categoria' => 'educacion'
            ],
            [
                'nombre' => 'Biodiversidad',
                'descripcion' => 'Plantilla para proyectos sobre biodiversidad y ecosistemas',
                'preview' => 'img/plantillas/biodiversidad.png',
                'contenido' => json_encode([
                    'elementos' => [
                        [
                            'tipo' => 'texto',
                            'contenido' => 'Biodiversidad',
                            'estilo' => 'font-size:32px;font-weight:bold;color:#009688;text-align:center;',
                            'posicion' => ['x' => 50, 'y' => 50],
                            'dimensiones' => ['ancho' => 300, 'alto' => 50],
                            'zIndex' => 1
                        ],
                        [
                            'tipo' => 'texto',
                            'contenido' => 'Protegiendo la riqueza de nuestros ecosistemas',
                            'estilo' => 'font-size:18px;color:#555;text-align:center;font-style:italic;',
                            'posicion' => ['x' => 50, 'y' => 110],
                            'dimensiones' => ['ancho' => 300, 'alto' => 40],
                            'zIndex' => 2
                        ],
                        [
                            'tipo' => 'forma',
                            'forma' => 'rectangulo',
                            'estilo' => 'background-color:rgba(0, 150, 136, 0.1);border:2px solid #009688;border-radius:10px;',
                            'posicion' => ['x' => 50, 'y' => 160],
                            'dimensiones' => ['ancho' => 300, 'alto' => 200],
                            'zIndex' => 0
                        ]
                    ],
                    'configuracion' => [
                        'fondo' => '#f8f9fa',
                        'ancho' => 400,
                        'alto' => 600
                    ]
                ]),
                'categoria' => 'ambiente'
            ]
        ];
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO plantillas (nombre, descripcion, preview, contenido, categoria)
                VALUES (:nombre, :descripcion, :preview, :contenido, :categoria)
            ");
            
            foreach ($plantillas as $plantilla) {
                $stmt->bindParam(':nombre', $plantilla['nombre']);
                $stmt->bindParam(':descripcion', $plantilla['descripcion']);
                $stmt->bindParam(':preview', $plantilla['preview']);
                $stmt->bindParam(':contenido', $plantilla['contenido']);
                $stmt->bindParam(':categoria', $plantilla['categoria']);
                $stmt->execute();
            }
        } catch(PDOException $e) {
            error_log("Error al insertar plantillas de muestra: " . $e->getMessage());
        }
    }
}