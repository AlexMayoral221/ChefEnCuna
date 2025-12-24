<?php
session_start();
require 'config/bd.php'; 

function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    $full = false;
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'Hace ' . implode(', ', $string) : 'Justo ahora';
}

$tema_id = $_GET['id'] ?? null;
$tema = null;
$respuestas = [];
$mensaje_status = '';
$es_error = false;

if (!$tema_id || !is_numeric($tema_id)) {
    header('Location: foro_ayuda.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'responder') {
    if (!isset($_SESSION['user_id'])) {
        $mensaje_status = 'Debes iniciar sesión para responder.';
        $es_error = true;
    } else {
        $usuario_id = $_SESSION['user_id'];
        $respuesta_texto = trim($_POST['respuesta_texto'] ?? '');

        if (empty($respuesta_texto)) {
            $mensaje_status = 'La respuesta no puede estar vacía.';
            $es_error = true;
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO foro_respuestas (tema_id, usuario_id, respuesta) 
                    VALUES (:tema_id, :usuario_id, :respuesta)
                ");
                $stmt->execute([
                    ':tema_id' => $tema_id, 
                    ':usuario_id' => $usuario_id, 
                    ':respuesta' => $respuesta_texto
                ]);
                header("Location: tema_detalle.php?id={$tema_id}");
                exit;

            } catch (PDOException $e) {
                $mensaje_status = 'Error de BD al publicar la respuesta: ' . $e->getMessage();
                $es_error = true;
            }
        }
    }
}

try {
    $stmt_tema = $pdo->prepare("
        SELECT t.*, u.nombre as autor_nombre, u.apellido as autor_apellido, u.rol as autor_rol, u.foto_perfil
        FROM foro_temas t
        JOIN usuarios u ON t.alumno_id = u.id
        WHERE t.id = :tema_id
    ");
    $stmt_tema->execute([':tema_id' => $tema_id]);
    $tema = $stmt_tema->fetch(PDO::FETCH_ASSOC);

    if (!$tema) {
        header('Location: foro_ayuda.php');
        exit;
    }

    $stmt_respuestas = $pdo->prepare("
        SELECT r.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, u.rol as usuario_rol, u.foto_perfil
        FROM foro_respuestas r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.tema_id = :tema_id
        ORDER BY r.fecha_respuesta ASC
    ");
    $stmt_respuestas->execute([':tema_id' => $tema_id]);
    $respuestas = $stmt_respuestas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar tema y respuestas: " . $e->getMessage());
    $mensaje_status = 'Error al cargar el tema. Inténtalo de nuevo.';
    $es_error = true;
}

function get_profile_photo($ruta, $rol) {
    if (!empty($ruta) && file_exists($ruta)) {
        return htmlspecialchars($ruta);
    }
    if ($rol === 'maestro') {
        return 'https://placehold.co/100x100/4ecdc4/ffffff?text=M';
    } elseif ($rol === 'administrador') {
        return 'https://placehold.co/100x100/69A64A/ffffff?text=A'; 
    }
    return 'https://placehold.co/100x100/f7f1e3/2d3436?text=U'; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Tema | ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #ffffffff; 
            --secondary: #4ecdc4; 
            --dark: #2d3436; 
            --light: #f7f1e3;
            --theme-green: #69A64A; 
        }
        body { margin:0; font-family: 'Inter', sans-serif; background:var(--light); color: var(--dark); }
        .logo { 
            font-size:1.8rem; 
            font-weight:bold; 
            color:var(--dark); 
            text-decoration:none; 
        }
        .text-dark { color: var(--dark); }
        .text-secondary { color: var(--secondary); }
        .bg-secondary { background-color: var(--secondary); }
        .text-accent { color: var(--theme-green); } 
        .bg-accent { background-color: var(--theme-green); }
        .btn-primary {
            background: var(--theme-green); 
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.15s;
        }
        .btn-primary:hover { 
            background-color: #5d9140; 
        }
        .post-card {
            background: var(--primary); 
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .post-header {
            border-bottom: 2px solid var(--secondary); 
        }
        .reply-box {
            border-top: 2px solid var(--secondary); 
        }
        .avatar {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--secondary); 
        }
        .author-tag {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: bold;
            margin-left: 8px;
        }
        .focus\:border-accent:focus, .focus\:ring-accent:focus {
            --tw-ring-color: var(--theme-green);
            --tw-border-color: var(--theme-green);
        }
    </style>
</head>
<body>

    <header class="bg-primary p-4 shadow-md sticky top-0 z-10">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <a href="index.php" class="logo">ChefEnCuna 👨‍🍳</a>
            <a href="foro_ayuda.php" class="text-secondary hover:text-accent transition duration-150">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Foro
            </a>
        </div>
    </header>
    
    <div class="container mx-auto p-4 lg:p-10 max-w-4xl">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-dark mb-6">
            <?php echo htmlspecialchars($tema['titulo'] ?? 'Tema no encontrado'); ?>
        </h1>

        <?php if ($mensaje_status): ?>
            <div class="p-4 mb-6 rounded-lg <?php echo $es_error ? 'bg-red-100 border-l-4 border-red-500 text-red-700' : 'bg-green-100 border-l-4 border-green-500 text-green-700'; ?>" role="alert">
                <p><?php echo htmlspecialchars($mensaje_status); ?></p>
            </div>
        <?php endif; ?>

        <div class="post-card">
            <div class="post-header p-5 flex items-center justify-between">
                <div class="flex items-center">
                    <img src="<?php echo get_profile_photo($tema['foto_perfil'], $tema['autor_rol']); ?>" alt="Foto de perfil" class="avatar mr-3">
                    <div>
                        <p class="text-lg font-bold text-dark">
                            <?php echo htmlspecialchars($tema['autor_nombre'] . ' ' . $tema['autor_apellido']); ?>
                            <span class="author-tag text-white 
                                <?php 
                                    if ($tema['autor_rol'] === 'maestro') {
                                        echo 'bg-secondary'; 
                                    } elseif ($tema['autor_rol'] === 'administrador') {
                                        echo 'bg-accent';
                                    } else {
                                        echo 'bg-gray-400'; 
                                    }
                                ?>">
                                <?php echo ucfirst($tema['autor_rol']); ?>
                            </span>
                        </p>
                        <p class="text-sm text-gray-500">
                            Publicado <?php echo time_elapsed_string($tema['fecha_creacion']); ?>
                        </p>
                    </div>
                </div>
                <div class="text-sm font-semibold px-3 py-1 rounded-full bg-gray-200 text-gray-600">
                    Etiqueta: <?php echo htmlspecialchars($tema['etiqueta']); ?>
                </div>
            </div>
            
            <div class="p-5 text-dark leading-relaxed">
                <p><?php echo nl2br(htmlspecialchars($tema['contenido'])); ?></p>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-dark mb-4 flex items-center">
            <i class="fas fa-comments text-accent mr-2"></i> Respuestas (<?php echo count($respuestas); ?>)
        </h2>

        <?php if (!empty($respuestas)): ?>
            <div class="space-y-4">
                <?php foreach ($respuestas as $respuesta): ?>
                    <div class="post-card p-5 border border-gray-200 shadow-sm">
                        <div class="flex items-start mb-3 border-b pb-3 border-gray-100">
                            <img src="<?php echo get_profile_photo($respuesta['foto_perfil'], $respuesta['usuario_rol']); ?>" alt="Foto de perfil" class="avatar w-10 h-10 mr-3">
                            <div>
                                <p class="font-bold text-dark">
                                    <?php echo htmlspecialchars($respuesta['usuario_nombre'] . ' ' . $respuesta['usuario_apellido']); ?>
                                    <span class="author-tag text-white 
                                        <?php 
                                            if ($respuesta['usuario_rol'] === 'maestro') {
                                                echo 'bg-secondary'; 
                                            } elseif ($respuesta['usuario_rol'] === 'administrador') {
                                                echo 'bg-accent'; 
                                            } else {
                                                echo 'bg-gray-400'; 
                                            }
                                        ?>
                                    ">
                                        <?php echo ucfirst($respuesta['usuario_rol']); ?>
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo time_elapsed_string($respuesta['fecha_respuesta']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-dark leading-relaxed pl-1">
                            <p><?php echo nl2br(htmlspecialchars($respuesta['respuesta'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-6 post-card text-center text-gray-500 border border-dashed border-gray-300">
                Sé el primero en responder a este tema.
            </div>
        <?php endif; ?>

        <div class="post-card p-6 mt-8 reply-box">
            <h3 class="text-xl font-bold text-dark mb-4">
                <i class="fas fa-reply mr-2 text-accent"></i> Publicar una Respuesta
            </h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="tema_detalle.php?id=<?php echo $tema_id; ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="accion" value="responder">
                    
                    <div>
                        <label for="respuesta_texto" class="sr-only">Tu respuesta</label>
                        <textarea id="respuesta_texto" name="respuesta_texto" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-1 focus:ring-accent focus:border-accent" placeholder="Escribe tu respuesta aquí..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary flex items-center justify-center w-full">
                        <i class="fas fa-paper-plane mr-2"></i> Enviar Respuesta
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center p-4 bg-gray-100 rounded-lg">
                    <p class="mb-3 text-dark">Para participar y responder a este tema, por favor, inicia sesión.</p>
                    <a href="login.php" class="text-accent font-semibold hover:underline">Ir a Iniciar Sesión</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>