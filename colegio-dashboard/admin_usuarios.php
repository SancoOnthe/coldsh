<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Manejo de activar/desactivar usuario
if (isset($_POST['accion'], $_POST['id_usuario']) && in_array($_POST['accion'], ['activar', 'desactivar'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $nuevo_estado = ($_POST['accion'] === 'activar') ? 'activo' : 'inactivo';

    $stmt = $conn->prepare("UPDATE usuarios SET estado=? WHERE id_usuario=?");
    $stmt->bind_param("si", $nuevo_estado, $id_usuario);
    $stmt->execute();

    // Auditoría
    $descripcion = "Administrador cambió el estado del usuario $id_usuario a '$nuevo_estado'";
    $id_admin = $_SESSION['usuario']['id_usuario'];
    $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'update', ?, ?)");
    $stmtAud->bind_param("iss", $id_usuario, $descripcion, $id_admin);
    $stmtAud->execute();
}

// Manejo de eliminación de usuario
if (isset($_POST['accion'], $_POST['id_usuario']) && $_POST['accion'] === 'eliminar') {
    $id_usuario = intval($_POST['id_usuario']);
    $id_admin = $_SESSION['usuario']['id_usuario'];

    // No puede eliminar a sí mismo ni admins
    $stmtCheck = $conn->prepare("SELECT tipo_usuario FROM usuarios WHERE id_usuario=?");
    $stmtCheck->bind_param("i", $id_usuario);
    $stmtCheck->execute();
    $stmtCheck->bind_result($tipo_usuario);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($id_usuario == $id_admin) {
        $msg = "No puedes eliminar tu propio usuario.";
    } elseif ($tipo_usuario == 'admin') {
        $msg = "No puedes eliminar usuarios administradores.";
    } else {
        // Auditoría antes de eliminar
        $descripcion = "Administrador eliminó el usuario $id_usuario";
        $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'delete', ?, ?)");
        $stmtAud->bind_param("iss", $id_usuario, $descripcion, $id_admin);
        $stmtAud->execute();

        // Eliminar usuario
        $stmtDel = $conn->prepare("DELETE FROM usuarios WHERE id_usuario=?");
        $stmtDel->bind_param("i", $id_usuario);
        $stmtDel->execute();

        $msg = "Usuario eliminado correctamente.";
    }
}

// Búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtro_estado = isset($_GET['estado']) && in_array($_GET['estado'], ['activo','inactivo']) ? $_GET['estado'] : '';
$filtro_rol = isset($_GET['tipo_usuario']) && in_array($_GET['tipo_usuario'], ['estudiante','docente','admin']) ? $_GET['tipo_usuario'] : '';

$query = "SELECT id_usuario, nombre, apellido, email, tipo_usuario, estado FROM usuarios WHERE 1=1";
$params = [];
$types = "";

if ($busqueda !== "") {
    $query .= " AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
if ($filtro_estado) {
    $query .= " AND estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}
if ($filtro_rol) {
    $query .= " AND tipo_usuario = ?";
    $params[] = $filtro_rol;
    $types .= "s";
}
$query .= " ORDER BY tipo_usuario ASC, estado DESC, nombre ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$usuarios = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios | Panel Admin</title>
    <link rel="stylesheet" href="assets/dashboard.css"/>
    <style>
        /* ... (estilos igual que antes) ... */
        .acciones-form .eliminar {
            color: #b91c1c;
            background: #fee2e2;
            margin-left: 2px;
        }
        .acciones-form .eliminar:hover {
            background: #dc2626;
            color: #fff;
        }
        .msg-alert {
            color: #dc2626;
            background: #fee2e2;
            padding: 9px 18px;
            border-radius: 7px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
    </style>
    <script>
        function confirmarEliminacion(form) {
            if(confirm("¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.")) {
                form.submit();
            }
            return false;
        }
    </script>
</head>
<body>
    <?php include 'header_dashboard_d.php'; ?>
    <div class="container" style="max-width:1050px; margin: 0 auto;">
        <div class="usuarios-header">
            <a href="admin_dashboard.php" class="volver-btn" style="color:#2563eb;font-weight:700;text-decoration:none;">&larr; Volver al panel</a>
            <h2>Gestión de Usuarios</h2>
            <a href="admin_usuario_nuevo.php" class="nuevo-btn" style="background:#2563eb;color:#fff;padding:9px 20px;border-radius:7px;font-weight:700;text-decoration:none;">+ Nuevo usuario</a>
            
            <form class="usuarios-filtros" method="get" action="">
                <input type="text" name="q" placeholder="Buscar nombre, apellido o correo" value="<?php echo htmlspecialchars($busqueda); ?>">
                <select name="tipo_usuario">
                    <option value="">Todos los roles</option>
                    <option value="admin" <?php if($filtro_rol=='admin') echo 'selected'; ?>>Admin</option>
                    <option value="docente" <?php if($filtro_rol=='docente') echo 'selected'; ?>>Docente</option>
                    <option value="estudiante" <?php if($filtro_rol=='estudiante') echo 'selected'; ?>>Estudiante</option>
                </select>
                <select name="estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php if($filtro_estado=='activo') echo 'selected'; ?>>Activo</option>
                    <option value="inactivo" <?php if($filtro_estado=='inactivo') echo 'selected'; ?>>Inactivo</option>
                </select>
                <button type="submit">Filtrar</button>
            </form>
        </div>
        <?php if (isset($msg) && $msg): ?>
            <div class='msg-alert'><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <table class="usuarios-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre completo</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($usuarios) === 0): ?>
                <tr><td colspan="6" style="text-align:center;">No se encontraron usuarios.</td></tr>
            <?php else: foreach ($usuarios as $u): ?>
                <tr>
                    <td><?php echo $u['id_usuario']; ?></td>
                    <td><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td class="usuario-tipo-<?php echo $u['tipo_usuario']; ?>">
                        <?php echo ucfirst($u['tipo_usuario']); ?>
                    </td>
                    <td class="usuario-estado-<?php echo $u['estado']; ?>">
                        <?php echo ucfirst($u['estado']); ?>
                    </td>
                    <td>
                        <div class="acciones-form">
                        <?php if ($u['tipo_usuario'] != 'admin'): ?>
                            <a href="admin_usuario_editar.php?id=<?php echo $u['id_usuario']; ?>" class="editar" title="Editar usuario">Editar</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                                <?php if ($u['estado'] == 'activo'): ?>
                                    <button type="submit" name="accion" value="desactivar" class="desactivar" title="Desactivar usuario">Desactivar</button>
                                <?php else: ?>
                                    <button type="submit" name="accion" value="activar" class="activar" title="Activar usuario">Activar</button>
                                <?php endif; ?>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirmarEliminacion(this);">
                                <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                                <button type="submit" name="accion" value="eliminar" class="eliminar" title="Eliminar usuario">Eliminar</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #a3a3a3;">-</span>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>