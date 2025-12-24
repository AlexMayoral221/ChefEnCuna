<?php
session_start();
require 'config/bd.php'; 

const UPLOAD_DIR = 'img/perfiles/'; 
const DEFAULT_AVATAR = 'https://placehold.co/150x150/69A64A/FFFFFF?text=P'; 
const MAX_FILE_SIZE = 5 * 1024 * 1024; 
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

$mensaje = '';
$tipo_mensaje = ''; 
$active_page = 'ajustes'; 

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'alumno') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$nombre_usuario = htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario');
$dashboard_url = "alumno_dashboard.php"; 
$csrf_token = generate_csrf_token(); 

if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} else if (time() - $_SESSION['LAST_ACTIVITY'] > 1800) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $mensaje = "Error de seguridad: Solicitud rechazada (Token CSRF inválido).";
        $tipo_mensaje = "error";
    } else {

        if (!isset($pdo)) {
            error_log("Error: PDO no inicializado.");
            $mensaje = "Error interno: conexión a BD no disponible.";
            $tipo_mensaje = "error";
        } else {

            if (isset($_POST['action']) && $_POST['action'] === 'update_info') {

                $nuevo_nombre = trim($_POST['nombre']);
                $nuevo_apellido = trim($_POST['apellido']);
                $nuevo_email = trim($_POST['email']);
                $nuevo_genero = $_POST['genero'] ?? null;

                if (!empty($nuevo_nombre) && filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, genero = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nuevo_nombre, $nuevo_apellido, $nuevo_email, $nuevo_genero, $user_id]);

                        $_SESSION['user_nombre'] = $nuevo_nombre;
                        
                        $mensaje = "¡Información actualizada con éxito!";
                        $tipo_mensaje = "success";
                    } catch (PDOException $e) {
                        $tipo_mensaje = "error";
                        if ($e->getCode() == 23000) {
                            $mensaje = "El correo ya está registrado por otro usuario.";
                        } else {
                            $mensaje = "Error al actualizar.";
                            error_log("DB Error: " . $e->getMessage());
                        }
                    }
                } else {
                    $mensaje = "Nombre obligatorio y correo válido.";
                    $tipo_mensaje = "error";
                }
            }
            if (isset($_POST['action']) && $_POST['action'] === 'change_pass') {

                $pass_actual  = trim($_POST['pass_actual'] ?? '');
                $pass_nueva   = trim($_POST['pass_nueva'] ?? '');
                $pass_confirm = trim($_POST['pass_confirm'] ?? '');

                if ($pass_actual === '' || $pass_nueva === '' || $pass_confirm === '') {
                    $mensaje = "Todos los campos son obligatorios.";
                    $tipo_mensaje = "error";

                } elseif (strlen($pass_nueva) < 6) {
                    $mensaje = "La nueva contraseña debe tener mínimo 6 caracteres.";
                    $tipo_mensaje = "error";

                } elseif ($pass_nueva !== $pass_confirm) {
                    $mensaje = "Las nuevas contraseñas no coinciden.";
                    $tipo_mensaje = "error";

                } else {
                    try {
                        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$usuario_db) {
                            $mensaje = "Usuario no encontrado.";
                            $tipo_mensaje = "error";

                        } elseif ($pass_actual !== $usuario_db['password']) {
                            $mensaje = "La contraseña actual es incorrecta.";
                            $tipo_mensaje = "error";

                        } else {

                            $stmt_upd = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                            $stmt_upd->execute([$pass_nueva, $user_id]); 

                            $mensaje = "Contraseña actualizada correctamente.";
                            $tipo_mensaje = "success";
                        }

                    } catch (PDOException $e) {
                        error_log("Error en change_pass: " . $e->getMessage());
                        $mensaje = "Error interno al cambiar contraseña.";
                        $tipo_mensaje = "error";
                    }
                }
            }
            if (isset($_POST['action']) && $_POST['action'] === 'upload_photo') {

                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                if (!isset($_FILES['foto_perfil'])) {
                    $mensaje = "Sube una imagen válida.";
                    $tipo_mensaje = "error";

                } elseif ($_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
                    if ($_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $mensaje = "Error al subir archivo.";
                        $tipo_mensaje = "error";
                    } else {
                        $mensaje = "Selecciona una imagen.";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $fileTmpPath = $_FILES['foto_perfil']['tmp_name'];
                    $fileName = $_FILES['foto_perfil']['name'];
                    $fileSize = $_FILES['foto_perfil']['size'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
                        $mensaje = "Solo JPG, JPEG, PNG o GIF.";
                        $tipo_mensaje = "error";

                    } elseif ($fileSize > MAX_FILE_SIZE) {
                        $mensaje = "Máximo permitido: 5MB.";
                        $tipo_mensaje = "error";

                    } else {
                        $newFileName = hash('sha256', uniqid('', true) . $user_id) . '.' . $fileExtension;
                        $destPath = UPLOAD_DIR . $newFileName;

                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $stmt_current = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
                            $stmt_current->execute([$user_id]);
                            $current_photo_path = $stmt_current->fetchColumn();

                            $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                            $stmt->execute([$destPath, $user_id]);

                            if (!empty($current_photo_path) &&
                                file_exists($current_photo_path) &&
                                $current_photo_path !== DEFAULT_AVATAR &&
                                strpos($current_photo_path, UPLOAD_DIR) === 0) {
                                unlink($current_photo_path);
                            }

                            $mensaje = "Foto actualizada con éxito.";
                            $tipo_mensaje = "success";

                        } else {
                            $mensaje = "Error al guardar la imagen.";
                            $tipo_mensaje = "error";
                        }
                    }
                }
            }
        }
    }
}
$usuario = [];
try {
    $stmt = $pdo->prepare("SELECT nombre, apellido, email, rol, genero, foto_perfil, fecha_registro 
                           FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: logout.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Error al cargar perfil: " . $e->getMessage());

    $usuario = [
        'nombre' => $_SESSION['user_nombre'] ?? 'Usuario',
        'apellido' => '',
        'email' => $_SESSION['user_email'] ?? '',
        'rol' => $_SESSION['user_rol'] ?? 'alumno',
        'genero' => '',
        'foto_perfil' => '',
        'fecha_registro' => date('Y-m-d')
    ];
    if (empty($mensaje)) {
        $mensaje = "No se pudieron cargar los datos del perfil.";
        $tipo_mensaje = "error";
    }
}
$foto_url = (!empty($usuario['foto_perfil']) && file_exists($usuario['foto_perfil']))
            ? htmlspecialchars($usuario['foto_perfil'])
            : DEFAULT_AVATAR;
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
                        'brand-green': '#69A64A',   
                        'dark': '#2d3436',      
                        'light': '#f7f1e3',     
                    },
                    boxShadow: {
                        'sidebar': '5px 0 15px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f4f7f6;
            color: #4a4a4a; 
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
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e0; 
            border-radius: 0.5rem;
            margin-top: 0.25rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: white;
        }
        .form-input:focus {
            outline: none;
            border-color: #4ecdc4;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.2);
        }
        .btn-brand {
            background-color: #69A64A; 
            color: white;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
        }
        .btn-brand:hover { 
            background-color: #558b3a; 
            transform: translateY(-1px);
        }
        .inner-nav-link {
            display: inline-block;
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            color: #4a4a4a;
            transition: border-color 0.2s, color 0.2s;
        }
        .inner-nav-link.active {
            border-bottom-color: #4ecdc4;
            color: #4ecdc4;
        }
        .inner-nav-link:hover {
            color: #4ecdc4;
        }
        .file-upload-container {
            border: 2px dashed #a0aec0; 
            transition: border-color 0.2s, background-color 0.2s;
            cursor: pointer;
        }
        .file-upload-container:hover {
            border-color: #4ecdc4;
            background-color: #f7f9fb;
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
            <a href="alumno_dashboard.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-th-large w-5 mr-3"></i>
                Mi Dashboard
            </a>

            <a href="cursos.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
                <i class="fas fa-book-open w-5 mr-3"></i>
                Explorar Cursos
            </a>

            <a href="recetas_favoritas.php" class="nav-link flex items-center p-3 font-semibold text-gray-600 transition duration-150">
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

            <a href="perfil_ajustes.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'ajustes') ? 'active' : 'text-gray-600'; ?>">
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
                    Ajustes de Cuenta
                </h2>
                <a href="index.php" class="text-gray-500 hover:text-primary-dark transition duration-200 text-sm">
                    <i class="fas fa-home mr-1"></i> Ir a Inicio
                </a>
            </div>
        </header>

        <main class="p-4 lg:p-10">
            <h1 class="text-4xl font-extrabold text-dark mb-8">
                Configuración de Perfil
            </h1>

            <?php if ($mensaje): 
                $mensaje_escapado = htmlspecialchars($mensaje);
            ?>
                <div class="<?php echo $tipo_mensaje === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 mb-8 rounded-lg shadow-sm" role="alert">
                    <p class="font-bold"><?php echo $tipo_mensaje === 'success' ? '¡Éxito!' : 'Error'; ?></p>
                    <p><?php echo $mensaje_escapado; ?></p>
                </div>
            <?php endif; ?>

            <div x-data="{ activeTab: 'general', photoPreview: '<?php echo $foto_url; ?>' }">
                <nav class="border-b border-gray-200 mb-8 flex space-x-2 lg:space-x-4">
                    <span @click="activeTab = 'general'" 
                          :class="{ 'active': activeTab === 'general' }" 
                          class="inner-nav-link">
                        <i class="fas fa-id-card mr-2"></i> Información General
                    </span>
                    <span @click="activeTab = 'seguridad'" 
                          :class="{ 'active': activeTab === 'seguridad' }" 
                          class="inner-nav-link">
                        <i class="fas fa-lock mr-2"></i> Seguridad
                    </span>
                    <span @click="activeTab = 'notificaciones'" 
                          :class="{ 'active': activeTab === 'notificaciones' }" 
                          class="inner-nav-link">
                        <i class="fas fa-bell mr-2"></i> Notificaciones
                    </span>
                </nav>

                <div class="space-y-10">
                    <section id="general" x-show="activeTab === 'general'" x-transition:enter class="bg-white p-6 lg:p-8 rounded-xl shadow-md">
                        
                        <h2 class="text-2xl font-bold text-dark mb-6 border-b pb-2 flex items-center">
                            <i class="fas fa-user-edit text-accent mr-3"></i> Detalles de la Cuenta
                        </h2>

                        <div class="mb-8 border border-gray-100 p-6 rounded-xl bg-gray-50/50">
                            <h3 class="text-xl font-bold text-dark mb-4">
                                Foto de Perfil
                            </h3>

                            <form method="POST" action="" enctype="multipart/form-data" x-data="{ }" class="flex flex-col md:flex-row md:items-center md:space-x-6">
                                <input type="hidden" name="action" value="upload_photo">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div class="flex-shrink-0 mb-4 md:mb-0">
                                    <div class="w-24 h-24 rounded-full mx-auto flex items-center justify-center overflow-hidden border-4 border-accent shadow-md">
                                        <img :src="photoPreview" alt="Foto actual" class="w-full h-full object-cover">
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <label for="foto_perfil" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Subir nueva imagen (JPG, PNG, GIF - Máx 5MB)
                                    </label>
                                    <div class="relative file-upload-container p-4 rounded-lg bg-white">
                                        <input 
                                            type="file" 
                                            name="foto_perfil" 
                                            id="foto_perfil" 
                                            accept="image/jpeg,image/png,image/gif"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            @change="
                                                const file = $event.target.files[0];
                                                if (file) {
                                                    const reader = new FileReader();
                                                    reader.onload = (e) => {
                                                        photoPreview = e.target.result;
                                                    };
                                                    reader.readAsDataURL(file);
                                                }
                                            ">
                                        <div class="text-center text-gray-500">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-brand-green mb-2"></i>
                                            <p class="text-sm font-medium">Clic para seleccionar o arrastra una imagen aquí.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-shrink-0 mt-4 md:mt-0">
                                    <button type="submit" class="btn-brand shadow-lg hover:shadow-xl w-full md:w-auto">
                                        <i class="fas fa-upload mr-2"></i> Guardar Foto
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_info">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
                                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" class="form-input" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Apellido</label>
                                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>" class="form-input">
                                </div>
                                 <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Correo Electrónico</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" class="form-input" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Género</label>
                                    <select name="genero" class="form-input bg-white">
                                        <option value="" disabled <?php echo empty($usuario['genero']) ? 'selected' : ''; ?>>Seleccionar</option>
                                        <option value="Masculino" <?php echo ($usuario['genero'] === 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="Femenino" <?php echo ($usuario['genero'] === 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="Otro" <?php echo ($usuario['genero'] === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-100 mt-6">
                                <button type="submit" class="btn-brand shadow-lg hover:shadow-xl">
                                    <i class="fas fa-floppy-disk mr-2"></i> Guardar Información Personal
                                </button>
                            </div>
                        </form>
                    </section>

                    <section id="seguridad" x-show="activeTab === 'seguridad'" x-transition:enter class="bg-white p-6 lg:p-8 rounded-xl shadow-md">
                        <h2 class="text-2xl font-bold text-dark mb-6 border-b pb-2 flex items-center">
                            <i class="fas fa-shield-alt text-secondary-red mr-3"></i> Cambiar Contraseña
                        </h2>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="change_pass">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="space-y-4 max-w-lg">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña Actual</label>
                                    <div class="relative">
                                        <input type="password" name="pass_actual" class="form-input pr-10" placeholder="Ingresa tu contraseña actual" required>
                                        <i class="fas fa-key absolute right-3 top-4 text-gray-400"></i>
                                    </div>
                                </div>
                                
                                <hr class="border-gray-100 my-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nueva Contraseña</label>
                                    <input type="password" name="pass_nueva" class="form-input" placeholder="Mínimo 6 caracteres" required minlength="6">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                                    <input type="password" name="pass_confirm" class="form-input" placeholder="Confirma la nueva contraseña" required minlength="6">
                                </div>
                            </div>

                            <div class="flex justify-end mt-6 pt-4 border-t border-gray-100">
                                <button type="submit" class="bg-primary-dark text-white font-bold py-3 px-6 rounded-md hover:bg-gray-800 transition shadow-md hover:shadow-xl transform hover:-translate-y-0.5">
                                    <i class="fas fa-unlock-alt mr-2"></i> Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                    </section>

                    <section id="notificaciones" x-show="activeTab === 'notificaciones'" x-transition:enter class="bg-white p-6 lg:p-8 rounded-xl shadow-md">
                        <h2 class="text-2xl font-bold text-dark mb-6 border-b pb-2 flex items-center">
                            <i class="fas fa-bell text-accent mr-3"></i> Preferencias de Notificaciones
                        </h2>
                        <p class="text-gray-600 mb-6">Gestiona cómo quieres que nos comuniquemos contigo.</p>
                        
                        <div class="space-y-4">
                            <label class="flex items-center space-x-3 cursor-default p-3 border rounded-lg bg-gray-50">
                                <input type="checkbox" checked class="w-5 h-5 text-accent rounded focus:ring-accent border-gray-300" disabled>
                                <span class="text-gray-700 font-medium">Recibir correos sobre nuevos cursos y recetas</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-default p-3 border rounded-lg bg-gray-50">
                                <input type="checkbox" checked class="w-5 h-5 text-accent rounded focus:ring-accent border-gray-300" disabled>
                                <span class="text-gray-700 font-medium">Notificarme cuando un instructor responda mis preguntas</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-default p-3 border rounded-lg bg-gray-50">
                                <input type="checkbox" class="w-5 h-5 text-accent rounded focus:ring-accent border-gray-300" disabled>
                                <span class="text-gray-700 font-medium">Alertas de recetas favoritas cerca de su fecha de caducidad</span>
                            </label>
                        </div>
                        <div class="mt-8 text-sm text-gray-500 italic border-t pt-4">
                            <p><i class="fas fa-info-circle mr-1"></i> La funcionalidad de gestión de notificaciones está pendiente de desarrollo. Por ahora, las opciones están fijas.</p>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>