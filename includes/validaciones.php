<?php
/**
 * Validaciones del registro y del perfil (responsable: Ander).
 *
 * Reglas (revisión técnica del ing.):
 *   - Matrícula de alumno: SOLO números y exactamente 8 dígitos.
 *   - Matrícula de docente/personal: SOLO números (3 a 8 dígitos).
 *   - RFC (externos): patrón oficial de México  3-4 letras + 6 dígitos (AAMMDD) + 3 de homoclave  (12 o 13 caracteres).
 *   - Teléfono: exactamente 10 dígitos.
 *   - Contraseña: mínimo 8 caracteres con letra, número y símbolo especial.
 *   - Correo y matrícula/RFC no se pueden repetir en NINGUNA de las tablas de solicitantes
 *     (así al iniciar sesión siempre se sabe a qué cuenta pertenece).
 * Cada función devuelve null si el dato es válido, o el mensaje de error.
 */

const REGEX_RFC = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u';

/** ¿Lo escrito parece una matrícula o un RFC? Sirve para dar un mensaje claro cuando se equivocan de tipo. */
function detectar_clave(string $v): string
{
    $v = trim($v);
    if ($v !== '' && ctype_digit($v)) {
        return 'matricula';
    }
    if (preg_match('/^[A-Za-zÑñ&0-9]{9,}$/u', $v)) {
        return 'rfc';
    }
    return 'invalida';
}

function val_matricula(string $v, string $tipo): ?string
{
    $v = trim($v);
    if ($v === '') {
        return 'Escribe tu matrícula.';
    }
    if (detectar_clave($v) === 'rfc' || preg_match('/[A-Za-z]/', $v)) {
        return $tipo === 'alumno'
            ? 'Los alumnos se registran con su matrícula (8 dígitos, solo números). Si lo que escribiste es un RFC, elige el tipo Externo.'
            : 'Los docentes se registran con su matrícula (solo números). Si lo que escribiste es un RFC, elige el tipo Externo.';
    }
    if (!ctype_digit($v)) {
        return 'La matrícula debe contener solo números.';
    }
    if ($tipo === 'alumno' && strlen($v) !== 8) {
        return 'La matrícula del alumno debe tener exactamente 8 dígitos (escribiste ' . strlen($v) . ').';
    }
    if ($tipo === 'docente' && (strlen($v) < 3 || strlen($v) > 8)) {
        return 'La matrícula del docente debe tener entre 3 y 8 dígitos.';
    }
    return null;
}

function val_rfc(string $v): ?string
{
    $v = strtoupper(trim($v));
    if ($v === '') {
        return 'Escribe tu RFC.';
    }
    if (ctype_digit($v)) {
        return 'Los usuarios externos se registran con RFC (letras y números), no con matrícula.';
    }
    if (!preg_match(REGEX_RFC, $v)) {
        return 'El RFC no es válido. Debe tener 12 o 13 caracteres: 3 o 4 letras, 6 dígitos de fecha y 3 de homoclave (ej. MERC850412H10).';
    }
    return null;
}

function val_telefono(string $v): ?string
{
    $v = trim($v);
    if ($v === '') {
        return 'Escribe tu teléfono a 10 dígitos.';
    }
    if (!preg_match('/^\d{10}$/', $v)) {
        return 'El teléfono debe tener exactamente 10 dígitos, solo números.';
    }
    return null;
}

function val_nombre_completo(string $v): ?string
{
    $v = trim(preg_replace('/\s+/u', ' ', $v));
    if (!preg_match('/^[\p{L}][\p{L}\s.\'-]*$/u', $v)) {
        return 'El nombre solo puede llevar letras y espacios.';
    }
    if (count(explode(' ', $v)) < 2) {
        return 'Escribe tu nombre completo (nombre y al menos un apellido).';
    }
    if (mb_strlen($v) > 250) {
        return 'El nombre es demasiado largo.';
    }
    return null;
}

/** Contraseña: mínimo 8, con letra, número y símbolo especial. */
function password_valida(string $p): bool
{
    return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $p);
}

const MSG_PASSWORD = 'La contraseña debe tener mínimo 8 caracteres e incluir una letra, un número y un símbolo especial (ej. ! @ # $ *).';

/** ¿Ya existe una cuenta con ese correo en cualquiera de las 3 tablas de solicitantes? $excepto = [tabla, pk, id] a ignorar (perfil). */
function correo_ya_registrado_en_tablas(string $correo, ?array $excepto = null): bool
{
    foreach (['tblalumnos' => 'id_alumno', 'tbltrabajadores' => 'matricula', 'tblexterno' => 'id_externo'] as $t => $pk) {
        $sql = "SELECT 1 AS x FROM $t WHERE correo_electronico = ?";
        $tipos = 's';
        $par = [$correo];
        if ($excepto && $excepto[0] === $t) {
            $sql .= " AND $pk <> ?";
            $tipos .= 'i';
            $par[] = (int) $excepto[1];
        }
        if (db_una($sql . ' LIMIT 1', $tipos, ...$par)) {
            return true;
        }
    }
    return false;
}

/** ¿La matrícula (número) ya existe en alumnos o trabajadores, o el RFC en externos? */
function clave_ya_registrada(string $clave): bool
{
    $clave = trim($clave);
    if (ctype_digit($clave)) {
        return (bool) (db_una("SELECT 1 AS x FROM tblalumnos WHERE id_alumno = ? LIMIT 1", 'i', (int) $clave)
                    || db_una("SELECT 1 AS x FROM tbltrabajadores WHERE matricula = ? LIMIT 1", 'i', (int) $clave));
    }
    return (bool) db_una("SELECT 1 AS x FROM tblexterno WHERE rfc = ? LIMIT 1", 's', strtoupper($clave));
}

/**
 * Valida TODO el formulario de registro y devuelve la lista completa de errores (vacía = todo bien),
 * para que el usuario vea de una vez qué campos corregir.
 * $d: tipo, matricula (matrícula o RFC), nombre, correo, telefono, password, pregunta, respuesta
 */
function validar_registro(array $d, array $preguntasPermitidas): array
{
    $err = [];
    $tipo = $d['tipo'] ?? '';
    if (!in_array($tipo, ['alumno', 'docente', 'externo'], true)) {
        $err[] = 'Selecciona el tipo de usuario.';
    } else {
        $m = $tipo === 'externo' ? val_rfc($d['matricula'] ?? '') : val_matricula($d['matricula'] ?? '', $tipo);
        if ($m) {
            $err[] = $m;
        } elseif (clave_ya_registrada($d['matricula'])) {
            $err[] = $tipo === 'externo' ? 'Ya existe una cuenta con ese RFC.' : 'Ya existe una cuenta con esa matrícula.';
        }
    }
    if ($e = val_nombre_completo($d['nombre'] ?? '')) {
        $err[] = $e;
    }
    $correo = trim($d['correo'] ?? '');
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 100) {
        $err[] = 'Escribe un correo electrónico válido.';
    } elseif (correo_ya_registrado_en_tablas($correo)) {
        $err[] = 'Ese correo ya está registrado. Cada correo solo puede usarse en una cuenta.';
    }
    if ($e = val_telefono($d['telefono'] ?? '')) {
        $err[] = $e;
    }
    if (!password_valida($d['password'] ?? '')) {
        $err[] = MSG_PASSWORD;
    }
    if (!in_array($d['pregunta'] ?? '', $preguntasPermitidas, true)) {
        $err[] = 'Selecciona una pregunta de seguridad.';
    }
    $r = trim($d['respuesta'] ?? '');
    if (mb_strlen($r) < 2 || mb_strlen($r) > 150) {
        $err[] = 'Escribe la respuesta secreta (de 2 a 150 caracteres).';
    }
    return $err;
}
