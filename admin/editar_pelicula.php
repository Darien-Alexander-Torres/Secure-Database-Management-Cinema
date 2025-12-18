<?php
session_start();

if (empty($_SESSION["IdUsuario"]) || empty($_SESSION["EsAdmin"]) || (int)$_SESSION["EsAdmin"] !== 1) {
    header("Location: /cine/index.php");
    exit;
}

require_once __DIR__ . "/../config/getconex.php";

$idPelicula = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$idPelicula) {
    die("Película inválida.");
}

$errores = [];
$exito = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo        = trim($_POST["Titulo"] ?? "");
    $clasificacion = trim($_POST["Clasificacion"] ?? "");
    $idGenero      = $_POST["IdGenero"] !== "" ? (int)$_POST["IdGenero"] : null;

    if ($titulo === "") {
        $errores[] = "El título es obligatorio.";
    }

    // 1) Cargar datos actuales para conservar PosterUrl si no suben nada
    $sqlActual = "
        SELECT PosterUrl
        FROM Peliculas
        WHERE IdPelicula = :id;
    ";
    $stmtAct = $pdo->prepare($sqlActual);
    $stmtAct->execute([":id" => $idPelicula]);
    $actual = $stmtAct->fetch();
    $posterActual = $actual ? $actual["PosterUrl"] : null;
    $posterNuevo  = $posterActual;

    // 2) Si se subió un archivo, procesarlo
    if (isset($_FILES["PosterFile"]) && $_FILES["PosterFile"]["error"] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES["PosterFile"]["tmp_name"];
        $nombreOriginal = $_FILES["PosterFile"]["name"];

        $info = pathinfo($nombreOriginal);
        $ext  = strtolower($info["extension"] ?? "");

        $extValidas = ["jpg","jpeg","png","gif","webp"];
        if (!in_array($ext, $extValidas, true)) {
            $errores[] = "Formato de imagen no permitido. Usa jpg, png, gif o webp.";
        } else {
            $nombreSeguro = "poster_" . $idPelicula . "_" . time() . "." . $ext;

            $rutaCarpeta = realpath(__DIR__ . "/../assets/posters");
            if ($rutaCarpeta === false) {
                $errores[] = "No existe la carpeta de posters en el servidor.";
            } else {
                $rutaDestinoFs   = $rutaCarpeta . DIRECTORY_SEPARATOR . $nombreSeguro;
                $rutaDestinoWeb  = "/cine/assets/posters/" . $nombreSeguro;

                if (!move_uploaded_file($tmpName, $rutaDestinoFs)) {
                    $errores[] = "No se pudo guardar la imagen en el servidor.";
                } else {
                    $posterNuevo = $rutaDestinoWeb;
                }
            }
        }
    }

    if (empty($errores)) {
        $sqlUpdate = "
            UPDATE Peliculas
            SET Titulo = :titulo,
                Clasificacion = :clasificacion,
                IdGenero = :genero,
                PosterUrl = :poster
            WHERE IdPelicula = :id;
        ";
        $stmtU = $pdo->prepare($sqlUpdate);
        $stmtU->execute([
            ":titulo"        => $titulo,
            ":clasificacion" => $clasificacion,
            ":genero"        => $idGenero,
            ":poster"        => $posterNuevo,
            ":id"            => $idPelicula
        ]);
        $exito = "Película actualizada correctamente.";
    }
}

$sqlP = "
    SELECT IdPelicula, Titulo, Clasificacion, IdGenero, PosterUrl
    FROM Peliculas
    WHERE IdPelicula = :id;
";
$stmtP = $pdo->prepare($sqlP);
$stmtP->execute([":id" => $idPelicula]);
$pelicula = $stmtP->fetch();

if (!$pelicula) {
    die("Película no encontrada.");
}

$sqlG = "SELECT IdGenero, Nombre FROM Generos ORDER BY Nombre;";
$generos = $pdo->query($sqlG)->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<h1 class="mb-4">Editar película</h1>

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

<form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="Titulo" class="form-control"
               value="<?php echo htmlspecialchars($pelicula["Titulo"]); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Clasificación</label>
        <input type="text" name="Clasificacion" class="form-control"
               value="<?php echo htmlspecialchars($pelicula["Clasificacion"]); ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Género</label>
        <select name="IdGenero" class="form-select">
            <option value="">Sin género</option>
            <?php foreach ($generos as $g): ?>
                <option value="<?php echo (int)$g["IdGenero"]; ?>"
                    <?php echo ($pelicula["IdGenero"] == $g["IdGenero"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($g["Nombre"]); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Póster (subir imagen)</label>
        <input type="file" name="PosterFile" class="form-control">
        <small class="text-muted">Opcional. Si no eliges archivo, se mantiene el póster actual.</small>
    </div>

    <?php if (!empty($pelicula["PosterUrl"])): ?>
        <div class="mb-3">
            <label class="form-label d-block">Póster actual</label>
            <img src="<?php echo htmlspecialchars($pelicula["PosterUrl"]); ?>"
                 alt="Póster actual"
                 style="max-width:200px;border-radius:8px;">
        </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">Guardar cambios</button>
    <a href="dashboard.php" class="btn btn-secondary ms-2">Volver al panel</a>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
