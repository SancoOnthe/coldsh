<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Solo admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Filtros
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtro_operacion = isset($_GET['operacion']) && in_array($_GET['operacion'], ['insert','update','delete']) ? $_GET['operacion'] : '';
$filtro_usuario = isset($_GET['usuario']) ? intval($_GET['usuario']) : '';
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Preparar query
$query = "SELECT a.id_auditoria, a.fecha_operacion, a.operacion, a.descripcion, a.usuario_afectado, a.usuario_realiza_operacion, 
                 u1.nombre AS nombre_afectado, u1.apellido AS apellido_afectado, 
                 u2.nombre AS nombre_realiza, u2.apellido AS apellido_realiza
          FROM auditoria a
          LEFT JOIN usuarios u1 ON a.usuario_afectado = u1.id_usuario
          LEFT JOIN usuarios u2 ON a.usuario_realiza_operacion = u2.id_usuario
          WHERE 1=1";
$params = [];
$types = "";

if ($busqueda !== "") {
    $query .= " AND (a.descripcion LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like;
    $types .= "s";
}
if ($filtro_operacion) {
    $query .= " AND a.operacion = ?";
    $params[] = $filtro_operacion;
    $types .= "s";
}
if ($filtro_usuario) {
    $query .= " AND (a.usuario_afectado = ? OR a.usuario_realiza_operacion = ?)";
    $params[] = $filtro_usuario;
    $params[] = $filtro_usuario;
    $types .= "ii";
}
if ($filtro_fecha) {
    $query .= " AND DATE(a.fecha_operacion) = ?";
    $params[] = $filtro_fecha;
    $types .= "s";
}
$query .= " ORDER BY a.fecha_operacion DESC LIMIT 100";

// Obtener lista de usuarios para filtro
$res_usuarios = $conn->query("SELECT id_usuario, nombre, apellido FROM usuarios ORDER BY nombre, apellido");
$usuarios_lista = $res_usuarios->fetch_all(MYSQLI_ASSOC);

// Ejecutar consulta auditoría
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$auditorias = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría | Panel Admin</title>
    <link rel="stylesheet" href="assets/dashboard.css"/>
    <style>
        .auditoria-header {
            margin: 30px 0 14px 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .auditoria-header h2 {
            color: #2563eb;
            font-size: 2em;
            font-weight: 800;
        }
        .auditoria-filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: end;
        }
        .auditoria-filtros input, .auditoria-filtros select {
            border-radius: 7px;
            border: 1.5px solid #a7f3d0;
            padding: 7px 12px;
            font-size: 16px;
            background: #f9fafb;
        }
        .auditoria-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 2px 10px #2563eb10;
            overflow: hidden;
        }
        .auditoria-table th, .auditoria-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        .auditoria-table th {
            background: #f1f5f9;
            color: #2563eb;
            font-weight: bold;
        }
        .auditoria-table tr:last-child td { border-bottom: none; }
        .op-insert { color: #059669; font-weight:700; }
        .op-update { color: #2563eb; font-weight:700; }
        .op-delete { color: #b91c1c; font-weight:700; }
        @media (max-width: 700px) {
            .auditoria-table, .auditoria-table th, .auditoria-table td {
                font-size: 13px;
            }
            .auditoria-header h2 { font-size: 1.2em; }
        }
    </style>
</head>
<body>
    <?php include 'header_dashboard_d.php'; ?>
    <a href="admin_dashboard.php" class="nuevo-btn" style="background:#2563eb;color:#fff;padding:9px 20px;border-radius:7px;font-weight:700;text-decoration:none;">Volver</a>
    <div class="container" style="max-width:1100px; margin: 0 auto;">
        <div class="auditoria-header">
            <h2>Auditoría del sistema</h2>
            <form class="auditoria-filtros" method="get" action="">
                <input type="text" name="q" placeholder="Buscar en descripción" value="<?php echo htmlspecialchars($busqueda); ?>">
                <select name="operacion">
                    <option value="">Todas las operaciones</option>
                    <option value="insert" <?php if($filtro_operacion=='insert') echo 'selected'; ?>>Insertar</option>
                    <option value="update" <?php if($filtro_operacion=='update') echo 'selected'; ?>>Actualizar</option>
                    <option value="delete" <?php if($filtro_operacion=='delete') echo 'selected'; ?>>Eliminar</option>
                </select>
                <select name="usuario">
                    <option value="">Todos los usuarios</option>
                    <?php foreach($usuarios_lista as $u): ?>
                        <option value="<?php echo $u['id_usuario']; ?>" <?php if($filtro_usuario==$u['id_usuario']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($u['nombre'].' '.$u['apellido']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                <button type="submit">Filtrar</button>
            </form>
        </div>
        <table class="auditoria-table">
            <thead>
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Operación</th>
                    <th>Usuario afectado</th>
                    <th>Usuario que realiza</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($auditorias) === 0): ?>
                <tr><td colspan="5" style="text-align:center;">No se encontraron registros de auditoría.</td></tr>
            <?php else: foreach ($auditorias as $a): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($a['fecha_operacion'])); ?></td>
                    <td class="op-<?php echo htmlspecialchars($a['operacion']); ?>">
                        <?php
                        switch($a['operacion']) {
                            case 'insert': echo 'Insertar'; break;
                            case 'update': echo 'Actualizar'; break;
                            case 'delete': echo 'Eliminar'; break;
                            default: echo htmlspecialchars($a['operacion']);
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($a['nombre_afectado'] || $a['apellido_afectado'])
                            echo htmlspecialchars($a['nombre_afectado'].' '.$a['apellido_afectado']);
                        else
                            echo '<span style="color:#a3a3a3;">[N/A]</span>';
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($a['nombre_realiza'] || $a['apellido_realiza'])
                            echo htmlspecialchars($a['nombre_realiza'].' '.$a['apellido_realiza']);
                        else
                            echo '<span style="color:#a3a3a3;">[N/A]</span>';
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($a['descripcion']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>