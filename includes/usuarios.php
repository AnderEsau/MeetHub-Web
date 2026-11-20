<?php
/**
 * Acceso a usuarios (responsable: Ander).
 *
 * Los usuarios viven en 4 tablas y esto esconde esa diferencia para que el login sea ÚNICO:
 *
 *   tblalumnos        PK id_alumno (= matrícula)      -> Solicitante (tipo 'alumno')
 *   tbltrabajadores   PK matricula (docentes/personal) -> Solicitante (tipo 'docente')
 *   tblexterno        PK id_externo, rfc               -> Solicitante (tipo 'externo')
 *   tblbibliotecario  PK id_bibliotecario, usuarios,   -> Bibliotecario o Administrador
 *                     matricula -> tbltrabajadores        (campo `tipo`)
 *
 * Se entra con: correo, matrícula, RFC (solicitantes) o nombre de usuario (bibliotecario/admin).
 * Para reservar, tblreserva guarda según el tipo: id_alumno | matricula | id_externo.
 */

require_once __DIR__ . '/db.php';

/** Verifica contraseña: acepta hash (password_hash) y texto plano (datos de prueba actuales). */
function verificar_password(string $ingresada, string $guardada): bool
{
    if (password_get_info($guardada)['algo'] !== null && password_get_info($guardada)['algo'] !== 0) {
        return password_verify($ingresada, $guardada);
    }
    return hash_equals($guardada, $ingresada);
}

/** Une nombre y apellidos para mostrar. */
function nombre_completo(array $f): string
{
    return trim(($f['nombre'] ?? '') . ' ' . ($f['apellido_paterno'] ?? '') . ' ' . ($f['apellido_materno'] ?? ''));
}

/** Ejecuta una consulta preparada de 1 parámetro y devuelve la primera fila (o null). */
function _una_fila(mysqli $conexion, string $sql, string $tipos, ...$params): ?array
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return $fila;
}

/**
 * Busca una cuenta ACTIVA por correo, matrícula, RFC o usuario.
 * @return array|null ['id'=>int, 'nombre'=>string, 'password'=>string, 'rol'=>ROL_*, 'tipo'=>string] o null
 *   tipo: 'alumno' | 'docente' | 'externo' | 'bibliotecario'  (para saber en qué columna de tblreserva guardar)
 */
function buscar_usuario_por_identificador(mysqli $conexion, string $ident): ?array
{
    $ident = trim($ident);
    if ($ident === '') {
        return null;
    }
    $esNumero = ctype_digit($ident);   // evita que '2025abc' coincida con 2025 por conversión de MySQL

    // 1) Bibliotecario / Administrador: entra con su nombre de usuario (tblbibliotecario.usuarios)
    $f = _una_fila($conexion,
        "SELECT b.id_bibliotecario AS id, b.psw, b.tipo, t.nombre, t.apellido_paterno, t.apellido_materno
           FROM tblbibliotecario b
           LEFT JOIN tbltrabajadores t ON t.matricula = b.matricula
          WHERE b.usuarios = ? AND b.activo = 1 LIMIT 1", 's', $ident);
    if ($f) {
        $nombre = nombre_completo($f);
        return ['id' => (int) $f['id'], 'nombre' => $nombre !== '' ? $nombre : $ident, 'password' => $f['psw'],
                'rol' => strcasecmp($f['tipo'], 'Administrador') === 0 ? ROL_ADMIN : ROL_BIBLIOTECARIO,
                'tipo' => 'bibliotecario'];
    }

    // 2) Alumno (correo o matrícula)
    $f = $esNumero
        ? _una_fila($conexion, "SELECT id_alumno AS id, nombre, apellido_paterno, apellido_materno, psw FROM tblalumnos
                                 WHERE (id_alumno = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'is', (int) $ident, $ident)
        : _una_fila($conexion, "SELECT id_alumno AS id, nombre, apellido_paterno, apellido_materno, psw FROM tblalumnos
                                 WHERE correo_electronico = ? AND activo = 1 LIMIT 1", 's', $ident);
    if ($f) {
        return ['id' => (int) $f['id'], 'nombre' => nombre_completo($f), 'password' => $f['psw'], 'rol' => ROL_SOLICITANTE, 'tipo' => 'alumno'];
    }

    // 3) Docente / personal (correo o matrícula)
    $f = $esNumero
        ? _una_fila($conexion, "SELECT matricula AS id, nombre, apellido_paterno, apellido_materno, psw FROM tbltrabajadores
                                 WHERE (matricula = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'is', (int) $ident, $ident)
        : _una_fila($conexion, "SELECT matricula AS id, nombre, apellido_paterno, apellido_materno, psw FROM tbltrabajadores
                                 WHERE correo_electronico = ? AND activo = 1 LIMIT 1", 's', $ident);
    if ($f) {
        return ['id' => (int) $f['id'], 'nombre' => nombre_completo($f), 'password' => $f['psw'], 'rol' => ROL_SOLICITANTE, 'tipo' => 'docente'];
    }

    // 4) Externo (correo o RFC)
    $f = _una_fila($conexion, "SELECT id_externo AS id, nombre, apellido_paterno, apellido_materno, psw FROM tblexterno
                                WHERE (rfc = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'ss', strtoupper($ident), $ident);
    if ($f) {
        return ['id' => (int) $f['id'], 'nombre' => nombre_completo($f), 'password' => $f['psw'], 'rol' => ROL_SOLICITANTE, 'tipo' => 'externo'];
    }
    return null;
}

/**
 * El mockup pide un solo campo "Nombre completo"; la BD guarda nombre + apellido paterno + materno.
 * Regla: la última palabra = apellido materno, la penúltima = paterno, el resto = nombre(s).
 * Con 2 palabras: nombre + paterno. @return array|null [nombre, paterno, materno]
 */
function separar_nombre_completo(string $completo): ?array
{
    $p = preg_split('/\s+/u', trim($completo));
    $n = count($p);
    if ($n < 2) {
        return null;
    }
    if ($n === 2) {
        return [$p[0], $p[1], ''];
    }
    $materno = array_pop($p);
    $paterno = array_pop($p);
    return [implode(' ', $p), $paterno, $materno];
}

/** ¿Ya existe una cuenta con ese correo en cualquiera de las 3 tablas de solicitantes? */
function correo_ya_registrado(mysqli $conexion, string $correo): bool
{
    foreach (['tblalumnos', 'tbltrabajadores', 'tblexterno'] as $t) {
        if (_una_fila($conexion, "SELECT 1 AS x FROM $t WHERE correo_electronico = ? LIMIT 1", 's', $correo)) {
            return true;
        }
    }
    return false;
}

/**
 * Registra un solicitante nuevo (Alumno, Docente o Externo).
 * $datos: tipo, matricula (matrícula o RFC), nombre (completo), correo, telefono, password, pregunta, respuesta
 * @return string|null mensaje de error, o null si se guardó bien
 */
function registrar_solicitante(mysqli $conexion, array $datos): ?string
{
    $partes = separar_nombre_completo($datos['nombre']);
    if (!$partes) {
        return 'Escribe tu nombre completo (nombre y al menos un apellido).';
    }
    [$nombre, $paterno, $materno] = $partes;
    $tipo = $datos['tipo'];
    $clave = trim($datos['matricula']);
    $correo = $datos['correo'];
    $tel = $datos['telefono'];
    $psw = $datos['password'];              // TODO(equipo): password_hash($psw, PASSWORD_DEFAULT) si se decide hashear
    $preg = $datos['pregunta'];
    $resp = $datos['respuesta'];

    if (correo_ya_registrado($conexion, $correo)) {
        return 'Ya existe una cuenta con ese correo electrónico.';
    }

    if ($tipo === 'externo') {
        $rfc = strtoupper($clave);
        if (!preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc)) {
            return 'Escribe un RFC válido (12 o 13 caracteres).';
        }
        if (_una_fila($conexion, "SELECT 1 AS x FROM tblexterno WHERE rfc = ? LIMIT 1", 's', $rfc)) {
            return 'Ya existe una cuenta con ese RFC.';
        }
        $inst = 'No especificada';           // tblexterno.institucion es NOT NULL y el mockup no la pide
        $stmt = mysqli_prepare($conexion,
            "INSERT INTO tblexterno (rfc, nombre, apellido_paterno, apellido_materno, correo_electronico, telefono, institucion, psw, pregunta_secreta, respuesta_secreta)
             VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssssssssss', $rfc, $nombre, $paterno, $materno, $correo, $tel, $inst, $psw, $preg, $resp);
    } else {
        if (!ctype_digit($clave)) {
            return 'La matrícula debe contener solo números.';
        }
        $matricula = (int) $clave;
        $tabla = $tipo === 'alumno' ? 'tblalumnos' : 'tbltrabajadores';
        $pk = $tipo === 'alumno' ? 'id_alumno' : 'matricula';
        if (_una_fila($conexion, "SELECT 1 AS x FROM $tabla WHERE $pk = ? LIMIT 1", 'i', $matricula)) {
            return 'Ya existe una cuenta con esa matrícula.';
        }
        $stmt = mysqli_prepare($conexion,
            "INSERT INTO $tabla ($pk, nombre, apellido_paterno, apellido_materno, correo_electronico, telefono, psw, pregunta_secreta, respuesta_secreta)
             VALUES (?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'issssssss', $matricula, $nombre, $paterno, $materno, $correo, $tel, $psw, $preg, $resp);
    }

    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        return 'No se pudo crear la cuenta. Intenta de nuevo.';
    }
    mysqli_stmt_close($stmt);
    return null;
}

/**
 * Recuperación de contraseña por pregunta secreta.
 * @return array|null ['tabla','pk','id','pregunta','respuesta'] o null si no existe
 */
function buscar_cuenta_recuperacion(string $ident): ?array
{
    $ident = trim($ident);
    if ($ident === '') {
        return null;
    }
    $num = ctype_digit($ident) ? (int) $ident : -1;

    $f = db_una("SELECT b.id_bibliotecario AS id, t.pregunta_secreta AS p, t.respuesta_secreta AS r
                   FROM tblbibliotecario b LEFT JOIN tbltrabajadores t ON t.matricula = b.matricula
                  WHERE b.usuarios = ? AND b.activo = 1 LIMIT 1", 's', $ident);
    if ($f) {
        return ['tabla' => 'tblbibliotecario', 'pk' => 'id_bibliotecario', 'id' => (int) $f['id'], 'pregunta' => (string) $f['p'], 'respuesta' => (string) $f['r']];
    }
    $f = db_una("SELECT id_alumno AS id, pregunta_secreta AS p, respuesta_secreta AS r FROM tblalumnos
                  WHERE (id_alumno = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'is', $num, $ident);
    if ($f) {
        return ['tabla' => 'tblalumnos', 'pk' => 'id_alumno', 'id' => (int) $f['id'], 'pregunta' => $f['p'], 'respuesta' => $f['r']];
    }
    $f = db_una("SELECT matricula AS id, pregunta_secreta AS p, respuesta_secreta AS r FROM tbltrabajadores
                  WHERE (matricula = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'is', $num, $ident);
    if ($f) {
        return ['tabla' => 'tbltrabajadores', 'pk' => 'matricula', 'id' => (int) $f['id'], 'pregunta' => $f['p'], 'respuesta' => $f['r']];
    }
    $f = db_una("SELECT id_externo AS id, pregunta_secreta AS p, respuesta_secreta AS r FROM tblexterno
                  WHERE (rfc = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'ss', strtoupper($ident), $ident);
    if ($f) {
        return ['tabla' => 'tblexterno', 'pk' => 'id_externo', 'id' => (int) $f['id'], 'pregunta' => $f['p'], 'respuesta' => $f['r']];
    }
    return null;
}

/** Cambia la contraseña de una cuenta (tabla y pk vienen de buscar_cuenta_recuperacion, nunca del usuario). */
function cambiar_password(string $tabla, string $pk, int $id, string $nuevo): void
{
    $permitidas = ['tblalumnos' => 'id_alumno', 'tbltrabajadores' => 'matricula', 'tblexterno' => 'id_externo', 'tblbibliotecario' => 'id_bibliotecario'];
    if (($permitidas[$tabla] ?? null) !== $pk) {
        throw new InvalidArgumentException('Tabla no permitida.');
    }
    db_exec("UPDATE $tabla SET psw = ? WHERE $pk = ?", 'si', $nuevo, $id);   // TODO(equipo): password_hash() si se decide hashear
}

/** ¿La contraseña cumple la regla del sistema? (mín. 8, letras y números) */
function password_valida(string $p): bool
{
    return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $p);
}
