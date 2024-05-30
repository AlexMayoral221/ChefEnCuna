<?php
session_start();

require 'bd.php';
include 'templates/_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>ChefEnCuna</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
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
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Georgia', serif;
        color: #007bff;
    }
    .about-container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 30px;
    }
    .logo-container {
        text-align: right;
    }
    .logo-container img {
        width: 320px;
        display: inline; 
    }
    .text-justify {
    text-align: justify;
    }
    </style>
</head>
<body>

<div class="container">
    <div class="about-container">
        <div class="row">
            <div class="col-xs-12 col-sm-8">
                <h1>Sobre Nosotros</h1>
                <h3>Misión y Visión</h3>
                    <p class="text-justify"><strong>Misión:</strong> ChefEnCuna se dedica a desmitificar el arte de la cocina, haciéndolo accesible para todos. Nuestra plataforma proporciona cursos de cocina en línea, desde lo básico hasta técnicas avanzadas, permitiendo a los entusiastas de todas las edades y niveles de habilidad descubrir el placer de cocinar en casa.</p>
                    <p class="text-justify"><strong>Visión:</strong> Nos proyectamos como líderes en la educación culinaria en línea, creando una comunidad global donde los usuarios no solo aprendan a cocinar, sino que también compartan sus experiencias y creaciones, fomentando una cultura de colaboración y amor por la gastronomía.</p>
            </div>
            <div class="col-xs-12 col-sm-4">
                <div class="logo-container">
                    <img src="img/logo.png" alt="Logo de ChefEnCuna" class="img-responsive">
                </div>
            </div>
        </div>
    
    <h3>Historia</h3>
    <p class="text-justify">ChefEnCuna nació de la pasión compartida de un pequeño grupo de chefs y educadores por llevar el arte de la cocina a cada rincón del mundo. Identificando una falta de recursos accesibles y asequibles para el aprendizaje culinario, lanzamos nuestra plataforma en 2024, con el objetivo de ofrecer cursos de alta calidad que cualquier persona, independientemente de su experiencia previa, pudiera seguir y disfrutar.</p>

    <h3>Valores o Filosofía</h3>
    <p>En ChefEnCuna, creemos firmemente que cocinar es una forma de arte que todos deberían tener la oportunidad de explorar. Nuestros valores incluyen:</p>
    <ul>
        <li><strong>Accesibilidad:</strong> Hacemos que aprender a cocinar sea fácil y accesible para todos.</li>
        <li><strong>Comunidad:</strong> Fomentamos un ambiente donde todos pueden compartir, aprender y crecer juntos.</li>
        <li><strong>Innovación:</strong> Nos mantenemos al tanto de las últimas tendencias culinarias y tecnológicas para mejorar constantemente nuestra oferta.</li>
    </ul>

<section class="contact-info">
    <h3>Contacto</h3>
    <p>Nos encantaría escuchar tus comentarios o responder cualquier pregunta que puedas tener. Contacta con nosotros a través de:</p>
    <ul>
        <li><strong>Correo electrónico:</strong> <a href="mailto:contacto@chefencuna.com">contacto@chefencuna.com</a></li>
        <li><strong>Teléfono:</strong> <a href="tel:+1234567890">+123 456 7890</a></li>
    </ul>
</section>
</div>
</div><br>

<?php include 'templates/_footer.php'; ?>
</body>
</html>