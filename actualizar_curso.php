<?php
session_start();

require 'bd.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cursoId = $_POST['curso_id'];
    $titulo = $_POST['titulo'];
    $nivel = $_POST['nivel'];
    $requisitos = $_POST['requisitos'];
    $duracion = $_POST['duracion'];

    try {
        $stmt = $pdo->prepare("UPDATE cursos SET titulo = :titulo, nivel = :nivel, requisitos = :requisitos, duracion = :duracion WHERE id = :id");
        $stmt->execute(['id' => $cursoId, 'titulo' => $titulo, 'nivel' => $nivel, 'requisitos' => $requisitos, 'duracion' => $duracion]);
        
        // Redireccionar al usuario de vuelta a la página de detalle del curso
        header("Location: detalle_curso.php?id=" . $cursoId);
        exit();
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>