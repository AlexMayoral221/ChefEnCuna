<?php
session_start();

require 'config/bd.php'; 

$pdo = $pdo ?? null; 
$mensaje_error = '';
$mensaje_exito = '';
$nombre = '';
$apellido = '';
$email = '';

$dashboard_url = "alumno_dashboard.php"; 

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_rol'])) {
        switch ($_SESSION['user_rol']) {
            case 'administrador': $dashboard_url = "admin_dashboard.php"; break;
            case 'maestro': $dashboard_url = "maestro_dashboard.php"; break;
            default: $dashboard_url = "alumno_dashboard.php"; 
        }
    }
    header("Location: " . $dashboard_url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $rol = 'alumno'; 

    if (empty($nombre) || empty($email) || empty($password)) {
        $mensaje_error = 'Todos los campos obligatorios (Nombre, Email y Contraseña) deben ser llenados.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'El formato del email no es válido.';
    } elseif (strlen($password) < 6) {
        $mensaje_error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!$pdo) {
        $mensaje_error = 'Error de base de datos: La conexión no está disponible. Contacta al soporte.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $mensaje_error = 'Este email ya está registrado. Intenta con otro.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, password, rol) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $apellido, $email, $password_hash, $rol]);

                $mensaje_exito = '¡Registro exitoso! Ya eres un alumno de ChefEnCuna. Serás redirigido al inicio de sesión en 3 segundos.';
                
            }
        } catch (PDOException $e) {
            error_log("Error de registro: " . $e->getMessage()); 
            $mensaje_error = 'Ocurrió un error inesperado al intentar registrarte. Por favor, inténtalo de nuevo.';
        }
    }
}
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
                        'primary': '#69A64A', // Tu --theme-green para el branding
                        'accent': '#4ecdc4',  // Tu --secondary (Teal)
                        'dark': '#2d3436',    // Tu --dark
                        'light-bg': '#f7f1e3', // Tu --light (Fondo del Cuerpo)
                    },
                }
            }
        }
    </script>
    <style>
        :root {
            --primary: #ffffff; /* Blanco */
            --secondary: #4ecdc4; /* Teal */
            --dark: #2d3436; /* Gris Oscuro */
            --light: #f7f1e3; /* Fondo del Cuerpo */
            --theme-green: #69A64A; /* Fondo del Header */
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
        .page-center-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1; 
            padding: 40px 20px;
        }
        .shadow-custom {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 10px rgba(105, 166, 74, 0.3); 
        }
        .focus-ring-primary:focus {
            --tw-ring-color: #69A64A;
        }
        .focus-border-primary:focus {
            border-color: #69A64A;
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
            }
            .app-nav a {
                margin: 0.5rem 0.3rem;
                font-size: 0.9rem;
            }
        }
        footer { background: var(--dark); color: white; text-align: center; padding: 20px; margin-top: 50px; }
        footer a { color: var(--secondary); text-decoration: none; margin: 0 10px; }
        footer a:hover { text-decoration: underline; }        
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
                        <i class="fas fa-user mr-2"></i> <?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario') ?>
                        <i class="fas fa-caret-down ml-2"></i>
                    </button>

                    <div x-show="open"
                        x-transition
                        class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1
                               bg-[var(--theme-green)] text-white ring-1 ring-black ring-opacity-5">

                        <a href="<?= htmlspecialchars($dashboard_url) ?>"
                           class="block px-4 py-2 text-sm text-white hover:bg-green-700">
                            <i class="fas fa-gauge-high mr-2"></i> Dashboard
                        </a>
                            
                        <a href="logout.php"
                           class="block px-4 py-2 text-sm text-white hover:bg-red-700 border-t border-white/25">
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

    <main class="page-center-container">
        <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-custom border border-gray-100">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-800">Crea tu Cuenta.</h1>
                <p class="text-gray-500">Regístrate para acceder a las recetas y cursos.</p>
            </div>

            <?php if ($mensaje_error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg" role="alert">
                    <p class="font-bold">Error</p>
                    <p><?php echo htmlspecialchars($mensaje_error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($mensaje_exito): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg" role="alert">
                    <p class="font-bold">Éxito</p>
                    <p><?php echo htmlspecialchars($mensaje_exito); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="registro.php" class="space-y-6">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre*</label> 
                    <input type="text" id="nombre" name="nombre" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary focus:ring-1 transition duration-150 ease-in-out" 
                           value="<?php echo htmlspecialchars($nombre); ?>">
                </div>
                <div>
                    <label for="apellido" class="block text-sm font-medium text-gray-700">Apellido* </label>
                    <input type="text" id="apellido" name="apellido" 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary focus:ring-1 transition duration-150 ease-in-out" 
                           value="<?php echo htmlspecialchars($apellido); ?>">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email*</label>
                    <input type="email" id="email" name="email" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary focus:ring-1 transition duration-150 ease-in-out" 
                           value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña* </label>
                    <input type="password" id="password" name="password" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary focus:ring-1 transition duration-150 ease-in-out">
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-primary hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-150 ease-in-out">
                    <i class="fas fa-arrow-right-to-bracket mr-2"></i> Registrarse
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                ¿Ya tienes una cuenta?
                <a href="login.php" class="font-medium text-primary hover:text-green-700 transition duration-150">
                    Inicia Sesión aquí
                </a>
            </p>
        </div>
    </main>
    
    <?php if ($mensaje_exito): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php'; 
        }, 3000); 
    </script>
    <?php endif; ?>
    
</body>
</html>