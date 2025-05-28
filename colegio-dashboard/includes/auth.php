<?php
require_once 'db.php';

// Función para iniciar sesión

require_once 'db.php';

function login($email, $password) {
    global $conn;

    // Buscar usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if (!$usuario) {
        return ["error" => "Usuario no encontrado."];
    }

    // Revisar si el usuario está bloqueado/inactivo
    if ($usuario['estado'] === 'inactivo') {
        // Buscar fecha de bloqueo más reciente
        $stmt = $conn->prepare("SELECT fecha_bloqueo FROM usuariosbloqueados WHERE id_usuario = ? ORDER BY fecha_bloqueo DESC LIMIT 1");
        $stmt->bind_param("i", $usuario['id_usuario']);
        $stmt->execute();
        $result = $stmt->get_result();
        $bloqueo = $result->fetch_assoc();
        if ($bloqueo) {
            $fecha_bloqueo = $bloqueo['fecha_bloqueo'];
            $fecha_bloqueo_time = strtotime($fecha_bloqueo);
            $ahora = time();
            if ($ahora - $fecha_bloqueo_time >= 15 * 60) {
                // Han pasado 15 minutos: desbloquear usuario y resetear intentos
                $stmt = $conn->prepare("UPDATE usuarios SET estado = 'activo' WHERE id_usuario = ?");
                $stmt->bind_param("i", $usuario['id_usuario']);
                $stmt->execute();

                $stmt = $conn->prepare("UPDATE intentoslogin SET intentos = 0 WHERE id_usuario = ?");
                $stmt->bind_param("i", $usuario['id_usuario']);
                $stmt->execute();

                return [
                    "info" => "Tu usuario fue desbloqueado automáticamente. Ahora puedes intentar iniciar sesión."
                ];
            } else {
                $minutos_restantes = 15 - floor(($ahora - $fecha_bloqueo_time)/60);
                return [
                    "error" => "Usuario bloqueado el: $fecha_bloqueo.<br>Intenta de nuevo en $minutos_restantes minutos."
                ];
            }
        } else {
            return ["error" => "Usuario bloqueado. Contacte al administrador."];
        }
    }

    // Revisar intentos fallidos
    $stmt = $conn->prepare("SELECT * FROM intentoslogin WHERE id_usuario = ?");
    $stmt->bind_param("i", $usuario['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    $intentos = $result->fetch_assoc();

    if ($intentos && $intentos['intentos'] >= 3) {
        // Bloquear usuario si no está bloqueado aún
        if ($usuario['estado'] === 'activo') {
            $stmt = $conn->prepare("UPDATE usuarios SET estado = 'inactivo' WHERE id_usuario = ?");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();

            // Registrar en usuariosbloqueados
            $stmt = $conn->prepare("INSERT INTO usuariosbloqueados (id_usuario, fecha_bloqueo) VALUES (?, NOW())");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();
        }
        // Buscar fecha de bloqueo para mostrar en el mensaje
        $stmt = $conn->prepare("SELECT fecha_bloqueo FROM usuariosbloqueados WHERE id_usuario = ? ORDER BY fecha_bloqueo DESC LIMIT 1");
        $stmt->bind_param("i", $usuario['id_usuario']);
        $stmt->execute();
        $result = $stmt->get_result();
        $bloqueo = $result->fetch_assoc();
        $fecha_bloqueo = $bloqueo ? $bloqueo['fecha_bloqueo'] : 'desconocida';

        return ["error" => "Usuario bloqueado el: $fecha_bloqueo. Espere 15 minutos antes de reintentar."];
    }

    // Verificar password
    if (password_verify($password, $usuario['password'])) {
        // Login OK - reiniciar intentos
        if ($intentos) {
            $stmt = $conn->prepare("UPDATE intentoslogin SET intentos = 0 WHERE id_usuario = ?");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();
        }
        // Registro en auditoría si es necesario
        // $accion = "Inicio de sesión";
        // $id_usuario = $usuario['id_usuario'];
        // $stmt = $conn->prepare("INSERT INTO auditoria (accion, usuario_realiza_operacion, fecha) VALUES (?, ?, NOW())");
        // $stmt->bind_param("si", $accion, $id_usuario);
        // $stmt->execute();

        return ["ok" => $usuario];
    } else {
        // Sumar intento
        if ($intentos) {
            $stmt = $conn->prepare("UPDATE intentoslogin SET intentos = intentos + 1, ultimo_intento = NOW() WHERE id_usuario = ?");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO intentoslogin (id_usuario, intentos, ultimo_intento) VALUES (?, 1, NOW())");
            $stmt->bind_param("i", $usuario['id_usuario']);
            $stmt->execute();
        }
        return ["error" => "Contraseña o Correo incorrecto."];
    }
}
// Registro de usuario (igual que antes)
function register($nombre, $apellido, $email, $password, $tipo_usuario, $telefono, $documento_identidad) {
    global $conn;

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $estado = 'activo';

    // Revisar si existe el email o documento
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? OR documento_identidad = ?");
    $stmt->bind_param("ss", $email, $documento_identidad);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return ["error" => "Ya existe un usuario con ese email o documento de identidad"];
    }

    // Insertar usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, password, tipo_usuario, estado, fecha_registro, telefono, documento_identidad) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
    $stmt->bind_param("ssssssss", $nombre, $apellido, $email, $password_hash, $tipo_usuario, $estado, $telefono, $documento_identidad);
    if ($stmt->execute()) {
        return ["ok" => "Usuario creado exitosamente"];
    } else {
        return ["error" => "Error al crear usuario: " . $stmt->error];
    }
}
?>