<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["agregar_instructor"])) {

    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";

    // Crear conexión a la base de datos
    $conn = new mysqli($host, $user, $password, $db);

    // Verificar la conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Recuperar los datos del formulario
    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $contraseña = $_POST["contraseña"];

    // Preparar la consulta SQL para insertar un nuevo instructor
    $sql = "INSERT INTO instructores (nombre, correo, contraseña) VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("sss", $nombre, $correo, $contraseña);

    if ($stmt->execute()) {

        header("Location: gestion_instructores.php?success=1");
        exit();
    } else {
        // Si hay algún error al ejecutar la consulta SQL, mostrar un mensaje de error
        echo "Error al agregar el instructor: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: index.php");
    exit();
}
?>
