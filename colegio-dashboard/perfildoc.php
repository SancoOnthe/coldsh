<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Verificar sesión y rol
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'docente') {
    header('Location: index.php');
    exit;
}

$id_docente = $_SESSION['usuario']['id_usuario'];

// Mensaje para mostrar éxito o error
$msg = "";

// Procesar actualización de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $documento_identidad = trim($_POST['documento_identidad']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET nombre=?, apellido=?, documento_identidad=?, email=?, telefono=?, direccion=?
        WHERE id_usuario=?
    ");
    $stmt->bind_param("ssssssi", $nombre, $apellido, $documento_identidad, $email, $telefono, $direccion, $id_docente);
    if ($stmt->execute()) {
        $msg = "<div class='success'>Perfil actualizado correctamente.</div>";
        // Actualizar datos de sesión
        $_SESSION['usuario']['nombre'] = $nombre;
        $_SESSION['usuario']['apellido'] = $apellido;
        $_SESSION['usuario']['email'] = $email;

        // Registrar en auditoria
        $descripcion = "El docente actualizó su perfil (Nombre: $nombre, Apellido: $apellido, Documento: $documento_identidad, Email: $email, Teléfono: $telefono, Dirección: $direccion)";
        $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'update', ?, ?)");
        $stmtAud->bind_param("iss", $id_docente, $descripcion, $id_docente);
        $stmtAud->execute();
    } else {
        $msg = "<div class='error'>Ocurrió un error al actualizar el perfil.</div>";
    }
}

// Obtener información actualizada del docente
$stmt = $conn->prepare("
    SELECT nombre, apellido, documento_identidad, email, telefono, direccion, estado, fecha_registro
    FROM usuarios
    WHERE id_usuario = ?
    LIMIT 1
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$docente = $stmt->get_result()->fetch_assoc();

if (!$docente) {
    echo "<p>No se encontró información del docente.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil Docente</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <style>
        .perfil-container {
            max-width: 520px;
            margin: 48px auto 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 6px 32px rgba(37,99,235,0.09), 0 1.5px 4px rgba(0,0,0,0.04);
            padding: 38px 42px 30px 42px;
            border: 2px solid #fef08a;
        }
        .perfil-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 28px;
        }
        .perfil-header img {
            width: 82px;
            height: 82px;
            border-radius: 16px;
            border: 3px solid #2563eb33;
            background: #f1f5f9;
            object-fit: cover;
        }
        .perfil-header .nombre {
            font-size: 2em;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 1px;
        }
        .perfil-form label {
            font-weight: 600;
            color: #1e293b;
            display: block;
            margin-bottom: 3px;
        }
        .perfil-form input {
            width: 100%;
            margin-bottom: 13px;
            padding: 10px;
            border-radius: 8px;
            border: 1.5px solid #a7f3d0;
            background: #f9fafb;
            font-size: 16px;
        }
        .perfil-form input:focus {
            border-color: #2563eb;
            box-shadow: 0 1px 5px #2563eb22;
        }
        .perfil-form button {
            background: linear-gradient(90deg, #38bdf8 10%, #2563eb 90%);
            color: #fff;
            border: none;
            padding: 12px 0;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            letter-spacing: 0.5px;
            transition: background 0.17s, box-shadow 0.17s;
            box-shadow: 0 2px 12px #2563eb3a;
            width: 100%;
        }
        .perfil-form button:hover {
            background: linear-gradient(90deg, #2563eb 60%, #38bdf8 100%);
        }
        .readonly-info {
            background: #f1f5f9;
            border-radius: 7px;
            padding: 10px 16px;
            margin-bottom: 13px;
            color: #555;
        }
        .volver {
            margin-top: 20px;
            display: inline-block;
            background: none;
            color: #2563eb;
            border: 1.5px solid #2563eb;
            border-radius: 7px;
            padding: 9px 22px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.16s, color 0.16s;
        }
        .volver:hover {
            background: #2563eb;
            color: #fff;
        }
        .success { color: #059669; font-weight: bold; margin-bottom: 12px;}
        .error { color: #b91c1c; font-weight: bold; margin-bottom: 12px;}
        @media (max-width: 700px) {
            .perfil-container { padding: 18px 10px; }
            .perfil-header .nombre { font-size: 1.3em; }
        }
    </style>
</head>
<body>
<?php include 'header_dashboard.php'; ?>
<div class="perfil-container">
    <div class="perfil-header">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($docente['nombre'].' '.$docente['apellido']); ?>&background=dbeafe&color=2563eb&rounded=true&size=128" alt="Avatar Docente">
        <div class="nombre">
            <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
        </div>
    </div>
    <?php if ($msg) echo $msg; ?>
    <form method="post" class="perfil-form" autocomplete="off">
        <label for="nombre">Nombre</label>
        <input type="text" required name="nombre" id="nombre" value="<?php echo htmlspecialchars($docente['nombre']); ?>">

        <label for="apellido">Apellido</label>
        <input type="text" required name="apellido" id="apellido" value="<?php echo htmlspecialchars($docente['apellido']); ?>">

        <label for="documento_identidad">Documento de identidad</label>
        <input type="text" required name="documento_identidad" id="documento_identidad" value="<?php echo htmlspecialchars($docente['documento_identidad']); ?>">

        <label for="email">Correo electrónico</label>
        <input type="email" required name="email" id="email" value="<?php echo htmlspecialchars($docente['email']); ?>">

        <label for="telefono">Teléfono</label>
        <input type="text" name="telefono" id="telefono" value="<?php echo htmlspecialchars($docente['telefono'] ?? ''); ?>">

        <label for="direccion">Dirección</label>
        <input type="text" name="direccion" id="direccion" value="<?php echo htmlspecialchars($docente['direccion'] ?? ''); ?>">

        <div class="readonly-info">
            <strong>Estado:</strong> <?php echo htmlspecialchars($docente['estado']); ?><br>
            <strong>Fecha de registro:</strong> <?php echo htmlspecialchars($docente['fecha_registro']); ?>
        </div>

        <button type="submit">Guardar cambios</button>
    </form>
    <a href="teacher_dashboard.php" class="volver">Volver al dashboard</a>
</div>
</body>
</html>