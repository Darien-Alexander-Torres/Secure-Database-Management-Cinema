<?php
$server   = "localhost\\SQLEXPRESS";
$database = "cine_db";
$user     = "CineLogin";
$pass     = "Cine_2025_Seguro";

$dsn = "sqlsrv:Server=$server;Database=$database";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
