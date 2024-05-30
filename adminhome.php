<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}

include 'templates/_header.php';

if (isset($_POST['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$db = "chefencuna";
$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si se ha enviado el formulario para eliminar una receta
if(isset($_POST['eliminar_receta'])){
    $receta_id = $_POST['receta_id'];

    // Consulta SQL para eliminar la receta
    $sql_eliminar_receta = "DELETE FROM recetas WHERE id = ?";
    $stmt_eliminar_receta = $conn->prepare($sql_eliminar_receta);
    $stmt_eliminar_receta->bind_param("i", $receta_id);

    // Ejecutar la consulta
    if ($stmt_eliminar_receta->execute()) {
        // Receta eliminada correctamente
        echo "Receta eliminada correctamente.";
        // Puedes redirigir o realizar otras acciones después de eliminar la receta
    } else {
        // Error al eliminar la receta
        echo "Error al eliminar la receta: " . $stmt_eliminar_receta->error;
    }

    // Cerrar la declaración
    $stmt_eliminar_receta->close();
}

// Agregar un nuevo instructor
if (isset($_POST["agregar_instructor"])) {
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $correo = $_POST["correo"];
    $contraseña = $_POST["contraseña"];

    if (empty($nombre) || empty($apellido) || empty($correo) || empty($contraseña) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "Error: Faltan datos o el correo electrónico no es válido.";
        exit();
    }

    $sql_check_email = "SELECT COUNT(*) FROM instructores WHERE correo = ?";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param("s", $correo);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();
    $row_check_email = $result_check_email->fetch_assoc();
    $stmt_check_email->close();

    if ($row_check_email["COUNT(*)"] > 0) {
        echo "Error: El correo electrónico ya está en uso.";
        exit();
    }

    $hashed_password = password_hash($contraseña, PASSWORD_DEFAULT);

    $sql = "INSERT INTO instructores (nombre, apellido, correo, genero, contraseña) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $apellido, $correo, $genero, $hashed_password);

    if ($stmt->execute()) {
        echo "Nuevo instructor agregado correctamente.";
        echo "<script>
                $.ajax({
                    url: 'agregar_instructor.php', 
                    type: 'POST', 
                    data: $('#form_agregar_instructor').serialize(), 
                    success: function(response) {
                        $('#lista_instructores').html(response); 
                        window.location.href = 'adminhome.php'; 
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            </script>"; 
    } else {
        echo "Error al agregar el instructor: " . $stmt->error;
    }

    $stmt->close();
}

function obtenerNombreInstructor($conn, $instructor_id) {
    $sql = "SELECT nombre, apellido FROM instructores WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['nombre'] . " " . $row['apellido'];
    }
    return "Sin instructor asignado";
}

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']);
}

// Mostrar mensaje de error si existe
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']);
}

// Obtener lista de instructores
$sql_instructores = "SELECT * FROM instructores";
$result_instructores = $conn->query($sql_instructores);
$instructores = $result_instructores->fetch_all(MYSQLI_ASSOC);

// Obtener lista de cursos
$sql_cursos = "SELECT * FROM cursos";
$result_cursos = $conn->query($sql_cursos);
$cursos = $result_cursos->fetch_all(MYSQLI_ASSOC);

// Obtener lista de recetas
$sql_recetas = "SELECT * FROM recetas";
$result_recetas = $conn->query($sql_recetas);
$recetas = $result_recetas->fetch_all(MYSQLI_ASSOC);

// Obtener lista de usuarios
$sql_usuarios = "SELECT * FROM usuarios";
$result_usuarios = $conn->query($sql_usuarios);
$usuarios = $result_usuarios->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home - ChefEnCuna</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-image: url('img/wallpaper.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .instructor-option {
            max-width: 10px; 
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Bienvenido Administrador</h1>
    <div class="text-center mt-4">
        <a href="logout.php" class="btn btn-danger mt-3">Cerrar Sesión</a>
    </div>
</div><br>

<div class="container mt-5">
    <h1 class="text-center">Gestión de Instructores</h1>

    <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#agregarInstructorModal">Agregar Instructor</button>

    <div class="row">
        <?php foreach ($instructores as $instructor): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $instructor["nombre"] . " " . $instructor["apellido"]; ?></h3>
                        <p class="card-text"><strong>Correo:</strong> <?php echo $instructor["correo"]; ?></p>
                        <p class="card-text"><strong>Género:</strong> <?php echo $instructor["genero"]; ?></p>
                        <div class="form-group row">
                            <label class="control-label col-sm-2" for="contraseña_<?php echo $instructor['id']; ?>">Contraseña:</label>
                            <div class="col-sm-10 input-group">
                                <input type="password" class="control-label col-sm-3" value="<?php echo $instructor["contraseña"]; ?>" id="contraseña_<?php echo $instructor['id']; ?>" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('<?php echo $instructor['id']; ?>')">Ver</button>
                                </div>
                            </div>
                        </div>
                        <form action="eliminar_instructor.php" method="post">
                            <input type="hidden" name="instructor_id" value="<?php echo $instructor['id']; ?>">
                            <button type="submit" class="btn btn-danger mr-2">Eliminar Instructor</button>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#cambiarContraseñaModal_<?php echo $instructor['id']; ?>">
                                Cambiar Contraseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal de cambio de contraseña -->
            <div class="modal fade" id="cambiarContraseñaModal_<?php echo $instructor['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="cambiarContraseñaModalLabel_<?php echo $instructor['id']; ?>" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cambiarContraseñaModalLabel_<?php echo $instructor['id']; ?>">Cambiar Contraseña</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Formulario para cambiar la contraseña -->
                            <form action="cambiar_contraseña.php" method="post">
                                <input type="hidden" name="instructor_id" value="<?php echo $instructor['id']; ?>">
                                <div class="form-group">
                                    <label for="nueva_contraseña_<?php echo $instructor['id']; ?>">Nueva Contraseña:</label>
                                    <input type="password" class="form-control" id="nueva_contraseña_<?php echo $instructor['id']; ?>" name="nueva_contraseña" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div><br>
<!-- Modal para agregar un nuevo instructor -->
<div class="modal fade" id="agregarInstructorModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Agregar Nuevo Instructor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo electrónico:</label>
                        <input type="email" class="form-control" id="correo" name="correo" required>
                    </div>
                    <div class="form-group">
                        <label for="genero">Género:</label>
                        <select class="form-control" id="genero" name="genero" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="contraseña">Contraseña:</label>
                        <input type="password" class="form-control" id="contraseña" name="contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-success" name="agregar_instructor">Agregar Instructor</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h1 class="text-center">Gestión de Usuarios (Estudiantes)</h1>

    <h2 class="text-center">Lista de Usuarios</h2>
    <div class="row mt-4">
        <div class="col-md-6">
            <ul class="list-group">
                <?php foreach ($usuarios as $usuario): ?>
                    <li class="list-group-item">
                        <h3><?php echo $usuario["nombre"] . " " . $usuario["apellido"]; ?></h3>
                        <p><strong>Correo: </strong> <?php echo $usuario["correo"]; ?></p>
                        <div class="form-group row">
                            <label class="control-label col-sm-2" for="contraseña_<?php echo $usuario['id']; ?>">Contraseña: </label>
                            <div class="col-sm-10 input-group">
                                <input type="password" class="control-label col-sm-3" value="<?php echo $usuario["contraseña"]; ?>" id="contraseña_<?php echo $usuario['id']; ?>" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('<?php echo $usuario['id']; ?>')">Ver</button>
                                </div>
                            </div>
                        </div>
                        <p><strong>Género:</strong> <?php echo $usuario["genero"]; ?></p>
                        <p><strong>Fecha de Registro:</strong> <?php echo $usuario["fecha_registro"]; ?></p>
                        <form action="eliminar_usuario.php" method="post" style="display:inline;">
                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                            <button type="submit" class="btn btn-danger">Eliminar Usuario</button>
                        </form>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#cambiarContraseñaModal_<?php echo $usuario['id']; ?>">
                            Cambiar Contraseña
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div><br>

<!-- Modal de cambio de contraseña -->
<div class="modal fade" id="cambiarContraseñaModal" tabindex="-1" role="dialog" aria-labelledby="cambiarContraseñaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cambiarContraseñaModalLabel">Cambiar Contraseña</h5>
            </div>
            <div class="modal-body">
                <form action="cambiar_contraseña_usuario.php" method="post">
                    <div class="form-group">
                        <label for="nueva_contraseña">Nueva Contraseña:</label>
                        <input type="password" class="form-control" id="nueva_contraseña" name="nueva_contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h1 class="text-center">Cursos Disponibles</h1>

    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#agregarCursoModal">Agregar Curso</button>

    <div class="row mt-4">
        <?php foreach ($cursos as $curso): ?>
            <div class="col-md-6">
                <table class="table border='0'">
                    <thead>
                        <tr>
                            <th colspan="2"><?php echo htmlspecialchars($curso["titulo"]); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Nivel:</strong></td>
                            <td><?php echo htmlspecialchars($curso["nivel"]); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de Creación:</strong></td>
                            <td><?php echo htmlspecialchars($curso["fecha_creacion"]); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Duración:</strong></td>
                            <td><?php echo htmlspecialchars($curso["duracion"]); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Descripción:</strong></td>
                            <td><?php echo htmlspecialchars($curso["descripcion"]); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Instructor:</strong></td>
                            <td><?php echo $curso['instructor_id'] ? obtenerNombreInstructor($conn, $curso['instructor_id']) : "Sin instructor asignado"; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <form id="form_asignar_instructor_<?php echo $curso['id']; ?>" action="asignar_instructor.php" method="post">
                                    <input type="hidden" name="curso_id" value="<?php echo htmlspecialchars($curso['id']); ?>">
                                    <div class="form-group">
                                        <label for="instructor_id_<?php echo $curso['id']; ?>">Instructor:</label>
                                        <select name="instructor_id" id="instructor_id_<?php echo $curso['id']; ?>" class="form-control">
                                            <option value="">Seleccionar Instructor</option>
                                            <?php foreach ($instructores as $instructor): ?>
                                                <option value="<?php echo htmlspecialchars($instructor['id']); ?>"><?php echo htmlspecialchars($instructor['nombre']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success">Asignar Instructor</button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <form id="form_eliminar_instructor_<?php echo $curso['id']; ?>" action="eliminar_instructor_curso.php" method="post">
                                    <input type="hidden" name="curso_id" value="<?php echo htmlspecialchars($curso['id']); ?>">
                                    <button type="submit" class="btn btn-danger">Eliminar Instructor</button>
                                </form>
                            </td>
                            <td>
                                <form action="eliminar_curso.php" method="post">
                                    <input type="hidden" name="curso_id" value="<?php echo $curso['id']; ?>">
                                    <button type="submit" name="eliminar_curso" class="btn btn-danger">Eliminar Curso</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div><br>

<!-- Modal para agregar nuevo curso -->
<div class="modal fade" id="agregarCursoModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Agregar Nuevo Curso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="guardar_curso.php" method="post">
                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" name="titulo" class="form-control" id="titulo" required>
                    </div>
                    <div class="form-group">
                        <label for="nivel">Nivel:</label>
                        <input type="text" name="nivel" class="form-control" id="nivel" required>
                    </div>
                    <div class="form-group">
                        <label for="duracion">Duración:</label>
                        <input type="text" name="duracion" class="form-control" id="duracion" required>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea name="descripcion" class="form-control" id="descripcion" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="fecha_creacion">Fecha de Creación:</label>
                        <input type="date" name="fecha_creacion" class="form-control" id="fecha_creacion" required>
                    </div>
                    <button type="submit" class="btn btn-success">Agregar Curso</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php foreach ($recetas as $receta): ?>
    <div class="col-md-6">
        <ul class="list-group">
            <li class="list-group-item">
                <h3><?php echo $receta["titulo"]; ?></h3>
                <p><strong>Descripción:</strong> <?php echo $receta["descripcion"]; ?></p>
                <p><strong>Ingredientes:</strong> <?php echo nl2br($receta["ingredientes"]); ?></p>
                <p><strong>Procedimiento:</strong> <?php echo nl2br($receta["procedimiento"]); ?></p>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display:inline;">
                    <input type="hidden" name="receta_id" value="<?php echo $receta['id']; ?>">
                    <button type="submit" name="eliminar_receta" class="btn btn-danger">Eliminar Receta</button>
                </form>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editarRecetaModal_<?php echo $receta['id']; ?>">Editar Receta</button>
            </li>
        </ul>
    </div>

    <!-- Modal para editar receta -->
    <div class="modal fade" id="editarRecetaModal_<?php echo $receta['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editarRecetaModalLabel_<?php echo $receta['id']; ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarRecetaModalLabel_<?php echo $receta['id']; ?>">Editar Receta</h5>
                </div>
                <div class="modal-body">
                    <form action="editar_receta.php" method="post">
                        <input type="hidden" name="receta_id" value="<?php echo $receta['id']; ?>">
                        <div class="form-group">
                            <label for="titulo_<?php echo $receta['id']; ?>">Título:</label>
                            <input type="text" name="titulo" class="form-control" id="titulo_<?php echo $receta['id']; ?>" value="<?php echo $receta['titulo']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="descripcion_<?php echo $receta['id']; ?>">Descripción:</label>
                            <textarea name="descripcion" class="form-control" id="descripcion_<?php echo $receta['id']; ?>" required><?php echo $receta['descripcion']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="ingredientes_<?php echo $receta['id']; ?>">Ingredientes:</label>
                            <textarea name="ingredientes" class="form-control" id="ingredientes_<?php echo $receta['id']; ?>" required><?php echo $receta['ingredientes']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="procedimiento_<?php echo $receta['id']; ?>">Procedimiento:</label>
                            <textarea name="procedimiento" class="form-control" id="procedimiento_<?php echo $receta['id']; ?>" required><?php echo $receta['procedimiento']; ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Éxito</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        <?php if (isset($_SESSION['success_message'])): ?>
            $('#successModal .modal-body').html('<?php echo addslashes($_SESSION['success_message']); ?>');
            $('#successModal').modal('show');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    });
</script>

<script>
    function togglePasswordVisibility(id) {
        var passwordField = document.getElementById('contraseña_' + id);
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
</script>
<script>
$(document).ready(function() {
    $(".btn-eye").click(function() {
        var input = $(this).closest('.input-group').find('input');
        if (input.attr("type") === "password") {
            input.attr("type", "text");
            $(this).find('i').removeClass('glyphicon-eye-open').addClass('glyphicon-eye-close');
        } else {
            input.attr("type", "password");
            $(this).find('i').removeClass('glyphicon-eye-close').addClass('glyphicon-eye-open');
        }
    });
});
</script>

</body>
</html>

<?php
$conn->close();
?>
