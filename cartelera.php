<?php
session_start();
require_once "getconex.php";

$hoy = new DateTime();
$fechaParam = $_GET['fecha'] ?? $hoy->format('Y-m-d');

try {
    $fechaSeleccionada = new DateTime($fechaParam);
} catch (Exception $e) {
    $fechaSeleccionada = $hoy;
}
$fechaStr = $fechaSeleccionada->format('Y-m-d');

$fechaOpciones = [];
for ($i = 0; $i < 3; $i++) {
    $f = (clone $hoy)->modify("+$i day");
    $fechaOpciones[] = $f;
}

/*
 * OJO: SQL Server ⇒ usamos CONVERT(date, ...) y nombres reales de columnas:
 *   Peliculas: IdPelicula, Titulo, Sinopsis, Clasificacion, duracion_min, poster_url
 *   Funciones: IdFuncion, IdPelicula, Cine, Sala, FechaHora, Precio, Idioma, Formato, Activa
 */

$sqlPeliculas = "
    SELECT DISTINCT 
        p.IdPelicula      AS id_pelicula,
        p.Titulo          AS titulo,
        p.Sinopsis        AS sinopsis,
        p.Clasificacion   AS clasificacion,
        p.duracion_min    AS duracion_min,
        p.poster_url      AS poster_url
    FROM Peliculas p
    INNER JOIN Funciones f ON f.IdPelicula = p.IdPelicula
    WHERE CONVERT(date, f.FechaHora) = :fecha
      AND f.Activa = 1
    ORDER BY p.Titulo;
";
$stmtP = $pdo->prepare($sqlPeliculas);
$stmtP->execute([':fecha' => $fechaStr]);
$peliculas = $stmtP->fetchAll();

$sqlFunciones = "
    SELECT 
        f.IdFuncion AS id_funcion,
        f.Cine      AS cine,
        f.Sala      AS sala,
        f.FechaHora AS fecha_hora,
        f.Precio    AS precio,
        f.Idioma    AS idioma,
        f.Formato   AS formato
    FROM Funciones f
    WHERE f.IdPelicula = :id_pelicula
      AND CONVERT(date, f.FechaHora) = :fecha
      AND f.Activa = 1
    ORDER BY f.FechaHora;
";
$stmtF = $pdo->prepare($sqlFunciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cartelera – Cine Capibaras Mx</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .movie-card {
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .movie-poster {
            width: 100%;
            height: 260px;
            object-fit: cover;
            background: #eee;
        }
        .horario-btn {
            margin: 3px;
        }
        .dia-chip {
            margin-right: 8px;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="cartelera.php">Cine Capibaras Mx</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<div class="container mb-4">
    <h1 class="mb-3">Cartelera</h1>

    <div class="mb-4">
        <?php foreach ($fechaOpciones as $f): 
            $fStr = $f->format('Y-m-d');
            $esHoy = $f->format('Y-m-d') === $hoy->format('Y-m-d');
            $texto = $esHoy ? 'Hoy' : $f->format('D d M');
            ?>
            <a class="btn dia-chip <?php echo $fStr === $fechaStr ? 'btn-primary' : 'btn-outline-primary'; ?>"
               href="cartelera.php?fecha=<?php echo $fStr; ?>">
                <?php echo htmlspecialchars($texto); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($peliculas)): ?>
        <div class="alert alert-info">
            No hay funciones programadas para esta fecha.
        </div>
    <?php else: ?>

        <?php foreach ($peliculas as $p): ?>
            <?php
            $stmtF->execute([
                ':id_pelicula' => $p['id_pelicula'],
                ':fecha'       => $fechaStr
            ]);
            $funciones = $stmtF->fetchAll();
            ?>

            <div class="row movie-card p-3">
                <div class="col-md-3">
                    <?php if (!empty($p['poster_url'])): ?>
                        <img src="<?php echo htmlspecialchars($p['poster_url']); ?>"
                             alt="Póster"
                             class="movie-poster">
                    <?php else: ?>
                        <div class="movie-poster d-flex align-items-center justify-content-center text-muted">
                            Sin imagen
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-9">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($p['titulo']); ?></h4>
                            <div class="text-muted small">
                                <?php if (!empty($p['clasificacion'])): ?>
                                    <span class="badge bg-secondary me-1">
                                        <?php echo htmlspecialchars($p['clasificacion']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($p['duracion_min'])): ?>
                                    <span class="me-2">
                                        <?php echo (int)$p['duracion_min']; ?> min
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted small">
                        <?php echo nl2br(htmlspecialchars(substr($p['sinopsis'], 0, 200))); ?>...
                    </p>

                    <div class="mt-2">
                        <strong>Horarios:</strong><br>
                        <?php if (empty($funciones)): ?>
                            <span class="text-muted">Sin horarios para esta fecha.</span>
                        <?php else: ?>
                            <?php foreach ($funciones as $f): 
                                $hora = (new DateTime($f['fecha_hora']))->format('H:i');
                                $labelIdioma = $f['idioma'] === 'DOB' ? 'Dob' :
                                               ($f['idioma'] === 'SUB' ? 'Sub' : 'Ori');
                                $labelFormato = $f['formato'];
                                ?>
                                <a href="comprar.php?id_funcion=<?php echo (int)$f['id_funcion']; ?>"
                                   class="btn btn-sm btn-outline-primary horario-btn">
                                    <?php echo $hora; ?>
                                    <span class="badge bg-light text-dark ms-1">
                                        <?php echo htmlspecialchars($labelIdioma); ?>
                                    </span>
                                    <span class="badge bg-light text-dark ms-1">
                                        <?php echo htmlspecialchars($labelFormato); ?>
                                    </span>
                                    <?php if (!empty($f['sala'])): ?>
                                        <span class="badge bg-secondary ms-1">
                                            <?php echo htmlspecialchars($f['sala']); ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
