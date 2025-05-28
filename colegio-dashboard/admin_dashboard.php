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
        .usuarios-header {
            margin: 30px 0 14px 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .usuarios-header h2 {
            color: #2563eb;
            font-size: 2em;
            font-weight: 800;
        }
        .usuarios-filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: end;
        }
        .usuarios-filtros input, .usuarios-filtros select {
            border-radius: 7px;
            border: 1.5px solid #a7f3d0;
            padding: 7px 12px;
            font-size: 16px;
            background: #f9fafb;
        }
        .usuarios-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 2px 10px #2563eb10;
            overflow: hidden;
        }
        .usuarios-table th, .usuarios-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        .usuarios-table th {
            background: #f1f5f9;
            color: #2563eb;
            font-weight: bold;
        }
        .usuarios-table tr:last-child td { border-bottom: none; }
        .usuario-estado-activo {
            color: #059669;
            font-weight: 700;
        }
        .usuario-estado-inactivo {
            color: #b91c1c;
            font-weight: 700;
        }
        .usuario-tipo-admin { color: #f59e42; }
        .usuario-tipo-docente { color: #2563eb; }
        .usuario-tipo-estudiante { color: #0ea5e9; }
        .acciones-form {
            display: flex;
            gap: 8px;
        }
        .acciones-form button {
            border: none;
            background: #f1f5f9;
            color: #2563eb;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .acciones-form button:hover {
            background: #2563eb;
            color: #fff;
        }
        .acciones-form .activar { color: #059669; }
        .acciones-form .desactivar { color: #b91c1c; }
        @media (max-width: 700px) {
            .usuarios-table, .usuarios-table th, .usuarios-table td {
                font-size: 13px;
            }
            .usuarios-header h2 { font-size: 1.2em; }
        }
    </style>
</head>
<body>
    <?php include 'header_dashboard_d.php'; ?>
    <div class="container" style="max-width:1050px; margin: 0 auto;">
        <div class="dashboard-header">
            <h1>Panel de Administración</h1>
            <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario']['nombre']); ?></strong></p>
            <p>Gestione los usuarios del sistema desde aquí.</p>
            <a href="admin_usuarios.php" class="nuevo-btn" style="background:#2563eb;color:#fff;padding:9px 20px;border-radius:7px;font-weight:700;text-decoration:none;">Usuarios Gestion</a>
            <a href="admin_auditoria.php" class="nuevo-btn" style="background:#2563eb;color:#fff;padding:9px 20px;border-radius:7px;font-weight:700;text-decoration:none;">Auditoría</a>
            <a href="logout.php" class="logout-btn" style="background:#dc2626;color:#fff;padding:9px 20px;border-radius:7px;font-weight:700;text-decoration:none;float:right;">Cerrar sesión</a>
        </div>
        <div class="usuarios-header">
            <h2>Gestión de Usuarios</h2>
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
                        <?php if ($u['tipo_usuario'] != 'admin'): ?>
                        <form method="post" class="acciones-form">
                            <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                            <?php if ($u['estado'] == 'activo'): ?>
                                <button type="submit" name="accion" value="desactivar" class="desactivar" title="Desactivar usuario">Desactivar</button>
                            <?php else: ?>
                                <button type="submit" name="accion" value="activar" class="activar" title="Activar usuario">Activar</button>
                            <?php endif; ?>
                        </form>
                        <?php else: ?>
                            <span style="color: #a3a3a3;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>