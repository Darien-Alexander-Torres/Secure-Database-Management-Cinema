<?php
session_start();

if (empty($_SESSION["IdUsuario"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/getconex.php";
require_once __DIR__ . "/../includes/header.php";

$idFuncion = filter_input(INPUT_GET, "idFuncion", FILTER_VALIDATE_INT);
if (!$idFuncion) {
    die("Función inválida.");
}

$sqlF = "
    SELECT 
        f.IdFuncion,
        f.FechaHora,
        f.Precio,
        f.Idioma,
        f.Formato,
        s.NombreSala,
        f.IdSala,
        p.Titulo
    FROM Funciones f
    INNER JOIN Salas s     ON f.IdSala     = s.IdSala
    INNER JOIN Peliculas p ON f.IdPelicula = p.IdPelicula
    WHERE f.IdFuncion = :id;
";
$stmtF = $pdo->prepare($sqlF);
$stmtF->execute([":id" => $idFuncion]);
$funcion = $stmtF->fetch();

if (!$funcion) {
    die("Función no encontrada.");
}

$sqlA = "
    SELECT 
        a.IdAsiento,
        a.Fila,
        a.Numero,
        CASE WHEN b.IdBoleto IS NULL THEN 0 ELSE 1 END AS Ocupado
    FROM Asientos a
    LEFT JOIN Boletos b
      ON b.IdAsiento = a.IdAsiento
     AND b.IdFuncion = :idFuncion
     AND b.Estado = 'COMPRADO'
    WHERE a.IdSala = :idSala
    ORDER BY a.Fila, a.Numero;
";
$stmtA = $pdo->prepare($sqlA);
$stmtA->execute([
    ":idFuncion" => $idFuncion,
    ":idSala"    => $funcion["IdSala"]
]);
$asientos = $stmtA->fetchAll();

$filas = [];
foreach ($asientos as $a) {
    $filas[$a["Fila"]][] = $a;
}
?>

<style>
.asientos-wrapper {
    max-width: 600px;
    margin: 0 auto;
}
.pantalla-cine {
    width: 100%;
    padding: 6px;
    text-align: center;
    background: #212529;
    color: #fff;
    border-radius: 4px;
    font-weight: bold;
    margin-bottom: 10px;
}
.tarjeta-fields {
    display: none;
}
</style>

<h1 class="mb-3">Compra de boletos</h1>

<div class="mb-3">
    <strong>Película:</strong> <?php echo htmlspecialchars($funcion["Titulo"]); ?><br>
    <strong>Sala:</strong> <?php echo htmlspecialchars($funcion["NombreSala"]); ?><br>
    <strong>Fecha y hora:</strong>
    <?php
    $fecha = new DateTime($funcion["FechaHora"]);
    echo $fecha->format("d/m/Y H:i");
    ?><br>
    <strong>Precio por boleto:</strong> $<?php echo number_format($funcion["Precio"], 2); ?><br>
    <strong>Idioma / Formato:</strong>
    <?php echo htmlspecialchars($funcion["Idioma"]); ?> /
    <?php echo htmlspecialchars($funcion["Formato"]); ?>
</div>

<form method="post" action="procesar_compra.php" class="card p-3 mb-4">

    <input type="hidden" name="idFuncion" value="<?php echo (int)$idFuncion; ?>">

    <h5 class="mb-2">Selecciona tus asientos</h5>

    <div class="asientos-wrapper mb-3">
        <div class="pantalla-cine">PANTALLA</div>

        <?php foreach ($filas as $fila => $asientosFila): ?>
            <div class="d-flex mb-1 align-items-center">
                <div class="me-2 fw-bold" style="width:40px;">
                    <?php echo htmlspecialchars($fila); ?>
                </div>
                <div class="d-flex flex-wrap">
                    <?php foreach ($asientosFila as $a): ?>
                        <?php
                        $ocupado = (int)$a["Ocupado"] === 1;
                        $label   = $a["Numero"];
                        ?>
                        <div class="m-1">
                            <?php if ($ocupado): ?>
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        disabled><?php echo htmlspecialchars($label); ?></button>
                            <?php else: ?>
                                <label class="btn btn-sm btn-success">
                                    <input type="checkbox" name="asientos[]"
                                           value="<?php echo (int)$a["IdAsiento"]; ?>"
                                           class="form-check-input me-1">
                                    <?php echo htmlspecialchars($label); ?>
                                </label>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <small class="text-muted mt-1 d-block">Rojo = ocupado, Verde = disponible</small>
    </div>

    <hr>

    <h5 class="mb-2">Pago</h5>

    <div class="mb-3">
        <label class="form-label">Método de pago</label>
        <select name="metodo" class="form-select" required>
            <option value="Tarjeta">Tarjeta</option>
            <option value="Efectivo">Efectivo en taquilla</option>
        </select>
    </div>

    <div class="tarjeta-fields mb-3">
        <label class="form-label">Número de tarjeta</label>
        <input type="text" name="tarjeta_numero" maxlength="16"
               class="form-control" placeholder="Solo dígitos">
    </div>

    <div class="row tarjeta-fields">
        <div class="col-md-4 mb-3">
            <label class="form-label">Tipo</label>
            <select name="tarjeta_tipo" class="form-select">
                <option value="">Seleccione</option>
                <option value="VISA">VISA</option>
                <option value="MASTERCARD">MasterCard</option>
                <option value="AMEX">American Express</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Mes vencimiento</label>
            <input type="text" name="tarjeta_mes" maxlength="2"
                   class="form-control" placeholder="MM">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Año vencimiento</label>
            <input type="text" name="tarjeta_anio" maxlength="4"
                   class="form-control" placeholder="AAAA">
        </div>
    </div>

    <div class="mb-3 tarjeta-fields">
        <label class="form-label">CVV</label>
        <input type="password" name="tarjeta_cvv" maxlength="4"
               class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Nombre del titular (opcional)</label>
        <input type="text" name="titular" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Confirmar compra</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var metodoSelect = document.querySelector("select[name='metodo']");
    var tarjetaFields = document.querySelectorAll(".tarjeta-fields");

    function toggleTarjetaFields() {
        var isTarjeta = metodoSelect.value === "Tarjeta";
        tarjetaFields.forEach(function (el) {
            el.style.display = isTarjeta ? "block" : "none";
        });
    }

    if (metodoSelect) {
        metodoSelect.addEventListener("change", toggleTarjetaFields);
        toggleTarjetaFields();
    }
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

