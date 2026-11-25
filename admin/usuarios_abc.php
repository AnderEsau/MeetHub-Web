<?php
/**
 * Usuarios y roles (ABC) — Responsable: Ander. Solo Administrador.
 *   Pestaña "Personal": cuentas de tblbibliotecario (Bibliotecario / Administrador) ligadas a tbltrabajadores.
 *   Pestaña "Solicitantes": alumnos, docentes y externos (listado y baja/reactivación).
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_ADMIN);
require_once __DIR__ . '/../includes/usuarios.php';

$tab = ($_GET['tab'] ?? '') === 'solicitantes' ? 'solicitantes' : 'personal';
$errores = [];
$vacio = ['id_bibliotecario' => 0, 'matricula' => '', 'usuarios' => '', 'tipo' => 'Bibliotecario', 'activo' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';
    // --- Baja / reactivar (personal o solicitantes) ---
    if (in_array($accion, ['baja', 'reactivar'], true)) {
        $valor = $accion === 'baja' ? 0 : 1;
        $id = (int) ($_POST['id'] ?? 0);
        $mapa = ['bibliotecario' => ['tblbibliotecario', 'id_bibliotecario'], 'alumno' => ['tblalumnos', 'id_alumno'], 'docente' => ['tbltrabajadores', 'matricula'], 'externo' => ['tblexterno', 'id_externo']];
        $quien = $_POST['quien'] ?? '';
        if (isset($mapa[$quien])) {
            if ($quien === 'bibliotecario' && $id === (int) $_SESSION['usuario_id'] && $valor === 0) {
                flash_set('danger', 'No puedes dar de baja tu propia cuenta.');
            } else {
                [$tb, $pk] = $mapa[$quien];
                db_exec("UPDATE $tb SET activo = ? WHERE $pk = ?", 'ii', $valor, $id);
                flash_set('success', $valor ? 'Cuenta reactivada.' : 'Cuenta dada de baja (ya no puede iniciar sesión).');
            }
        }
        header('Location: ' . url('admin/usuarios_abc.php?tab=' . $tab)); exit;
    }
    // --- Alta / cambios de personal ---
    $f = array_merge($vacio, array_map('trim', array_intersect_key($_POST, $vacio)));
    $id = (int) $f['id_bibliotecario'];
    $pass = $_POST['psw'] ?? '';
    if (!ctype_digit((string) $f['matricula']) || !db_una("SELECT 1 x FROM tbltrabajadores WHERE matricula = ?", 'i', (int) $f['matricula'])) { $errores[] = 'Selecciona un trabajador registrado.'; }
    if ($f['usuarios'] === '' || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $f['usuarios'])) { $errores[] = 'El usuario debe tener 3 a 50 caracteres (letras, números, punto, guion).'; }
    if (!in_array($f['tipo'], ['Bibliotecario', 'Administrador'], true)) { $errores[] = 'Selecciona el rol.'; }
    if (!$errores) {
        $dup = db_una("SELECT id_bibliotecario FROM tblbibliotecario WHERE usuarios = ? AND id_bibliotecario <> ?", 'si', $f['usuarios'], $id);
        if ($dup) { $errores[] = 'Ese nombre de usuario ya existe.'; }
        $dupM = db_una("SELECT id_bibliotecario FROM tblbibliotecario WHERE matricula = ? AND id_bibliotecario <> ?", 'ii', (int) $f['matricula'], $id);
        if ($dupM) { $errores[] = 'Ese trabajador ya tiene una cuenta de personal.'; }
    }
    if ($id === 0 && !password_valida($pass)) { $errores[] = MSG_PASSWORD; }
    if ($id > 0 && $pass !== '' && !password_valida($pass)) { $errores[] = MSG_PASSWORD; }
    if ($id === (int) $_SESSION['usuario_id'] && $f['tipo'] !== 'Administrador') { $errores[] = 'No puedes quitarte a ti mismo el rol de Administrador.'; }
    if (!$errores) {
        if ($id > 0) {
            db_exec("UPDATE tblbibliotecario SET matricula=?, usuarios=?, tipo=? WHERE id_bibliotecario=?", 'issi', (int) $f['matricula'], $f['usuarios'], $f['tipo'], $id);
            if ($pass !== '') { cambiar_password('tblbibliotecario', 'id_bibliotecario', $id, $pass); }
            flash_set('success', 'Cuenta de personal actualizada.');
        } else {
            db_exec("INSERT INTO tblbibliotecario (matricula, usuarios, psw, tipo, activo) VALUES (?,?,?,?,1)", 'isss', (int) $f['matricula'], $f['usuarios'], hash_password($pass), $f['tipo']);
            flash_set('success', 'Cuenta de personal creada.');
        }
        header('Location: ' . url('admin/usuarios_abc.php')); exit;
    }
    $form = $f;
} else {
    $form = isset($_GET['editar']) ? (db_una("SELECT * FROM tblbibliotecario WHERE id_bibliotecario = ?", 'i', (int) $_GET['editar']) ?? $vacio) : $vacio;
}

$trabajadores = db_todos("SELECT t.matricula, t.nombre, t.apellido_paterno, t.apellido_materno FROM tbltrabajadores t
                           WHERE t.activo = 1 AND (NOT EXISTS (SELECT 1 FROM tblbibliotecario b WHERE b.matricula = t.matricula) OR t.matricula = ?) ORDER BY t.nombre", 'i', (int) $form['matricula']);
$personal = db_todos("SELECT b.*, t.nombre, t.apellido_paterno, t.apellido_materno FROM tblbibliotecario b LEFT JOIN tbltrabajadores t ON t.matricula = b.matricula ORDER BY b.id_bibliotecario");
$solic = [];
if ($tab === 'solicitantes') {
    $solic = db_todos("SELECT 'alumno' AS quien, 'Alumno' AS tipo, id_alumno AS id, id_alumno AS clave, nombre, apellido_paterno, apellido_materno, correo_electronico, activo FROM tblalumnos
                       UNION ALL SELECT 'docente', 'Docente', matricula, matricula, nombre, apellido_paterno, apellido_materno, correo_electronico, activo FROM tbltrabajadores
                       UNION ALL SELECT 'externo', 'Externo', id_externo, rfc, nombre, apellido_paterno, apellido_materno, correo_electronico, activo FROM tblexterno ORDER BY tipo, nombre");
}

$titulo = 'Usuarios / Roles (ABC)';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Usuarios y Roles (Alta, Baja, Cambios y Listado)</h1>
        <p class="small mb-3">Administra las cuentas del personal (Bibliotecario / Administrador) y consulta o da de baja a los solicitantes.</p>
        <div class="mh-pestanas mb-3">
            <a class="<?= $tab === 'personal' ? 'active' : '' ?>" href="?tab=personal">Personal de biblioteca</a>
            <a class="<?= $tab === 'solicitantes' ? 'active' : '' ?>" href="?tab=solicitantes">Solicitantes (Alumnos, Docentes, Externos)</a>
        </div>

        <?php if ($tab === 'personal'): ?>
        <div class="row g-3">
            <div class="col-lg-4"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3"><?= $form['id_bibliotecario'] ? 'Editar cuenta de personal' : 'Nueva cuenta de personal' ?></h2>
                <?php foreach ($errores as $er): ?><div class="alert alert-danger py-2 small"><?= e($er) ?></div><?php endforeach; ?>
                <form method="POST" novalidate autocomplete="off">
                    <input type="hidden" name="id_bibliotecario" value="<?= (int) $form['id_bibliotecario'] ?>">
                    <div class="mb-3"><label class="form-label" for="matricula">Trabajador (tbltrabajadores) *</label>
                        <select class="form-select" id="matricula" name="matricula" required><option value="">Selecciona...</option>
                            <?php foreach ($trabajadores as $t): ?><option value="<?= (int) $t['matricula'] ?>" <?= (int) $form['matricula'] === (int) $t['matricula'] ? 'selected' : '' ?>><?= (int) $t['matricula'] ?> · <?= e(nombre_completo($t)) ?></option><?php endforeach; ?></select>
                        <div class="form-text">El trabajador debe estar dado de alta como Docente/Personal.</div></div>
                    <div class="mb-3"><label class="form-label" for="usuarios">Nombre de usuario *</label><input class="form-control" id="usuarios" name="usuarios" maxlength="50" value="<?= e($form['usuarios']) ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="tipo">Rol *</label><select class="form-select" id="tipo" name="tipo"><?php foreach (['Bibliotecario', 'Administrador'] as $o): ?><option <?= $form['tipo'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label" for="psw"><?= $form['id_bibliotecario'] ? 'Nueva contraseña (opcional)' : 'Contraseña *' ?></label><input type="password" class="form-control" id="psw" name="psw" autocomplete="new-password"><div class="form-text">Mín. 8 caracteres con letra, número y símbolo especial.</div></div>
                    <button class="btn btn-mh w-100" type="submit">Guardar Alta / Cambios</button>
                    <?php if ($form['id_bibliotecario']): ?><a href="<?= url('admin/usuarios_abc.php') ?>" class="btn btn-link btn-sm w-100 mt-2">Cancelar edición / Nueva cuenta</a><?php endif; ?>
                </form>
            </section></div>
            <div class="col-lg-8"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3">Cuentas del personal</h2>
                <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
                    <?php foreach ($personal as $b): ?>
                        <tr><td>U-<?= str_pad((string) $b['id_bibliotecario'], 2, '0', STR_PAD_LEFT) ?></td><td class="fw-semibold"><?= e(nombre_completo($b)) ?></td><td><?= e($b['usuarios']) ?></td><td><?= e($b['tipo']) ?></td>
                            <td><span class="mh-badge <?= $b['activo'] ? 'mh-badge-activo' : 'mh-badge-baja' ?>"><?= $b['activo'] ? 'Activo' : 'Baja' ?></span></td>
                            <td style="min-width:110px"><a class="btn btn-mh-outline btn-sm" href="?editar=<?= (int) $b['id_bibliotecario'] ?>">Editar</a>
                                <form method="POST" class="d-inline"><input type="hidden" name="quien" value="bibliotecario"><input type="hidden" name="id" value="<?= (int) $b['id_bibliotecario'] ?>">
                                <?php if ($b['activo']): ?><button class="btn btn-mh-danger btn-sm" name="accion" value="baja" data-confirm="¿Dar de baja esta cuenta?">Baja</button>
                                <?php else: ?><button class="btn btn-mh-outline btn-sm" name="accion" value="reactivar">Reactivar</button><?php endif; ?></form></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
            </section></div>
        </div>
        <?php else: ?>
        <section class="mh-card p-4">
            <h2 class="h6 mh-title mb-3">Solicitantes registrados</h2>
            <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                <thead><tr><th>Tipo</th><th>Matrícula / RFC</th><th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
                <?php foreach ($solic as $s): ?>
                    <tr><td><?= e($s['tipo']) ?></td><td><?= e($s['clave']) ?></td><td class="fw-semibold"><?= e(nombre_completo($s)) ?></td><td><?= e($s['correo_electronico']) ?></td>
                        <td><span class="mh-badge <?= $s['activo'] ? 'mh-badge-activo' : 'mh-badge-baja' ?>"><?= $s['activo'] ? 'Activo' : 'Baja' ?></span></td>
                        <td><form method="POST" class="m-0"><input type="hidden" name="quien" value="<?= e($s['quien']) ?>"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <?php if ($s['activo']): ?><button class="btn btn-mh-danger btn-sm" name="accion" value="baja" data-confirm="¿Dar de baja esta cuenta?">Baja</button>
                            <?php else: ?><button class="btn btn-mh-outline btn-sm" name="accion" value="reactivar">Reactivar</button><?php endif; ?></form></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
        </section>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
