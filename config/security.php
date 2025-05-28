<?php
// Configuración de seguridad
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutos en segundos
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL_CHARS', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);

// Configuración de headers de seguridad
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; img-src \'self\' data:; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';');
}

// Validar fortaleza de contraseña
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres";
    }
    
    if (PASSWORD_REQUIRE_SPECIAL_CHARS && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un carácter especial";
    }
    
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un número";
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra mayúscula";
    }
    
    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra minúscula";
    }
    
    return $errors;
}

// Registrar intento de login fallido
function registerFailedLogin($email) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    if (!isset($_SESSION['login_attempts'][$email])) {
        $_SESSION['login_attempts'][$email] = [
            'count' => 0,
            'last_attempt' => time()
        ];
    }
    
    $_SESSION['login_attempts'][$email]['count']++;
    $_SESSION['login_attempts'][$email]['last_attempt'] = time();
}

// Verificar si el usuario está bloqueado
function isUserBlocked($email) {
    if (!isset($_SESSION['login_attempts'][$email])) {
        return false;
    }
    
    $attempts = $_SESSION['login_attempts'][$email];
    
    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        $timeSinceLastAttempt = time() - $attempts['last_attempt'];
        if ($timeSinceLastAttempt < LOGIN_TIMEOUT) {
            return true;
        } else {
            // Resetear intentos si ha pasado el tiempo de espera
            unset($_SESSION['login_attempts'][$email]);
            return false;
        }
    }
    
    return false;
}

// Limpiar intentos de login antiguos
function cleanupOldLoginAttempts() {
    if (!isset($_SESSION['login_attempts'])) {
        return;
    }
    
    foreach ($_SESSION['login_attempts'] as $email => $attempts) {
        if (time() - $attempts['last_attempt'] > LOGIN_TIMEOUT) {
            unset($_SESSION['login_attempts'][$email]);
        }
    }
}

// CSRF Token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?> 