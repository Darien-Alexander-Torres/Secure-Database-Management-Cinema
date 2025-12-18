<?php
session_start();

if (empty($_SESSION["IdUsuario"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/getconex.php";
require_once __DIR__ . "/../includes/header.php";

$idUsuario = (int)$_SESSION["IdUsuario"];

$idFuncion = filter_input(INPUT_POST, "idFuncion", FILTER_VALIDATE_INT);
$asientos  = $_POST["asientos"] ?? [];
$metodo    = trim($_POST["metodo"] ?? "");
$titular   = trim($_POST["titular"] ?? "");

$tarjeta_numero = preg_replace("/\D/", "", $_POST["tarjeta_numero"] ?? "");
$tarjeta_tipo   = trim($_POST["tarjeta_tipo"] ?? "");
$tarjeta_mes    = trim($_POST["tarjeta_mes"] ?? "");
$tarjeta_anio   = trim($_POST["tarjeta_anio"] ?? "");
$tarjeta_cvv    = trim($_POST["tarjeta_cvv"] ?? "");

if (!$idFuncion || empty($asientos) || $metodo === "") {
    die("Datos incompletos.");
}

if ($metodo === "Tarjeta") {
    if ($tarjeta_numero === "" || strlen($tarjeta_numero) < 13 || strlen($tarjeta_numero) > 16 ||
        $tarjeta_tipo === "" || $tarjeta_mes === "" || $tarjeta_anio === "" || $tarjeta_cvv === "") {
        die("Datos de tarjeta incompletos o inválidos.");
    }
    $autorizacion = $tarjeta_tipo . " ****" . substr($tarjeta_numero, -4) .
                    " Vence " . $tarjeta_mes . "/" . $tarjeta_anio;
} else {
    $autorizacion = $titular !== "" ? $titular : "EFECTIVO-TAQUILLA";
}

$sqlFunc = "SELECT Precio FROM Funciones WHERE IdFuncion = :id";
$stmt = $pdo->prepare($sqlFunc);
$stmt->execute([":id" => $idFuncion]);
$funcion = $stmt->fetch();

if (!$funcion) {
    die("Función no encontrada.");
}

$precio     = (float)$funcion["Precio"];
$cantidad   = count($asientos);
$montoTotal = $precio * $cantidad;

try {
    $pdo->beginTransaction();

    $sqlPago = "
        INSERT INTO Pagos (IdUsuario, MontoTotal, MetodoPago, Autorizacion)
        VALUES (:idUsuario, :monto, :metodo, :aut);
    ";
    $stmtPago = $pdo->prepare($sqlPago);
    $stmtPago->execute([
        ":idUsuario" => $idUsuario,
        ":monto"     => $montoTotal,
        ":metodo"    => $metodo,
        ":aut"       => $autorizacion
    ]);
    $idPago = $pdo->lastInsertId();

    $sqlBol = "
        INSERT INTO Boletos (IdFuncion, IdAsiento, IdUsuario, IdPago, CodigoBoleto)
        VALUES (:idFuncion, :idAsiento, :idUsuario, :idPago, :codigo);
    ";
    $stmtBol = $pdo->prepare($sqlBol);

    foreach ($asientos as $idAsiento) {
        $idAsiento = (int)$idAsiento;
        if ($idAsiento <= 0) {
            continue;
        }

        $codigo = "CINE-" . date("YmdHis") . "-" . $idFuncion . "-" . $idAsiento . "-" . bin2hex(random_bytes(2));

        $stmtBol->execute([
            ":idFuncion" => $idFuncion,
            ":idAsiento" => $idAsiento,
            ":idUsuario" => $idUsuario,
            ":idPago"    => $idPago,
            ":codigo"    => $codigo
        ]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al procesar la compra: " . $e->getMessage());
}

$sqlTickets = "
    SELECT 
        b.CodigoBoleto,
        f.FechaHora,
        p.Titulo,
        s.NombreSala,
        a.Fila,
        a.Numero,
        f.Idioma,
        f.Formato
    FROM Boletos b
    INNER JOIN Funciones f ON b.IdFuncion = f.IdFuncion
    INNER JOIN Peliculas p ON f.IdPelicula = p.IdPelicula
    INNER JOIN Salas s     ON f.IdSala     = s.IdSala
    INNER JOIN Asientos a  ON b.IdAsiento  = a.IdAsiento
    WHERE b.IdPago = :idPago
    ORDER BY a.Fila, a.Numero;
";
$stmtT = $pdo->prepare($sqlTickets);
$stmtT->execute([":idPago" => $idPago]);
$boletos = $stmtT->fetchAll();
?>

<h1 class="mb-3">Compra realizada</h1>

<p>Tu compra se realizó correctamente.</p>
<p><strong>Boletos:</strong> <?php echo (int)$cantidad; ?></p>
<p><strong>Total:</strong> $<?php echo number_format($montoTotal, 2); ?></p>

<button type="button" class="btn btn-secondary btn-no-print mb-3"
        onclick="window.print();">
    Imprimir boletos
</button>

<?php foreach ($boletos as $b): ?>
    <div class="ticket-print mb-3">
        <h4 class="mb-2">Cine Capibaras Mx</h4>
        <p class="mb-1"><strong>Película:</strong> <?php echo htmlspecialchars($b["Titulo"]); ?></p>
        <p class="mb-1">
            <strong>Fecha y hora:</strong>
            <?php
                $fecha = new DateTime($b["FechaHora"]);
                echo $fecha->format("d/m/Y H:i");
            ?>
        </p>
        <p class="mb-1">
            <strong>Sala:</strong> <?php echo htmlspecialchars($b["NombreSala"]); ?>
            |
            <strong>Asiento:</strong> <?php echo htmlspecialchars($b["Fila"] . $b["Numero"]); ?>
        </p>
        <p class="mb-1">
            <strong>Idioma:</strong> <?php echo htmlspecialchars($b["Idioma"]); ?>
            |
            <strong>Formato:</strong> <?php echo htmlspecialchars($b["Formato"]); ?>
        </p>
        <p class="mb-0">
            <strong>Código de boleto:</strong>
            <?php echo htmlspecialchars($b["CodigoBoleto"]); ?>
        </p>
    </div>
<?php endforeach; ?>

<a href="../index.php" class="btn btn-primary btn-no-print mt-3">Volver a la cartelera</a>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
