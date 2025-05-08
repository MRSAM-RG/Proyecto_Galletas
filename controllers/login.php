<?php

require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitización de datos
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if (empty($email) || empty($password)) {
        header("Location: ../views/login.php?error=Todos los campos son obligatorios");
        exit();
    }

    $db = new MySQL();
    $db->conectar();
    $queryManager = new QueryManager($db);

    // Verificar si el usuario está bloqueado
    if (isUserBlocked($email)) {
        $db->desconectar();
        header("Location: ../views/login.php?error=Demasiados intentos fallidos. Por favor, espere unos minutos.");
        exit();
    }

    // Obtener usuario
    $usuario = $queryManager->getUserByEmail($email);

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Limpiar intentos de login antiguos
        cleanupOldLoginAttempts();
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        
        $db->desconectar();
        header("Location: ../views/index.php");
        exit();
    } else {
        // Registrar intento fallido
        registerFailedLogin($email);
        
        $db->desconectar();
        header("Location: ../views/login.php?error=Credenciales incorrectas");
        exit();
    }
}
// Si es GET, solo mostrar el formulario (no redirigir)
