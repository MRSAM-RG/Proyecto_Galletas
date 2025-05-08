<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitización de datos
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);

    // Validación de campos vacíos
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: ../views/registro.php?error=Todos los campos son obligatorios");
        exit();
    }

    // Validación de longitud del nombre
    if (strlen($nombre) < 3 || strlen($nombre) > 50) {
        header("Location: ../views/registro.php?error=El nombre debe tener entre 3 y 50 caracteres");
        exit();
    }

    // Validación del email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../views/registro.php?error=El formato del correo electrónico no es válido");
        exit();
    }

    // Validación de contraseñas coincidentes
    if ($password !== $confirm_password) {
        header("Location: ../views/registro.php?error=Las contraseñas no coinciden");
        exit();
    }

    // Validación de fortaleza de contraseña
    $passwordErrors = validatePasswordStrength($password);
    if (!empty($passwordErrors)) {
        $errorMessage = implode(", ", $passwordErrors);
        header("Location: ../views/registro.php?error=" . urlencode($errorMessage));
        exit();
    }

    // Sanitización adicional
    $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $email = strtolower($email); // Normalizar email a minúsculas

    // Generar hash de contraseña con opciones mejoradas
    $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);

    $db = new MySQL();
    $db->conectar();
    $queryManager = new QueryManager($db);

    // Verificar si el email ya existe
    if ($queryManager->checkEmailExists($email)) {
        $db->desconectar();
        header("Location: ../views/registro.php?error=El correo ya está registrado");
        exit();
    }

    // Insertar nuevo usuario
    if ($queryManager->createUser($nombre, $email, $passwordHash)) {
        // Limpiar intentos de login antiguos
        cleanupOldLoginAttempts();
        $db->desconectar();
        header("Location: ../views/login.php?success=Registro exitoso, ahora puedes iniciar sesión");
        exit();
    } else {
        $db->desconectar();
        header("Location: ../views/registro.php?error=Error al registrar usuario");
        exit();
    }
}
// Si es GET, solo mostrar el formulario (no redirigir)
