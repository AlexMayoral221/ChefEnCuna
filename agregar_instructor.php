<?php
session_start();
include 'templates/_header.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Procesar el formulario para agregar un nuevo instructor
if (isset($_POST['agregar_instructor'])) {
    // Conexión a la base de datos
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "chefencuna";
    $conn = new mysqli($host, $user, $password, $db);

    // Validar la conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Obtener datos del formulario
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];

    // Validar datos de entrada
    if (empty($nombre) || empty($correo) || empty($contraseña) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Error: Faltan datos o el correo electrónico no es válido.";
        goto end; // Ir al final del código para mostrar el error
    }

    // Verificar si el correo ya está en uso
    $sql_check_email = "SELECT COUNT(*) FROM instructores WHERE correo = ?";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param("s", $correo);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();
    $row_check_email = $result_check_email->fetch_assoc();
    $stmt_check_email->close();

    if ($row_check_email['COUNT(*)'] > 0) {
        $error = "Error: El correo electrónico ya está en uso.";
        goto end; // Ir al final del código para mostrar el error
    }

    // Hash de la contraseña
    $hashed_password = password_hash($contraseña, PASSWORD_DEFAULT);

    // Insertar nuevo instructor en la base de datos
    $sql_insert = "INSERT INTO instructores (nombre, correo, contraseña) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sss", $nombre, $correo, $hashed_password);

    if ($stmt_insert->execute()) {
        $success_message = "Nuevo instructor agregado correctamente.";
    } else {
        $error = "Error al agregar el instructor: " . $stmt_insert->error;
    }

    $stmt_insert->close();
    $conn->close();

end: // Etiqueta para ir al final del código

    // Mostrar mensaje de éxito o error (según corresponda)
    if (isset($success_message)) {
        $_SESSION['success_message'] = $success_message; // Guardar mensaje en la sesión
        header('Location: admin_home.php'); // Redireccionar a la página principal
        exit();
    } elseif (isset($error)) {
        echo $error; // Mostrar el mensaje de error en la página actual
    }
} else {
    // Si no se envió el formulario, redirigir a la página principal
    header('Location: admin_home.php');
    exit();
}
?>