<?php
session_start();
require 'config/header.php'; 

$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "chefencuna";   

$error_message = null;
$faqs_data = [];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $error_message = "Error de Conexión a la Base de Datos: " . $conn->connect_error;
} else {
    $sql = "SELECT pregunta, respuesta FROM faqs ORDER BY orden ASC";
    $result = $conn->query($sql);

    if ($result === FALSE) {
        $error_message = "Error en la consulta SQL. Asegúrate que la tabla 'faqs' existe y es correcta. Error: " . $conn->error;
    } elseif ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $faqs_data[] = $row;
        }
    } else {
        $no_faqs_message = "No se encontraron preguntas frecuentes registradas.";
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefEnCuna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-green': '#69A64A',
                        'primary': '#69A64A',
                        'secondary': '#4ecdc4',
                        'dark': '#2d3436',
                        'light': '#f7f1e3',
                    }
                }
            }
        }
    </script>
    <style>
        :root { 
            --theme-green: #69A64A; 
            --secondary: #4ecdc4; 
            --dark: #2d3436; 
            --light: #f7f1e3;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--light); 
            color: var(--dark); 
        }
        .faq-item { border-bottom: 1px solid #e5e7eb; }
        .faq-question { background-color: white; transition: background-color 0.2s; }
        .faq-question:hover { background-color: #f9fafb; }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .faq-answer.active {
            max-height: 1000px; 
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .arrow-icon { transition: transform 0.3s; }
        .arrow-icon.active { transform: rotate(180deg); }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <main class="flex-grow">
        <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
            <header class="text-center mb-10 mt-6">
                <h1 class="text-4xl font-extrabold text-dark sm:text-5xl mb-3">
                    Preguntas Frecuentes (FAQ)
                </h1>
                <p class="text-lg text-gray-600">
                    Encuentra respuestas rápidas a las dudas más comunes sobre nuestros cursos y recetas.
                </p>
            </header>

            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">¡Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <div id="faq-accordion" class="bg-white shadow-xl rounded-xl divide-y divide-gray-200 border-t-4 border-secondary">
                <?php if (!empty($faqs_data)): ?>
                    
                    <?php foreach ($faqs_data as $index => $item): ?>
                        <div class="faq-item">
                            <button class="faq-question w-full flex justify-between items-center py-5 px-6 text-left font-semibold text-lg focus:outline-none" 
                                    aria-expanded="false" 
                                    aria-controls="answer-<?php echo $index; ?>" 
                                    onclick="toggleFAQ(this, 'answer-<?php echo $index; ?>')">
                                <span class="text-gray-800 hover:text-secondary transition duration-150">
                                    <?php echo htmlspecialchars($item['pregunta']); ?>
                                </span>
                                <svg class="arrow-icon w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div 
                                id="answer-<?php echo $index; ?>" class="faq-answer bg-gray-50" role="region">
                                <p class="text-base text-gray-700 leading-relaxed">
                                    <?php 
                                    echo nl2br(htmlspecialchars($item['respuesta'])); 
                                    ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php elseif (!isset($error_message)): ?>
                    <div class="p-6 text-center text-gray-500">
                        <?php echo $no_faqs_message ?? "No hay preguntas frecuentes registradas en el sistema."; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <script>
        function toggleFAQ(questionButton, answerId) {
            const answerElement = document.getElementById(answerId);
            const arrowIcon = questionButton.querySelector('.arrow-icon');
            
            const isActive = answerElement.classList.contains('active');

            document.querySelectorAll('.faq-answer.active').forEach(activeAnswer => {
                if (activeAnswer.id !== answerId) {
                    activeAnswer.classList.remove('active');
                    const parentItem = activeAnswer.closest('.faq-item');
                    const associatedQuestion = parentItem.querySelector('.faq-question');
                    const associatedArrow = associatedQuestion ? associatedQuestion.querySelector('.arrow-icon') : null;
                    if (associatedArrow) {
                        associatedArrow.classList.remove('active');
                    }
                    if (associatedQuestion) {
                         associatedQuestion.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (isActive) {
                answerElement.classList.remove('active');
                arrowIcon.classList.remove('active');
                questionButton.setAttribute('aria-expanded', 'false');
            } else {
                answerElement.classList.add('active');
                arrowIcon.classList.add('active');
                questionButton.setAttribute('aria-expanded', 'true');
            }
        }
    </script>
</body>
</html>