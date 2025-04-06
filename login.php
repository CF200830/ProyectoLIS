<?php
include('conecta.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    // Buscar al usuario en la tabla `usuario`
    $sql = "SELECT * FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Cifrar la contraseña ingresada y comparar con la almacenada
        $contraseña_ingresada_cifrada = hash('sha256', $password);
        if ($contraseña_ingresada_cifrada === $row['contraseña']) {
            // Inicio de sesión exitoso para usuarios
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['nombre'] = $row['nombre'] . ' ' . $row['apellido'];
            $_SESSION['tipo_usuario'] = $row['tipo_usuario'];
            $_SESSION['correo'] = $row['correo'];

            // Redirección según tipo de usuario
            if ($row['tipo_usuario'] == 'cliente') {
                header("Location: home/home.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            echo "<p style='color:red;'>❌ Contraseña incorrecta para el usuario.</p>";
        }
    } else {
        // Si no está en usuario, buscar en empleado
        $sql = "SELECT * FROM empleado WHERE correo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Cifrar la contraseña ingresada y comparar con la almacenada
            $contraseña_ingresada_cifrada = hash('sha256', $password);
            if ($contraseña_ingresada_cifrada === $row['contraseña']) {
                // Inicio de sesión exitoso para empleados
                $_SESSION['id_empleado'] = $row['id_empleado'];
                $_SESSION['nombre'] = $row['nombre'] . ' ' . $row['apellido'];
                $_SESSION['tipo_usuario'] = $row['tipo_usuario'];
                $_SESSION['correo'] = $row['correo'];
                $_SESSION['id_empresa'] = $row['id_empresa'];

                // Redirección según tipo de empleado
                if ($row['tipo_usuario'] == 'administrador') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: vendedor/dashboard.php");
                }
                exit();
            } else {
                echo "<p style='color:red;'>❌ Contraseña incorrecta para el empleado.</p>";
            }
        } else {
            echo "<p style='color:red;'>❌ No se encontró el usuario con el correo proporcionado.</p>";
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="css/estilologin.css">
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>
    <form method="POST" action="">
        <label for="correo">Correo electrónico:</label><br>
        <input type="email" name="correo" required><br>
        <label for="password">Contraseña:</label><br>
        <input type="password" name="password" required><br>
        <input type="submit" value="Iniciar Sesión">
    </form>
    <a href="registro.php">Registrarse</a>
</body>
<footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">© 2025 Cuponera. Todos los derechos reservados LIS.</p>
        </div>
    </footer>
</html>