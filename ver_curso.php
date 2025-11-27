<?php
session_start();
require 'config/bd.php'; 

$pdo = $pdo ?? null; 
$session_error = '';
$dashboard_url = "perfil.php"; 

if ($pdo) {
    if (isset($_SESSION['user_id']) && (!isset($_SESSION['user_nombre']) || !isset($_SESSION['user_rol']))) {
        try {
            $stmt = $pdo->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
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
}

$curso = null;
$error = '';
$curso_id = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $curso_id = (int)$_GET['id'];

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.*, 
                    u.nombre AS instructor_nombre, 
                    u.apellido AS instructor_apellido,
                    u.foto_perfil AS instructor_foto,
                    u.id AS instructor_id
                FROM cursos c 
                LEFT JOIN usuarios u ON c.instructor_id = u.id 
                WHERE c.id = :id
            ");
            $stmt->execute([':id' => $curso_id]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$curso) {
                $error = "El curso solicitado (ID: " . $curso_id . ") no existe o fue eliminado.";
            }
        } catch (PDOException $e) {
            $error = "Error al ejecutar la consulta: " . $e->getMessage();
        }
    } else {
        $error = "Error: La conexión a la base de datos no está disponible.";
    }
} else {
    $error = "ID de curso no válido o faltante en la URL.";
}

$pageTitle = $curso ? htmlspecialchars($curso['titulo']) : 'Curso No Encontrado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>    
    <script src="https://cdn.tailwindcss.com"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="//unpkg.com/alpinejs" defer></script> 

    <style>
        :root {
            --primary: #ffffff;
            --secondary: #4ecdc4;
            --dark: #2d3436;
            --light: #f7f1e3;
            --theme-green: #69A64A;
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
        .app-header { 
            background: var(--theme-green);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
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
            margin: 0 auto;
            padding: 1rem 3rem; 
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
            white-space: nowrap; 
        }
        .app-nav a:hover { color: var(--secondary); }
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
        .btn-profile { background-color: var(--secondary); color: white; border-radius: 9999px; padding: 0.5rem 1rem; font-weight:600; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; flex-grow: 1; padding-top: 40px; }
        h2.section-title { text-align: center; margin-top: 0; margin-bottom: 40px; color: var(--dark); position: relative; }
        h2.section-title::after { content: ''; display: block; width: 50px; height: 3px; background: var(--theme-green); margin: 10px auto; }
        .btn-sm { text-decoration: none; background-color: var(--secondary); color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: bold; font-size: 0.9rem; transition: background-color 0.2s; }
        .btn-sm:hover { background-color: #3aa69e; }
        .course-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; margin-top: 30px; }
        .course-info { background-color: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .course-image { width: 100%; max-height: 450px; object-fit: cover; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .course-sidebar { position: sticky; top: 100px; height: fit-content; background-color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        footer { background: var(--dark); color: white; text-align: center; padding: 20px; margin-top: 50px; }
        footer a { color: var(--secondary); text-decoration: none; margin: 0 10px; }
        footer a:hover { text-decoration: underline; }   
        .detail-box { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .maestro-info {
            padding: 15px;
            border: 2px dashed var(--secondary);
            border-radius: 8px;
            margin-top: 20px;
            background: white;
        }
        .maestro-img {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--secondary);
        }
        footer { background: var(--dark); color: white; text-align: center; padding: 20px; margin-top: 50px; }
        /* Clases de modal eliminadas */

        @media (max-width: 768px) {
            .header-content { flex-direction: column; padding: 1rem; }
            .app-nav { margin-top: 1rem; display:flex; flex-wrap:wrap; justify-content:center; }
            .course-layout { grid-template-columns: 1fr; }
            .course-sidebar { position: static; margin-top: 30px; }
            /* Estilos de modal eliminados */
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

                    <button @click="open = !open" class="btn-profile flex items-center">
                        <i class="fas fa-user mr-2"></i> <?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario') ?>
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
                    <button @click="open = !open" class="btn-access-style flex items-center">
                        <i class="fas fa-user-circle mr-2"></i> Acceso
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">
                        <a href="login.php" class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                        </a>
                        <a href="registro.php" class="block px-4 py-2 text-sm text-white hover:bg-green-700 border-t border-white/25">
                            <i class="fas fa-user-plus mr-2"></i> Registrarse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

    <div class="container" style="padding-top: 50px; min-height: 70vh;">
        
        <?php if ($error || !$curso): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ff6347; margin-bottom: 15px;"></i>
                <h2 style="color: var(--dark); margin-top: 0;">Curso No Encontrado</h2>
                <p style="color: #666;"><?php echo htmlspecialchars($error); ?></p>
                <p><a href="cursos.php" style="color: var(--secondary); font-weight:bold; text-decoration: none;">← Volver al listado de cursos</a></p>
            </div>
        <?php else: 
            $instructor_nombre = htmlspecialchars($curso['instructor_nombre'] ?? '');
            $instructor_apellido = htmlspecialchars($curso['instructor_apellido'] ?? '');
            $instructor_completo = trim($instructor_nombre . ' ' . $instructor_apellido);
            $instructor_display = $instructor_completo ?: 'Sin instructor asignado';
        ?>
            
            <div style="text-align: center; border-bottom: 2px solid #eee; margin-bottom: 30px; padding-bottom: 20px;">
                <h1 style="font-size: 2.5rem; margin-bottom: 5px; color: var(--theme-green);"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
            </div>
            
            <div class="course-layout">
                
                <div class="course-info">
                    <img src="img/cursos/<?php echo htmlspecialchars($curso['id']); ?>.jpg" 
                         onerror="this.onerror=null;this.src='https://via.placeholder.com/800x450/4ecdc4/ffffff?text=<?php echo urlencode($curso['titulo']); ?>';"
                         alt="Imagen del Curso"
                         class="course-image">
                    
                    <h2 style="margin-top: 30px; color: var(--dark);">Acerca de este Curso</h2>
                    <p style="font-size: 1.1rem; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($curso['descripcion'])); ?>
                    </p>

                    <div class="list-section">
                        <h3><i class="fas fa-bullseye"></i> Objetivos del Curso</h3>
                        <div class="list-content">
                            <?php 
                            $objetivos = $curso['objetivos'] ?? "No especificados.";
                            if (strpos($objetivos, "\n") !== false) {
                                $objetivos_array = explode("\n", $objetivos);
                                echo '<ul>';
                                foreach ($objetivos_array as $obj) {
                                    $obj = trim($obj);
                                    if ($obj) {
                                        echo '<li>' . htmlspecialchars($obj) . '</li>';
                                    }
                                }
                                echo '</ul>';
                            } else {
                                echo '<p>' . htmlspecialchars($objetivos) . '</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="list-section">
                        <h3><i class="fas fa-clipboard-check"></i> Requisitos</h3>
                        <div class="list-content">
                            <?php 
                            $requisitos = $curso['requisitos'] ?? "Ninguno en específico.";
                            if (strpos($requisitos, "\n") !== false) {
                                $requisitos_array = explode("\n", $requisitos);
                                echo '<ul>';
                                foreach ($requisitos_array as $req) {
                                    $req = trim($req);
                                    if ($req) {
                                        echo '<li>' . htmlspecialchars($req) . '</li>';
                                    }
                                }
                                echo '</ul>';
                            } else {
                                echo '<p>' . htmlspecialchars($requisitos) . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="course-sidebar">
                    
                    <div class="detail-box">
                        <h3>Detalles Clave</h3>
                        <ul>
                            <li>
                                <span><i class="fas fa-layer-group"></i> Nivel:</span> 
                                <span><?php echo htmlspecialchars($curso['nivel'] ?? 'Principiante'); ?></span>
                            </li>
                            <li>
                                <span><i class="far fa-clock"></i> Duración:</span> 
                                <span><?php echo htmlspecialchars($curso['duracion'] ?? 'N/D'); ?></span>
                            </li>
                            <li>
                                <span><i class="fas fa-calendar-alt"></i> Publicado:</span> 
                                <span><?php echo date('d/m/Y', strtotime($curso['fecha_creacion'] ?? date('Y-m-d'))); ?></span>
                            </li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                         <a href="inscripcion.php?curso_id=<?php echo $curso_id; ?>" class="btn-sm" style="display: block; width: 100%; font-size: 1.1rem; padding: 1rem 0; border-radius: 9999px;">
                             <i class="fas fa-cart-plus"></i> Inscribirse Ahora
                         </a>
                         <p style="margin-top: 10px; font-weight: bold; font-size: 1.5rem; color: var(--theme-green);">
                             $<?php echo htmlspecialchars(number_format($curso['precio'] ?? 0, 2)); ?>
                         </p>
                    </div>

                    <?php if ($instructor_completo): ?>
                        <div class="maestro-info text-center">
                            <h3 style="text-align: center;"><b>El Maestro:</b></h3><br>
                            <img 
                                src="<?php echo htmlspecialchars($curso['instructor_foto'] ?? 'https://via.placeholder.com/100/4ecdc4/ffffff?text=Chef'); ?>" 
                                onerror="this.onerror=null;this.src='https://via.placeholder.com/100/4ecdc4/ffffff?text=Chef';"
                                class="maestro-img mx-auto mb-3"
                                alt="Foto del Instructor"
                            >

                            <p class="text-lg font-bold mb-1">
                                <?php echo $instructor_completo; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="detail-box" style="margin-top: 40px;">
                            <h3><i class="fas fa-user-times"></i> Instructor</h3>
                            <p class="text-center" style="font-style: italic;">Pendiente de asignación.</p>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        <?php endif; ?>
    </div>
    <footer>
        <p>
            &copy; <?php echo date('Y'); ?> ChefEnCuna — Todos los derechos reservados.
            <br>
            <a href="sobre_nosotros.php">Sobre Nosotros</a>
        </p>
    </footer>
</body>
</html>