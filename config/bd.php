<?php
/**
 * Archivo de configuración de la conexión a la base de datos (PDO).
 * Este archivo DEBE ser incluido por todos los scripts que necesiten interactuar con la DB.
 */

// Configuración de la Base de Datos
$host = 'localhost';
$db   = 'chefencuna'; // Nombre de tu base de datos
$user = 'root';          // Tu usuario de DB
$pass = '';              // Tu contraseña de DB (dejar vacío si no hay)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Muestra errores de PDO
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve resultados como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva la emulación de prepared statements
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // En un entorno real, solo registrar el error, no exponer detalles sensibles.
     die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>