<?php
require_once 'includes/auth.php';
require_once 'includes/db.php'; // Asegúrate de tener la conexión aquí para la auditoría
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $telefono = trim($_POST['telefono']);
    $documento_identidad = trim($_POST['documento_identidad']);

    // Validaciones backend
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($confirm_password) || empty($tipo_usuario) || empty($telefono) || empty($documento_identidad)) {
        $msg = "Todos los campos son obligatorios.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/u", $nombre)) {
        $msg = "El nombre solo puede contener letras y espacios.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/u", $apellido)) {
        $msg = "El apellido solo puede contener letras y espacios.";
    } elseif (!preg_match("/^[0-9]{7,20}$/", $telefono)) {
        $msg = "El teléfono debe contener solo números y tener entre 7 y 20 dígitos.";
    } elseif (!preg_match("/^[0-9a-zA-Z\-]{6,30}$/", $documento_identidad)) {
        $msg = "El documento de identidad debe tener entre 6 y 30 caracteres (números, letras y guiones).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "El correo no tiene un formato válido.";
    } elseif (strlen($nombre) > 100 || strlen($apellido) > 100 || strlen($email) > 255) {
        $msg = "Has superado el límite de caracteres permitido.";
    } elseif (strlen($password) < 8) {
        $msg = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $confirm_password) {
        $msg = "Las contraseñas no coinciden.";
    } elseif (!in_array($tipo_usuario, ['estudiante', 'docente'])) {
        $msg = "Solo puedes registrar estudiantes o docentes.";
    } else {
        $res = register($nombre, $apellido, $email, $password, $tipo_usuario, $telefono, $documento_identidad);
        if (isset($res['ok'])) {
            // --- AUDITORÍA: Registrar el registro exitoso ---
            // Recuperar el id del usuario recién registrado
            $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($id_nuevo_usuario);
            $stmt->fetch();
            $stmt->close();

            if ($id_nuevo_usuario) {
                $descripcion = "Se registró un nuevo usuario (ID $id_nuevo_usuario, email $email, tipo $tipo_usuario)";
                $id_realiza = isset($_SESSION['usuario']['id_usuario']) ? $_SESSION['usuario']['id_usuario'] : null;
                $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'insert', ?, ?)");
                $stmtAud->bind_param("iss", $id_nuevo_usuario, $descripcion, $id_realiza);
                $stmtAud->execute();
            }
            // --- FIN AUDITORÍA ---

            header("Location: index.php?msg=registered");
            exit;
        } else {
            $msg = $res['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Colegio</title>
    <link rel="stylesheet" href="assets/logyreg.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <img src="assets/logo-colegio.png" alt="Logo Colegio" />
        <span class="header-title">Colegio Quibdó</span>
    </div>
    <h2>Registro de Usuario</h2>
    <?php if ($msg) echo "<p style='color:red'>$msg</p>"; ?>
    <form method="POST" autocomplete="off">
        <input type="text" name="nombre" placeholder="Nombre" required maxlength="100"
            pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$" title="Solo letras y espacios" />
        <input type="text" name="apellido" placeholder="Apellido" required maxlength="100"
            pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$" title="Solo letras y espacios" />
        <input type="email" name="email" placeholder="Correo" required maxlength="255" />
        <input type="text" name="telefono" placeholder="Teléfono" required maxlength="20" pattern="^[0-9]{7,20}$" title="Solo números (7 a 20 dígitos)" />
        <input type="text" name="documento_identidad" placeholder="Documento de identidad" required maxlength="30" pattern="^[0-9a-zA-Z\-]{6,30}$" title="Números, letras y guiones (6 a 30 caracteres)" />
        <input type="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" required minlength="8" maxlength="30" />
        <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required minlength="8" maxlength="30" />
        <select name="tipo_usuario" required>
            <option value="">Selecciona Rol</option>
            <option value="estudiante">Estudiante</option>
            <option value="docente">Docente</option>
        </select>
        <button type="submit">Registrar</button>
    </form>
    <a href="index.php">¿Ya tienes cuenta? Inicia sesión</a>
</body>
</html>