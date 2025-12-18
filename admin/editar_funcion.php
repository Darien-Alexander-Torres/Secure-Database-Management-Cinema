<?php
session_start();

if (empty($_SESSION["IdUsuario"]) || empty($_SESSION["EsAdmin"]) || (int)$_SESSION["EsAdmin"] !== 1) {
    header("Location: /cine/index.php");
    exit;
}

require_once __DIR__ . "/../config/getconex.php";

$idFuncion = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$idFuncion) {
    die("Función inválida.");
}

$errores = [];
$exito = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fechaHoraStr = $_POST["FechaHora"] ?? "";

    if ($fechaHoraStr === "") {
        $errores[] = "La fecha y hora son obligatorias.";
    }

    $fechaHoraSql = null;
    if ($fechaHoraStr !== "") {
        $dt = DateTime::createFromFormat("Y-m-d\TH:i", $fechaHoraStr);
        if ($dt === false) {
            $errores[] = "Formato de fecha y hora inválido.";
        } else {
            $fechaHoraSql = $dt->format("Y-m-d H:i:00");
        }
    }

    if (empty($errores)) {
        $sqlUpdate = "
            UPDATE Funciones
            SET FechaHora = :fecha
            WHERE IdFuncion = :id;
        ";
        $stmtU = $pdo->prepare($sqlUpdate);
        $stmtU->execute([
            ":fecha" => $fechaHoraSql,
            ":id"    => $idFuncion
        ]);
        $exito = "Horario de función actualizado correctamente.";
    }
}

$sqlF = "
    SELECT 
        f.IdFuncion,
        f.FechaHora,
        p.Titulo
    FROM Funciones f
    INNER JOIN Peliculas p ON f.IdPelicula = p.IdPelicula
    WHERE f.IdFuncion = :id;
";
$stmtF = $pdo->prepare($sqlF);
$stmtF->execute([":id" => $idFuncion]);
$funcion = $stmtF->fetch();

if (!$funcion) {
    die("Función no encontrada.");
}

$dtValue = (new DateTime($funcion["FechaHora"]))->format("Y-m-d\TH:i");

require_once __DIR__ . "/../includes/header.php";
?>

<h1 class="mb-4">Editar horario de función</h1>

<p class="mb-2">
    Película: <strong><?php echo htmlspecialchars($funcion["Titulo"]); ?></strong>
</p>

<?php if ($exito): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div>
<?php endif; ?>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $e): ?>
                <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label class="form-label">Fecha y hora de la función</label>
        <input type="datetime-local" name="FechaHora" class="form-control"
               value="<?php echo htmlspecialchars($dtValue); ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar cambios</button>
    <a href="dashboard.php" class="btn btn-secondary ms-2">Volver al panel</a>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
