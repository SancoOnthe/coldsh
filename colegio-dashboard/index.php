<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php'; // Para la auditoría
$rol = null;
$msg = "";
$tipo_msg = ""; // 'success', 'info', 'error'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $resultado = login($email, $password);

    if (isset($resultado['ok'])) {
        $_SESSION['usuario'] = $resultado['ok'];
        $rol = $_SESSION['usuario']['tipo_usuario'];

        // ---- AUDITORÍA: registrar inicio de sesión exitoso ----
        $id_usuario = $_SESSION['usuario']['id_usuario'];
        $tipo_usuario = $_SESSION['usuario']['tipo_usuario'];
        $descripcion = "El usuario ($id_usuario, tipo $tipo_usuario) inició sesión correctamente";
        $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'insert', ?, ?)");
        $stmtAud->bind_param("iss", $id_usuario, $descripcion, $id_usuario);
        $stmtAud->execute();
        // ---- FIN AUDITORÍA ----

        if ($rol === 'estudiante') {
            header("Location: student_dashboard.php");
        } elseif ($rol === 'docente') {
            header("Location: teacher_dashboard.php");
        } elseif ($rol === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        // ---- AUDITORÍA: registrar intento de login fallido ----
        $descripcion = "Intento fallido de inicio de sesión para el email: $email";
        // Buscamos si existe el usuario para asociar el id_usuario, si no existe, ponemos null
        $stmtUsr = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email=? LIMIT 1");
        $stmtUsr->bind_param("s", $email);
        $stmtUsr->execute();
        $stmtUsr->bind_result($id_fallido);
        $stmtUsr->fetch();
        $stmtUsr->close();
        $id_fallido = $id_fallido ? $id_fallido : null;
        $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'insert', ?, NULL)");
        $stmtAud->bind_param("is", $id_fallido, $descripcion);
        $stmtAud->execute();
        // ---- FIN AUDITORÍA ----

        if (isset($resultado['error'])) {
            $msg = $resultado['error'];
            $tipo_msg = "error";
        } elseif (isset($resultado['info'])) {
            $msg = $resultado['info'];
            $tipo_msg = "info";
        }
    }
} else {
    if (isset($_GET['msg']) && $_GET['msg'] == 'registered') {
        $msg = "Usuario registrado exitosamente. Por favor, inicie sesión.";
        $tipo_msg = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión | Colegio</title>
    <link rel="stylesheet" href="assets/logyreg.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
</head>
<body>
    <div class="header">
        <img src="assets/logo-colegio.png" alt="Logo Colegio" />
        <span class="header-title">Colegio Quibdó</span>
    </div>
    <div class="login-card">
        <h2>Iniciar Sesión</h2>
        <?php if ($msg) echo "<p style='color:red'>$msg</p>"; ?>
        <form method="POST" autocomplete="off">
            <input type="email" name="email" placeholder="Correo" required maxlength="255" />
            <input type="password" name="password" placeholder="Contraseña" required minlength="8" maxlength="30" />
            <button type="submit">Ingresar</button>
        </form>
        <a href="register.php">¿No tienes cuenta? Regístrate</a>
    </div>
</body>
</html>