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

// Procesar registro o edición de notas
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'], $_POST['id_curso'], $_POST['nota'], $_POST['periodo'])) {
    $nota = floatval($_POST['nota']);
    $periodo = $_POST['periodo'];
    $id_usuario = intval($_POST['id_usuario']);
    $id_curso = intval($_POST['id_curso']);

    // Verificar si ya existe una nota
    $stmt = $conn->prepare("SELECT id_nota FROM notas WHERE id_usuario=? AND id_curso=? AND periodo=?");
    $stmt->bind_param("iis", $id_usuario, $id_curso, $periodo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($nota >= 0 && $nota <= 100) {
        if ($res->num_rows > 0) {
            // Actualizar nota
            $stmt = $conn->prepare("UPDATE notas SET nota=? WHERE id_usuario=? AND id_curso=? AND periodo=?");
            $stmt->bind_param("diis", $nota, $id_usuario, $id_curso, $periodo);
            if ($stmt->execute()) {
                $op = 'update';
                $descripcion = "Docente ($id_docente) actualizó nota ($nota) para estudiante $id_usuario en el curso $id_curso y periodo $periodo";
                $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, ?, ?, ?)");
                $stmtAud->bind_param("isss", $id_usuario, $op, $descripcion, $id_docente);
                $stmtAud->execute();
                $msg = "Nota actualizada correctamente.";
            }
        } else {
            // Insertar nota
            $stmt = $conn->prepare("INSERT INTO notas (id_usuario, id_curso, nota, periodo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iids", $id_usuario, $id_curso, $nota, $periodo);
            if ($stmt->execute()) {
                $op = 'insert';
                $descripcion = "Docente ($id_docente) registró nota ($nota) para estudiante $id_usuario en el curso $id_curso y periodo $periodo";
                $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, ?, ?, ?)");
                $stmtAud->bind_param("isss", $id_usuario, $op, $descripcion, $id_docente);
                $stmtAud->execute();
                $msg = "Nota registrada correctamente.";
            }
        }
    } else {
        $msg = "La nota debe estar entre 0 y 100.";
    }
}

// Procesar creación de programa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_programa'])) {
    $nombre_programa = trim($_POST['nombre_programa']);
    $descripcion = trim($_POST['descripcion']);
    $nivel = trim($_POST['nivel']);
    if ($nombre_programa && $nivel) {
        $stmt = $conn->prepare("INSERT INTO programas (nombre_programa, descripcion, nivel) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre_programa, $descripcion, $nivel);
        if ($stmt->execute()) {
            $descripcionAud = "Docente ($id_docente) creó el programa '$nombre_programa'";
            $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'insert', ?, ?)");
            $stmtAud->bind_param("iss", $id_docente, $descripcionAud, $id_docente);
            $stmtAud->execute();
            $msg = "Programa creado correctamente.";
        }
    }
}

// Procesar inscripción de estudiante a curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_estudiante'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $id_curso = intval($_POST['id_curso']);
    $fecha = date('Y-m-d');
    // Verificar que el estudiante no esté inscrito ya
    $stmt = $conn->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_usuario=? AND id_curso=?");
    $stmt->bind_param("ii", $id_usuario, $id_curso);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $msg = "El estudiante ya está inscrito en ese curso.";
    } else {
        $stmt = $conn->prepare("INSERT INTO inscripciones (id_usuario, id_curso, fecha_inscripcion) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_usuario, $id_curso, $fecha);
        if ($stmt->execute()) {
            $descripcion = "Docente ($id_docente) inscribió al estudiante $id_usuario en el curso $id_curso";
            $stmtAud = $conn->prepare("CALL RegistrarAuditoria(?, 'insert', ?, ?)");
            $stmtAud->bind_param("iss", $id_usuario, $descripcion, $id_docente);
            $stmtAud->execute();
            $msg = "Estudiante inscrito correctamente.";
        }
    }
}

// 1. Cursos que imparte el docente
$stmt = $conn->prepare("
    SELECT c.id_curso, c.nombre_curso, p.nombre_programa
    FROM docentecurso dc
    JOIN cursos c ON dc.id_curso = c.id_curso
    JOIN programas p ON c.id_programa = p.id_programa
    WHERE dc.id_usuario = ?
");
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Materias que dicta el docente agrupadas por curso
$cursos_materias = [];
$stmtCM = $conn->prepare("
    SELECT c.id_curso, c.nombre_curso, m.id_materia, m.nombre AS nombre_materia
    FROM materias m
    JOIN cursos c ON m.id_curso = c.id_curso
    WHERE m.id_docente = ?
    ORDER BY c.nombre_curso, m.nombre
");
$stmtCM->bind_param("i", $id_docente);
$stmtCM->execute();
$resCM = $stmtCM->get_result();
while ($row = $resCM->fetch_assoc()) {
    $id_curso = $row['id_curso'];
    if (!isset($cursos_materias[$id_curso])) {
        $cursos_materias[$id_curso] = [
            'nombre_curso' => $row['nombre_curso'],
            'materias' => []
        ];
    }
    $cursos_materias[$id_curso]['materias'][] = [
        'id_materia' => $row['id_materia'],
        'nombre_materia' => $row['nombre_materia']
    ];
}

// 3. Próximos eventos
$eventos = [];
$stmtE = $conn->prepare("
    SELECT titulo_evento, descripcion_evento, fecha_evento, lugar_evento
    FROM eventos
    WHERE fecha_evento >= CURDATE()
    ORDER BY fecha_evento ASC
    LIMIT 10
");
$stmtE->execute();
$resEventos = $stmtE->get_result();
while ($row = $resEventos->fetch_assoc()) $eventos[] = $row;

// 4. Listar estudiantes para registrar (solo tipo estudiante y activos)
$estudiantes = [];
$stmtEst = $conn->prepare("SELECT id_usuario, nombre, apellido, email FROM usuarios WHERE tipo_usuario='estudiante' AND estado='activo' ORDER BY nombre, apellido");
$stmtEst->execute();
$resEst = $stmtEst->get_result();
while ($row = $resEst->fetch_assoc()) $estudiantes[] = $row;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Docente</title>
    <link rel="stylesheet" href="assets/dashboard.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin:0; }
        .sidebar {
            width: 220px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            padding-top: 30px;
        }
        .sidebar h2 { text-align: center; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar li { padding: 15px 20px; }
        .sidebar li a { color: white; text-decoration: none; display: block; }
        .sidebar li a:hover { background: #34495e; }
        .sidebar ul ul { background: #22313a; margin: 0; }
        .sidebar ul ul li { padding: 10px 30px; }
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        .curso { border: 1px solid #ccc; margin: 1em 0; padding: 1em; background: #f9f9f9; }
        .materias, .estudiantes, .notas { margin: 1em 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #aaa; padding: 0.5em; text-align: left; }
        .nota-form { display: flex; gap: 6px; align-items: center; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        /* Para la ancla invisible */
        #notas-general { display: block; height: 1px; margin-top: -80px; visibility: hidden; }
        /* NUEVO: para eventos/programas */
        .evento-item {
            background: #fef9c3;
            margin-bottom: 13px;
            padding: 14px 18px;
            border-radius: 7px;
            box-shadow: 0 1px 6px #2563eb0d;
        }
        .card {
            background: #f9fafb;
            padding: 18px;
            margin-bottom: 1.3em;
            border-radius: 7px;
            box-shadow: 0 1px 6px #2563eb11;
        }
    </style>
</head>
<body>
    <?php include 'header_dashboard.php'; ?>
    <div class="sidebar">
        <h2>Docente</h2>
        <ul>
            <li><a href="teacher_dashboard.php">Inicio</a></li>
            <li><a href="#cursos">Mis cursos</a></li>
            <li>
                <a href="#notas-general">Registrar Notas</a>
                <ul>
                    <?php foreach ($cursos as $curso): ?>
                        <li>
                            <a href="#curso-<?php echo $curso['id_curso']; ?>">Notas <?php echo htmlspecialchars($curso['nombre_curso']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li><a href="#eventos">Eventos</a></li>
            <li><a href="#programas">Crear Programa</a></li>
            <li><a href="#inscribir">Inscribir Estudiante</a></li>
            <li><a href="perfildoc.php">Mi Perfil</a></li>
            <li><a href="logout.php">Cerrar sesión</a></li>
        </ul>
    </div>
    <div class="main-content">
        <!-- Ancla para "Registrar Notas" -->
        <div id="notas-general"></div>
        <h1>Bienvenido, <?php echo $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido']; ?></h1>
        <h2 id="cursos">Cursos que imparte</h2>

        <?php if ($msg): ?>
            <div class="<?php echo strpos($msg, 'correcta') !== false ? 'success' : 'error'; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <?php if (empty($cursos)): ?>
            <p>No tiene cursos asignados.</p>
        <?php else: ?>
            <?php foreach ($cursos as $curso): ?>
                <div class="curso" id="curso-<?php echo $curso['id_curso']; ?>">
                    <h3><?php echo htmlspecialchars($curso['nombre_curso']); ?> (<?php echo htmlspecialchars($curso['nombre_programa']); ?>)</h3>

                    <!-- Materias del curso que imparte este docente -->
                    <div class="materias">
                        <strong>Materias que imparte:</strong>
                        <ul>
                            <?php
                            $stmt2 = $conn->prepare("SELECT nombre FROM materias WHERE id_curso = ? AND id_docente = ?");
                            $stmt2->bind_param("ii", $curso['id_curso'], $id_docente);
                            $stmt2->execute();
                            $materias = $stmt2->get_result();
                            if ($materias->num_rows == 0) {
                                echo "<li>No tiene materias asignadas en este curso.</li>";
                            } else {
                                while ($mat = $materias->fetch_assoc()) {
                                    echo "<li>" . htmlspecialchars($mat['nombre']) . "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>

                    <!-- Estudiantes inscritos en el curso -->
                    <div class="estudiantes">
                        <strong>Estudiantes inscritos y notas:</strong>
                        <table>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Nota 1er Periodo</th>
                                <th>Nota 2do Periodo</th>
                                <th>Registrar / Editar Nota</th>
                            </tr>
                            <?php
                            $stmt3 = $conn->prepare("
                                SELECT u.id_usuario, u.nombre, u.apellido, u.email
                                FROM inscripciones i
                                JOIN usuarios u ON i.id_usuario = u.id_usuario
                                WHERE i.id_curso = ?
                            ");
                            $stmt3->bind_param("i", $curso['id_curso']);
                            $stmt3->execute();
                            $estudiantes = $stmt3->get_result();
                            if ($estudiantes->num_rows == 0) {
                                echo "<tr><td colspan='5'>No hay estudiantes inscritos.</td></tr>";
                            } else {
                                while ($est = $estudiantes->fetch_assoc()) {
                                    // Obtener notas del estudiante en este curso
                                    $stmt4 = $conn->prepare("SELECT periodo, nota FROM notas WHERE id_usuario = ? AND id_curso = ?");
                                    $stmt4->bind_param("ii", $est['id_usuario'], $curso['id_curso']);
                                    $stmt4->execute();
                                    $notas = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);
                                    $nota1 = $nota2 = '';
                                    foreach ($notas as $n) {
                                        if ($n['periodo'] == 'primer') $nota1 = $n['nota'];
                                        if ($n['periodo'] == 'segundo') $nota2 = $n['nota'];
                                    }
                                    // Formulario para registrar/editar nota
                                    echo "<tr>
                                        <td>" . htmlspecialchars($est['nombre']) . " " . htmlspecialchars($est['apellido']) . "</td>
                                        <td>" . htmlspecialchars($est['email']) . "</td>
                                        <td>" . htmlspecialchars($nota1) . "</td>
                                        <td>" . htmlspecialchars($nota2) . "</td>
                                        <td>
                                            <form method='post' class='nota-form'>
                                                <input type='hidden' name='id_usuario' value='{$est['id_usuario']}'>
                                                <input type='hidden' name='id_curso' value='{$curso['id_curso']}'>
                                                <select name='periodo'>
                                                    <option value='primer'>1er Periodo</option>
                                                    <option value='segundo'>2do Periodo</option>
                                                </select>
                                                <input type='number' name='nota' step='0.01' min='0' max='100' required placeholder='Nota'>
                                                <button type='submit'>Guardar</button>
                                            </form>
                                        </td>
                                    </tr>";
                                }
                            }
                            ?>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Materias por curso del docente -->
        <div class="card" id="materias">
            <h2>Materias que dicta por curso</h2>
            <?php if (empty($cursos_materias)): ?>
                <p>No tienes materias asignadas actualmente.</p>
            <?php else: ?>
                <?php foreach ($cursos_materias as $curso): ?>
                    <div>
                        <strong><?php echo htmlspecialchars($curso['nombre_curso']); ?>:</strong>
                        <ul>
                            <?php foreach ($curso['materias'] as $mat): ?>
                                <li><?php echo htmlspecialchars($mat['nombre_materia']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Próximos eventos -->
        <div class="card" id="eventos">
            <h2>Próximos eventos</h2>
            <?php if (empty($eventos)): ?>
                <p>No hay eventos próximos.</p>
            <?php else: ?>
                <?php foreach ($eventos as $e): ?>
                    <div class="evento-item">
                        <strong><?php echo htmlspecialchars($e['titulo_evento']); ?></strong><br>
                        <?php echo htmlspecialchars($e['descripcion_evento']); ?><br>
                        <span>Fecha: <?php echo htmlspecialchars($e['fecha_evento']); ?></span><br>
                        <span>Lugar: <?php echo htmlspecialchars($e['lugar_evento']); ?></span>
                    </div>
                <?php endforeach ?>
            <?php endif; ?>
        </div>

        <!-- Crear nuevo programa -->
        <div class="card" id="programas">
            <h2>Crear nuevo programa</h2>
            <form method="post">
                <input type="hidden" name="crear_programa" value="1">
                <label for="nombre_programa">Nombre del programa:</label>
                <input type="text" name="nombre_programa" id="nombre_programa" required>
                <label for="descripcion">Descripción:</label>
                <input type="text" name="descripcion" id="descripcion">
                <label for="nivel">Nivel:</label>
                <select name="nivel" id="nivel" required>
                    <option value="bajo">Bajo</option>
                    <option value="medio">Medio</option>
                    <option value="alto">Alto</option>
                </select>
                <button type="submit">Crear programa</button>
            </form>
        </div>

        <!-- Inscribir estudiante en curso -->
        <div class="card" id="inscribir">
            <h2>Inscribir estudiante en curso</h2>
            <form method="post">
                <input type="hidden" name="registrar_estudiante" value="1">
                <label for="id_usuario">Estudiante:</label>
                <select name="id_usuario" id="id_usuario" required>
                    <option value="">Seleccione estudiante</option>
                    <?php foreach ($estudiantes as $estu): ?>
                        <option value="<?php echo $estu['id_usuario']; ?>">
                            <?php echo htmlspecialchars($estu['nombre'] . ' ' . $estu['apellido'] . ' (' . $estu['email'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="id_curso">Curso:</label>
                <select name="id_curso" id="id_curso" required>
                    <option value="">Seleccione curso</option>
                    <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id_curso']; ?>">
                            <?php echo htmlspecialchars($curso['nombre_curso']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Inscribir</button>
            </form>
        </div>
    </div>
</body>
</html>