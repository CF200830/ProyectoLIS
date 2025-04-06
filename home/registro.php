<?php
include('conecta.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña']; // Contraseña en texto plano
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $dui = $_POST['dui'];
    $tipo_usuario = "cliente"; // Forzamos el tipo de usuario a "cliente"

    // Cifrar la contraseña usando SHA-256
    $contraseña_cifrada = hash('sha256', $contraseña);

    // Consulta SQL sin el token de verificación
    $sql = "INSERT INTO usuario (nombre, apellido, correo, contraseña, telefono, direccion, dui, tipo_usuario)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $nombre, $apellido, $correo, $contraseña_cifrada, $telefono, $direccion, $dui, $tipo_usuario);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo "<p class='success-message'>✅ Registro exitoso. Ahora puedes <a href='login.php'>iniciar sesión</a>.</p>";
    } else {
        echo "<p class='error-message'>❌ Error: " . $stmt->error . "</p>";
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="css/estiloregistro.css">
</head>
<body>
    <h2>Registro de Usuario</h2>
    <form method="POST" action="">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br>

        <label>Apellido:</label><br>
        <input type="text" name="apellido" required><br>

        <label>Correo:</label><br>
        <input type="email" name="correo" required><br>

        <label>Contraseña:</label><br>
        <input type="password" name="contraseña" required><br>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono" required><br>

        <label>Dirección:</label><br>
        <textarea name="direccion" required></textarea><br>

        <label>DUI:</label><br>
        <input type="text" name="dui" required><br>

        <!-- Campo oculto con el valor fijo "cliente" -->
        <input type="hidden" name="tipo_usuario" value="cliente">

        <input type="submit" value="Registrarse">

        <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar sesión</a></p>
    </form>
</body>
</html>