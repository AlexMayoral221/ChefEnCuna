<?php
session_start();
require 'config/bd.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'maestro') {
    header('Location: login.php');
    exit;
}

$maestro_id = $_SESSION['user_id'];
$receta_id = $_POST['id'] ?? null;

if (!$receta_id || !is_numeric($receta_id)) {
    header("Location: maestro_recetas.php?msg=ID inválido");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM recetas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$receta_id, $maestro_id]);
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receta) {
        header("Location: maestro_recetas.php?msg=No puedes eliminar esta receta");
        exit;
    }

    $extensiones = ['jpg', 'jpeg', 'png', 'webp'];
    foreach ($extensiones as $ext) {
        $ruta = "img/recetas/" . $receta_id . "." . $ext;
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM recetas WHERE id = ?");
    $stmt->execute([$receta_id]);

    header("Location: maestro_recetas.php?msg=Receta eliminada correctamente");
    exit;

} catch (PDOException $e) {
    header("Location: maestro_recetas.php?msg=Error eliminando receta");
    exit;
}
?>