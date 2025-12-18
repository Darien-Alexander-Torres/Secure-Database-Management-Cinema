<?php
session_start();
require_once __DIR__ . "/config/getconex.php";
require_once __DIR__ . "/includes/header.php";

$sql = "
    SELECT 
        f.IdFuncion,
        f.FechaHora,
        f.Precio,
        f.Idioma,
        f.Formato,
        p.IdPelicula,
        p.Titulo,
        p.Clasificacion,
        p.DuracionMin,
        p.PosterUrl
    FROM Funciones f
    INNER JOIN Peliculas p ON f.IdPelicula = p.IdPelicula
    WHERE f.Activa = 1
    ORDER BY f.FechaHora ASC;
";

$stmt = $pdo->query($sql);
$funciones = $stmt->fetchAll();
?>

<h1 class="mb-4">Cartelera</h1>

<?php if (empty($funciones)): ?>
    <div class="alert alert-info">
        No hay funciones programadas por el momento.
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($funciones as $f): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($f['PosterUrl'])): ?>
                        <img src="<?php echo htmlspecialchars($f['PosterUrl']); ?>"
                             class="card-img-top"
                             alt="Póster de <?php echo htmlspecialchars($f['Titulo']); ?>">
                    <?php else: ?>
                        <div style="height:260px;background:#eee"
                             class="d-flex align-items-center justify-content-center text-muted">
                            Sin póster
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($f['Titulo']); ?></h5>
                        <p class="card-text text-muted mb-1">
                            Clasificación:
                            <?php echo htmlspecialchars($f['Clasificacion']); ?>
                            |
                            Duración:
                            <?php echo $f['DuracionMin'] ? (int)$f['DuracionMin'] . " min" : "-"; ?>
                        </p>
                        <p class="card-text mb-1">
                            <?php
                                $fecha = new DateTime($f['FechaHora']);
                                echo $fecha->format('d/m/Y H:i');
                            ?>
                            |
                            <?php echo htmlspecialchars($f['Idioma']); ?>
                            |
                            <?php echo htmlspecialchars($f['Formato']); ?>
                        </p>
                        <p class="card-text fw-bold mb-2">
                            $<?php echo number_format($f['Precio'], 2); ?>
                        </p>
                        <a href="peliculas/pelicula.php?id=<?php echo (int)$f['IdPelicula']; ?>"
                           class="btn btn-primary btn-sm">
                            Ver horarios
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
