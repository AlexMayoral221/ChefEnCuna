<?php
session_start();
require 'config/bd.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = ''; 

$UPLOAD_DIR = 'img/perfiles/'; 
$DEFAULT_AVATAR = 'https://placehold.co/150x150/69A64A/FFFFFF?text=P'; 

$user_rol = $_SESSION['user_rol'] ?? 'alumno';
$dashboard_url = ($user_rol === 'maestro') ? 'maestro_dashboard.php' : 'alumno_dashboard.php'; 
$user_nombre = htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario');
$user_logged_in = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($pdo)) {
         error_log("Error: PDO object is not initialized. Cannot process POST requests.");
         $mensaje = "Error interno: La conexión a la base de datos no está disponible.";
         $tipo_mensaje = "error";
    } else {

        if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
            $nuevo_nombre = trim($_POST['nombre']);
            $nuevo_apellido = trim($_POST['apellido']);
            $nuevo_email = trim($_POST['email']);
            $nuevo_genero = $_POST['genero'] ?? null;

            if (!empty($nuevo_nombre) && !empty($nuevo_email)) {
                try {
                    $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, genero = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nuevo_nombre, $nuevo_apellido, $nuevo_email, $nuevo_genero, $user_id]);

                    $_SESSION['user_nombre'] = $nuevo_nombre;
                    
                    $mensaje = "¡Información actualizada con éxito!";
                    $tipo_mensaje = "success";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $mensaje = "El correo electrónico ya está registrado por otro usuario.";
                    } else {
                        $mensaje = "Error al actualizar: " . $e->getMessage();
                    }
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "El nombre y el correo no pueden estar vacíos.";
                $tipo_mensaje = "error";
            }
        }

        if (isset($_POST['action']) && $_POST['action'] === 'change_pass') {
            $pass_actual = $_POST['pass_actual'];
            $pass_nueva = $_POST['pass_nueva'];
            $pass_confirm = $_POST['pass_confirm'];

            if (!empty($pass_actual) && !empty($pass_nueva)) {
                if ($pass_nueva === $pass_confirm) {
                    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($usuario_db && password_verify($pass_actual, $usuario_db['password'])) {
                        $new_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
                        $stmt_upd = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                        $stmt_upd->execute([$new_hash, $user_id]);

                        $mensaje = "Contraseña actualizada correctamente.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "La contraseña actual es incorrecta.";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Las nuevas contraseñas no coinciden.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "Todos los campos de contraseña son obligatorios.";
                $tipo_mensaje = "error";
            }
        }
        if (isset($_POST['action']) && $_POST['action'] === 'upload_photo') {

            if (!is_dir($UPLOAD_DIR)) {
                mkdir($UPLOAD_DIR, 0777, true); 
            }

            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto_perfil']['tmp_name'];
                $fileName = $_FILES['foto_perfil']['name'];
                $fileSize = $_FILES['foto_perfil']['size'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif');
                if (!in_array($fileExtension, $allowedfileExtensions)) {
                    $mensaje = "Tipo de archivo no permitido. Solo se aceptan JPG, JPEG, PNG y GIF.";
                    $tipo_mensaje = "error";
                } elseif ($fileSize > 5 * 1024 * 1024) { 
                    $mensaje = "El archivo es demasiado grande (máx 5MB).";
                    $tipo_mensaje = "error";
                } else {
                    $newFileName = md5(uniqid('', true) . $user_id) . '.' . $fileExtension;
                    $destPath = $UPLOAD_DIR . $newFileName;

                    if(move_uploaded_file($fileTmpPath, $destPath)) {
                        
                        $stmt_current = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
                        $stmt_current->execute([$user_id]);
                        $current_photo_path = $stmt_current->fetchColumn();

                        $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$destPath, $user_id]);

                        if (!empty($current_photo_path) && file_exists($current_photo_path) && $current_photo_path !== $DEFAULT_AVATAR && strpos($current_photo_path, 'uploads/') === 0) {
                             unlink($current_photo_path);
                             error_log("Foto anterior eliminada: " . $current_photo_path);
                        }
                        
                        $mensaje = "¡Foto de perfil actualizada con éxito!";
                        $tipo_mensaje = "success";

                    } else {
                        $mensaje = "Hubo un error al mover el archivo subido. Verifique permisos del servidor.";
                        $tipo_mensaje = "error";
                    }
                }
            } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
                 $mensaje = "Error de subida: Código de error PHP " . $_FILES['foto_perfil']['error'];
                 $tipo_mensaje = "error";
            } else {
                 $mensaje = "Por favor, selecciona una imagen para subir.";
                 $tipo_mensaje = "error";
            }
        }
    }
}

try {
    if (!isset($pdo)) {
        throw new PDOException("PDO object is not initialized, using session data.");
    }
    
    $stmt = $pdo->prepare("SELECT nombre, apellido, email, rol, genero, foto_perfil, fecha_registro FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new PDOException("User not found in database.");
    }
} catch (PDOException $e) {
    error_log("Error al cargar perfil: " . $e->getMessage());
    $usuario = [
        'nombre' => $_SESSION['user_nombre'] ?? 'Usuario', 
        'apellido' => '', 
        'email' => $_SESSION['user_email'] ?? '', 
        'rol' => $_SESSION['user_rol'] ?? 'alumno', 
        'genero' => '', 
        'foto_perfil' => null, 
        'fecha_registro' => date('Y-m-d')
    ];
    if (empty($mensaje)) {
        $mensaje = "Error: No se pudieron cargar los datos del perfil desde la base de datos.";
        $tipo_mensaje = "error";
    }
}

$foto_url = (!empty($usuario['foto_perfil']) && file_exists($usuario['foto_perfil'])) 
            ? htmlspecialchars($usuario['foto_perfil']) 
            : $DEFAULT_AVATAR;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script> 
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-green': '#69A64A',   /* Verde Principal (Header) */
                        'primary': '#69A64A',       /* Alias para componentes */
                        'secondary': '#4ecdc4',     /* Teal */
                        'dark': '#2d3436',          /* Gris Oscuro */
                        'light': '#f7f1e3',         /* Crema */
                    }
                }
            }
        }
    </script>
    <style>
        :root { 
            --primary: #69A64A; 
            --secondary: #4ecdc4; 
            --dark: #2d3436; 
            --light: #f7f1e3; 
            --theme-green: #69A64A; 
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--light); 
            color: var(--dark); 
        }
        .app-header { 
            background: var(--theme-green); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0;
            z-index: 50;
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
            color: white; 
            text-decoration: none; 
        }
        .app-nav a {
            margin-left: 1.5rem;
            text-decoration: none;
            color: white; 
            font-weight: 500;
            transition: color 0.15s;
            white-space: nowrap; 
        }
        .app-nav a:hover { 
            color: var(--secondary); 
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
            margin-left: 1.5rem; 
        }
        .btn-profile:hover {
            background-color: #3aa69e; 
        }
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
                gap: 10px;
            }
            .app-nav a {
                margin: 0;
            }
            .btn-profile {
                margin-left: 0.5rem !important;
                margin-right: 0.5rem !important;
            }
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-top: 0.25rem;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.2);
        }
        .btn-save {
            background-color: var(--primary);
            color: white;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-save:hover { background-color: #558b3a; }
        
        .nav-link-active { 
            border-left: 4px solid var(--secondary); 
            background-color: #f8fafc; 
            color: var(--secondary); 
            font-weight: bold; 
        }
        .file-upload-container {
            border: 2px dashed #e2e8f0;
            transition: border-color 0.2s;
        }
        .file-upload-container:hover {
            border-color: var(--secondary);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

<header class="app-header">
    <div class="header-content">
        <a href="index.php" class="logo">ChefEnCuna 👨‍🍳</a>

        <nav class="app-nav flex items-center">
            <a href="index.php">Inicio</a>
            <a href="recetas.php">Recetas</a>
            <a href="cursos.php">Cursos</a>
            <a href="foro_ayuda.php">Foro</a>

            <?php if($user_logged_in): ?>
                <div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" class="btn-profile flex items-center">
                        <i class="fas fa-user mr-2"></i> <?= $user_nombre ?>
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">
                        <a href="<?= htmlspecialchars($dashboard_url) ?>"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700">
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
                        class="btn-profile flex items-center bg-secondary hover:bg-[#3aa69e]">
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

    <main class="container mx-auto p-4 lg:p-10 flex-grow">
        
        <div class="mb-6 flex items-center gap-4">
            <a href="<?php echo htmlspecialchars($dashboard_url); ?>" class="text-gray-500 hover:text-primary transition">
                <i class="fas fa-arrow-left"></i> Volver al Perfil
            </a>
            <h1 class="text-3xl font-bold text-dark">Ajustes de Perfil</h1>
        </div>

        <?php if ($mensaje): ?>
            <div class="<?php echo $tipo_mensaje === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 mb-6 rounded shadow-sm" role="alert">
                <p class="font-bold"><?php echo $tipo_mensaje === 'success' ? '¡Éxito!' : 'Error'; ?></p>
                <p><?php echo $mensaje; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8" x-data="{ activeTab: 'general', photoPreview: '<?php echo $foto_url; ?>' }">
            
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-md text-center border-t-4 border-secondary">
                    <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center overflow-hidden border-2 border-primary">
                        <img :src="photoPreview" alt="Foto de perfil" class="w-full h-full object-cover">
                    </div>
                    
                    <h2 class="text-xl font-bold text-dark">
                        <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                    </h2>
                    <p class="text-secondary font-semibold uppercase text-sm mb-2"><?php echo htmlspecialchars($usuario['rol']); ?></p>
                    <p class="text-xs text-gray-400">Miembro desde <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'] ?? 'now')); ?></p>
                    
                    <hr class="my-4 border-gray-100">
                    
                    <div class="text-left space-y-2">
                        <a href="#" @click.prevent="activeTab = 'general'" :class="{ 'nav-link-active': activeTab === 'general' }" class="block p-2 rounded text-dark hover:bg-gray-50 transition">
                            <i class="fas fa-id-card mr-2 w-5 text-center"></i> General
                        </a>
                        <a href="#" @click.prevent="activeTab = 'seguridad'" :class="{ 'nav-link-active': activeTab === 'seguridad' }" class="block p-2 rounded text-dark hover:bg-gray-50 transition">
                            <i class="fas fa-lock mr-2 w-5 text-center"></i> Seguridad
                        </a>
                        <a href="#" @click.prevent="activeTab = 'notificaciones'" :class="{ 'nav-link-active': activeTab === 'notificaciones' }" class="block p-2 rounded text-dark hover:bg-gray-50 transition">
                            <i class="fas fa-bell mr-2 w-5 text-center"></i> Notificaciones
                        </a>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3 space-y-8">
                <section id="general" x-show="activeTab === 'general'" class="bg-white p-6 lg:p-8 rounded-xl shadow-md space-y-8">
                    
                    <div class="border p-4 rounded-lg bg-gray-50">
                        <h2 class="text-xl font-bold text-dark mb-4 flex items-center">
                            <i class="fas fa-camera text-primary mr-3"></i> Foto de Perfil
                        </h2>
                        <form method="POST" action="" enctype="multipart/form-data" 
                              x-data="{ isDragging: false }"
                              class="md:flex md:items-center md:space-x-6">
                            
                            <input type="hidden" name="action" value="upload_photo">

                            <div class="flex-shrink-0 mb-4 md:mb-0">
                                <div class="w-24 h-24 rounded-full mx-auto flex items-center justify-center overflow-hidden border-4 border-secondary shadow-md">
                                    <img :src="photoPreview" alt="Foto actual" class="w-full h-full object-cover">
                                </div>
                            </div>
                            <div class="flex-grow">
                                <label for="foto_perfil" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Subir nueva imagen (JPG, PNG, GIF - Máx 5MB)
                                </label>
                                <div class="relative file-upload-container p-4 rounded-md">
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
                                        "
                                    >
                                    <div class="text-center text-gray-500">
                                        <i class="fas fa-cloud-upload-alt text-2xl text-secondary mb-1"></i>
                                        <p class="text-sm font-medium">Clic para seleccionar o arrastra una imagen aquí.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-shrink-0 mt-4 md:mt-0">
                                <button type="submit" class="btn-save py-3 px-4 w-full md:w-auto">
                                    Guardar Foto
                                </button>
                            </div>
                        </form>
                    </div>

                    <hr class="border-gray-100">
                    
                    <div>
                        <h2 class="text-xl font-bold text-dark mb-6 flex items-center">
                            <i class="fas fa-user-edit text-secondary mr-3"></i> Información Personal
                        </h2>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_info">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
                                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" class="form-input" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Apellido</label>
                                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>" class="form-input">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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

                            <div class="flex justify-end">
                                <button type="submit" class="btn-save shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition">
                                    Guardar Información Personal
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <section id="seguridad" x-show="activeTab === 'seguridad'" class="bg-white p-6 lg:p-8 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-dark mb-6 flex items-center">
                        <i class="fas fa-shield-alt text-red-500 mr-3"></i> Cambiar Contraseña
                    </h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_pass">
                        
                        <div class="space-y-4 max-w-lg">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña Actual</label>
                                <div class="relative">
                                    <input type="password" name="pass_actual" class="form-input pr-10" required>
                                    <i class="fas fa-key absolute right-3 top-4 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <hr class="border-gray-100 my-4">

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nueva Contraseña</label>
                                <input type="password" name="pass_nueva" class="form-input" required minlength="6">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                                <input type="password" name="pass_confirm" class="form-input" required minlength="6">
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="submit" class="bg-dark text-white font-bold py-3 px-6 rounded-md hover:bg-gray-800 transition shadow-md">
                                Actualizar Contraseña
                            </button>
                        </div>
                    </form>
                </section>

                <section id="notificaciones" x-show="activeTab === 'notificaciones'" class="bg-white p-6 lg:p-8 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-dark mb-4 flex items-center">
                        <i class="fas fa-bell text-yellow-500 mr-3"></i> Preferencias de Notificaciones
                    </h2>
                    <p class="text-sm text-gray-500 mb-6">Gestiona cómo quieres que nos comuniquemos contigo.</p>
                    
                    <div class="space-y-4">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" checked class="w-5 h-5 text-secondary rounded focus:ring-secondary border-gray-300" disabled>
                            <span class="text-gray-700">Recibir correos sobre nuevos cursos</span>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" checked class="w-5 h-5 text-secondary rounded focus:ring-secondary border-gray-300" disabled>
                            <span class="text-gray-700">Notificarme cuando un instructor responda mis preguntas</span>
                        </label>
                    </div>
                    <div class="mt-6 text-sm text-gray-400 italic">
                        <p>Estas opciones están actualmente deshabilitadas para demostración.</p>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>