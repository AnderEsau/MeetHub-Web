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
require_once __DIR__ . '/validaciones.php';

/**
 * Verifica contraseña: acepta hash (password_hash) y texto plano (cuentas viejas de la app móvil).
 * Las contraseñas nuevas pasan por hash_password(): texto plano o bcrypt según CIFRAR_PASSWORDS.
 */
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

/** Consulta preparada que devuelve la primera fila (o null). Usa los mismos ayudantes db_* de todo el proyecto. */
function _una_fila(string $sql, string $tipos, ...$params): ?array
{
    return db_una($sql, $tipos, ...$params);
}

/**
 * Busca una cuenta ACTIVA por correo, matrícula, RFC o usuario.
 * @return array|null ['id'=>int, 'nombre'=>string, 'password'=>string, 'rol'=>ROL_*, 'tipo'=>string] o null
 *   tipo: 'alumno' | 'docente' | 'externo' | 'bibliotecario'  (para saber en qué columna de tblreserva guardar)
 */
function buscar_usuario_por_identificador(string $ident): ?array
{
    $ident = trim($ident);
    if ($ident === '') {
        return null;
    }
    $esNumero = ctype_digit($ident);   // evita que '2025abc' coincida con 2025 por conversión de MySQL

    // 1) Bibliotecario / Administrador: entra con su nombre de usuario (tblbibliotecario.usuarios)
    $f = _una_fila(        "SELECT b.id_bibliotecario AS id, b.psw, b.tipo, t.nombre, t.apellido_paterno, t.apellido_materno
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
        ? _una_fila("SELECT id_alumno AS id, nombre, apellido_paterno, apellido_materno, psw FROM tblalumnos
                                 WHERE (id_alumno = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'is', (int) $ident, $ident)
        : _una_fila("SELECT id_alumno AS id, nombre, apellido_paterno, apellido_materno, psw FROM tblalumnos
                                 WHERE correo_electronico = ? AND activo = 1 LIMIT 1", 's', $ident);
    if ($f) {
        return ['id' => (int) $f['id'], 'nombre' => nombre_completo($f), 'password' => $f['psw'], 'rol' => ROL_SOLICITANTE, 'tipo' => 'alumno'];
    }

    // 3) Docente / personal (correo o matrícula)
    $f = $esNumero
        ? _una_fila("SELECT matricula AS id, nombre, apellido_paterno, apellido_materno, psw FROM tbltrabajadores
                                 WHERE (matricula = ? OR correo_electronico = ?) AND activo = 1 LIMIT 1", 'is', (int) $ident, $ident)
        : _una_fila("SELECT matricula AS id, nombre, apellido_paterno, apellido_materno, psw FROM tbltrabajadores
                                 WHERE correo_electronico = ? AND activo = 1 LIMIT 1", 's', $ident);
    if ($f) {
        return ['id' => (int) $f['id'], 'nombre' => nombre_completo($f), 'password' => $f['psw'], 'rol' => ROL_SOLICITANTE, 'tipo' => 'docente'];
    }

    // 4) Externo (correo o RFC)
    $f = _una_fila("SELECT id_externo AS id, nombre, apellido_paterno, apellido_materno, psw FROM tblexterno
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

/** Preguntas de seguridad disponibles en el registro. */
const PREGUNTAS_SEGURIDAD = ['¿Nombre de tu primera mascota?', '¿Ciudad donde naciste?', '¿Nombre de tu mejor amigo de la infancia?',
                             '¿Cuál es tu comida favorita?', '¿Nombre de tu primera escuela?'];

/**
 * Valor que se guarda en la columna psw. Con CIFRAR_PASSWORDS = false (config/app.php) queda en texto plano, igual que la app móvil;
 * con true se cifra con bcrypt. verificar_password() entiende las dos formas.
 */
function hash_password(string $p): string
{
    return CIFRAR_PASSWORDS ? password_hash($p, PASSWORD_DEFAULT) : $p;
}

/**
 * Registra un solicitante nuevo (Alumno, Docente o Externo).
 * $datos: tipo, matricula (matrícula o RFC), nombre (completo), correo, telefono, password, pregunta, respuesta
 * La tabla donde se guarda depende del tipo elegido: alumno -> tblalumnos, docente -> tbltrabajadores, externo -> tblexterno.
 * @return array lista de errores; vacía si la cuenta se creó bien.
 */
function registrar_solicitante(array $datos): array
{
    $errores = validar_registro($datos, PREGUNTAS_SEGURIDAD);
    if ($errores) {
        return $errores;
    }
    [$nombre, $paterno, $materno] = separar_nombre_completo($datos['nombre']);
    $tipo = $datos['tipo'];
    $clave = trim($datos['matricula']);
    $correo = trim($datos['correo']);
    $tel = trim($datos['telefono']);
    $psw = hash_password($datos['password']);
    $preg = $datos['pregunta'];
    $resp = trim($datos['respuesta']);

    if ($tipo === 'externo') {
        $rfc = strtoupper($clave);
        $inst = 'No especificada';           // tblexterno.institucion es NOT NULL y el formulario no la pide
        db_exec("INSERT INTO tblexterno (rfc, nombre, apellido_paterno, apellido_materno, correo_electronico, telefono, institucion, psw, pregunta_secreta, respuesta_secreta)
                 VALUES (?,?,?,?,?,?,?,?,?,?)", 'ssssssssss', $rfc, $nombre, $paterno, $materno, $correo, $tel, $inst, $psw, $preg, $resp);
    } else {
        $matricula = (int) $clave;
        $tabla = $tipo === 'alumno' ? 'tblalumnos' : 'tbltrabajadores';
        $pk = $tipo === 'alumno' ? 'id_alumno' : 'matricula';
        db_exec("INSERT INTO $tabla ($pk, nombre, apellido_paterno, apellido_materno, correo_electronico, telefono, psw, pregunta_secreta, respuesta_secreta)
                 VALUES (?,?,?,?,?,?,?,?,?)", 'issssssss', $matricula, $nombre, $paterno, $materno, $correo, $tel, $psw, $preg, $resp);
    }
    return [];
}

/** Texto del tipo de cuenta para mostrar: Alumno | Docente | Externo | Bibliotecario | Administrador. */
function etiqueta_tipo_usuario(): string
{
    $m = ['alumno' => 'Alumno', 'docente' => 'Docente', 'externo' => 'Externo'];
    $t = $_SESSION['usuario_tipo'] ?? '';
    if (isset($m[$t])) {
        return $m[$t];
    }
    return rol_actual() === ROL_ADMIN ? 'Administrador' : 'Bibliotecario';
}

/** ¿El solicitante ya completó los datos que se piden antes de reservar (domicilio)? Si la columna aún no existe, no se exige. */
function perfil_completo(): bool
{
    $mapa = ['alumno' => ['tblalumnos', 'id_alumno'], 'docente' => ['tbltrabajadores', 'matricula'], 'externo' => ['tblexterno', 'id_externo']];
    $t = $_SESSION['usuario_tipo'] ?? '';
    if (!isset($mapa[$t]) || !columna_existe($mapa[$t][0], 'domicilio')) {
        return true;
    }
    $f = db_una("SELECT domicilio FROM {$mapa[$t][0]} WHERE {$mapa[$t][1]} = ?", 'i', (int) $_SESSION['usuario_id']);
    return $f && trim((string) $f['domicilio']) !== '';
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
    db_exec("UPDATE $tabla SET psw = ? WHERE $pk = ?", 'si', hash_password($nuevo), $id);   // según CIFRAR_PASSWORDS
}
