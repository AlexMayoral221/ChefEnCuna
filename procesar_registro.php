<?php
require 'bd.php';
include 'templates/_header.php';

$nombre = $apellido = $genero = $correo = $contrasena = "";
$nombre_err = $apellido_err = $genero_err = $correo_err = $contrasena_err = "";
$registro_exitoso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $genero = trim($_POST["genero"]);
    $correo = trim($_POST["correo"]);
    $contrasena = trim($_POST["contrasena"]);

    if (empty($nombre)) {
        $nombre_err = "Por favor ingrese un nombre.";
    }

    if (empty($apellido)) {
        $apellido_err = "Por favor ingrese un apellido.";
    }

    if (empty($genero)) {
        $genero_err = "Por favor seleccione un género.";
    }

    if (empty($correo)) {
        $correo_err = "Por favor ingrese un correo electrónico.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $correo_err = "Formato de correo electrónico inválido.";
    }

    if (empty($contrasena)) {
        $contrasena_err = "Por favor ingresa una contraseña.";
    } elseif (strlen($contrasena) < 6) {
        $contrasena_err = "La contraseña al menos debe tener 6 caracteres.";
    }

    if (empty($nombre_err) && empty($apellido_err) && empty($genero_err) && empty($correo_err) && empty($contrasena_err)) {
        $sql = "INSERT INTO usuarios (nombre, apellido, genero, correo, contraseña) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($data, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssss", $param_nombre, $param_apellido, $param_genero, $param_correo, $param_contrasena);

            $param_nombre = $nombre;
            $param_apellido = $apellido;
            $param_genero = $genero;
            $param_correo = $correo;
            $param_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);

            if (mysqli_stmt_execute($stmt)) {
                $registro_exitoso = "¡Registro exitoso! Por favor, inicia sesión.";
                mysqli_stmt_close($stmt);
                mysqli_close($data);
                header("location: login.php");
                exit;
            } else {
                $registro_exitoso = "Algo salió mal, por favor inténtalo de nuevo.";
            }
        } else {
            $registro_exitoso = "Error al preparar la consulta.";
        }
    }
}
?>