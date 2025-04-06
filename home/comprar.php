<?php
session_start();
include('conecta.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se recibió el ID de oferta
if (!isset($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$id_oferta = intval($_GET['id']);
$id_cliente = $_SESSION['id_usuario'];

// Obtener información de la oferta
$sql_oferta = "SELECT * FROM oferta WHERE id_oferta = ? AND estado = 'activo' 
              AND fecha_inicio <= NOW() AND fecha_fin >= NOW()";
$stmt_oferta = $conn->prepare($sql_oferta);
$stmt_oferta->bind_param("i", $id_oferta);
$stmt_oferta->execute();
$result_oferta = $stmt_oferta->get_result();

if ($result_oferta->num_rows == 0) {
    $error = "La oferta no está disponible o ha expirado";
} else {
    $oferta = $result_oferta->fetch_assoc();
}

// Procesar la compra si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirmar_compra'])) {
    // Verificar disponibilidad
    $sql_disponible = "SELECT COUNT(*) as total FROM cupon WHERE id_oferta = ? AND estado = 'activo'";
    $stmt_disponible = $conn->prepare($sql_disponible);
    $stmt_disponible->bind_param("i", $id_oferta);
    $stmt_disponible->execute();
    $result_disponible = $stmt_disponible->get_result();
    $disponibilidad = $result_disponible->fetch_assoc();
    
    if ($disponibilidad['total'] >= $oferta['cantidad_limite']) {
        $error = "Lo sentimos, se ha alcanzado el límite de cupones para esta oferta";
    } else {
        // Generar código único para el cupón
        $codigo_cupon = 'CUP-' . strtoupper(uniqid());
        $fecha_compra = date('Y-m-d H:i:s');
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // 1. Crear el cupón
            $sql_cupon = "INSERT INTO cupon (codigo_cupon, id_oferta, id_cliente, fecha_compra, estado)
                         VALUES (?, ?, ?, ?, 'activo')";
            $stmt_cupon = $conn->prepare($sql_cupon);
            $stmt_cupon->bind_param("siis", $codigo_cupon, $id_oferta, $id_cliente, $fecha_compra);
            $stmt_cupon->execute();
            $id_cupon = $stmt_cupon->insert_id;
            
            // 2. Crear la compra
            $sql_compra = "INSERT INTO compra (id_cliente, fecha_compra, total)
                          VALUES (?, ?, ?)";
            $stmt_compra = $conn->prepare($sql_compra);
            $stmt_compra->bind_param("isd", $id_cliente, $fecha_compra, $oferta['precio_oferta']);
            $stmt_compra->execute();
            $id_compra = $stmt_compra->insert_id;
            
            // 3. Crear el detalle de compra
            $sql_detalle = "INSERT INTO detalle_compra (id_compra, id_cupon, cantidad, precio_unitario)
                           VALUES (?, ?, 1, ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            $stmt_detalle->bind_param("iid", $id_compra, $id_cupon, $oferta['precio_oferta']);
            $stmt_detalle->execute();
            
            // Confirmar transacción
            $conn->commit();
            
            $success = "¡Compra realizada con éxito! Tu código de cupón es: " . $codigo_cupon;
        } catch (Exception $e) {
            // Revertir en caso de error
            $conn->rollback();
            $error = "Error al procesar la compra: " . $e->getMessage();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Compra - Cuponera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-success {
            padding: 10px 20px;
            font-size: 1.1rem;
        }
        .price-old {
            text-decoration: line-through;
            color: #6c757d;
        }
        .price-new {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Confirmar Compra</h3>
                    </div>
                    
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success">
                                <h4><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></h4>
                                <p class="mt-3">Puedes encontrar este cupón en tu sección de "Mis Cupones" en cualquier momento.</p>
                            </div>
                            <div class="text-center mt-4">
                                <a href="home.php" class="btn btn-primary">Volver al inicio</a>
                            </div>
                        <?php elseif(isset($oferta)): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="<?php echo htmlspecialchars($oferta['imagen'] ?? 'img/default.jpg'); ?>" 
                                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($oferta['titulo']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <h4><?php echo htmlspecialchars($oferta['titulo']); ?></h4>
                                    <p><?php echo htmlspecialchars($oferta['descripcion']); ?></p>
                                    
                                    <div class="mb-3">
                                        <span class="price-old">
                                            $<?php echo number_format($oferta['precio_regular'], 2); ?>
                                        </span>
                                        <span class="price-new ms-2">
                                            $<?php echo number_format($oferta['precio_oferta'], 2); ?>
                                        </span>
                                        <span class="badge bg-danger ms-2">
                                            <?php echo number_format((($oferta['precio_regular'] - $oferta['precio_oferta']) / $oferta['precio_regular']) * 100, 0); ?>% OFF
                                        </span>
                                    </div>
                                    
                                    <ul class="list-group list-group-flush mb-4">
                                        <li class="list-group-item">
                                            <i class="bi bi-calendar-check"></i> 
                                            <strong>Válido hasta:</strong> <?php echo date('d/m/Y', strtotime($oferta['fecha_limite'])); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-info-circle"></i> 
                                            <strong>Disponibles:</strong> 
                                           </li>
                                    </ul>
                                    
                                    <form method="POST" action="">
                                        <input type="hidden" name="confirmar_compra" value="1">
                                        <button type="submit" class="btn btn-success btn-lg w-100">
                                            <i class="bi bi-cart-check"></i> Confirmar Compra - $<?php echo number_format($oferta['precio_oferta'], 2); ?>
                                        </button>
                                    </form>
                                    <div class="text-center mt-3">
                                        <a href="home.php" class="text-decoration-none">
                                            <i class="bi bi-arrow-left"></i> Volver a las ofertas
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>