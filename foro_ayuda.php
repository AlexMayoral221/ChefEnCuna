<?php
session_start();
require 'config/bd.php'; 
require 'config/header.php'; 

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array('y' => 'año', 'm' => 'mes', 'w' => 'semana', 'd' => 'día', 'h' => 'hora', 'i' => 'minuto', 's' => 'segundo');
    foreach ($string as $k => &$v) {
        if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        else unset($string[$k]);
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'Hace ' . implode(', ', $string) : 'Justo ahora';
}

$rol_permitido = ['alumno', 'maestro', 'administrador']; 
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], $rol_permitido)) {
    header('Location: login.php');
    exit;
}

if (!isset($pdo)) {
    try { $pdo = new PDO('sqlite::memory:'); } catch (PDOException $e) {}
}

$user_rol = $_SESSION['user_rol'];
$user_id = $_SESSION['user_id'];
$dashboard_url = match($user_rol) {
    'maestro' => 'maestro_dashboard.php',
    'administrador' => 'admin_dashboard.php',
    default => 'alumno_dashboard.php',
};

$mensaje_status = '';
$es_error = false;
$titulo_input = $_POST['titulo'] ?? '';
$contenido_input = $_POST['contenido'] ?? '';
$etiqueta_input = $_POST['etiqueta'] ?? 'General';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'crear_tema' && $user_rol === 'alumno') {
        $titulo = trim($titulo_input);
        $contenido = trim($contenido_input);
        $etiqueta = trim($etiqueta_input);

        if (empty($titulo) || empty($contenido)) {
            $mensaje_status = 'Error: Título y Contenido son obligatorios.'; $es_error = true;
        } elseif (strlen($titulo) > 255) {
            $mensaje_status = 'Error: El título es demasiado largo (máx. 255 caracteres).'; $es_error = true;
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO foro_temas (alumno_id, titulo, contenido, etiqueta) VALUES (:alumno_id, :titulo, :contenido, :etiqueta)");
                $stmt->execute([':alumno_id' => $user_id, ':titulo' => $titulo, ':contenido' => $contenido, ':etiqueta' => $etiqueta]);
                header('Location: foro_ayuda.php?status=success_posted'); exit;
            } catch (PDOException $e) {
                $mensaje_status = 'Error de BD al crear el tema: ' . $e->getMessage(); $es_error = true;
            }
        }
    } else if ($_POST['accion'] === 'crear_tema' && $user_rol !== 'alumno') {
        $mensaje_status = 'Solo los alumnos pueden crear nuevos temas.'; $es_error = true;
    } else if ($_POST['accion'] === 'eliminar_tema' && $user_rol === 'administrador') {
        $tema_id = intval($_POST['tema_id'] ?? 0);
        if ($tema_id > 0 && $pdo instanceof PDO) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM foro_respuestas WHERE tema_id = :tema_id")->execute([':tema_id' => $tema_id]);
                $pdo->prepare("DELETE FROM foro_temas WHERE id = :tema_id")->execute([':tema_id' => $tema_id]);
                $pdo->commit();
                header('Location: foro_ayuda.php?status=success_deleted'); exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $mensaje_status = 'Error de BD al eliminar el tema: ' . $e->getMessage(); $es_error = true;
            }
        } else {
            $mensaje_status = 'Error: ID de tema inválido.'; $es_error = true;
        }
    } else if ($_POST['accion'] === 'eliminar_tema' && $user_rol !== 'administrador') {
        $mensaje_status = 'Acceso denegado: Solo los administradores pueden eliminar temas.'; $es_error = true;
    }
}

switch ($_GET['status'] ?? '') {
    case 'success_posted': $mensaje_status = '¡Tu nuevo tema ha sido publicado con éxito!'; break;
    case 'success_deleted': $mensaje_status = 'El tema y sus respuestas han sido eliminados.'; break;
}

$temas_foro = [];
try {
    if ($pdo instanceof PDO) {
        $stmt = $pdo->query("
            SELECT t.id, t.titulo, t.etiqueta, t.fecha_creacion, u.nombre as autor_nombre,
                (SELECT COUNT(id) FROM foro_respuestas r WHERE r.tema_id = t.id) as respuestas_count
            FROM foro_temas t JOIN usuarios u ON t.alumno_id = u.id ORDER BY t.fecha_creacion DESC LIMIT 15
        ");
        $temas_foro = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (empty($mensaje_status)) {
        $mensaje_status = 'Advertencia: Conexión a BD no disponible.'; $es_error = true;
    }
} catch (PDOException $e) {
    error_log("Error al cargar temas: " . $e->getMessage());
    $mensaje_status = 'Error: No se pudieron cargar los temas.'; $es_error = true;
}

function get_tag_info($tag) {
    return match (strtolower($tag)) {
        'técnico' => ['color' => 'bg-blue-600', 'text' => 'Técnico'], 
        'receta' => ['color' => 'bg-secondary', 'text' => 'Receta'], 
        'sugerencia' => ['color' => 'bg-purple-600', 'text' => 'Sugerencia'],
        'reporte' => ['color' => 'bg-red-600', 'text' => 'Reporte'],
        default => ['color' => 'bg-gray-500', 'text' => 'General'],
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro de Ayuda | ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script> 
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-green': '#69A64A', 'primary-accent': '#ff6b6b', 'secondary-accent': '#4ecdc4', 'dark': '#2d3436',    
                    },
                }
            }
        }
    </script>
    <style>
        :root { --primary-color: #ff6b6b; --secondary-color: #4ecdc4; --dark: #2d3436; --light: #f7f1e3; --theme-green: #69A64A; }
        body { font-family: 'Inter', sans-serif; color: var(--dark); background-color: var(--light); min-height: 100vh; margin: 0; padding: 0; display: flex; flex-direction: column; }
        .text-primary { color: var(--primary-color); }
        .bg-primary { background-color: var(--primary-color); }
        .text-secondary { color: var(--secondary-color); }
        .bg-secondary { background-color: var(--secondary-color); }
        .lg\:sticky { align-self: flex-start; }
    </style>
</head>
<body>
    <div class="container mx-auto p-4 lg:p-10 max-w-7xl">
        <h1 class="text-4xl font-extrabold text-gray-800 mb-2 border-b-2 border-primary pb-2">Foro de Ayuda y Comunidad</h1>
        <p class="text-xl text-gray-500 mb-8">Pregunta a la comunidad o reporta un problema.</p>

        <?php if ($mensaje_status): ?>
            <div class="p-4 mb-6 rounded-lg <?= $es_error ? 'bg-red-100 border-l-4 border-red-500 text-red-700' : 'bg-green-100 border-l-4 border-green-500 text-green-700'; ?>" role="alert">
                <p class="font-bold"><?= $es_error ? '¡Atención!' : '¡Éxito!'; ?></p>
                <p><?= htmlspecialchars($mensaje_status); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <?php if ($user_rol === 'alumno'): ?>
            <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg lg:h-fit lg:sticky lg:top-24">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-question-circle text-primary-color mr-2"></i> Nuevo Tema</h2>
                <form action="foro_ayuda.php" method="POST" class="space-y-4">
                    <input type="hidden" name="accion" value="crear_tema">
                    <div>
                        <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                        <input type="text" id="titulo" name="titulo" required maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color" value="<?= htmlspecialchars($titulo_input) ?>">
                    </div>
                    <div>
                        <label for="etiqueta" class="block text-sm font-medium text-gray-700 mb-1">Etiqueta</label>
                        <select id="etiqueta" name="etiqueta" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color">
                            <option value="General" <?= ($etiqueta_input === 'General') ? 'selected' : ''; ?>>General</option>
                            <option value="Técnico" <?= ($etiqueta_input === 'Técnico') ? 'selected' : ''; ?>>Técnico</option>
                            <option value="Receta" <?= ($etiqueta_input === 'Receta') ? 'selected' : ''; ?>>Receta</option>
                            <option value="Sugerencia" <?= ($etiqueta_input === 'Sugerencia') ? 'selected' : ''; ?>>Sugerencia</option>
                        </select>
                    </div>
                    <div>
                        <label for="contenido" class="block text-sm font-medium text-gray-700 mb-1">Detalles</label>
                        <textarea id="contenido" name="contenido" rows="5" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color"><?= htmlspecialchars($contenido_input) ?></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-primary text-white font-semibold rounded-lg hover:opacity-90 transition duration-150 shadow-md">
                        <i class="fas fa-paper-plane mr-2"></i> Publicar Pregunta
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div class="<?= ($user_rol === 'alumno') ? 'lg:col-span-2' : 'lg:col-span-3'; ?> bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-comments text-primary-color mr-2"></i> Temas Recientes
                        <?php if ($user_rol !== 'alumno'): ?>
                            <span class="ml-4 text-sm font-medium text-primary-accent bg-primary-accent/10 px-3 py-1 rounded-full">Modo <?= ucfirst($user_rol); ?></span>
                        <?php endif; ?>
                        <?php if ($user_rol === 'administrador'): ?>
                        <?php endif; ?>
                    </h2>
                </div>
                
                <ul class="divide-y divide-gray-100">
                    <?php if (!empty($temas_foro)): ?>
                        <?php foreach ($temas_foro as $tema): 
                            $tag_info = get_tag_info($tema['etiqueta']);
                        ?>
                            <li class="forum-item flex items-center justify-between">
                                <a href="tema_detalle.php?id=<?= $tema['id'] ?>" class="block flex-grow p-4 sm:p-6 flex justify-between items-center hover:bg-gray-50 transition duration-150">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center mb-1">
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full text-white mr-3 mb-1 sm:mb-0 <?= $tag_info['color'] ?>">
                                                <?= $tag_info['text'] ?>
                                            </span>
                                            <h3 class="text-lg font-semibold text-gray-900 hover:text-primary-color transition duration-150 truncate">
                                                <?= $tema['titulo'] ?>
                                            </h3>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-user-edit mr-1"></i> Publicado por <b class="text-gray-700"><?= htmlspecialchars($tema['autor_nombre']) ?></b>
                                            <span class="ml-2 text-xs italic text-gray-400">
                                                <i class="fas fa-clock mr-1"></i> <?= time_elapsed_string($tema['fecha_creacion']) ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0 text-right ml-4">
                                        <div class="text-xl font-bold text-primary-color"><?= $tema['respuestas_count'] ?></div>
                                        <p class="text-sm text-gray-500"><i class="fas fa-reply mr-1"></i> Respuestas</p>
                                    </div>
                                </a>

                                <?php if ($user_rol === 'administrador'): ?>
                                <div x-data="{ open: false }" class="flex-shrink-0 pr-4 sm:pr-6">
                                    <button @click.prevent="open = true" class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition" title="Eliminar Tema">
                                        <i class="fas fa-trash-alt text-lg"></i>
                                    </button>

                                    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
                                        <div @click.away="open = false" class="bg-white rounded-xl shadow-2xl p-6 w-11/12 max-w-md">
                                            <h3 class="text-xl font-bold mb-4 text-red-600 border-b pb-2">Confirmar Eliminación</h3>
                                            <p class="mb-6 text-gray-700">
                                                ¿Seguro que deseas eliminar el tema <b>"<?= htmlspecialchars($tema['titulo']) ?>"</b>? Esto borrará todas las respuestas.
                                            </p>
                                            <div class="flex justify-end space-x-3">
                                                <button @click="open = false" class="px-4 py-2 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition">Cancelar</button>
                                                <form action="foro_ayuda.php" method="POST" class="inline">
                                                    <input type="hidden" name="accion" value="eliminar_tema">
                                                    <input type="hidden" name="tema_id" value="<?= $tema['id'] ?>">
                                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition duration-150 shadow-md">Sí, Eliminar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-6 text-center text-gray-500">Aún no hay temas en el foro. ¡Sé el primero en preguntar!</div>
                    <?php endif; ?>
                </ul>

            </div>
        </div>
        
        <div class="text-center mt-12 pt-6 border-t border-gray-300">
             <a href="preguntas_frecuentes.php" class="text-primary-color font-semibold hover:underline flex items-center justify-center">
                Visitar Preguntas Frecuentes <i class="fas fa-external-link-alt ml-2"></i>
            </a>
        </div>
    </div>
</body>
</html>