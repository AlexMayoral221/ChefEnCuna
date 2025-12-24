<?php
session_start();
require 'config/bd.php'; 

$active_page = 'manage_users';

$message = '';
$message_type = '';
$users = [];

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("❌ Error Crítico: No se pudo establecer la conexión a la base de datos (PDO). Verifique el archivo 'config/bd.php'.");
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'administrador') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = (int)$_POST['delete_user_id'];
    $current_user_id = (int)$_SESSION['user_id'];

    if ($delete_id === $current_user_id) {
        $_SESSION['flash_message'] = "🚫 Error: No puedes eliminar tu propia cuenta de administrador.";
        $_SESSION['flash_type'] = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            
            if ($stmt->execute([$delete_id]) && $stmt->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['flash_message'] = "✅ Usuario (ID: $delete_id) eliminado exitosamente.";
                $_SESSION['flash_type'] = 'success';
            } else {
                $pdo->rollBack();
                $_SESSION['flash_message'] = "❌ Error: No se encontró el usuario (ID: $delete_id) o la eliminación falló.";
                $_SESSION['flash_type'] = 'error';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "❌ Error al eliminar el usuario: " . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
    }
    header('Location: admin_manage_users.php');
    exit;
}

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

try {
    $stmt = $pdo->query("SELECT id, nombre, email, rol FROM usuarios ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = ($message ? $message . '<br>' : '') . "❌ Error al obtener la lista de usuarios: " . $e->getMessage();
    $message_type = $message_type === 'success' ? 'warning' : 'error';
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1e272e', 
                        'primary-light': '#f4f7f6', 
                        'accent': '#4ecdc4',
                        'primary-accent': '#ff6b6b', 
                        'text-base': '#4a4a4a', 
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
        } 
        .nav-link { 
            transition: background-color 0.2s, color 0.2s; 
        }
        .nav-link.active { 
            background-color: #ff6b6b; color: white; 
            border-radius: 0.5rem; 
        }
        .nav-link:not(.active):hover { 
            background-color: rgba(255, 107, 107, 0.1); 
            color: #ff6b6b; 
            border-radius: 0.5rem; 
        }
        .btn-primary {
            background-color: #ff6b6b; 
            color: white; 
            transition: background-color 0.2s; 
        }
        .btn-primary:hover { 
            background-color: #d84a4a; 
        }
        .table-header { 
            background-color: #1e272e; 
            color: white; 
        }
        @media (min-width: 1024px) {
            .ml-64 { margin-left: 16rem; }
        }
    </style>
</head>
<body class="bg-primary-light text-text-base">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-sidebar flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex-shrink-0">
            <h1 class="text-2xl font-extrabold text-primary-dark tracking-wide">ChefEnCuna<span class="text-primary-accent">.</span></h1>
        </div>

        <nav class="flex-grow p-4 space-y-2 overflow-y-auto">
            <a href="admin_dashboard.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'dashboard' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                Dashboard
            </a>
            
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Gestión de Contenido</p>
            
            <a href="admin_manage_courses.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_courses' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-book w-5 mr-3 text-accent"></i>
                Cursos
            </a>

            <a href="admin_manage_recipes.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_recipes' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-utensils w-5 mr-3 text-primary-accent"></i>
                Recetas
            </a>
            <p class="text-xs text-gray-400 uppercase font-bold pt-4 pb-1 px-3">Usuarios y Comunidad</p>

            <a href="admin_manage_users.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_users' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-users-cog w-5 mr-3 text-blue-500"></i>
                Cuentas de Usuarios
            </a>
            
            <a href="admin_manage_bios.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'manage_bios' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-address-card w-5 mr-3 text-purple-500"></i>
                Biografías Maestros
            </a>

            <a href="foro_ayuda.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'foro_ayuda' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-comments w-5 mr-3 text-green-500"></i>
                Moderar Foro
            </a>
            
            <a href="admin_faqs.php" class="nav-link flex items-center p-3 font-semibold transition duration-150 <?php echo ($active_page === 'admin_faqs' ? 'active' : 'text-gray-600'); ?>">
                <i class="fas fa-question-circle w-5 mr-3 text-orange-500"></i>
                FAQs
            </a>
        </nav>

        <div class="p-6 border-t border-gray-100 flex-shrink-0">
            <a href="logout.php" class="flex items-center text-primary-accent font-medium hover:text-red-700 transition duration-200">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
        </div>
    </aside>
    
    <div class="flex-1 overflow-y-auto ml-64">
        
        <header class="bg-white shadow-md p-4 sticky top-0 z-10">
             <div class="flex justify-between items-center max-w-7xl mx-auto">
                <h2 class="text-xl font-bold text-text-base">
                    Usuarios y Comunidad / <span class="text-accent">Gestión de Usuarios</span>
                </h2>
                <a href="admin_add_user.php" class="btn-primary px-4 py-2 rounded-xl font-semibold hover:opacity-90 transition duration-150 text-sm inline-flex items-center shadow-lg">
                    <i class="fas fa-user-plus mr-2"></i> Añadir Nuevo Usuario
                </a>
            </div>
        </header>
        
        <main class="max-w-7xl mx-auto p-4 lg:p-10">

            <div class="bg-white rounded-xl shadow-2xl p-6 md:p-10">
                
                <header class="mb-8 border-b pb-4">
                    <h1 class="text-3xl font-extrabold text-primary-dark flex items-center">
                        <i class="fas fa-users-cog mr-3 text-accent"></i> Cuentas de Usuarios Registrados
                    </h1>
                    <p class="text-gray-500 mt-2">Revisa y administra las cuentas de todos los usuarios, incluyendo cambios de rol y eliminación.</p>
                </header>

                <?php if ($message): ?>
                    <div id="alertMessage" class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-500' : 'bg-red-100 text-red-700 border-red-500'; ?> border-l-4 font-sans text-base" role="alert" style="transition: opacity 0.5s ease-out;">
                        <p class="font-bold mb-1">Mensaje del sistema:</p>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto shadow-xl rounded-xl border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="table-header">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider rounded-tl-xl">ID</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Nombre</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Rol</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider rounded-tr-xl">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 whitespace-nowrap text-center text-lg text-gray-500">
                                        <i class="fas fa-sad-tear text-4xl text-gray-300 mb-3"></i>
                                        <p>No hay usuarios registrados.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php $is_current_user = $user['id'] === $_SESSION['user_id']; ?>
                                    <tr class="<?php echo $is_current_user ? 'bg-indigo-50 font-semibold border-l-4 border-indigo-500' : 'hover:bg-gray-50'; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['nombre']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full 
                                                <?php 
                                                    if ($user['rol'] == 'administrador') echo 'bg-primary-accent text-white';
                                                    elseif ($user['rol'] == 'maestro') echo 'bg-accent text-primary-dark';
                                                    else echo 'bg-gray-200 text-gray-800';
                                                ?>">
                                                <?php echo ucfirst(htmlspecialchars($user['rol'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            
                                            <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>" class="text-accent hover:text-teal-700 mr-4 inline-flex items-center p-2 rounded-full hover:bg-teal-50 transition duration-150" title="Editar Usuario">
                                                <i class="fas fa-edit text-lg"></i>
                                            </a>

                                            <?php if (!$is_current_user): ?>
                                                <button 
                                                    class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition duration-150 delete-btn" 
                                                    data-id="<?php echo $user['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($user['nombre']); ?>"
                                                    title="Eliminar Usuario">
                                                    <i class="fas fa-trash-alt text-lg"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-gray-300 p-2 cursor-not-allowed" title="No puedes eliminar tu propia cuenta">
                                                    <i class="fas fa-lock text-lg"></i> 
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
    
    <div id="delete-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center p-4 z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-8 transform transition-transform duration-300 scale-95">
            <h3 class="text-2xl font-bold mb-4 text-primary-accent flex items-center border-b pb-2"><i class="fas fa-exclamation-triangle mr-3"></i> Confirmar Eliminación de Usuario</h3>
            <p id="modal-text" class="mb-6 text-gray-700 leading-relaxed"></p>
            
            <form id="delete-form" method="POST" action="admin_manage_users.php" class="flex justify-end space-x-3">
                <input type="hidden" name="delete_user_id" id="modal-delete-id" value="">

                <button type="button" id="cancel-delete" class="px-5 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-primary-accent text-white font-semibold rounded-lg hover:bg-red-700 transition duration-150 inline-flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Eliminar Usuario
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const modal = document.getElementById('delete-modal');
            const modalText = document.getElementById('modal-text');
            const modalDeleteIdInput = document.getElementById('modal-delete-id');
            const cancelDeleteButton = document.getElementById('cancel-delete');
            const messageDiv = document.getElementById('alertMessage');

            deleteButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const userId = e.currentTarget.getAttribute('data-id');
                    const userName = e.currentTarget.getAttribute('data-name');
                    
                    modalText.innerHTML = `¿Está **absolutamente seguro** de que desea eliminar permanentemente la cuenta de **${userName}** (ID: ${userId})? <br><br><span class="text-sm text-red-600 font-medium flex items-center"><i class="fas fa-exclamation-circle mr-1"></i> Esta acción es irreversible y eliminará todos los datos asociados a este usuario.</span>`;
                    
                    modalDeleteIdInput.value = userId;
                    
                    modal.classList.remove('hidden');
                });
            });

            cancelDeleteButton.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            if (messageDiv) {
                const hideMessage = () => {
                    messageDiv.style.opacity = '0';
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 500); 
                };
                
                setTimeout(hideMessage, 7000); 
            }
        });
    </script>
</body>
</html>