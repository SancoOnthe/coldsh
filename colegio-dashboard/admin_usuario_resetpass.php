<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_usuarios.php");
    exit;
}

$id_usuario = intval($_GET['id']);
$msg = "";

// Obtener datos usuario
$stmt = $conn->prepare("SELECT id_usuario, nombre, apellido, email, tipo_usuario FROM usuarios WHERE id_usuario=?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();

if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $msg = "Debes completar ambos campos de contraseña.";
    } elseif (strlen($password) < 8) {
        $msg = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $confirm_password) {
        $msg = "Las contraseñas no coinciden.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET password=? WHERE id_usuario=?");
        $stmt->bind_param("si", $password_hash, $id_usuario);
        $stmt->execute();

        // Auditoría
        $descripcion = "Administrador reseteó la contraseña del usuario $id_usuario (" . $usuario['email'] . ")";
        $id_admin = $_SESSION['usuario']['id_usuario'];
        $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'update', ?, ?)");
        $stmtAud->bind_param("iss", $id_usuario, $descripcion, $id_admin);
        $stmtAud->execute();

        header("Location: admin_usuarios.php?msg=reset_ok");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resetear Contraseña | Admin</title>
    <link rel="stylesheet" href="assets/dashboard.css"/>
    <style>
        .reset-form {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px #2563eb20;
            max-width: 400px;
            margin: 45px auto 0 auto;
            padding: 34px 34px 28px 34px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .reset-form input {
            border-radius: 7px;
            border: 1.5px solid #a7f3d0;
            padding: 9px 12px;
            font-size: 16px;
            background: #f9fafb;
        }
        .reset-form label {
            font-weight: bold;
            color: #2563eb;
        }
        .reset-form button {
            background: #2563eb;
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 7px;
            padding: 12px;
            font-size: 1.12em;
            margin-top: 12px;
            cursor: pointer;
        }
        .reset-form button:hover {
            background: #1e40af;
        }
        .reset-form .msg {
            color: #b91c1c;
            font-weight: bold;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <?php include 'header_dashboard.php'; ?>
    <?php include 'admin_volver_panel.php'; ?>
    <form class="reset-form" method="post">
        <h2>Resetear Contraseña</h2>
        <div style="font-size:1.1em;">
            Usuario: <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong><br>
            Email: <strong><?php echo htmlspecialchars($usuario['email']); ?></strong>
        </div>
        <?php if ($msg) echo "<div class='msg'>$msg</div>"; ?>
        <label>Nueva contraseña</label>
        <input type="password" name="password" minlength="8" maxlength="30" required>
        <label>Confirmar contraseña</label>
        <input type="password" name="confirm_password" minlength="8" maxlength="30" required>
        <button type="submit">Resetear contraseña</button>
        <a href="admin_usuarios.php" style="color:#2563eb;margin-top:8px;display:inline-block;">&larr; Volver</a>
    </form>
</body>
</html>