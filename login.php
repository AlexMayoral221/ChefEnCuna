<?php
session_start();
require 'config/bd.php'; 

$pdo = $pdo ?? null;
$message = '';
$dashboard_url = "perfil.php"; 

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_rol'])) {
        switch ($_SESSION['user_rol']) {
            case 'administrador': $dashboard_url = "admin_dashboard.php"; break;
            case 'maestro': $dashboard_url = "maestro_dashboard.php"; break;
            default: $dashboard_url = "alumno_dashboard.php";
        }
    }
    header("Location: $dashboard_url");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "⚠️ Por favor, ingrese correo electrónico y contraseña.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                
                $passwordBD = $user['password'];
                $verificado = false;

                if (strpos($passwordBD, '$2y$') === 0 || strpos($passwordBD, '$2a$') === 0) {
                    $verificado = password_verify($password, $passwordBD);
                } else {
                    $verificado = ($password === $passwordBD);
                }

                if ($verificado) {

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nombre'] = $user['nombre']; 
                    $_SESSION['user_rol'] = $user['rol'];

                    if ($user['rol'] === 'administrador') {
                        header('Location: admin_dashboard.php');
                        exit;
                    } elseif ($user['rol'] === 'maestro') {
                        header('Location: maestro_dashboard.php');
                        exit;
                    } else {
                        header('Location: alumno_dashboard.php'); 
                        exit;
                    }

                } else {
                    $message = "❌ Credenciales incorrectas. Verifique su correo y contraseña.";
                }

            } else {
                $message = "❌ No existe una cuenta con ese correo.";
            }

        } catch (PDOException $e) {
            $message = "❌ Error de conexión a la base de datos: " . $e->getMessage();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
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
            justify-content: center; 
            align-items: center; 
        }
        
        .login-wrapper {
            flex-grow: 1; 
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 100%;
        }
        .card-shadow {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .input-focus:focus {
            border-color: var(--theme-green);
            box-shadow: 0 0 0 3px rgba(105, 166, 74, 0.5); 
            outline: none;
        }
        .btn-form-submit {
            background-color: var(--theme-green);
            color: var(--primary);
            transition: background-color 0.2s;
        }
        .btn-form-submit:hover {
            background-color: #5d9140; 
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-xl card-shadow p-8 sm:p-10 border-t-8" style="border-top-color: var(--theme-green);">
                
                <h1 class="text-3xl font-extrabold text-center text-dark mb-2">ChefEnCuna</h1>
                <p class="text-center text-gray-500 mb-6">Inicia sesión en tu cuenta</p>


                <?php if ($message): ?>
                    <div class="p-3 mb-6 rounded-lg bg-red-100 text-red-700 border-red-400 border-l-4" role="alert">
                        <p class="text-sm"><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Correo Electrónico</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus" 
                               placeholder="ejemplo@dominio.com" required>
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                        <input type="password" id="password" name="password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus" 
                               placeholder="********" required>
                    </div>

                    <button type="submit" class="btn-form-submit w-full py-3 rounded-lg text-lg font-semibold flex items-center justify-center shadow-md">
                        <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
                    </button>
                </form>

                <a href="index.php" 
                   class="mt-4 w-full block text-center py-3 rounded-lg text-lg font-semibold 
                          bg-gray-200 hover:bg-gray-300 text-gray-800 transition shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i> Regresar al Inicio
                </a>

                <p class="mt-6 text-center text-sm text-gray-500">
                    ¿No tienes cuenta? <a href="registro.php" class="text-secondary hover:underline font-medium">Regístrate aquí</a>.
                </p>

            </div>
        </div>
    </div>
</body>
</html>