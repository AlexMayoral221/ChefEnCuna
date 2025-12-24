<?php
session_start();

require 'config/bd.php'; 

$pdo = $pdo ?? null; 
$session_error = '';
$dashboard_url = "perfil.php"; 

if (!isset($pdo) || !$pdo) {
    $pdo = null; 
    $error = "Advertencia: No se pudo establecer la conexión a la base de datos.";
}

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
$error = $session_error; 
$curso_id = null;

if (!$pdo && !$session_error) {
     $error = "Error: La conexión a la base de datos no está disponible.";
} else if (isset($_GET['id']) && is_numeric($_GET['id'])) {
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
    } 
} else {
    $error = "ID de curso no válido o faltante en la URL.";
}

$pageTitle = $curso ? htmlspecialchars($curso['titulo']) : 'Curso No Encontrado';

require 'config/header.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | ChefEnCuna</title>    
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
        .detail-box ul li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #f0f0f0;
            font-size: 0.95rem;
        }
        .detail-box ul li:last-child {
            border-bottom: none;
        }
        .detail-box h3 {
            color: var(--theme-green);
            font-size: 1.25rem;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .list-section h3 {
            font-size: 1.5rem;
            color: var(--theme-green);
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 5px;
        }
        .list-section ul {
            list-style-type: none;
            padding-left: 0;
        }
        .list-section ul li {
            background-color: var(--light);
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            border-left: 5px solid var(--secondary);
            line-height: 1.4;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .list-section p {
            padding-left: 10px;
            font-style: italic;
            color: #555;
        }
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
        @media (max-width: 768px) {
        .course-layout { grid-template-columns: 1fr; }
        .course-sidebar { position: static; margin-top: 30px; }
        }
    </style>
</head>
<body>
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
            $course_title_encoded = urlencode(htmlspecialchars($curso['titulo']));
            $course_image_url_db = htmlspecialchars($curso['imagen_url'] ?? ''); 
            $final_image_source = !empty($course_image_url_db) ? $course_image_url_db : "img/cursos/{$curso['id']}.jpg";
            $fallback_placeholder = "https://via.placeholder.com/800x450/4ecdc4/ffffff?text={$course_title_encoded}";?>

            <div style="text-align: center; border-bottom: 2px solid #eee; margin-bottom: 30px; padding-bottom: 20px;">
                <h1 style="font-size: 2.5rem; margin-bottom: 5px; color: var(--theme-green);"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
            </div>
            
            <div class="course-layout">
                <div class="course-info">
                <img src="<?php echo $final_image_source; ?>" onerror="this.onerror=null;this.src='<?php echo $fallback_placeholder; ?>';" alt="Imagen del Curso" class="course-image">
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
                                <img src="<?php echo htmlspecialchars($curso['instructor_foto'] ?? 'https://via.placeholder.com/100/4ecdc4/ffffff?text=Chef'); ?>" onerror="this.onerror=null;this.src='https://via.placeholder.com/100/4ecdc4/ffffff?text=Chef';" class="maestro-img mx-auto mb-3" alt="Foto del Instructor">
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