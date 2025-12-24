<?php
session_start();
header('Content-Type: application/json');

require 'config/bd.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión para marcar favoritos.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['receta_id']) || !is_numeric($_POST['receta_id'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$receta_id = (int)$_POST['receta_id'];
$pdo = $pdo ?? null;

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM recetas_favoritas WHERE usuario_id = ? AND receta_id = ?");
    $stmt->execute([$user_id, $receta_id]);
    $favorito = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($favorito) {
        $stmt_delete = $pdo->prepare("DELETE FROM recetas_favoritas WHERE id = ?");
        $stmt_delete->execute([$favorito['id']]);
        
        echo json_encode([
            'success' => true, 
            'is_favorite' => false, 
            'message' => 'Receta eliminada de favoritos.'
        ]);
        
    } else {
        $stmt_insert = $pdo->prepare("INSERT INTO recetas_favoritas (usuario_id, receta_id) VALUES (?, ?)");
        $stmt_insert->execute([$user_id, $receta_id]);
        
        echo json_encode([
            'success' => true, 
            'is_favorite' => true, 
            'message' => 'Receta agregada a favoritos.'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error DB: ' . $e->getMessage()]);
}
?>