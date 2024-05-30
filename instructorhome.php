<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'instructor') {
    header('Location: login.php');
    exit();
}

include 'templates/_header.php';

if (isset($_POST['cerrar_sesion'])) {
    echo '<script type="text/javascript">
        if(confirm("¿Estás seguro de que deseas cerrar sesión?")) {
            window.location = "logout.php"; // Redirigir a la página de cierre de sesión si se confirma
        }
    </script>';
}

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$db = "chefencuna";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Consulta para obtener el nombre completo del instructor
$instructor_id = $_SESSION['usuario_id'];
$sql_nombre_instructor = "SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM instructores WHERE id = $instructor_id";
$result_nombre_instructor = $conn->query($sql_nombre_instructor);
$row_nombre_instructor = $result_nombre_instructor->fetch_assoc();
$nombre_completo = $row_nombre_instructor['nombre_completo'];

// Consulta para obtener los datos del instructor
$instructor_id = $_SESSION['usuario_id'];
$sql_datos_instructor = "SELECT nombre, apellido, correo, genero FROM instructores WHERE id = $instructor_id";
$result_datos_instructor = $conn->query($sql_datos_instructor);
if ($result_datos_instructor->num_rows > 0) {
    $row_datos_instructor = $result_datos_instructor->fetch_assoc();
    $nombre = $row_datos_instructor['nombre'];
    $apellido = $row_datos_instructor['apellido'];
    $correo = $row_datos_instructor['correo'];
    $genero = $row_datos_instructor['genero'];
} else {
    $nombre = "Nombre no encontrado";
    $apellido = "Apellido no encontrado";
    $correo = "Correo no encontrado";
    $genero = "Género no encontrado";
}

// Consulta para obtener los cursos asignados al instructor
$sql_cursos_instructor = "SELECT * FROM cursos WHERE instructor_id = $instructor_id";
$result_cursos_instructor = $conn->query($sql_cursos_instructor);

$cursos_instructor = array();

if ($result_cursos_instructor->num_rows > 0) {
    // Iterar sobre cada fila de resultado y almacenar los cursos en un array
    while ($row_curso = $result_cursos_instructor->fetch_assoc()) {
        $cursos_instructor[] = $row_curso;
    }
}

$imagen_perfil_instructor = "img/instructores/" . $nombre_completo . ".jpg";

// Verificar si el archivo de imagen existe
if (!file_exists($imagen_perfil_instructor)) {
    // Si la imagen no existe, utilizar una imagen por defecto
    $imagen_perfil_instructor = "img/instructores/default.jpg";
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Home - ChefEnCuna</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
    <h1 class="text-center">Bienvenido <?php echo $nombre_completo; ?></h1>
    <div class="text-center mt-4">
        <form method="post" action="">
            <button type="submit" name="cerrar_sesion" class="btn btn-danger mt-3">Cerrar Sesión</button>
        </form>
    </div><br>

    <div class="text-center mt-4">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#perfilInstructorModal">Ver Datos</button>
    </div>
</div>

<!-- Modal para mostrar los datos del instructor -->
<div class="modal fade" id="perfilInstructorModal" tabindex="-1" role="dialog" aria-labelledby="perfilInstructorModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="perfilInstructorModalLabel">Datos del Instructor</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <img src="<?php echo $imagen_perfil_instructor; ?>" alt="Foto de Perfil del Instructor" class="img-thumbnail" style="max-width: 200px;">
                    </div>
                    <div class="col-md-8">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td><?php echo $nombre; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Apellido:</strong></td>
                                    <td><?php echo $apellido; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Correo:</strong></td>
                                    <td><?php echo $correo; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Género:</strong></td>
                                    <td><?php echo $genero; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div><br>

<!-- Cursos asignados -->
<h2 class="text-center">Cursos Asignados</h2>
<div class="row">
    <?php if (!empty($cursos_instructor)): ?>
        <?php foreach ($cursos_instructor as $curso): ?>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $curso["titulo"]; ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><strong>Nivel:</strong> <?php echo $curso["nivel"]; ?></p>
                        <p class="card-text"><strong>Fecha de Creación:</strong> <?php echo $curso["fecha_creacion"]; ?></p>
                        <p class="card-text"><strong>Duración:</strong> <?php echo $curso["duracion"]; ?></p>
                        <p class="card-text"><strong>Descripción:</strong> <?php echo $curso["descripcion"]; ?></p>
                        <!-- Enlace para más detalles del curso -->
                        <a href="detalle_curso.php?id=<?php echo $curso['id']; ?>" class="btn btn-primary">Detalles</a>
                        <!-- Botón para editar el curso -->
                        <a href="editar_curso.php?id=<?php echo $curso['id']; ?>" class="btn btn-warning">Editar</a>
                        <!-- Botón para eliminar el curso -->
                        <a href="eliminar_curso.php?id=<?php echo $curso['id']; ?>" class="btn btn-danger">Eliminar</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-md-12">
            <p class="text-center">No hay cursos asignados.</p>
        </div>
    <?php endif; ?>
</div><br>

<div class="modal fade" id="agregarRecetaModal" tabindex="-1" role="dialog" aria-labelledby="agregarRecetaModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="agregarRecetaModalLabel">Agregar Receta</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" action="guardar_receta.php">
                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="ingredientes">Ingredientes:</label>
                        <textarea class="form-control" id="ingredientes" name="ingredientes" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="instrucciones">Instrucciones:</label>
                        <textarea class="form-control" id="instrucciones" name="instrucciones" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Receta</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <button type="button" class="btn btn-success btn-lg btn-block" data-toggle="modal" data-target="#agregarRecetaModal">Agregar Receta</button>
        </div>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>