<?php
session_start();

// Asumimos que config/bd.php contiene la lógica de conexión $pdo
require 'config/bd.php'; 

// 1. Verificación de seguridad: solo alumnos autenticados
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'alumno') {
    header('Location: login.php');
    exit;
}

$alumno_id = $_SESSION['user_id'];
$nombre_alumno = htmlspecialchars($_SESSION['user_nombre'] ?? 'Estudiante');
$dashboard_url = "alumno_dashboard.php"; 

// 2. Simulación de carga de datos de recetas favoritas
$recetas_favoritas = [];
$error_bd_recetas = null;

// Configuración de conexión simulada para entornos sin BD
if (!isset($pdo)) {
    try {
        $pdo = new PDO('sqlite::memory:'); 
    } catch (PDOException $e) {
        // Manejo de error si la conexión de prueba falla
    }
}


try {
    // Solo intentamos la consulta si $pdo está inicializado y no es la conexión SQLite en memoria
    if ($pdo instanceof PDO && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
        
        // ** CONSULTA SQL CORREGIDA **
        // Se seleccionan solo las columnas existentes (id y titulo)
        // Se corrige 'alumno_id' a 'usuario_id' y 'fecha_favorito' a 'fecha_agregado'
        $stmt = $pdo->prepare("
            SELECT 
                r.id, 
                r.titulo
            FROM recetas_favoritas rf
            JOIN recetas r ON rf.receta_id = r.id
            WHERE rf.usuario_id = ? 
            ORDER BY rf.fecha_agregado DESC
        ");
        $stmt->execute([$alumno_id]);
        $recetas_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recetas_db as $receta) {
            $recetas_favoritas[] = [
                'id' => $receta['id'],
                'titulo' => htmlspecialchars($receta['titulo']),
                // Usamos valores por defecto ya que 'tiempo_preparacion' y 'dificultad' no están en la tabla 'recetas'
                'tiempo' => 'Tiempo Estimado', 
                'dificultad' => 'No Especificada', 
                'imagen' => 'img/recetas/' . $receta['id'] . '.jpg',
            ];
        }

    } else {
        // Datos simulados si la conexión real o de prueba falla/no es accesible
        $error_bd_recetas = "Advertencia: La conexión a la base de datos no está disponible o el esquema no es completo. Se muestran datos simulados.";
        $recetas_favoritas = [
            [
                'id' => 10,
                'titulo' => 'Tacos al Pastor Caseros',
                'tiempo' => '45 min (Simulado)',
                'dificultad' => 'Media (Simulada)',
                'imagen' => 'img/recetas/10.jpg',
            ],
            [
                'id' => 11,
                'titulo' => 'Lasaña Clásica de Carne',
                'tiempo' => '90 min (Simulado)',
                'dificultad' => 'Alta (Simulada)',
                'imagen' => 'img/recetas/11.jpg',
            ],
        ];
    }
} catch (PDOException $e) {
    // Si la consulta falla (a pesar de las correcciones), registramos el error
    $error_bd_recetas = "Error al cargar recetas favoritas: " . $e->getMessage();
}

// Mantenemos la misma configuración de estilo del dashboard
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetas Favoritas | ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script> 
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-green': '#69A64A',   
                        'primary-accent': '#ff6b6b',  
                        'secondary': '#4ecdc4', 
                        'dark': '#2d3436',      
                        'light': '#f7f1e3',     
                        'white': '#ffffff',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --primary: #69A64A;
            --secondary-color: #4ecdc4; 
            --dark: #2d3436;
            --light: #f7f1e3;
            --theme-green: #69A64A; 
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--light); 
        }
        .app-header { 
            background: var(--theme-green); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0;
            z-index: 50;
        }
        .header-content {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            width: 100%;
            max-width: 1600px; 
            margin: 0 auto;
            padding: 1rem 3rem; 
        }
        .logo { 
            font-size: 1.8rem; 
            font-weight: bold; 
            color: white; 
            text-decoration: none; 
        }
        .app-nav a {
            margin-left: 1.5rem;
            text-decoration: none;
            color: white; 
            font-weight: 500;
            transition: color 0.15s;
            white-space: nowrap; 
        }
        .app-nav a:hover { 
            color: var(--secondary-color); 
        }
        .btn-profile {
            background-color: var(--secondary-color); 
            color: white;
            transition: background-color 0.2s;
            border-radius: 9999px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            margin-left: 1.5rem; 
        }
        .btn-profile:hover {
            background-color: #3aa69e; 
        }
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                padding: 1rem;
            }
            .app-nav {
                margin-top: 1rem;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            .app-nav a {
                margin: 0;
            }
            .btn-profile {
                margin-left: 0.5rem !important;
                margin-right: 0.5rem !important;
            }
        }
        .recipe-card {
            background-color: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eee;
        }
        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
<header class="app-header">
    <div class="header-content">
        <a href="index.php" class="logo">ChefEnCuna 👨‍🍳</a>

        <nav class="app-nav flex items-center">
            <a href="index.php">Inicio</a>
            <a href="recetas.php">Recetas</a>
            <a href="cursos.php">Cursos</a>
            <a href="foro_ayuda.php">Foro</a>

            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" 
                        class="btn-profile flex items-center">
                        <i class="fas fa-user mr-2"></i> <?= htmlspecialchars($_SESSION['user_nombre']) ?>
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open"
                        x-transition
                        class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1
                               bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">

                        <a href="<?= htmlspecialchars($dashboard_url) ?>"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-gauge-high mr-2"></i> Mi perfil
                        </a>
                            
                        <a href="logout.php"
                           class="block px-4 py-2 text-sm text-white hover:bg-red-700 border-t border-white/25">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" 
                        class="btn-access-style flex items-center">
                        <i class="fas fa-user-circle mr-2"></i> Acceso
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open"
                        x-transition
                        class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1
                               bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">

                        <a href="login.php"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                        </a>
                            
                        <a href="registro.php"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700 border-t border-white/25">
                            <i class="fas fa-user-plus mr-2"></i> Registrarse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
    
    <main class="container mx-auto p-4 lg:p-10 flex-grow">
        
        <div class="mb-8 flex items-center justify-between border-b-2 border-primary-accent pb-4">
            <div>
                <h1 class="text-4xl font-extrabold text-dark mb-1 flex items-center">
                    <i class="fas fa-heart text-primary-accent mr-3"></i> Mis Recetas Favoritas
                </h1>
                <p class="text-xl text-gray-500">
                    Las creaciones que has guardado para cocinar una y otra vez.
                </p>
            </div>
        </div>

        <?php if ($error_bd_recetas): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-8 rounded-lg shadow" role="alert">
                <p class="font-bold"><i class="fas fa-exclamation-triangle"></i> Aviso Importante:</p>
                <p class='text-sm'>
                    <?php echo $error_bd_recetas; ?> 
                    <br>
                    **Nota:** El tiempo de preparación y la dificultad se muestran como valores predeterminados, ya que estas columnas no existen en tu tabla `recetas`.
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($recetas_favoritas)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                
                <?php foreach ($recetas_favoritas as $receta): ?>
                <div class="recipe-card flex flex-col h-full">
                    <a href="ver_receta.php?id=<?php echo $receta['id']; ?>" class="block overflow-hidden relative group">
                        <img src="<?php echo $receta['imagen']; ?>" 
                             alt="<?php echo htmlspecialchars($receta['titulo']); ?>" 
                             class="w-full h-48 object-cover transform group-hover:scale-105 transition duration-500"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x225/ff6b6b/ffffff?text=Receta+<?php echo htmlspecialchars($receta['id']); ?>';">
                        
                        <div class="absolute top-2 left-2 bg-primary-accent text-white px-3 py-1 rounded-full text-xs font-bold shadow-md">
                            <i class="fas fa-star mr-1"></i> Favorita
                        </div>
                    </a>
                    <div class="p-5 flex flex-col flex-grow">
                        <h3 class="text-xl font-bold text-dark mb-2 leading-tight">
                            <a href="ver_receta.php?id=<?php echo $receta['id']; ?>" class="hover:text-primary-accent transition">
                                <?php echo htmlspecialchars($receta['titulo']); ?>
                            </a>
                        </h3>
                        
                        <div class="flex justify-between items-center text-sm text-gray-600 mb-4 mt-auto border-t pt-3">
                            <span class="flex items-center">
                                <i class="fas fa-clock mr-1 text-secondary"></i> 
                                <?php echo htmlspecialchars($receta['tiempo']); ?>
                            </span>
                            <span class="flex items-center">
                                <i class="fas fa-chart-bar mr-1 text-brand-green"></i> 
                                <?php echo htmlspecialchars($receta['dificultad']); ?>
                            </span>
                        </div>

                        <a href="ver_receta.php?id=<?php echo $receta['id']; ?>" class="mt-2 block w-full py-2 text-center text-sm font-bold text-white bg-primary-accent rounded-full hover:bg-red-700 transition shadow-lg">
                            <i class="fas fa-eye mr-2"></i> Ver Receta
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <div class="text-center p-12 bg-white rounded-xl shadow-lg mt-6 border-l-4 border-secondary">
                <i class="fas fa-utensils text-7xl text-gray-300 mb-4"></i>
                <h3 class="text-3xl font-semibold mb-2 text-gray-700">Aún no tienes recetas favoritas.</h3>
                <p class="text-lg text-gray-500 mb-6">Explora nuestro catálogo de recetas y marca las que más te gusten.</p>
                <a href="recetas.php" class="py-3 px-8 rounded-full bg-secondary text-white font-bold text-lg hover:opacity-90 transition duration-150 shadow-xl">
                    <i class="fas fa-search mr-2"></i> Explorar Recetas
                </a>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>