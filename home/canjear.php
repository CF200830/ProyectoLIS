<<?php
session_start();
include('conecta.php');

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se recibió el ID del cupón
if (!isset($_GET['id_cupon'])) {
    $_SESSION['error'] = "No se especificó el cupón a canjear";
    header("Location: miscupones.php");
    exit();
}

$id_cupon = intval($_GET['id_cupon']);
$id_usuario = $_SESSION['id_usuario'];

// Obtener información del cupón
$sql = "SELECT c.*, o.titulo, o.precio_oferta, o.fecha_limite, o.usos_por_cupon, e.nombre_empresa 
        FROM cupon c
        JOIN oferta o ON c.id_oferta = o.id_oferta
        JOIN empresa e ON o.id_empresa = e.id_empresa
        WHERE c.id_cupon = ? AND c.id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_cupon, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Cupón no encontrado o no pertenece a tu cuenta";
    header("Location: miscupones.php");
    exit();
}

$cupon = $result->fetch_assoc();

// Verificar si el cupón puede canjearse
$puede_canjear = false;
$mensaje_estado = "";

if ($cupon['estado'] == 'activo' && strtotime($cupon['fecha_limite']) >= time()) {
    if ($cupon['veces_utilizado'] < $cupon['usos_por_cupon']) {
        $puede_canjear = true;
    } else {
        $mensaje_estado = "Este cupón ya ha alcanzado su límite de usos";
    }
} elseif ($cupon['estado'] == 'utilizado') {
    $mensaje_estado = "Este cupón ya fue utilizado";
} elseif ($cupon['estado'] == 'caducado' || strtotime($cupon['fecha_limite']) < time()) {
    $mensaje_estado = "Este cupón ha caducado";
} else {
    $mensaje_estado = "Este cupón no puede ser canjeado";
}

// Procesar el canje si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirmar_canje']) && $puede_canjear) {
    $conn->begin_transaction();
    
    try {
        $nuevo_uso = $cupon['veces_utilizado'] + 1;
        
        if ($nuevo_uso >= $cupon['usos_por_cupon']) {
            // Último uso - marcar como utilizado
            $sql_update = "UPDATE cupon SET estado = 'utilizado', 
                          veces_utilizado = ?, 
                          fecha_utilizacion = NOW() 
                          WHERE id_cupon = ?";
        } else {
            // Aún tiene usos disponibles
            $sql_update = "UPDATE cupon SET veces_utilizado = ? 
                          WHERE id_cupon = ?";
        }
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $nuevo_uso, $id_cupon);
        $stmt_update->execute();
        
        // Registrar el canje en una tabla de historial (opcional)
        $sql_historial = "INSERT INTO historial_canje 
                         (id_cupon, id_cliente, fecha_canje, codigo_cupon) 
                         VALUES (?, ?, NOW(), ?)";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("iis", $id_cupon, $id_usuario, $cupon['codigo_cupon']);
        $stmt_historial->execute();
        
        $conn->commit();
        
        $_SESSION['success'] = "Cupón canjeado exitosamente";
        header("Location: miscupones.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error al canjear el cupón: " . $e->getMessage();
        header("Location: canjear.php?id_cupon=".$id_cupon);
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canjear Cupón - Cuponera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .cupon-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
        }
        .cupon-body {
            border-left: 4px solid #2575fc;
            border-right: 4px solid #2575fc;
            border-bottom: 4px solid #2575fc;
        }
        .codigo-cupon {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            letter-spacing: 2px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header cupon-header">
                        <h3 class="mb-0 text-center"><i class="bi bi-ticket-perforated"></i> Canjear Cupón</h3>
                    </div>
                    
                    <div class="card-body cupon-body">
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <div class="text-center mb-4">
                            <h4><?php echo htmlspecialchars($cupon['titulo']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($cupon['nombre_empresa']); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <div class="codigo-cupon mb-3">
                                <?php echo htmlspecialchars($cupon['codigo_cupon']); ?>
                            </div>
                            
                            <?php if($cupon['usos_por_cupon'] > 1): ?>
                                <div class="alert alert-info text-center">
                                    <i class="bi bi-info-circle"></i> 
                                    Usos disponibles: <?php echo ($cupon['usos_por_cupon'] - $cupon['veces_utilizado']); ?> de <?php echo $cupon['usos_por_cupon']; ?>
                                </div>
                            <?php endif; ?>
                            
                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item">
                                    <i class="bi bi-currency-dollar"></i> 
                                    <strong>Valor:</strong> $<?php echo number_format($cupon['precio_oferta'], 2); ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-calendar-check"></i> 
                                    <strong>Válido hasta:</strong> <?php echo date('d/m/Y', strtotime($cupon['fecha_limite'])); ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-person"></i> 
                                    <strong>Titular:</strong> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                                </li>
                            </ul>
                        </div>
                        
                        <?php if($puede_canjear): ?>
                            <form method="POST" action="">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> 
                                    Muestra este código al comercio para canjear tu cupón
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="confirmar_canje" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-circle"></i> Confirmar Canje
                                    </button>
                                    <a href="miscupones.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Volver a mis cupones
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger text-center">
                                <i class="bi bi-x-circle"></i> 
                                <strong>Este cupón no puede ser canjeado:</strong> <?php echo $mensaje_estado; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="miscupones.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left"></i> Volver a mis cupones
                                </a>
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