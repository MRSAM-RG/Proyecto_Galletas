<?php
require_once __DIR__ . '/../models/MailHandler.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido');
}

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

if (!$name || !$email || !$subject || !$message) {
    die('Todos los campos son requeridos');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Email inválido');
}

try {
    $mailHandler = new MailHandler();
    $mailHandler->sendContactEmail($name, $email, $subject, $message);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Mensaje Enviado</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f5f5f5;
            }
            .message-container {
                text-align: center;
                padding: 2rem;
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .success-message {
                color: #28a745;
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            .loading {
                color: #666;
                font-size: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="message-container">
            <div class="success-message">✓ Mensaje enviado correctamente</div>
            <div class="loading">Redirigiendo en unos segundos...</div>
        </div>
    </body>
    </html>
    <?php
    header('refresh:3;url=../views/index.php');
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f5f5f5;
            }
            .message-container {
                text-align: center;
                padding: 2rem;
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .error-message {
                color: #dc3545;
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            .back-link {
                display: inline-block;
                margin-top: 1rem;
                padding: 0.5rem 1rem;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background-color 0.3s;
            }
            .back-link:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="message-container">
            <div class="error-message">✕ Error al enviar el mensaje: <?php echo htmlspecialchars($e->getMessage()); ?></div>
            <a href="../views/index.php" class="back-link">Volver</a>
        </div>
    </body>
    </html>
    <?php
}
?> 