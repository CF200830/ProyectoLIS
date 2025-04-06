<?php
// Inicio del código PHP para manejar la sesión
session_start();
include('conecta.php');

$error = '';

// Procesar el formulario de login si se envió
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    // Buscar en la tabla usuario (clientes/proveedores)
    $sql = "SELECT * FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verificar contraseña (deberías usar password_verify() si usas hash)
        if ($password === $row['contraseña']) {
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['nombre'] = $row['nombre'] . ' ' . $row['apellido'];
            $_SESSION['tipo_usuario'] = $row['tipo_usuario'];
            $_SESSION['correo'] = $row['correo'];
            
            // Recargar la página para actualizar el estado
            header("Location: home.php");
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
    $stmt->close();
}

// Procesar logout si se solicitó
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: home.php");
    exit();
}

// Consulta para obtener las ofertas vigentes
$sql_ofertas = "SELECT * FROM oferta WHERE fecha_inicio <= NOW() AND fecha_fin >= NOW() AND estado = 'activo'";
$result_ofertas = $conn->query($sql_ofertas);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuponera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

    <header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
          <a class="navbar-brand" href="home.php"> <i class="bi bi-ticket-fill"></i>Cuponera</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="home.php">Inicio</a>
              </li>
              <?php if(isset($_SESSION['id_usuario'])): ?>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="miscupones.php"><i class="bi bi-cart-check"></i> Mis cupones</a>
                </li>
                <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="canjear.php">Canjear</a>
              </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="perfil.php">Mi perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="home.php?logout=1">Cerrar sesión</a></li>
                    </ul>
                </li>
              <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link active bg-primary-subtle text-primary rounded" aria-current="page" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-person-circle"></i> Iniciar sesión
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="registro.php"><i class="bi bi-person-plus"></i> Registrarse</a>
                </li>
              <?php endif; ?>
            </ul>
            <form class="d-flex" role="search">
              <input class="form-control me-2" type="search" placeholder="busqueda" aria-label="Search">
              <button class="btn btn-outline-success" type="submit">buscar</button>
            </form>
          </div>
        </div>
      </nav>
    </header>

    <!-- Modal de Login -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="loginModalLabel">Iniciar Sesión</h5>
            <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="home.php">
                <input type="hidden" name="login" value="1">
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
            </form>
          </div>
          <div class="modal-footer justify-content-center">
            <span>¿No tienes cuenta? <a href="registro.php">Regístrate</a></span>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div id="carouselExampleDark" class="carousel carousel-dark slide">
        
        <div class="carousel-inner">
            <?php if($result_ofertas && $result_ofertas->num_rows > 0): ?>
                <?php while($row = $result_ofertas->fetch_assoc()): ?>
                    <div class="carousel-item <?php echo ($row['id_oferta'] == 1) ? 'active' : ''; ?>" data-bs-interval="2000">
                        <div class="d-flex justify-content-center">
                          <div class="card" style="width: 18rem;">
                            <a href="detalledecupon.php?id=<?php echo $row['id_oferta']; ?>">
                            <img src="<?php echo $row['imagen'] ?? 'img/tiket.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['titulo']); ?>">
                            </a>
                            <div class="card-body text-center">
                              <h5 class="card-title"><?php echo htmlspecialchars($row['titulo']); ?></h5>
                              <p class="card-text"><?php echo htmlspecialchars($row['descripcion']); ?></p>
                              <?php if(isset($_SESSION['id_usuario'])): ?>
                                <a href="comprar.php?id=<?php echo $row['id_oferta']; ?>" class="btn btn-primary">Comprar</a>
                              <?php else: ?>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Inicia sesión para comprar</button>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="carousel-item active" data-bs-interval="2000">
                    <div class="d-flex justify-content-center">
                      <div class="card" style="width: 18rem;">
                        <img src="img/tiket.png" class="card-img-top" alt="Sin ofertas">
                        <div class="card-body text-center">
                          <h5 class="card-title">No hay ofertas disponibles</h5>
                          <p class="card-text">Vuelve más tarde para ver nuestras promociones</p>
                        </div>
                      </div>
                    </div>
                  </div>
            <?php endif; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>

    <!-- Resto de tu contenido HTML (categorías, testimonios, etc.) -->
    <section class="container my-5">
        <h3 class="text-center mb-4">Categorías Populares</h3>
        <div class="row justify-content-center">
            <div class="col-6 col-md-3 text-center mb-4">
                <a href="?categoria=restaurantes" class="text-decoration-none text-dark">
                    <img src="img/restaurantes.png" alt="Restaurantes" class="img-fluid mb-2" style="width: 80px;">
                    <div>Restaurantes</div>
                </a>
            </div>
            <!-- Más categorías -->
        </div>
    </section>

    <!-- Resto de tu HTML... -->

</body>
<footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">© 2025 Cuponera. Todos los derechos reservados LIS.</p>
        </div>
    </footer>
</html>