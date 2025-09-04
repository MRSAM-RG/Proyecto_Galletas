<?php
// Script para migrar y unificar el precio del paquete mixto
require_once '../models/MySQL.php';

echo "Iniciando migración del precio del paquete mixto...\n";

$db = new MySQL();
$db->conectar();

// Establecer el precio estándar del paquete mixto
$precio_estandar = 75000;

// Actualizar todos los productos para que tengan el mismo precio de paquete mixto
$stmt = $db->conexion->prepare("UPDATE precios_productos SET precio = ? WHERE presentacion = 'paquete_mixto'");
$stmt->bind_param("d", $precio_estandar);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo "✓ Precio del paquete mixto actualizado para {$affected_rows} productos.\n";
    echo "✓ Todos los productos ahora tienen un precio de paquete mixto de $" . number_format($precio_estandar, 0, ',', '.') . "\n";
} else {
    echo "✗ Error al actualizar los precios: " . $db->conexion->error . "\n";
}

$db->desconectar();
echo "Migración completada.\n";
?>