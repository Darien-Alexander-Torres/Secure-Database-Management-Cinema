<?php
session_start();

if (empty($_SESSION["IdUsuario"]) || empty($_SESSION["EsAdmin"]) || (int)$_SESSION["EsAdmin"] !== 1) {
    header("Location: /cine/index.php");
    exit;
}

require_once __DIR__ . "/../config/getconex.php";
require_once __DIR__ . "/../includes/header.php";

$sqlResumen = "
    SELECT 
        ISNULL(SUM(MontoTotal), 0) AS TotalRecaudado,
        COUNT(*)                  AS CantidadPagos
    FROM Pagos;
";
$resumen = $pdo->query($sqlResumen)->fetch();

$sqlBoletos = "
    SELECT COUNT(*) AS BoletosVendidos
    FROM Boletos;
";
$boletos = $pdo->query($sqlBoletos)->fetch();

$sqlFunciones = "
    SELECT 
        f.IdFuncion,
        f.FechaHora,
        f.Precio,
        f.Idioma,
        f.Formato,
        p.Titulo
    FROM Funciones f
    INNER JOIN Peliculas p ON f.IdPelicula = p.IdPelicula
    ORDER BY f.FechaHora DESC;
";
$funciones = $pdo->query($sqlFunciones)->fetchAll();

$sqlPeliculas = "
    SELECT 
        IdPelicula,
        Titulo,
        Clasificacion,
        DuracionMin,
        Anio
    FROM Peliculas
    ORDER BY Titulo;
";
$peliculas = $pdo->query($sqlPeliculas)->fetchAll();
?>

<h1 class="mb-4">Panel de administrador</h1>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total recaudado</h5>
                <p class="card-text fs-4">
                    $<?php echo number_format($resumen["TotalRecaudado"], 2); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Boletos vendidos</h5>
                <p class="card-text fs-4">
                    <?php echo (int)$boletos["BoletosVendidos"]; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-bg-secondary mb-3">
            <div class="card-body">
                <h5 class="card-title">Pagos registrados</h5>
                <p class="card-text fs-4">
                    <?php echo (int)$resumen["CantidadPagos"]; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<h3 class="mt-4 mb-3">Películas</h3>

<div class="table-responsive mb-4">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Título</th>
                <th>Clasificación</th>
                <th>Duración</th>
                <th>Año</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($peliculas as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p["Titulo"]); ?></td>
                <td><?php echo htmlspecialchars($p["Clasificacion"]); ?></td>
                <td>
                    <?php echo $p["DuracionMin"] ? (int)$p["DuracionMin"] . " min" : "-"; ?>
                </td>
                <td><?php echo $p["Anio"] ?: "-"; ?></td>
                <td>
                    <a href="editar_pelicula.php?id=<?php echo (int)$p["IdPelicula"]; ?>"
                       class="btn btn-sm btn-outline-primary">
                        Editar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h3 class="mt-4 mb-3">Funciones y horarios</h3>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Fecha y hora</th>
                <th>Película</th>
                <th>Idioma</th>
                <th>Formato</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($funciones as $f): ?>
            <tr>
                <td>
                    <?php
                        $fecha = new DateTime($f["FechaHora"]);
                        echo $fecha->format("d/m/Y H:i");
                    ?>
                </td>
                <td><?php echo htmlspecialchars($f["Titulo"]); ?></td>
                <td><?php echo htmlspecialchars($f["Idioma"]); ?></td>
                <td><?php echo htmlspecialchars($f["Formato"]); ?></td>
                <td>$<?php echo number_format($f["Precio"], 2); ?></td>
                <td>
                    <a href="editar_funcion.php?id=<?php echo (int)$f["IdFuncion"]; ?>"
                       class="btn btn-sm btn-outline-primary">
                        Editar horario
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
