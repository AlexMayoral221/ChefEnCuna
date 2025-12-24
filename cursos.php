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
} else {
    $session_error = "Error Crítico: No se pudo establecer la conexión a la base de datos (bd.php).";
}

$cursos = [];
$error = '';
$pageTitle = 'Todos los Cursos de Cocina';

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT c.id, c.titulo, c.descripcion, c.nivel, c.duracion, c.fecha_creacion, c.imagen_url,
                             u.nombre as instructor_nombre 
                             FROM cursos c 
                             LEFT JOIN usuarios u ON c.instructor_id = u.id 
                             ORDER BY c.fecha_creacion DESC");
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = "Error al cargar los cursos: " . $e->getMessage();
    }
}

$niveles = [];
foreach ($cursos as $curso) {
    $nivel = $curso['nivel'] ?? 'General';
    if (!in_array($nivel, $niveles)) {
        $niveles[] = $nivel;
    }
}
sort($niveles);

$initialFilter = 'Todas'; 

require 'config/header.php'; 
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
            --dark: #2c3e50;
            --light: #f7f1e3;
            --theme-green: #69A64A;
            --shadow-clean: rgba(44, 62, 80, 0.1);
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
        .container {
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
            flex-grow: 1; 
            padding-top: 40px;
        }
        h2.section-title { 
            text-align: center; 
            margin-top: 0; 
            margin-bottom: 20px; 
            color: var(--dark);
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: -1px;
        }
        h2.section-title span {
            color: var(--theme-green);
        }
        .filter-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
            gap: 10px;
        }
        .filter-buttons button, .filter-buttons a {
            background-color: var(--primary);
            color: var(--dark);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            border: 2px solid #ddd;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }
        .filter-buttons button:hover, .filter-buttons a:hover {
            background-color: #f7f7f7;
            border-color: var(--theme-green);
        }
        .filter-buttons .active {
            background-color: var(--theme-green);
            color: var(--primary);
            border-color: var(--theme-green);
            transform: translateY(-2px);
            box-shadow: 0 3px 5px rgba(105, 166, 74, 0.4);
        }
        .grid { 
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        @media (max-width: 1024px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 640px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        .card { 
            background-color: var(--primary);
            border-radius: 12px; 
            overflow: hidden; 
            transition: transform 0.3s ease, box-shadow 0.3s, opacity 0.4s; 
            box-shadow: 0 8px 15px var(--shadow-clean); 
            display: flex; 
            flex-direction: column;
            border-top: 4px solid var(--theme-green); 
        }
        .card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 25px rgba(44, 62, 80, 0.2); 
        }
        .card-img { 
            height: 220px; 
            object-fit: cover; 
            width: 100%; 
            background: #ecf0f1; 
        } 
        .card-body { 
            padding: 20px; 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column;
        }
        .card-category { 
             display: inline-block;
             background-color: var(--secondary);
             color: white;
             padding: 0.2rem 0.6rem;
             border-radius: 4px;
             font-size: 0.75rem;
             font-weight: 700;
             margin-bottom: 10px;
        }
        .card-title { 
            margin: 0 0 10px 0; 
            font-size: 1.5rem; 
            color: var(--dark); 
            font-weight: 700;
            line-height: 1.2;
        }
        .card-text { 
            font-size: 0.95rem; 
            color: #7f8c8d; 
            line-height: 1.5; 
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;  
            overflow: hidden;
        }
        .card-footer { 
            padding: 15px 20px; 
            border-top: 1px dashed #ecf0f1; 
            display: flex; 
            justify-content: space-between;
            align-items: center; 
        }
        .card-footer .meta-info {
             font-size: 0.8rem;
             color: #95a5a6;
        }
        .card-footer a.btn-read {
            background-color: var(--theme-green);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 50px; 
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.2s, transform 0.1s;
        }
        .card-footer a.btn-read:hover {
            background-color: #588e43; 
            transform: translateY(-1px);
        }
        @media (max-width: 768px) {
            
            .filter-buttons { margin-bottom: 20px; }
            .grid { gap: 20px; }
        }
        footer { background: var(--dark); color: white; text-align: center; padding: 20px; margin-top: 50px; }
        footer a { color: var(--secondary); text-decoration: none; margin: 0 10px; }
        footer a:hover { text-decoration: underline; }        
    </style>
</head>
<body x-data="{ currentFilter: '<?= htmlspecialchars($initialFilter, ENT_QUOTES) ?>' }">



<main class="container" style="min-height: 50vh;">
    <h2 class="section-title">Explora Nuestros <span>Cursos</span></h2>
    
    <?php if ($error || $session_error): ?>
        <div class="text-error bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center mb-8">
            Error al cargar datos. <br>
            <small><?php echo htmlspecialchars($error . " " . $session_error); ?></small>
        </div>
    <?php endif; ?>

    <div class="filter-buttons" role="tablist" aria-label="Filtrar por nivel de curso">
        <button :class="{'active': currentFilter === 'Todas'}" @click="currentFilter = 'Todas'">Todos los Niveles</button>

        <?php foreach($niveles as $nivel): ?>
            <?php $safeNivel = htmlspecialchars($nivel, ENT_QUOTES); ?>
            <button :class="{'active': currentFilter === '<?= $safeNivel ?>'}" @click="currentFilter = '<?= $safeNivel ?>'"><?= htmlspecialchars($nivel) ?></button>
        <?php endforeach; ?>
    </div>

    <?php if(empty($cursos)): ?>
        <div style="text-align:center; padding: 60px; background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
            <i class="fas fa-book-open" style="font-size: 3.5rem; color: #ccc; margin-bottom: 20px;"></i>
            <p style="font-size: 1.2rem; color: #555;">¡Ups! Actualmente no hay cursos disponibles en la base de datos.</p>
        </div>
    <?php else: ?>

        <div class="grid" aria-live="polite">
            <?php foreach($cursos as $curso): 
                $safe_id = htmlspecialchars($curso['id']);
                $safe_titulo = htmlspecialchars($curso['titulo']);
                $safe_instructor = htmlspecialchars($curso['instructor_nombre'] ?? 'Instructor no asignado');
                $nivelReceta = $curso['nivel'] ?? 'General';
                $safeNivel = htmlspecialchars($nivelReceta, ENT_QUOTES);
                $desc = htmlspecialchars($curso['descripcion']);
                $duracion = htmlspecialchars($curso['duracion'] ?? 'N/D');
                $fecha = isset($curso['fecha_creacion']) ? date('d/m/Y', strtotime($curso['fecha_creacion'])) : 'N/D';
                
                $imagePath = htmlspecialchars($curso['imagen_url'] ?? ''); 

                $placeholderUrl = "https://via.placeholder.com/400x220/4ecdc4/ffffff?text=Curso+de+Nivel+" . rawurlencode($nivelReceta);
            ?>
                <div class="card"
                     data-nivel="<?= $safeNivel ?>"
                     x-show="currentFilter === 'Todas' || currentFilter === '<?= $safeNivel ?>'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-90"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-90">

                    <img src="<?= $imagePath ?>" onerror="this.onerror=null;this.src='<?= $placeholderUrl ?>';" class="card-img" alt="Curso de <?= $safe_titulo ?>">
                    <div class="card-body">
                        <span class="card-category"><?= $safeNivel ?></span> 
                        <h3 class="card-title"><?= $safe_titulo ?></h3>
                        <p class="card-text">
                            <?= $desc ?>
                        </p>
                    </div>

                    <div class="card-footer">
                        <div class="meta-info">
                            <i class="fas fa-chalkboard-teacher"></i> Instructor: <?= $safe_instructor ?>
                            <br>
                            <i class="fas fa-clock"></i> Duración: <?= $duracion ?>
                        </div>
                        <a href="ver_curso.php?id=<?= $safe_id ?>" class="btn-read flex items-center">
                            Ver<i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
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