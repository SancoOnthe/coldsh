<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $tipo_usuario = $_POST['tipo_usuario'];
    $estado = $_POST['estado'];
    $telefono = trim($_POST['telefono']);
    $documento_identidad = trim($_POST['documento_identidad']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($email) || empty($tipo_usuario) || empty($estado) || empty($telefono) || empty($documento_identidad) || empty($password) || empty($confirm_password)) {
        $msg = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "El correo no tiene un formato válido.";
    } elseif (!in_array($tipo_usuario, ['estudiante','docente','admin'])) {
        $msg = "Rol de usuario inválido.";
    } elseif (!in_array($estado, ['activo','inactivo'])) {
        $msg = "Estado inválido.";
    } elseif (strlen($password) < 8) {
        $msg = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $confirm_password) {
        $msg = "Las contraseñas no coinciden.";
    } else {
        // Verificar que el email no exista
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $msg = "El correo ya está registrado.";
        } else {
            // Insertar usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, tipo_usuario, estado, telefono, documento_identidad, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $nombre, $apellido, $email, $tipo_usuario, $estado, $telefono, $documento_identidad, $password_hash);
            $stmt->execute();

            $id_nuevo = $conn->insert_id;

            // Auditoría
            $descripcion = "Administrador creó un nuevo usuario (ID $id_nuevo, $email)";
            $id_admin = $_SESSION['usuario']['id_usuario'];
            $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'insert', ?, ?)");
            $stmtAud->bind_param("iss", $id_nuevo, $descripcion, $id_admin);
            $stmtAud->execute();

            header("Location: admin_usuarios.php?msg=add_ok");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Usuario | Admin</title>
    <link rel="stylesheet" href="assets/dashboard.css"/>
    <style>
        .edit-form {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px #2563eb18;
            max-width: 480px;
            margin: 45px auto 0 auto;
            padding: 34px 34px 28px 34px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .edit-form input, .edit-form select {
            border-radius: 7px;
            border: 1.5px solid #a7f3d0;
            padding: 9px 12px;
            font-size: 16px;
            background: #f9fafb;
        }
        .edit-form label {
            font-weight: bold;
            color: #2563eb;
        }
        .edit-form button {
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
        .edit-form button:hover {
            background: #1e40af;
        }
        .edit-form .msg {
            color: #b91c1c;
            font-weight: bold;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <?php include 'header_dashboard.php'; ?>
    <form class="edit-form" method="post">
        <h2>Crear Nuevo Usuario</h2>
        <?php if ($msg) echo "<div class='msg'>$msg</div>"; ?>
        <label>Nombre</label>
        <input type="text" name="nombre" maxlength="100" required value="<?php if(isset($_POST['nombre'])) echo htmlspecialchars($_POST['nombre']); ?>">
        <label>Apellido</label>
        <input type="text" name="apellido" maxlength="100" required value="<?php if(isset($_POST['apellido'])) echo htmlspecialchars($_POST['apellido']); ?>">
        <label>Email</label>
        <input type="email" name="email" maxlength="255" required value="<?php if(isset($_POST['email'])) echo htmlspecialchars($_POST['email']); ?>">
        <label>Rol</label>
        <select name="tipo_usuario" required>
            <option value="">Seleccione rol</option>
            <option value="estudiante" <?php if(isset($_POST['tipo_usuario']) && $_POST['tipo_usuario']=='estudiante') echo 'selected'; ?>>Estudiante</option>
            <option value="docente" <?php if(isset($_POST['tipo_usuario']) && $_POST['tipo_usuario']=='docente') echo 'selected'; ?>>Docente</option>
            <option value="admin" <?php if(isset($_POST['tipo_usuario']) && $_POST['tipo_usuario']=='admin') echo 'selected'; ?>>Admin</option>
        </select>
        <label>Estado</label>
        <select name="estado" required>
            <option value="activo" <?php if(isset($_POST['estado']) && $_POST['estado']=='activo') echo 'selected'; ?>>Activo</option>
            <option value="inactivo" <?php if(isset($_POST['estado']) && $_POST['estado']=='inactivo') echo 'selected'; ?>>Inactivo</option>
        </select>
        <label>Teléfono</label>
        <input type="text" name="telefono" maxlength="20" required value="<?php if(isset($_POST['telefono'])) echo htmlspecialchars($_POST['telefono']); ?>">
        <label>Documento Identidad</label>
        <input type="text" name="documento_identidad" maxlength="30" required value="<?php if(isset($_POST['documento_identidad'])) echo htmlspecialchars($_POST['documento_identidad']); ?>">
        <label>Contraseña</label>
        <input type="password" name="password" maxlength="30" minlength="8" required>
        <label>Confirmar Contraseña</label>
        <input type="password" name="confirm_password" maxlength="30" minlength="8" required>
        <button type="submit">Crear Usuario</button>
        <a href="admin_usuarios.php" style="color:#2563eb;margin-top:8px;display:inline-block;">&larr; Volver</a>
    </form>
</body>
</html>