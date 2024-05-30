<?php
session_start();

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["curso_id"])) {
    $cursoId = filter_var($_POST["curso_id"], FILTER_SANITIZE_NUMBER_INT);

    // Configuración de la conexión a la base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";

    // Crear una nueva conexión a la base de datos
    $conn = new mysqli($host, $user, $password, $db);

    // Verificar si hay errores de conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Preparar la consulta SQL para eliminar el curso
    $sql = "DELETE FROM cursos WHERE id = ?";
    $stmt = $conn->prepare($sql);

    // Verificar si la preparación de la consulta fue exitosa
    if ($stmt === false) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    // Vincular el ID del curso al parámetro de la consulta
    $stmt->bind_param("i", $cursoId);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Verificar si se afectaron filas (si se eliminó el curso correctamente)
        if ($stmt->affected_rows > 0) {
            echo "El curso ha sido eliminado correctamente.";
        } else {
            echo "No se encontró ningún curso con el ID proporcionado.";
        }
    } else {
        // Error al ejecutar la consulta
        echo "Error al intentar eliminar el curso: " . $stmt->error;
    }

    // Cerrar la declaración y la conexión a la base de datos
    $stmt->close();
    $conn->close();

    // Redireccionar de vuelta a la página de administración después de eliminar el curso
    header("Location: adminhome.php");
    exit;
} else {
    // Si no se ha enviado el ID del curso o si la solicitud no es de tipo POST, redireccionar a la página de inicio del administrador
    header("Location: adminhome.php");
    exit;
}
?>