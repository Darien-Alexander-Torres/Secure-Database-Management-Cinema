<?php
session_start();

require_once __DIR__ . "/../config/getconex.php";

$errores = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $errores[] = "Todos los campos son obligatorios.";
    } else {
        try {
            $sql = "EXEC sp_LoginUsuario @Email = :email, @Password = :password";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":email"    => $email,
                ":password" => $password
            ]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION["IdUsuario"] = $user["IdUsuario"];
                $_SESSION["Nombre"]    = $user["Nombre"];
                $_SESSION["EsAdmin"]   = $user["EsAdmin"];

                if ((int)$user["EsAdmin"] === 1) {
                    header("Location: /cine/admin/dashboard.php");
                } else {
                    header("Location: /cine/index.php");
                }
                exit;
            } else {
                $errores[] = "Correo o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $errores[] = "Error al intentar iniciar sesión.";
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<h1 class="mb-4">Iniciar sesión</h1>

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
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control"
               value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Entrar</button>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
