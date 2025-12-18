<?php
session_start();
require_once __DIR__ . "/../config/getconex.php";
require_once __DIR__ . "/../includes/header.php";

$idPelicula = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$idPelicula) {
    die("Película inválida.");
}

$sqlP = "
    SELECT IdPelicula, Titulo, Sinopsis, Clasificacion, DuracionMin, Anio, PosterUrl
    FROM Peliculas
    WHERE IdPelicula = :id;
";
$stmtP = $pdo->prepare($sqlP);
$stmtP->execute([":id" => $idPelicula]);
$pelicula = $stmtP->fetch();

if (!$pelicula) {
    die("Película no encontrada.");
}

$sqlF = "
    SELECT 
        f.IdFuncion,
        f.FechaHora,
        f.Precio,
        f.Idioma,
        f.Formato,
        s.NombreSala
    FROM Funciones f
    INNER JOIN Salas s ON f.IdSala = s.IdSala
    WHERE f.IdPelicula = :id
      AND f.Activa = 1
    ORDER BY f.FechaHora;
";
$stmtF = $pdo->prepare($sqlF);
$stmtF->execute([":id" => $idPelicula]);
$funciones = $stmtF->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-4">
        <?php if (!empty($pelicula['PosterUrl'])): ?>
            <img src="<?php echo htmlspecialchars($pelicula['PosterUrl']); ?>"
                 class="img-fluid rounded shadow-sm mb-3"
                 alt="Póster">
        <?php else: ?>
            <div style="height:360px;background:#eee"
                 class="d-flex align-items-center justify-content-center text-muted rounded">
                Sin póster
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <h1 class="mb-3"><?php echo htmlspecialchars($pelicula['Titulo']); ?></h1>
        <p class="text-muted">
            Clasificación: <?php echo htmlspecialchars($pelicula['Clasificacion']); ?>
            |
            Duración:
            <?php echo $pelicula['DuracionMin'] ? (int)$pelicula['DuracionMin'] . " min" : "-"; ?>
            |
            Año: <?php echo $pelicula['Anio'] ?: "-"; ?>
        </p>
        <p><?php echo nl2br(htmlspecialchars($pelicula['Sinopsis'])); ?></p>
    </div>
</div>

<h3>Horarios disponibles</h3>

<?php if (empty($funciones)): ?>
    <div class="alert alert-info">
        No hay horarios disponibles para esta película.
    </div>
<?php else: ?>
    <div class="list-group mb-4">
        <?php foreach ($funciones as $f): ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
               href="../compras/comprar.php?idFuncion=<?php echo (int)$f['IdFuncion']; ?>">
                <div>
                    <?php
                        $fecha = new DateTime($f['FechaHora']);
                        echo $fecha->format('d/m/Y H:i');
                    ?>
                    –
                    Sala: <?php echo htmlspecialchars($f['NombreSala']); ?>
                    –
                    <?php echo htmlspecialchars($f['Idioma']); ?>
                    –
                    <?php echo htmlspecialchars($f['Formato']); ?>
                </div>
                <span class="fw-bold">
                    $<?php echo number_format($f['Precio'], 2); ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
