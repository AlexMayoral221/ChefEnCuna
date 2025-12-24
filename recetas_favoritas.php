<?php
session_start();

require 'config/bd.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'alumno') {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} else if (time() - $_SESSION['LAST_ACTIVITY'] > 1800) { 
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

$alumno_id = $_SESSION['user_id'];
$nombre_alumno = htmlspecialchars($_SESSION['user_nombre'] ?? 'Estudiante');
$dashboard_url = "alumno_dashboard.php"; 

$active_page = 'recetas_favoritas'; 
$brand_green = '#69A64A'; 
$primary_accent = '#ff6b6b'; 
$recetas_favoritas = [];
$error_bd_recetas = null;

if (!isset($pdo)) {
    try {
        $pdo = new PDO('sqlite::memory:'); 
    } catch (PDOException $e) {}
}

try {
    if ($pdo instanceof PDO && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
        
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
                'imagen' => 'img/recetas/' . $receta['id'] . '.jpg',
            ];
        }

    } else {
        $error_bd_recetas = "Advertencia: La conexión a la base de datos no está disponible o el esquema no es completo. Se muestran datos simulados.";
        $recetas_favoritas = [
            [
                'id' => 10,
                'titulo' => 'Tacos al Pastor Caseros',
                'imagen' => 'img/recetas/10.jpg',
            ],
            [
                'id' => 11,
                'titulo' => 'Lasaña Clásica de Carne',
                'imagen' => 'img/recetas/11.jpg',
            ],
        ];
    }
} catch (PDOException $e) {
    $error_bd_recetas = "Error al cargar recetas favoritas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script> 
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1e272e', 
                        'primary-light': '#f4f7f6', 
                        'accent': '#4ecdc4', 
                        'secondary-red': '#ff6b6b', 
                        'text-base': '#4a4a4a', 
                        'brand-green': '<?php echo $brand_green; ?>',   
                        'primary-accent': '<?php echo $primary_accent; ?>',  
                        'secondary': '#4ecdc4', 
                        'dark': '#2d3436',      
                    },
                     boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                        'card-hover': '0 8px 16px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .nav-link.active {
            background-color: #4ecdc4; 
            color: white;
            border-radius: 0.5rem;
        }
        .nav-link:not(.active):hover {
            background-color: rgba(78, 205, 196, 0.1); 
            color: #4ecdc4;
            border-radius: 0.5rem;
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
<body class="bg-primary-light text-text-base">
<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white shadow-sidebar flex flex-col fixed h-full z-20">
        
        <div class="p-6 border-b border-gray-100">
            <h1 class="text-2xl font-extrabold text-primary-dark tracking-wide">ChefEnCuna<span class="text-accent">.</span></h1>
            <p class="text-sm text-gray-500 mt-1">Panel Alumno</p>
        </div>

        <nav class="flex-grow p-4 space-y-2">
            
            <a href="alumno_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 
                      <?php echo ($active_page === 'dashboard') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-th-large w-5 mr-3"></i>
                Mi Dashboard
            </a>

            <a href="cursos.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-book-open w-5 mr-3"></i>
                Explorar Cursos
            </a>

            <a href="recetas_favoritas.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 
                      <?php echo ($active_page === 'recetas_favoritas') ? 'active' : 'text-gray-600'; ?>">
                <i class="fas fa-heart w-5 mr-3"></i>
                Recetas Favoritas
            </a>
            
            <a href="mis_certificados.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-award w-5 mr-3"></i>
                Mis Certificados
            </a>

            <a href="foro_ayuda.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-comments w-5 mr-3"></i>
                Foro y Ayuda
            </a>

            <a href="perfil_ajustes.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-cog w-5 mr-3"></i>
                Ajustes
            </a>
        </nav>

        <div class="p-6 border-t border-gray-100 mt-auto">
            <a href="logout.php" class="flex items-center text-secondary-red font-medium hover:text-red-700 transition duration-200">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="flex-1 overflow-y-auto ml-64">
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Bienvenido/a, <span class="text-accent"><?php echo htmlspecialchars($nombre_alumno); ?></span>
                </h2>
                <a href="index.php" 
                   class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm">
                    <i class="fas fa-home mr-1"></i> Ir a Inicio
                </a>
            </div>
        </header>
    
        <main class="p-4 lg:p-10">
            <div class="mb-8 flex flex-col md:flex-row items-start md:items-center justify-between border-b-2 border-primary-accent pb-4">
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
                        **Nota:** El tiempo de preparación y la dificultad se muestran como valores predeterminados.
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($recetas_favoritas)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    
                    <?php foreach ($recetas_favoritas as $receta): ?>
                    <div class="recipe-card flex flex-col h-full">
                        <a href="ver_receta.php?id=<?php echo $receta['id']; ?>" class="block overflow-hidden relative group">
                            <img src="<?php echo $receta['imagen']; ?>" alt="<?php echo htmlspecialchars($receta['titulo']); ?>" class="w-full h-48 object-cover transform group-hover:scale-105 transition duration-500" onerror="this.onerror=null;this.src='https://placehold.co/400x225/<?php echo substr($primary_accent, 1); ?>/ffffff?text=Receta+<?php echo htmlspecialchars($receta['id']); ?>';">
                            
                            <div class="absolute top-2 left-2 bg-primary-accent text-white px-3 py-1 rounded-full text-xs font-bold shadow-md">
                                <i class="fas fa-star mr-1"></i> Favorita
                            </div>
                        </a>
                        <div class="p-5 flex flex-col flex-grow">
                            <h3 class="text-xl font-bold text-dark mb-2 leading-tight text-center">
                                <a href="ver_receta.php?id=<?php echo $receta['id']; ?>" class="hover:text-primary-accent transition">
                                    <?php echo htmlspecialchars($receta['titulo']); ?>
                                </a>
                            </h3>

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
    </div>
</div>
</body>
</html>