<?php
session_start();

// Asegúrate de que el path a 'config/bd.php' es correcto en tu entorno.
require 'config/bd.php'; 

// 1. Autorización: Solo administradores
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}

$nombre_admin = htmlspecialchars($_SESSION['user_nombre'] ?? 'Administrador');
$faqs = [];
$edit_faq = null;
$error_message = null;
$success_message = null;

// Funciones de Sanitize
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// 2. Manejo de CRUD (Add, Edit, Delete)
try {
    // --- MANEJO DE ELIMINACIÓN ---
    if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $success_message = "FAQ eliminada exitosamente.";
        } else {
            $error_message = "Error al eliminar la FAQ.";
        }
    }
    
    // --- MANEJO DE EDICIÓN (Fetch) ---
    if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
        $edit_id = (int)$_GET['edit_id'];
        $stmt = $pdo->prepare("SELECT id, pregunta, respuesta, orden FROM faqs WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_faq = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_faq) {
            $error_message = "FAQ no encontrada.";
            $edit_faq = null;
        }
    }

    // --- MANEJO DE SUBMISIÓN (Add/Update) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pregunta = sanitize_input($_POST['pregunta']);
        $respuesta = sanitize_input($_POST['respuesta']);
        $orden = isset($_POST['orden']) && is_numeric($_POST['orden']) ? (int)$_POST['orden'] : 99; // Default order
        $faq_id = isset($_POST['faq_id']) && is_numeric($_POST['faq_id']) ? (int)$_POST['faq_id'] : 0;
        
        if (empty($pregunta) || empty($respuesta)) {
            $error_message = "La pregunta y la respuesta no pueden estar vacías.";
        } else {
            if ($faq_id > 0) {
                // UPDATE
                $sql = "UPDATE faqs SET pregunta = ?, respuesta = ?, orden = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$pregunta, $respuesta, $orden, $faq_id])) {
                    $success_message = "FAQ actualizada exitosamente.";
                } else {
                    $error_message = "Error al actualizar la FAQ.";
                }
            } else {
                // INSERT
                $sql = "INSERT INTO faqs (pregunta, respuesta, orden, fecha_creacion) VALUES (?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$pregunta, $respuesta, $orden])) {
                    $success_message = "FAQ agregada exitosamente.";
                } else {
                    $error_message = "Error al agregar la FAQ.";
                }
            }
            // Recargar para limpiar el formulario después de la acción
            header('Location: admin_faqs.php?status=' . urlencode($success_message ?? $error_message));
            exit;
        }
    }
    
    // Manejo de redirección para mensajes
    if (isset($_GET['status'])) {
        if (str_contains($_GET['status'], 'exitosa')) {
             $success_message = sanitize_input($_GET['status']);
        } else {
             $error_message = sanitize_input($_GET['status']);
        }
    }

} catch (PDOException $e) {
    $error_message = "Error de Base de Datos: " . $e->getMessage();
}

// 3. Consulta de todas las FAQs
try {
    $stmt = $pdo->query("SELECT id, pregunta, SUBSTRING(respuesta, 1, 150) as respuesta_corta, orden, DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_formato FROM faqs ORDER BY orden ASC, fecha_creacion DESC");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = ($error_message ?? "") . " Error al cargar la lista: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de FAQs | ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Estilos copiados de admin_dashboard.php */
        :root { --primary: #ff6b6b; --secondary: #4ecdc4; --dark: #2d3436; --light: #f7f1e3; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; background-color: var(--light); color: var(--dark); }
        header { background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--primary); text-decoration: none; }
        .admin-card { background-color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; text-align: center; }
        .admin-card-stats:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .text-primary { color: var(--primary); }
        /* Estilos específicos para el formulario y tabla */
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border-color 0.2s;
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 107, 107, 0.2);
        }
        .btn-submit {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #e55c5c;
        }
    </style>
</head>
<body>

    <!-- Header tomado de admin_dashboard.php -->
    <header>
        <div class="logo">Gestión de FAQs</div>
        <nav class="flex items-center space-x-6">
            <a href="admin_dashboard.php" class="text-sm font-medium text-gray-700 hover:text-primary transition duration-150">
                <i class="fas fa-arrow-left mr-1"></i> Dashboard
            </a>
        </nav>
    </header>

    <div class="container mx-auto p-8 lg:p-12">
        <h1 class="text-4xl font-extrabold text-gray-800 mb-8 border-b-2 border-primary pb-2">
            Administrar Preguntas Frecuentes
        </h1>

        <!-- Mensajes de estado -->
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg" role="alert">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg" role="alert">
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulario de Añadir/Editar FAQ -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-10 border-t-4 border-secondary">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                <?php echo $edit_faq ? 'Editar FAQ' : 'Añadir Nueva FAQ'; ?>
            </h2>
            <form method="POST" action="admin_faqs.php" class="space-y-4">
                
                <?php if ($edit_faq): ?>
                    <input type="hidden" name="faq_id" value="<?php echo htmlspecialchars($edit_faq['id']); ?>">
                <?php endif; ?>

                <div>
                    <label for="pregunta" class="block text-sm font-medium text-gray-700 mb-1">Pregunta</label>
                    <input type="text" id="pregunta" name="pregunta" required 
                           class="form-input" 
                           value="<?php echo htmlspecialchars($edit_faq['pregunta'] ?? ''); ?>">
                </div>

                <div>
                    <label for="respuesta" class="block text-sm font-medium text-gray-700 mb-1">Respuesta</label>
                    <textarea id="respuesta" name="respuesta" rows="5" required 
                              class="form-textarea"><?php echo htmlspecialchars($edit_faq['respuesta'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="orden" class="block text-sm font-medium text-gray-700 mb-1">Orden (prioridad, número bajo = primero)</label>
                        <input type="number" id="orden" name="orden" 
                               class="form-input" min="1" max="999"
                               value="<?php echo htmlspecialchars($edit_faq['orden'] ?? '99'); ?>">
                    </div>
                </div>

                <div class="flex justify-between items-center pt-2">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $edit_faq ? 'Guardar Cambios' : 'Crear FAQ'; ?>
                    </button>
                    
                    <?php if ($edit_faq): ?>
                        <a href="admin_faqs.php" class="text-sm text-gray-500 hover:text-red-500 transition duration-150">
                            Cancelar Edición
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Listado de FAQs Existentes -->
        <h2 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-2">
            FAQs Registradas (<?php echo count($faqs); ?>)
        </h2>
        
        <?php if (empty($faqs)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg" role="alert">
                <p>No hay preguntas frecuentes registradas aún.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pregunta</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Respuesta</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($faqs as $faq): ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-blue-600 font-bold"><?php echo htmlspecialchars($faq['orden']); ?></td>
                            <td class="px-6 py-4 max-w-xs overflow-hidden text-ellipsis text-sm text-gray-700"><?php echo htmlspecialchars($faq['pregunta']); ?></td>
                            <td class="px-6 py-4 max-w-md overflow-hidden text-ellipsis text-sm text-gray-500"><?php echo htmlspecialchars($faq['respuesta_corta']); ?>...</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($faq['fecha_formato']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-center">
                                <a href="admin_faqs.php?edit_id=<?php echo $faq['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3 transition duration-150" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="admin_faqs.php?delete_id=<?php echo $faq['id']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar esta FAQ? Esta acción no se puede deshacer.');" class="text-red-600 hover:text-red-900 transition duration-150" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>