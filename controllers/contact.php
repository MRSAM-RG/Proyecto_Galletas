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
    echo '<h2>Mensaje enviado correctamente</h2><a href="../views/index.php">Volver</a>';
} catch (Exception $e) {
    echo '<h2>Error al enviar el mensaje: ' . htmlspecialchars($e->getMessage()) . '</h2><a href="../views/index.php">Volver</a>';
}
?> 