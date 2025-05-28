<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$nombre = htmlspecialchars($usuario['nombre']);
$apellido = htmlspecialchars($usuario['apellido']);
$tipo_usuario = htmlspecialchars($usuario['tipo_usuario']);
$email = htmlspecialchars($usuario['email']);
$telefono = htmlspecialchars($usuario['telefono']);
$documento = htmlspecialchars($usuario['documento_identidad']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal | Colegio</title>
    <link rel="stylesheet" href="assets/style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <style>
        .dashboard-container {
            max-width: 430px;
            margin: 0 auto 30px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(37,99,235,0.09), 0 1.5px 4px rgba(0,0,0,0.05);
            padding: 34px 28px 28px 28px;
            border: 2px solid #fef08a;
            margin-top: 28px;
        }
        .dashboard-container h3 {
            color: #2563eb;
            margin-top: 0;
            margin-bottom: 18px;
            font-family: 'Nunito', Arial, sans-serif;
            font-size: 1.25em;
        }
        .user-info {
            font-size: 1.07em;
            line-height: 1.8;
            color: #333;
        }
        .user-info strong { color: #2563eb; }
        .dashboard-actions {
            margin-top: 28px;
            display: flex;
            justify-content: center;
            gap: 18px;
        }
        .dashboard-actions a, .dashboard-actions button {
            background: #38bdf8;
            color: #fff;
            padding: 11px 17px;
            border-radius: 7px;
            border: none;
            font-size: 1em;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.17s;
            cursor: pointer;
            box-shadow: 0 1px 6px #2563eb25;
        }
        .dashboard-actions a:hover, .dashboard-actions button:hover {
            background: #2563eb;
        }
        .profile-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }
        .profile-avatar span {
            background: #fef08a;
            color: #2563eb;
            border-radius: 50%;
            font-size: 2.1em;
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2.5px solid #38bdf8;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="assets/logo-colegio.png" alt="Logo Colegio" />
        <span class="header-title">Colegio Quibdó</span>
    </div>
    <div class="dashboard-container">
        <div class="profile-avatar">
            <span>🎓</span>
            <div>
                <strong><?= $nombre . " " . $apellido ?></strong><br>
                <small style="color:#555;font-size:0.97em;"><?= ucfirst($tipo_usuario) ?></small>
            </div>
        </div>
        <h3>Bienvenido/a al Panel Principal</h3>
        <div class="user-info">
            <div><strong>Correo:</strong> <?= $email ?></div>
            <div><strong>Teléfono:</strong> <?= $telefono ?></div>
            <div><strong>Documento:</strong> <?= $documento ?></div>
        </div>
        <div class="dashboard-actions">
            <a href="logout.php">Cerrar sesión</a>
            <!-- Puedes agregar más acciones aquí según el rol -->
        </div>
    </div>
</body>
</html>