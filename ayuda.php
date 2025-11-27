<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root { --primary: #ff6b6b; --secondary: #4ecdc4; --dark: #2d3436; --light: #f7f1e3; }
        body { font-family: 'Inter', sans-serif; background-color: var(--light); }
        .header-bg { background-color: var(--secondary); }
        .card-shadow { 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 
                        0 4px 6px -2px rgba(0, 0, 0, 0.05); 
        }
        .text-primary-custom { color: var(--primary); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <nav class="header-bg p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <h1 class="text-2xl font-bold text-dark">ChefEnCuna • Ayuda y Soporte</h1>

            <div class="flex items-center space-x-6">
            <div class="flex items-center gap-4">
                <a href="maestro_dashboard.php" class="text-gray-600 hover:text-gray-800">Panel</a>
            </div>
        </div>
    </nav>
<body class="bg-gray-100">

    <div class="max-w-3xl mx-auto mt-10 bg-white p-8 rounded-xl shadow">        
        <p class="text-gray-600 mb-6 text-center">
            Encuentra aquí información útil sobre cómo usar ChefEnCuna y las funciones disponibles para maestros.
        </p>

        <div class="space-y-6">

            <div class="p-4 bg-gray-50 border rounded">
                <h2 class="text-2xl font-semibold mb-2">📘 Cómo crear recetas</h2>
                <p class="text-gray-700">
                    Dirígete a la sección <strong>"Mis Recetas"</strong> y haz clic en
                    <em>"Crear nueva receta"</em>. Allí podrás agregar título, descripción,
                    ingredientes, procedimiento e imagen.
                </p>
            </div>

            <div class="p-4 bg-gray-50 border rounded">
                <h2 class="text-2xl font-semibold mb-2">✏️ Cómo editar o eliminar una receta</h2>
                <p class="text-gray-700">
                    Desde <strong>"Mis Recetas"</strong> puedes seleccionar:
                </p>
                <ul class="list-disc ml-6 text-gray-700">
                    <li><strong>Ver</strong> — para ver tu receta completa.</li>
                    <li><strong>Editar</strong> — para modificar cualquier dato.</li>
                    <li><strong>Eliminar</strong> — para borrar la receta permanentemente.</li>
                </ul>
            </div>

            <div class="p-4 bg-gray-50 border rounded">
                <h2 class="text-2xl font-semibold mb-2">👀 Cómo ver tu perfil</h2>
                <p class="text-gray-700">
                    En la opción <strong>"Ver Perfil"</strong> dentro del panel del maestro, puedes
                    visualizar tu información personal tal como la ve el sistema:
                    nombre, apellido, correo, género, foto de perfil y fecha de registro.
                </p>
            </div>

            <div class="p-4 bg-gray-50 border rounded">
                <h2 class="text-2xl font-semibold mb-2">👤 Cómo editar tu perfil</h2>
                <p class="text-gray-700">
                    En <strong>"Editar Perfil"</strong> puedes actualizar tu nombre, apellido,
                    contraseña y foto de perfil. Tu correo no puede ser modificado.
                </p>
            </div>

            <div class="p-4 bg-gray-50 border rounded">
                <h2 class="text-2xl font-semibold mb-2">🛠 Contacto del soporte</h2>
                <p class="text-gray-700">
                    Si necesitas asistencia técnica o tienes problemas con tu cuenta, puedes escribir a:<br>
                    <strong>soporte@chefcuna.com</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
