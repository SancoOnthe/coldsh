<?php
session_start();
session_destroy();
header("Location: index.php");
exit;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Sesión | Colegio</title>
    <link rel="stylesheet" href="assets/style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <img src="assets/logo-colegio.png" alt="Logo Colegio" />
        <span class="header-title">Colegio San José</span>
    </div>
    <div class="logout-container">
        <h2>Has cerrado sesión</h2>
        <p>Gracias por tu visita. ¡Hasta pronto!</p>
        <a href="index.php">Volver al inicio</a>
    </div>
</body>
</html>
