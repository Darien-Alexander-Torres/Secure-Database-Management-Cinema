<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = "/cine";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cine Capibaras Mx</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicons: usando PNG en assets/img y rompiendo caché con ?v=1 -->
    <link rel="icon" type="image/png" sizes="32x32"
          href="<?php echo $basePath; ?>/assets/img/favicon.png?v=1">
    <link rel="shortcut icon" type="image/png"
          href="<?php echo $basePath; ?>/assets/img/favicon.png?v=1">
    <link rel="apple-touch-icon"
          href="<?php echo $basePath; ?>/assets/img/favicon.png?v=1">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .ticket-print {
            max-width: 600px;
            margin: 0 auto;
            border: 1px dashed #999;
            padding: 16px;
            background: #fff;
        }
        @media print {
            nav, .btn-no-print, .btn, a.btn {
                display: none !important;
            }
            body {
                background: #fff !important;
            }
            .ticket-print {
                border: none;
            }
        }
        .logo-capibara {
            height: 32px;
            margin-left: 8px;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $basePath; ?>/index.php">
            <span>Cine Capibaras Mx</span>
            <img src="<?php echo $basePath; ?>/assets/img/capibara-logo.png"
                 alt="Logo Capibara"
                 class="logo-capibara">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (!empty($_SESSION['IdUsuario'])): ?>
                    <li class="nav-item">
                        <span class="nav-link">
                            Hola, <?php echo htmlspecialchars($_SESSION['Nombre']); ?>
                            <?php if (!empty($_SESSION['EsAdmin']) && (int)$_SESSION['EsAdmin'] === 1): ?>
                                <span class="badge bg-warning text-dark ms-1">Admin</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php if (!empty($_SESSION['EsAdmin']) && (int)$_SESSION['EsAdmin'] === 1): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/admin/dashboard.php">Panel admin</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath; ?>/auth/logout.php">Cerrar sesión</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath; ?>/auth/login.php">Iniciar sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath; ?>/auth/registro.php">Registrarse</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container mb-4">
