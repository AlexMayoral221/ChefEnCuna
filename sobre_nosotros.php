<?php
session_start();

require 'config/bd.php'; 
require 'config/header.php'; 

$pdo = $pdo ?? null; 
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
             error_log("Error al cargar datos de usuario en sesión: " . $e->getMessage());
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
    $equipo = [];
    try {
        $stmt_equipo = $pdo->prepare("SELECT id, nombre, apellido, rol, foto_perfil FROM usuarios WHERE rol = 'maestro' ORDER BY nombre ASC LIMIT 3");
        $stmt_equipo->execute();
        $equipo = $stmt_equipo->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener equipo de la base de datos: " . $e->getMessage());
    }
} else {
    $equipo = [];
}

$pageTitle = 'Sobre Nosotros';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#ff6b6b',
                        'secondary': '#4ecdc4',
                        'brand-green': '#69A64A', 
                        'dark': '#2d3436',    
                    },
                }
            }
        }
    </script>
    <style>
        :root { 
            --primary-color: #ff6b6b;
            --secondary-color: #4ecdc4;
            --dark: #2d3436; 
            --light: #f7f1e3; 
            --theme-green: #69A64A; 
        }
        .text-primary { 
            color: var(--primary-color); 
        }
        .bg-primary { 
            background-color: var(--primary-color); 
        }
        .text-secondary { 
            color: var(--secondary-color); 
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
        .section-title::after { 
            content: ''; 
            display: block; 
            width: 50px; 
            height: 3px; 
            background: var(--primary-color); 
            margin: 10px auto 0; 
        }
        .subsection-header {
             border-left: 5px solid var(--secondary-color);
        }
    </style>
</head>
<body>

<main class="container mx-auto max-w-[1000px] px-4 py-8 lg:py-12 flex-grow">
    <div class="text-center mb-10">
        <img src="img/logo.png" alt="Logo de ChefEnCuna" class="mx-auto max-w-[180px] h-auto rounded-xl shadow-lg mb-4" onerror="this.onerror=null;this.src='https://placehold.co/180x180/ff6b6b/ffffff?text=LOGO'">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-primary inline-block leading-tight">ChefEnCuna</h1>
        <p class="text-lg text-gray-600 mt-2">Tu hogar digital para la cocina.</p>
    </div>
    
    <h2 class="section-title text-3xl font-bold mb-10 text-dark">✨ Conoce Nuestra Historia</h2>
    
    <section class="mb-12 pb-8 border-b border-gray-200">
        <h3 class="text-2xl font-semibold text-primary mb-4 subsection-header pl-3">
            <i class="fas fa-bullseye mr-2 text-secondary"></i> Misión y Visión
        </h3>
        <div class="flex flex-wrap -mx-4">
            <div class="w-full md:w-1/2 px-4 mb-6">
                <h4 class="text-xl font-bold text-dark mb-2">Nuestra Misión</h4>
                <p class="text-gray-700">ChefEnCuna se dedica a desmitificar el arte de la cocina, haciéndolo accesible para todos. Nuestra plataforma proporciona cursos de cocina en línea, desde lo básico hasta técnicas avanzadas, permitiendo a los entusiastas de todas las edades y niveles de habilidad descubrir el placer de cocinar en casa.</p>
            </div>
            <div class="w-full md:w-1/2 px-4 mb-6">
                <h4 class="text-xl font-bold text-dark mb-2">Nuestra Visión</h4>
                <p class="text-gray-700">Nos proyectamos como líderes en la educación culinaria en línea, creando una comunidad global donde los usuarios no solo aprendan a cocinar, sino que también compartan sus experiencias y creaciones, fomentando una cultura de colaboración y amor por la gastronomía.</p>
            </div>
        </div>
    </section>
    
    <section class="mb-12 pb-8 border-b border-gray-200">
        <h3 class="text-2xl font-semibold text-primary mb-4 subsection-header pl-3">
            <i class="fas fa-history mr-2 text-secondary"></i> Historia
        </h3>
        <p class="text-gray-700">ChefEnCuna nació de la pasión compartida de un pequeño grupo de chefs y educadores por llevar el arte de la cocina a cada rincón del mundo. Identificando una falta de recursos accesibles y asequibles para el aprendizaje culinario, lanzamos nuestra plataforma en 2024, con el objetivo de ofrecer cursos de alta calidad que cualquier persona, independientemente de su experiencia previa, pudiera seguir y disfrutar.</p>
    </section>
    
    <section class="mb-12 pb-8 border-b border-gray-200">
        <h3 class="text-2xl font-semibold text-primary mb-6 subsection-header pl-3">
            <i class="fas fa-star mr-2 text-secondary"></i> Valores
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 bg-white rounded-xl shadow-md text-center border border-gray-100 transition hover:shadow-lg">
                <i class="fas fa-hands-helping text-3xl text-secondary mb-3"></i>
                <h4 class="text-lg font-bold text-dark mb-2">Comunidad</h4>
                <p class="text-sm text-gray-600">Fomentamos un ambiente donde todos pueden compartir, aprender y crecer juntos.</p>
            </div>
            <div class="p-6 bg-white rounded-xl shadow-md text-center border border-gray-100 transition hover:shadow-lg">
                <i class="fas fa-leaf text-3xl text-secondary mb-3"></i>
                <h4 class="text-lg font-bold text-dark mb-2">Sostenibilidad</h4>
                <p class="text-sm text-gray-600">Promovemos prácticas y recetas que respetan el medio ambiente y los productos locales.</p>
            </div>
            <div class="p-6 bg-white rounded-xl shadow-md text-center border border-gray-100 transition hover:shadow-lg">
                <i class="fas fa-lightbulb text-3xl text-secondary mb-3"></i>
                <h4 class="text-lg font-bold text-dark mb-2">Innovación</h4>
                <p class="text-sm text-gray-600">Nos mantenemos al tanto de las últimas tendencias culinarias y tecnológicas para mejorar constantemente nuestra oferta.</p>
            </div>
        </div>
    </section>
    
    <section class="mb-12 pb-8">
        <h3 class="text-2xl font-semibold text-primary mb-6 subsection-header pl-3">
            <i class="fas fa-users mr-2 text-secondary"></i> Nuestro Equipo de Maestros
        </h3>
        <p class="text-gray-700 mb-6">Conoce a los profesionales que lideran nuestros cursos. Ellos comparten su experiencia y pasión por la gastronomía contigo:</p>

        <div class="flex flex-wrap justify-center gap-6"> 
        <?php if (empty($equipo)): ?>
            <div class="text-center w-full p-4 bg-gray-100 rounded-lg text-gray-600">
                <p>No hay instructores (maestros) registrados en la plataforma en este momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($equipo as $miembro): 
                $full_name = htmlspecialchars(trim($miembro['nombre'] . ' ' . $miembro['apellido']));
                
                $initials = '';
                $nombre_parts = explode(' ', trim($miembro['nombre'] ?? ''));
                $apellido_parts = explode(' ', trim($miembro['apellido'] ?? ''));
                
                if (!empty($nombre_parts[0])) $initials .= strtoupper($nombre_parts[0][0]);
                if (!empty($apellido_parts[0])) $initials .= strtoupper($apellido_parts[0][0]);
                
                $placeholder_color = '4ecdc4'; 
                $image_src = $miembro['foto_perfil'] ?? '';
                
                $initials_encoded = urlencode($initials ?: 'U');
                $placeholder_url = "https://placehold.co/100x100/{$placeholder_color}/ffffff?text={$initials_encoded}";

                $error_handler = "this.onerror=null;this.src='{$placeholder_url}'";
                
                $final_image_src = empty($image_src) ? $placeholder_url : htmlspecialchars($image_src);

                $rol_display = "Chef Certificado"; ?>
                <div class="w-full sm:w-[calc(50%-12px)] md:w-[calc(33.33%-16px)] lg:w-1/4 xl:w-1/5 bg-white p-6 rounded-xl shadow-lg text-center transition hover:shadow-xl hover:scale-[1.02] duration-300">
                     <img src="<?= $final_image_src ?>" 
                          alt="<?= $full_name ?>"
                          class="w-24 h-24 rounded-full object-cover border-4 border-primary-color mx-auto mb-4"
                          onerror="<?= $error_handler ?>">
                    <div class="member-info">
                        <h4 class="text-lg font-bold text-primary-color mb-1"><?= $full_name ?></h4>
                        <p class="text-sm font-semibold text-secondary-color mb-2"><?= $rol_display ?></p>
                        <p class="text-xs text-gray-500 italic"><?= $description ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </section>
    
    <section class="pb-8">
        <h3 class="text-2xl font-semibold text-primary mb-4 subsection-header pl-3">
            <i class="fas fa-map-marker-alt mr-2 text-secondary"></i> Contacto
        </h3>
        <p class="text-gray-700 mb-4">Estamos siempre listos para ayudarte. Ponte en contacto con nosotros a través de los siguientes medios:</p>
        <ul class="space-y-2 text-gray-700">
            <li><i class="fas fa-envelope mr-2 text-primary"></i> Correo Electrónico: <a href="mailto:contacto@chefencuna.com" class="text-primary hover:underline">contacto@chefencuna.com</a></li>
            <li><i class="fas fa-phone mr-2 text-primary"></i> Teléfono: +52 6462587741</li>
            <li><i class="fas fa-home mr-2 text-primary"></i> Oficina Principal: En algún lugar de Fondo de Bikini.</li>
        </ul>
    </section>
</main>
</body>
</html>