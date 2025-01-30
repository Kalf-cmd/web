<?php
$host = 'localhost';
$port = 3306;
$dbname = 'tienda_zapatos';
$username = 'root';
$password = ''; // Asegúrate de que esta contraseña sea correcta

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa a la base de datos"; // Línea de depuración eliminada
} catch (PDOException $e) {
    echo "Error al conectar a la base de datos: " . $e->getMessage(); // Línea de depuración
    die();
}
?>
