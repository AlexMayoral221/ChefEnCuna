<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include 'templates/_header.php'; 
echo "<div class='container'>";
echo "<h2>Correo electrónico enviado</h2>";
echo "<p>Se ha enviado un correo electrónico con instrucciones para restablecer tu contraseña.</p>";
echo "</div>";

include 'templates/_footer.php'; 
?>
