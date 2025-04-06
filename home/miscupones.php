<?php
session_start();
include('conecta.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: home.php");
    exit();
}

// Obtener los cupones del usuario
$sql = "SELECT c.*, o.titulo, o.precio_oferta, o.fecha_limite, e.nombre_empresa 
        FROM cupon c
        JOIN oferta o ON c.id_oferta = o.id_oferta
        JOIN empresa e ON o.id_empresa = e.id_empresa
        WHERE c.id_cliente = ? 
        ORDER BY c.fecha_compra DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cupones - Cuponera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .cupon-card {
            border-left: 5px solid #0d6efd;
            transition: transform 0.3s;
        }
        .cupon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .badge-activo {
            background-color: #198754;
        }
        .badge-utilizado {
            background-color: #6c757d;
        }
        .badge-caducado {
            background-color: #dc3545;
        }
    </style>
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
                            <a class="nav-link" aria-current="page" href="home.php">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="miscupones.php"><i class="bi bi-cart-check"></i> Mis cupones</a>
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
                    </ul>
                    <form class="d-flex" role="search">
                        <input class="form-control me-2" type="search" placeholder="Buscar cupones" aria-label="Search">
                        <button class="btn btn-outline-success" type="submit">Buscar</button>
                    </form>
                </div>
            </div>
        </nav>
    </header>

    <main class="container my-5">
        <h2 class="mb-4"><i class="bi bi-cart-check"></i> Mis Cupones</h2>
        
        <?php if($result->num_rows > 0): ?>
            <div class="row">
                <?php while($cupon = $result->fetch_assoc()): 
                    // Determinar clase CSS según el estado
                    $badge_class = '';
                    if ($cupon['estado'] == 'activo') {
                        $badge_class = 'badge-activo';
                    } elseif ($cupon['estado'] == 'utilizado') {
                        $badge_class = 'badge-utilizado';
                    } else {
                        $badge_class = 'badge-caducado';
                    }
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 cupon-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><?php echo htmlspecialchars($cupon['nombre_empresa']); ?></span>
                            <span class="badge rounded-pill <?php echo $badge_class; ?>">
                                <?php echo ucfirst($cupon['estado']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($cupon['titulo']); ?></h5>
                            <p class="card-text">
                                <strong>Código:</strong> <?php echo htmlspecialchars($cupon['codigo_cupon']); ?><br>
                                <strong>Precio:</strong> $<?php echo number_format($cupon['precio_oferta'], 2); ?><br>
                                <strong>Comprado:</strong> <?php echo date('d/m/Y H:i', strtotime($cupon['fecha_compra'])); ?><br>
                                <strong>Válido hasta:</strong> <?php echo date('d/m/Y', strtotime($cupon['fecha_limite'])); ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <?php if($cupon['estado'] == 'activo'): ?>
                                <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalCupon<?php echo $cupon['id_cupon']; ?>">
                                    Ver código QR
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Modal para el código QR -->
                <div class="modal fade" id="modalCupon<?php echo $cupon['id_cupon']; ?>" tabindex="-1" aria-labelledby="modalCuponLabel<?php echo $cupon['id_cupon']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalCuponLabel<?php echo $cupon['id_cupon']; ?>">Código del cupón</h5>
                                <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div class="mb-3">
                                    <!-- Aquí iría el código QR generado -->
                                    <div style="width: 200px; height: 200px; margin: 0 auto; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                        [QR Code: <?php echo htmlspecialchars($cupon['codigo_cupon']); ?>]
                                    </div>
                                </div>
                                <h4><?php echo htmlspecialchars($cupon['codigo_cupon']); ?></h4>
                                <p class="text-muted">Muestra este código al canjear tu cupón</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary">Imprimir</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle-fill"></i> No has adquirido ningún cupón todavía.
                <a href="home.php" class="alert-link">Explora nuestras ofertas</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">© 2025 Cuponera. Todos los derechos reservados LIS.</p>
        </div>
    </footer>
</body>
</html>