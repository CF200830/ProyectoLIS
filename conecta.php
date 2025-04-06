<?php
$servername = "localhost"; // Cambia si es necesario
$username = "root";         // Cambia si es necesario
$password = "";             // Cambia si es necesario
$dbname = "cuponera"; // Cambia por tu nombre de base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>