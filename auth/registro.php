<?php
session_start();

require_once __DIR__ . "/../config/getconex.php";
require_once __DIR__ . "/../includes/header.php";

$errores = [];
$exito   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre    = trim($_POST["nombre"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $password  = $_POST["password"]  ?? "";
    $password2 = $_POST["password2"] ?? "";

    if ($nombre === "" || $email === "" || $password === "" || $password2 === "") {
        $errores[] = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido.";
    } elseif ($password !== $password2) {
        $errores[] = "Las contraseñas no coinciden.";
    } else {
        try {
            $sql = "EXEC sp_RegistrarUsuario @Nombre = :nombre, @Email = :email, @Password = :password";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":nombre"   => $nombre,
                ":email"    => $email,
                ":password" => $password
            ]);
            $exito = "Registro exitoso. Ahora puedes iniciar sesión.";
            // opcional: limpiar campos del formulario
            $_POST = [];
        } catch (PDOException $e) {
            $errores[] = "No se pudo registrar el usuario. Es posible que el correo ya exista.";
        }
    }
}
?>

<h1 class="mb-4">Registro</h1>

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
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control"
               value="<?php echo htmlspecialchars($_POST["nombre"] ?? ""); ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control"
               value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Repetir contraseña</label>
        <input type="password" name="password2" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Registrarme</button>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
