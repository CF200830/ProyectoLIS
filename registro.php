<?php
include('conecta.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT); // Encriptar
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $dui = $_POST['dui'];
    $tipo_usuario = $_POST['tipo_usuario'];

    // Aquí podrías generar un token de verificación si lo vas a usar
    $token = bin2hex(random_bytes(16)); // Opcional

    $sql = "INSERT INTO usuario (nombre, apellido, correo, contraseña, telefono, direccion, dui, tipo_usuario, token_verificacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $nombre, $apellido, $correo, $contraseña, $telefono, $direccion, $dui, $tipo_usuario, $token);

    if ($stmt->execute()) {
        echo "✅ Registro exitoso. Ahora puedes <a href='login.php'>iniciar sesión</a>.";
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="estiloregistro.css">
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

<!-- Agrega un campo oculto con el valor fijo "cliente" -->
<input type="hidden" name="tipo_usuario" value="cliente">

        <input type="submit" value="Registrarse">

        <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar sesión</a></p>
    </form>
</body>
<footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">© 2025 Cuponera. Todos los derechos reservados LIS.</p>
        </div>
    </footer>
</html>
