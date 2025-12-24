<?php
session_start();
require 'config/bd.php'; 

$pdo = $pdo ?? null; 
$session_error = '';
$dashboard_url = "perfil.php"; 
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : null;

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
                if ($is_logged_in && $user_id !== null) {
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

if ($receta) {
    $image_path = !empty($receta['imagen_ruta']) 
        ? htmlspecialchars($receta['imagen_ruta']) 
        : 'img/recetas/' . htmlspecialchars($receta['id']) . '.jpg';
} else {
    $image_path = 'https://via.placeholder.com/1200x400/69A64A/ffffff?text=Receta+No+Disponible';
}

$pageTitle = $receta ? htmlspecialchars($receta['titulo']) : 'Receta No Encontrada';

require 'config/header.php'; 
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ffffff;     
            --secondary: #4ecdc4;      
            --dark: #2d3436;            
            --light: #f7f1e3;           
            --theme-green: #69A64A;     
            --primary-accent: #ff6b6b;  
            --shadow: rgba(0,0,0,0.08); 
            --shadow-strong: rgba(0,0,0,0.15);
        }
        body { 
            font-family: 'Inter', sans-serif;
            color: var(--dark); 
            background-color: var(--light); /* Fondo crema mantenido */
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex; 
            flex-direction: column; 
            line-height: 1.6;
        }
        h1, h2, h3 {
            font-family: 'Playfair Display', serif; /* Tipo de letra más elegante para títulos */
            color: var(--dark);
            margin-top: 0;
            margin-bottom: 0.5em;
        }
        .container {
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 25px; 
            flex-grow: 1; 
            padding-top: 20px; 
            padding-bottom: 60px; 
        }
        .recipe-banner {
            width: 100%;
            height: 450px; 
            object-fit: cover;
            border-radius: 20px; 
            box-shadow: 0 10px 30px var(--shadow-strong); 
            margin-bottom: 30px; 
            transition: transform 0.3s ease; 
        }
        .recipe-banner:hover {
            transform: scale(1.005);
        }
        .recipe-card-container {
            background-color: var(--primary);
            border-radius: 20px; 
            box-shadow: 0 15px 40px var(--shadow); 
            padding: 30px; 
        }
        .section-header {
            font-size: 2.2rem; 
            color: var(--theme-green);
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 700;
            border-left: 5px solid var(--secondary);
            padding-left: 15px;
            display: flex;
            align-items: center;
        }
        .section-header .fas {
            color: var(--secondary);
            margin-right: 10px;
            font-size: 1.8rem;
        }
        .ingredients-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .ingredients-list li {
            padding: 10px 0; 
            color: #444;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            border-bottom: 1px dashed #e0e0e0; 
        }
        .ingredients-list li:last-child {
            border-bottom: none;
        }
        .ingredients-list .fas {
             color: var(--theme-green); 
             margin-right: 15px;
             font-size: 1.1rem;
        }
        .procedure-step {
            display: flex;
            margin-bottom: 25px;
            align-items: flex-start;
        }
        .step-circle {
            background-color: var(--secondary);
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            color: white;
            font-weight: 700;
            font-size: 1.4rem;
            margin-right: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
        }
        .procedure-step:hover .step-circle {
            transform: scale(1.05);
            background-color: var(--theme-green);
        }
        .step-content {
            padding: 10px;
            background-color: #fafafa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            flex-grow: 1;
            line-height: 1.7;
            font-size: 1.05rem;
        }
        .favorite-button {
            transition: all 0.3s ease;
            font-size: 1rem; 
            padding: 10px 20px; 
            font-weight: 700;
            border-radius: 50px;
        }
        .favorite-button.not-favorite {
            background-color: var(--secondary);
            color: white;
        }
        .favorite-button.not-favorite:hover {
            background-color: #3aa69e; 
        }
        .favorite-button.is-favorite {
            background-color: var(--primary-accent); 
            color: white;
        }
        .favorite-button.is-favorite:hover {
            background-color: #e65252; 
        }
        footer { 
            background: var(--dark); 
            color: white; 
            text-align: center; 
            padding: 25px; 
            margin-top: 60px; 
            font-size: 0.95rem;
        }
        footer a { 
            color: var(--secondary); 
            text-decoration: none; 
            margin: 0 10px; 
            transition: color 0.2s ease;
        }
        footer a:hover { 
            color: #7ff0e7; 
            text-decoration: underline; 
        }
        @media (max-width: 768px) {
            .recipe-banner {
                height: 250px;
                border-radius: 0; 
                box-shadow: none;
                margin-bottom: 20px;
            }
            .recipe-card-container {
                padding: 20px;
            }
            .section-header {
                font-size: 1.8rem;
                justify-content: center;
                border-left: none;
                border-bottom: 3px solid var(--secondary);
                padding-bottom: 5px;
                padding-left: 0;
            }
            .section-header .fas {
                margin-right: 8px;
            }
            .step-circle {
                width: 35px;
                height: 35px;
                line-height: 35px;
                font-size: 1.2rem;
                margin-right: 15px;
            }
        }
    </style>
</head>
<body>
<main class="container">
    <?php if ($error_receta || !$receta): ?>
        
        <div class="recipe-card-container p-10 text-center">
            <h2 class="text-4xl font-extrabold text-dark mb-4">¡Oops! Receta Perdida</h2>
            <p class="text-lg text-gray-600 mb-8"><?php echo htmlspecialchars($error_receta); ?></p>
            <p><a href="recetas.php" class="px-8 py-3 bg-theme-green text-white rounded-full hover:bg-opacity-90 transition-colors inline-flex items-center text-xl font-semibold shadow-lg">
                <i class="fas fa-arrow-left mr-3"></i> Explorar Otras Recetas
            </a></p>
        </div>
        
    <?php else: ?>

        <img src="<?php echo $image_path; ?>" onerror="this.onerror=null;this.src='https://via.placeholder.com/1200x450/69A64A/ffffff?text=<?php echo urlencode($receta['titulo']); ?>';" class="recipe-banner" alt="Imagen de la receta: <?php echo htmlspecialchars($receta['titulo']); ?>">
        <div class="recipe-card-container">
            <div class="mb-8 pb-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-start mb-3">
                    <div class="w-full md:w-3/4">
                         <h1 class="text-5xl lg:text-6xl font-extrabold text-theme-green leading-tight mb-2">
                             <?php echo htmlspecialchars($receta['titulo']); ?>
                         </h1>
                         <?php if (!empty($receta['autor_nombre'])): ?>
                             <p class="text-xl text-gray-600 flex items-center">
                                 <i class="fas fa-signature text-secondary mr-3"></i> 
                                 Por: <strong> <?php echo htmlspecialchars($receta['autor_nombre'] . ' ' . $receta['autor_apellido']); ?></strong>
                             </p>
                         <?php endif; ?>
                    </div>
                    
                    <?php if ($is_logged_in): ?>
                    <div x-data="{ 
                        isFavorite: <?= $is_favorite ? 'true' : 'false' ?>, 
                        loading: false, 
                        message: '' 
                    }" class="w-full md:w-1/4 mt-4 md:mt-0 flex justify-start md:justify-end">
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
                            class="favorite-button w-full lg:w-auto px-4 py-2 font-bold transition-all duration-300 shadow-md flex items-center justify-center"
                            :class="{ 
                                'not-favorite': !isFavorite, 
                                'is-favorite': isFavorite,
                                'opacity-60 cursor-not-allowed': loading 
                            }">

                            <i class="fas mr-2" :class="{ 'fa-heart': isFavorite, 'fa-regular fa-heart': !isFavorite, 'fa-spinner fa-spin': loading }"></i>
                            <span x-text="loading ? 'Procesando...' : (isFavorite ? 'Quitar de Favoritos' : 'Guardar Receta')">Guardar Receta</span>
                        </button>
                        <div x-show="message" x-text="message" x-transition.duration.500ms :class="{ 'text-primary-accent': message.includes('Error'), 'text-theme-green': message.includes('agregada') || message.includes('eliminada') }" class="absolute text-center mt-2 text-sm font-semibold w-full md:w-auto md:right-0">
                        </div>
                    </div>
                    <?php else: ?>
                         <a href="login.php" class="w-full md:w-1/4 mt-4 md:mt-0 px-4 py-2 rounded-full font-bold text-sm transition-all duration-300 shadow-md flex items-center justify-center bg-gray-400 text-white hover:bg-gray-500">
                            <i class="fas fa-lock mr-2"></i> Inicia Sesión
                         </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2"> 
                    <h2 class="section-header"><i class="fas fa-info-circle"></i> Sobre esta Receta</h2>
                    <p class="text-gray-700 text-lg mb-8 leading-relaxed p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <?php echo nl2br(htmlspecialchars($receta['descripcion'])); ?>
                    </p>

                    <h2 class="section-header mt-8"><i class="fas fa-route"></i> Pasos de Preparación</h2>
                    <div class="mt-6">
                        <?php                 
                        $pasos_texto = $receta['procedimiento'] ?? ""; 
                        $pasos_array = array_filter(array_map('trim', explode("\n", $pasos_texto)));
                        $step_counter = 1; 
                        
                        if (empty($pasos_array)) {
                            echo '<div class="text-center p-8 bg-gray-100 rounded-xl text-gray-600">No hay pasos de procedimiento definidos para esta receta.</div>';
                        } else {
                            foreach ($pasos_array as $paso) {
                                echo '<div class="procedure-step">';
                                echo '<div class="step-circle">';
                                echo $step_counter;
                                echo '</div>';
                                echo '<div class="step-content">';
                                echo nl2br(htmlspecialchars($paso));
                                echo '</div>';
                                echo '</div>';
                                $step_counter++; 
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <h2 class="section-header"><i class="fas fa-basket-shopping"></i> Ingredientes</h2>
                    <ul class="ingredients-list bg-gray-50 p-6 rounded-lg shadow-inner">
                    <?php 
                    $ingredientes_texto = $receta['ingredientes'] ?? "";
                    $ingredientes_array = array_filter(array_map('trim', explode("\n", $ingredientes_texto)));
                    if (empty($ingredientes_array)) {
                         echo '<li><i class="fas fa-exclamation-circle"></i> No hay ingredientes definidos.</li>';
                    } else {
                        foreach ($ingredientes_array as $ing) {
                            echo '<li><i class="fas fa-caret-right"></i><span>' . htmlspecialchars($ing) . '</span></li>';
                        }
                    }
                    ?>
                    </ul>
                </div>
            </div>
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