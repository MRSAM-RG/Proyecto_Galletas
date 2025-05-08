<?php

session_start();

session_unset(); // Destruir todas las variables de sesión

session_destroy(); // Destruir la sesión

header("Location: ../views/index.php"); // Redirigir a la página de inicio
exit(); // Asegurarse de que no se ejecute más código después de la redirección

?>