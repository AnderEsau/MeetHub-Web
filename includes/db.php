<?php
/**
 * Ayudas de base de datos (mysqli procedimental con consultas preparadas, como en clase).
 *   $filas = db_todos("SELECT * FROM tblsalas WHERE estado = ?", 's', 'Activa');
 *   $fila  = db_una("SELECT * FROM tblsalas WHERE id_sala = ?", 'i', $id);
 *   $id    = db_exec("INSERT INTO ... VALUES (?,?)", 'si', $a, $b);   // devuelve insert_id (o filas afectadas)
 */
require_once __DIR__ . '/funciones.php';

function _db_preparar(string $sql, string $tipos, array $params): mysqli_stmt
{
    $c = conectar_bd();
    $stmt = mysqli_prepare($c, $sql);
    if (!$stmt) {
        error_log('SQL error: ' . mysqli_error($c));
        throw new RuntimeException('Error en la consulta a la base de datos.');
    }
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

function db_todos(string $sql, string $tipos = '', ...$params): array
{
    $stmt = _db_preparar($sql, $tipos, $params);
    $res = mysqli_stmt_get_result($stmt);
    $filas = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $filas;
}

function db_una(string $sql, string $tipos = '', ...$params): ?array
{
    $f = db_todos($sql, $tipos, ...$params);
    return $f[0] ?? null;
}

/** INSERT / UPDATE / DELETE. Devuelve insert_id si hubo, si no las filas afectadas. */
function db_exec(string $sql, string $tipos = '', ...$params): int
{
    $stmt = _db_preparar($sql, $tipos, $params);
    $id = mysqli_stmt_insert_id($stmt);
    $n = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $id > 0 ? $id : $n;
}

/** ¿Existe la tabla? (para que la web siga funcionando si aún no se corrió database/v5_correcciones.sql) */
function tabla_existe(string $tabla): bool
{
    static $cache = [];
    if (!isset($cache[$tabla])) {
        $cache[$tabla] = (bool) db_una("SELECT 1 AS x FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1", 's', $tabla);
    }
    return $cache[$tabla];
}

/** ¿Existe la columna en la tabla? */
function columna_existe(string $tabla, string $columna): bool
{
    static $cache = [];
    $k = $tabla . '.' . $columna;
    if (!isset($cache[$k])) {
        $cache[$k] = (bool) db_una("SELECT 1 AS x FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1", 'ss', $tabla, $columna);
    }
    return $cache[$k];
}

/** Folio visible de una reserva: #MH-108 */
function folio(int $id): string
{
    return '#MH-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

/** SELECT base de reservas con sala y solicitante (Alumno | Docente | Externo). Agregar WHERE/ORDER después. */
const SQL_RESERVAS = "SELECT r.*, s.nombre_sala, s.capacidad_minima,
        CASE WHEN r.id_alumno IS NOT NULL THEN 'Alumno' WHEN r.matricula IS NOT NULL THEN 'Docente' ELSE 'Externo' END AS tipo_usuario,
        COALESCE(r.id_alumno, r.matricula, r.id_externo) AS clave_usuario,
        TRIM(COALESCE(CONCAT(a.nombre,' ',a.apellido_paterno), CONCAT(t.nombre,' ',t.apellido_paterno), CONCAT(x.nombre,' ',x.apellido_paterno))) AS solicitante
   FROM tblreserva r
   JOIN tblsalas s ON s.id_sala = r.id_sala
   LEFT JOIN tblalumnos a ON a.id_alumno = r.id_alumno
   LEFT JOIN tbltrabajadores t ON t.matricula = r.matricula
   LEFT JOIN tblexterno x ON x.id_externo = r.id_externo";

/** Columna de tblreserva según el tipo de usuario en sesión: ['id_alumno', 20251041] */
function columna_reserva_usuario(): array
{
    $map = ['alumno' => 'id_alumno', 'docente' => 'matricula', 'externo' => 'id_externo'];
    return [$map[$_SESSION['usuario_tipo'] ?? ''] ?? 'id_alumno', (int) ($_SESSION['usuario_id'] ?? 0)];
}

function fecha_mx(?string $f): string
{
    return $f ? date('d/m/Y', strtotime($f)) : '';
}

function hora_corta(?string $h): string
{
    return $h ? substr($h, 0, 5) : '';
}

/** Valida y guarda una imagen subida (jpg/png/webp, máx 3 MB). Devuelve ruta relativa o null; $error con el motivo. */
function guardar_imagen(string $campo, string $carpeta, ?string &$error = null): ?string
{
    if (empty($_FILES[$campo]['name']) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$campo];
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 3 * 1024 * 1024) {
        $error = 'La imagen no se pudo subir (máximo 3 MB).';
        return null;
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $error = 'Solo se permiten imágenes JPG, PNG o WEBP.';
        return null;
    }
    $dir = __DIR__ . '/../uploads/' . $carpeta;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $nombre = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($f['tmp_name'], $dir . '/' . $nombre);
    return 'uploads/' . $carpeta . '/' . $nombre;
}

/**
 * Guarda VARIAS imágenes de un <input type="file" name="campo[]" multiple>. Devuelve las rutas guardadas;
 * los motivos de rechazo se agregan a $errores. (Se usa para la galería de cada sala.)
 */
function guardar_imagenes_multiples(string $campo, string $carpeta, array &$errores): array
{
    $rutas = [];
    if (empty($_FILES[$campo]['name']) || !is_array($_FILES[$campo]['name'])) {
        return $rutas;
    }
    foreach ($_FILES[$campo]['name'] as $i => $nombre) {
        if ($nombre === '' || $_FILES[$campo]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $_FILES['__una'] = [
            'name' => $nombre, 'type' => $_FILES[$campo]['type'][$i], 'tmp_name' => $_FILES[$campo]['tmp_name'][$i],
            'error' => $_FILES[$campo]['error'][$i], 'size' => $_FILES[$campo]['size'][$i],
        ];
        $err = null;
        $r = guardar_imagen('__una', $carpeta, $err);
        if ($r) { $rutas[] = $r; } elseif ($err) { $errores[] = $nombre . ': ' . $err; }
    }
    unset($_FILES['__una']);
    return $rutas;
}

/** URL pública de una imagen guardada (ruta relativa o URL absoluta). */
function url_imagen(?string $ruta): string
{
    if (!$ruta) {
        return '';
    }
    return preg_match('#^https?://#', $ruta) ? $ruta : url($ruta);
}

/** Contenido público activo (FAQ / Politicas / Datos), opcionalmente de un apartado. */
function contenido_activo(string $modulo, ?string $apartado = null): array
{
    if ($apartado === null) {
        return db_todos("SELECT * FROM tblcontenido_publico WHERE modulo = ? AND estado = 'Activo' ORDER BY apartado, id_contenido", 's', $modulo);
    }
    return db_todos("SELECT * FROM tblcontenido_publico WHERE modulo = ? AND apartado = ? AND estado = 'Activo' ORDER BY id_contenido", 'ss', $modulo, $apartado);
}

/** Primer registro activo de un apartado de Datos (Misión, Visión, Política de calidad, Fachada...) o null. */
function dato_publico(string $apartado): ?array
{
    return contenido_activo('Datos', $apartado)[0] ?? null;
}
