<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require 'config/bd.php'; 

$mensaje_error = '';
$email = ''; 

$REQUIRED_ROL = 'maestro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $mensaje_error = 'Por favor, introduce tu email y contraseña.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                
                if ($user['rol'] !== $REQUIRED_ROL) {
                    $mensaje_error = 'Acceso denegado. Tu cuenta no es de ' . $REQUIRED_ROL . '.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nombre'] = $user['nombre'];
                    $_SESSION['user_rol'] = $user['rol'];
    
                    header('Location: maestro_dashboard.php'); 
                    exit;
                }
            } else {
                $mensaje_error = 'Email o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $mensaje_error = 'Error de base de datos durante el inicio de sesión.';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --primary: #ff6b6b; --secondary: #4ecdc4; --dark: #2d3436; --light: #f7f1e3; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; background-color: #f7f1e3; color: var(--dark); }
        header { 
            background: white; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--primary); text-decoration: none; }
        nav { display: flex; align-items: center; } 
        nav a { text-decoration: none; color: var(--dark); margin-left: 20px; font-weight: 500; }
        nav a:hover { color: var(--primary); }
        .btn-login { border: 2px solid var(--primary); padding: 5px 15px; border-radius: 20px; color: var(--primary); }
        .btn-register { background: var(--primary); color: white; padding: 7px 15px; border-radius: 20px; }
        .search-box { display: flex; align-items: center; border: 1px solid #ccc; border-radius: 20px; padding: 5px 10px; margin-right: 20px; background: #f8f8f8; }
        .search-box input[type="search"] { border: none; outline: none; background: none; padding: 5px; width: 200px; }
        .search-box button { background: none; border: none; color: var(--dark); cursor: pointer; padding: 0 5px; }
        .bg-primary { background-color: var(--primary); }
        .text-primary { color: var(--primary); }
        .focus-ring-primary:focus { --tw-ring-color: var(--primary); }
        .focus-border-primary:focus { border-color: var(--primary); }
        .hover-bg-red-700:hover { background-color: #d84a4a; }
        .shadow-custom { box-shadow: 0 10px 25px -5px rgba(45, 52, 54, 0.1), 0 5px 10px -5px rgba(45, 52, 54, 0.04); }
        .content-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 6rem);
            padding: 2rem 1rem;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo">ChefEnCuna 👨‍🍳</a>
        <form action="buscar.php" method="GET" class="search-box">
            <input type="search" name="q" placeholder="Buscar recetas o cursos..." required>
            <button type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="recetas.php">Recetas</a> 
            <a href="cursos.php">Cursos</a>   
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="perfil.php">Hola, <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?></a>
                <a href="logout.php" style="color: red;">Salir</a>
            <?php else: ?>
                <a href="login_maestro.php" class="btn-login" style="border-color:#2ecc71; color:#2ecc71;">Maestro</a>
                <a href="login.php" class="btn-login">Entrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="content-wrapper">
        <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-custom border border-gray-100">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-800">Acceso de Maestros</h1>
                <p class="text-gray-500">Inicia sesión para gestionar cursos y recetas.</p>
            </div>

            <?php if ($mensaje_error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg" role="alert">
                    <p class="font-bold">Error de Sesión</p>
                    <p><?php echo htmlspecialchars($mensaje_error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="login_maestro.php" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring-primary focus-border-primary focus:ring-1 transition duration-150 ease-in-out" 
                           value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input type="password" id="password" name="password" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus-ring-primary focus-border-primary focus:ring-1 transition duration-150 ease-in-out">
                </div>

                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover-bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-150 ease-in-out" style="background-color:#2ecc71; --tw-ring-color: #2ecc71;">
                    Entrar como Maestro
                </button>
            </form>
            <p class="mt-6 text-center text-sm text-gray-600">
                <a href="login_admin.php" class="font-medium text-gray-400 hover:text-gray-600">
                    Ir a Login de Administradores
                </a> | 
                <a href="login_alumno.php" class="font-medium text-gray-400 hover:text-gray-600">
                    Ir a Login de Alumnos
                </a>
            </p>
        </div>
    </div>
</body>
</html>