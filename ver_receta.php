<?php
session_start();
require 'config/bd.php'; 

$pdo = $pdo ?? null; 
$session_error = '';
$dashboard_url = "perfil.php"; 
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

if ($pdo) {
    if ($is_logged_in && (!isset($_SESSION['user_nombre']) || !isset($_SESSION['user_rol']))) {
        try {
            $stmt = $pdo->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                $_SESSION['user_nombre'] = $usuario['nombre'];
                $_SESSION['user_rol'] = $usuario['rol'];
            }
        } catch (PDOException $e) {
            $session_error = "Error DB al cargar datos de sesión: " . $e->getMessage();
        }
    }

    if (isset($_SESSION['user_rol'])) {
        switch ($_SESSION['user_rol']) {
            case 'maestro': $dashboard_url = "maestro_dashboard.php"; break;
            case 'alumno': $dashboard_url = "alumno_dashboard.php"; break;
            case 'administrador': $dashboard_url = "admin_dashboard.php"; break;
            default: $dashboard_url = "perfil.php";
        }
    }
} else {
    $session_error = "Error Crítico: No se pudo establecer la conexión a la base de datos (bd.php).";
}

$receta = null;
$error_receta = '';
$receta_id = null;
$is_favorite = false; 

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $receta_id = (int)$_GET['id'];
    
    if (!$pdo) {
        $error_receta = "Error de conexión a la base de datos. Por favor, intente más tarde.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT 
                                        r.id, r.titulo, r.descripcion, r.ingredientes, r.procedimiento, r.imagen_ruta,
                                        u.nombre as autor_nombre, u.apellido as autor_apellido
                                   FROM recetas r 
                                   JOIN usuarios u ON r.usuario_id = u.id 
                                   WHERE r.id = :id");
            $stmt->execute([':id' => $receta_id]);
            $receta = $stmt->fetch(PDO::FETCH_ASSOC); 

            if (!$receta) {
                $error_receta = "La receta solicitada (ID: " . $receta_id . ") no existe o fue eliminada.";
            } else {
                if ($is_logged_in) {
                    $stmt_fav = $pdo->prepare("SELECT 1 FROM recetas_favoritas WHERE usuario_id = ? AND receta_id = ?");
                    $stmt_fav->execute([$user_id, $receta_id]);
                    $is_favorite = $stmt_fav->fetch() !== false;
                }
            }
            
        } catch (PDOException $e) {
            $error_receta = "Error al ejecutar la consulta: " . $e->getMessage();
        }
    }

} else {
    $error_receta = "ID de receta no válido o faltante en la URL.";
}

$image_path = '';
if ($receta) {
    if (!empty($receta['imagen_ruta'])) {
        $image_path = htmlspecialchars($receta['imagen_ruta']);
    } else {
        $image_path = "img/recetas/{$receta['id']}.jpg";
    }
}
$pageTitle = $receta ? htmlspecialchars($receta['titulo']) : 'Receta No Encontrada';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <script src="//unpkg.com/alpinejs" defer></script> 
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
    <style>
        /* Definición de variables de color con foco en la limpieza y el contraste */
        :root {
            --primary: #ffffff;         /* Fondo de la tarjeta y secciones */
            --secondary: #4ecdc4;       /* Color de acento suave (Turquesa) */
            --dark: #2d3436;            /* Texto principal */
            --light: #f4f7f6;           /* Fondo de la página (gris muy claro) */
            --theme-green: #69A64A;     /* Verde principal de la marca */
            --primary-accent: #ff6b6b;  /* Rojo de acento/peligro */
            --shadow: rgba(0,0,0,0.08); /* Sombra suave */
        }
        body { 
            font-family: 'Inter', sans-serif; 
            color: var(--dark); 
            background-color: var(--light); 
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex; 
            flex-direction: column; 
        }
        /* Estilos del Header y Navegación (sin cambios mayores) */
        .app-header { 
            background: var(--theme-green); 
            box-shadow: 0 2px 5px var(--shadow); 
            position: sticky; 
            top: 0;
            z-index: 100;
        }
        .header-content {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            width: 100%;
            max-width: 1600px; 
            padding: 1rem 3rem; 
            margin: 0 auto;
        }
        .logo { 
            font-size: 1.8rem; 
            font-weight: bold; 
            color: var(--primary); 
            text-decoration: none; 
        }
        .app-nav a {
            margin-left: 1.5rem;
            text-decoration: none;
            color: var(--primary); 
            font-weight: 500;
            transition: color 0.15s;
        }
        .app-nav a:hover { 
            color: var(--secondary); 
        }
        .btn-access-style {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 9999px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s, color 0.2s;
        }
        .btn-access-style:hover {
            background-color: var(--primary);
            color: var(--theme-green);
        }
        .btn-profile {
            background-color: var(--secondary);
            color: white;
            transition: background-color 0.2s;
            border-radius: 9999px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
        }
        .btn-profile:hover {
            background-color: #3aa69e; 
        }
        .container {
            max-width: 1100px; /* Un poco más amplio para este diseño */
            margin: 0 auto; 
            padding: 0 20px; 
            flex-grow: 1; 
            padding-top: 40px;
        }
        
        /* Estilos específicos de la receta */
        .recipe-card {
            background-color: var(--primary);
            border-radius: 16px;
            box-shadow: 0 10px 30px var(--shadow);
            padding: 30px;
            margin-bottom: 40px;
        }

        .recipe-header h1 {
            font-size: 3rem;
            color: var(--theme-green);
            font-weight: 800;
            line-height: 1.1;
        }
        .recipe-author {
            font-size: 1rem;
            color: #888;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .recipe-image {
            width: 100%;
            height: 350px; /* Altura fija para consistencia */
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 5px 15px var(--shadow);
        }
        .subsection-header {
            font-size: 1.8rem;
            color: var(--dark);
            margin-top: 35px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        /* Estilos de Ingredientes (Side panel) */
        .ingredients-box {
            background-color: var(--light); 
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px; /* Separación del top en móvil */
        }
        .ingredients-box h3 {
            color: var(--theme-green);
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.4rem;
            font-weight: 600;
        }
        .ingredients-box ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .ingredients-box li {
            padding: 8px 0;
            color: var(--dark);
            font-size: 1rem;
            display: flex;
            align-items: baseline;
            border-bottom: 1px dotted #ccc;
        }
        .ingredients-box li:last-child {
            border-bottom: none;
        }
        .ingredients-box .fas {
             color: var(--secondary);
             margin-right: 12px;
             font-size: 0.9rem;
        }

        /* Estilos de Procedimiento */
        .procedure-list {
            list-style: none;
            counter-reset: procedure-step;
            padding: 0;
        }
        .procedure-list li {
            position: relative;
            padding: 20px 20px 20px 70px; /* Mayor padding para el número */
            margin-bottom: 15px;
            background-color: var(--primary);
            border-radius: 12px;
            box-shadow: 0 2px 4px var(--shadow);
            line-height: 1.6;
            font-size: 1.05rem;
            transition: background-color 0.2s;
        }
        .procedure-list li:hover {
            background-color: #fafafa;
        }
        .procedure-list li::before {
            counter-increment: procedure-step;
            content: counter(procedure-step);
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            background-color: var(--theme-green);
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 0 0 5px rgba(105, 166, 74, 0.2); /* Anillo sutil */
        }
        /* Media Queries para Responsive (manteniendo los del header) */
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
            }
            .app-nav a {
                margin: 0.5rem 0.5rem;
            }
            .recipe-card {
                padding: 20px;
            }
            .recipe-header h1 {
                font-size: 2.2rem;
            }
            .subsection-header {
                font-size: 1.5rem;
            }
        }
        footer { 
            background: var(--dark); 
            color: white; 
            text-align: center; 
            padding: 20px; 
            margin-top: 50px; 
        }
        footer a { 
            color: var(--secondary); 
            text-decoration: none; 
            margin: 0 10px; 
        }
        footer a:hover { 
            text-decoration: underline; 
        }        
    </style>
</head>
<body>
        
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
                        <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">
                            <a href="<?= htmlspecialchars($dashboard_url) ?>" class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                                <i class="fas fa-gauge-high mr-2"></i> Mi perfil
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-white hover:bg-red-700 border-t border-white/25">
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

<main class="container" style="padding-top: 50px; min-height: 70vh;">
    
    <?php 
    if ($error_receta || !$receta): ?>
        
        <div class="recipe-card" style="text-align: center;">
            <h2 style="color: var(--dark); font-size: 2rem;">Receta No Encontrada</h2>
            <p style="color: #666; font-size: 1.1rem;"><?php echo htmlspecialchars($error_receta); ?></p>
            <p><a href="recetas.php" class="btn-sm">← Volver al listado de recetas</a></p>
        </div>
        
    <?php else: ?>
        
        <div class="recipe-card">
            
            <!-- ENCABEZADO Y BOTÓN DE FAVORITO -->
            <div class="recipe-header flex justify-between items-start flex-wrap">
                <div class="flex-grow">
                    <h1><?php echo htmlspecialchars($receta['titulo']); ?></h1>
                
                    <?php if (!empty($receta['autor_nombre'])): ?>
                        <p class="recipe-author">
                            <i class="fas fa-user-edit" style="color: var(--theme-green);"></i> 
                            Creada por: <strong><?php echo htmlspecialchars($receta['autor_nombre'] . ' ' . $receta['autor_apellido']); ?></strong>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($is_logged_in): ?>
                <div x-data="{ 
                    isFavorite: <?= $is_favorite ? 'true' : 'false' ?>, 
                    loading: false, 
                    message: '' 
                }" class="mt-4 md:mt-2 ml-0 md:ml-4 flex-shrink-0 w-full sm:w-auto">
                    <button 
                        @click="
                            loading = true;
                            message = '';
                            fetch('toggle_favorito.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'receta_id=<?= $receta_id ?>'
                            })
                            .then(response => response.json())
                            .then(data => {
                                loading = false;
                                if(data.success) {
                                    isFavorite = data.is_favorite;
                                    message = data.message;
                                    setTimeout(() => message = '', 3000); 
                                } else {
                                    message = data.message || 'Error desconocido al actualizar favorito.';
                                }
                            })
                            .catch(error => {
                                loading = false;
                                message = 'Error de red. Intente más tarde.';
                                console.error('Error:', error);
                            });
                        "
                        :disabled="loading"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-full font-bold text-sm transition-all duration-300 shadow-lg flex items-center justify-center 
                               "
                        :class="{ 
                            'bg-[var(--secondary)] text-white hover:bg-[#3aa69e]': !isFavorite, 
                            'bg-yellow-500 text-white hover:bg-yellow-600': isFavorite,
                            'opacity-50 cursor-not-allowed': loading 
                        }"
                    >
                        <i class="fas mr-2" :class="{ 'fa-heart': isFavorite, 'fa-regular fa-heart': !isFavorite, 'fa-spinner fa-spin': loading }"></i>
                        <span x-text="loading ? 'Procesando...' : (isFavorite ? 'Quitar de Favoritos' : 'Guardar Receta')">Guardar Receta</span>
                    </button>
                    <div x-show="message" x-text="message" x-transition.duration.500ms
                         :class="{ 'text-primary-accent': message.includes('Error'), 'text-theme-green': message.includes('agregada') || message.includes('eliminada') }"
                         class="text-center mt-2 text-xs font-semibold">
                    </div>
                </div>
                <?php else: ?>
                     <a href="login.php" class="w-full sm:w-auto px-5 py-2.5 rounded-full font-bold text-sm transition-all duration-300 shadow-lg flex items-center justify-center bg-gray-400 text-white hover:bg-gray-500">
                        <i class="fas fa-lock mr-2"></i> Inicia Sesión para Guardar
                     </a>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-6">
                
                <div class="lg:col-span-2">
                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                         onerror="this.onerror=null;this.src='https://via.placeholder.com/700x350/69A64A/ffffff?text=<?php echo urlencode($receta['titulo']); ?>';" 
                         class="recipe-image"  
                         alt="Receta de <?php echo htmlspecialchars($receta['titulo']); ?>">
                         
                    <h2 class="subsection-header"><i class="fas fa-book-open"></i> Sobre esta Receta</h2>
                    <p style="font-size: 1.05rem; line-height: 1.7;">
                        <?php echo nl2br(htmlspecialchars($receta['descripcion'])); ?>
                    </p>
                </div>
                <div class="lg:col-span-1">
                    <div class="ingredients-box">
                        <h3><i class="fas fa-list-check"></i> Ingredientes</h3>
                        <ul>
                        <?php 
                        $ingredientes_texto = $receta['ingredientes'] ?? "No especificados.";
                        $ingredientes_array = array_filter(explode("\n", $ingredientes_texto));
                        if (empty($ingredientes_array)) {
                             echo '<li><i class="fas fa-exclamation-circle"></i> No hay ingredientes definidos.</li>';
                        } else {
                            foreach ($ingredientes_array as $ing) {
                                $ing = trim($ing);
                                if ($ing) {
                                    echo '<li><i class="fas fa-utensil-spoon"></i><span>' . htmlspecialchars($ing) . '</span></li>';
                                }
                            }
                        }
                        ?>
                        </ul>
                    </div>
                </div>
            </div>

            <h2 class="subsection-header mt-10"><i class="fas fa-kitchen-set"></i> Pasos para Preparar</h2>
            <ol class="procedure-list">
            <?php                 
            $pasos_texto = $receta['procedimiento'] ?? "Pasos no especificados."; 
            $pasos_array = array_filter(explode("\n", $pasos_texto));
            if (empty($pasos_array)) {
                echo '<li style="border-left: 5px solid #ccc; background-color: #f0f0f0;">No hay pasos de procedimiento definidos.</li>';
            } else {
                foreach ($pasos_array as $paso) {
                    $paso = trim($paso);
                    if ($paso) {
                        echo '<li>' . nl2br(htmlspecialchars($paso)) . '</li>';
                    }
                }
            }
            ?>
            </ol>
        </div> 
    <?php endif; ?>
</main> 
    <footer>
        <p>
            &copy; <?php echo date('Y'); ?> ChefEnCuna — Todos los derechos reservados.
            <br>
            <a href="sobre_nosotros.php">Sobre Nosotros</a>
        </p>
    </footer>
</body>
</html>