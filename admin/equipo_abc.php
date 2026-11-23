<?php
/** Mobiliario y equipamiento (Altas, Bajas, Cambios y Listado) — Responsable: Leonel. Tabla: tblequipamiento. */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_BIBLIOTECARIO);
require_once __DIR__ . '/../includes/db.php';

$salas = db_todos("SELECT id_sala, nombre_sala FROM tblsalas ORDER BY nombre_sala");
$tiposEq = ['Equipamiento', 'Mobiliario', 'Cómputo', 'Audio y video', 'Otro'];
$errores = [];
$vacio = ['id_equipamiento' => 0, 'nombre_equipamiento' => '', 'id_sala' => '', 'tipo_equipamiento' => 'Equipamiento', 'cantidad' => 1, 'descripcion' => '', 'estado' => 'Activo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';
    $id = (int) ($_POST['id_equipamiento'] ?? 0);
    if ($accion === 'baja' || $accion === 'reactivar') {
        db_exec("UPDATE tblequipamiento SET estado = ? WHERE id_equipamiento = ?", 'si', $accion === 'baja' ? 'Baja' : 'Activo', $id);
        flash_set('success', $accion === 'baja' ? 'Recurso dado de baja.' : 'Recurso reactivado.');
        header('Location: ' . url('admin/equipo_abc.php')); exit;
    }
    $f = array_merge($vacio, array_map('trim', array_intersect_key($_POST, $vacio)));
    $f['id_equipamiento'] = $id;
    if ($f['nombre_equipamiento'] === '' || $f['descripcion'] === '') { $errores[] = 'Nombre y descripción son obligatorios.'; }
    if (!db_una("SELECT 1 x FROM tblsalas WHERE id_sala = ?", 'i', (int) $f['id_sala'])) { $errores[] = 'Selecciona la sala asignada.'; }
    if (!in_array($f['tipo_equipamiento'], $tiposEq, true)) { $errores[] = 'Selecciona un tipo válido.'; }
    if ((int) $f['cantidad'] < 1) { $errores[] = 'La cantidad debe ser al menos 1.'; }
    if (!in_array($f['estado'], ['Activo', 'Baja'], true)) { $errores[] = 'Estado inválido.'; }
    if (!$errores) {
        if ($id > 0) {
            db_exec("UPDATE tblequipamiento SET id_sala=?, nombre_equipamiento=?, descripcion=?, tipo_equipamiento=?, cantidad=?, estado=? WHERE id_equipamiento=?",
                    'isssisi', (int) $f['id_sala'], $f['nombre_equipamiento'], $f['descripcion'], $f['tipo_equipamiento'], (int) $f['cantidad'], $f['estado'], $id);
            flash_set('success', 'Recurso actualizado.');
        } else {
            db_exec("INSERT INTO tblequipamiento (id_sala, nombre_equipamiento, descripcion, tipo_equipamiento, cantidad, estado) VALUES (?,?,?,?,?,?)",
                    'isssis', (int) $f['id_sala'], $f['nombre_equipamiento'], $f['descripcion'], $f['tipo_equipamiento'], (int) $f['cantidad'], $f['estado']);
            flash_set('success', 'Recurso registrado.');
        }
        header('Location: ' . url('admin/equipo_abc.php')); exit;
    }
    $form = $f;
} else {
    $form = isset($_GET['editar']) ? (db_una("SELECT * FROM tblequipamiento WHERE id_equipamiento = ?", 'i', (int) $_GET['editar']) ?? $vacio) : $vacio;
}

$fSala = (int) ($_GET['sala'] ?? 0); $fTipo = $_GET['tipo'] ?? ''; $q = trim($_GET['q'] ?? '');
$sql = "SELECT e.*, s.nombre_sala FROM tblequipamiento e JOIN tblsalas s ON s.id_sala = e.id_sala WHERE 1=1"; $t = ''; $p = [];
if ($fSala) { $sql .= " AND e.id_sala = ?"; $t .= 'i'; $p[] = $fSala; }
if (in_array($fTipo, $tiposEq, true)) { $sql .= " AND e.tipo_equipamiento = ?"; $t .= 's'; $p[] = $fTipo; }
if ($q !== '') { $sql .= " AND e.nombre_equipamiento LIKE ?"; $t .= 's'; $p[] = "%$q%"; }
$lista = db_todos($sql . " ORDER BY e.id_equipamiento", $t, ...$p);

$titulo = 'Mobiliario (ABC)';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Mobiliario y equipamiento (Alta, Baja, Cambios y Listado)</h1>
        <p class="small mb-3">Administra el mobiliario y equipo asignado a cada sala de la Biblioteca UTHH.</p>
        <div class="row g-3">
            <div class="col-lg-5"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3"><?= $form['id_equipamiento'] ? 'Editar recurso EQ-' . str_pad((string) $form['id_equipamiento'], 2, '0', STR_PAD_LEFT) : 'Formulario de Mobiliario y Equipamiento' ?></h2>
                <?php foreach ($errores as $er): ?><div class="alert alert-danger py-2 small"><?= e($er) ?></div><?php endforeach; ?>
                <form method="POST" novalidate>
                    <input type="hidden" name="id_equipamiento" value="<?= (int) $form['id_equipamiento'] ?>">
                    <div class="mb-3"><label class="form-label" for="nombre_equipamiento">Nombre del recurso *</label><input class="form-control" id="nombre_equipamiento" name="nombre_equipamiento" maxlength="100" value="<?= e($form['nombre_equipamiento']) ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="id_sala">Sala asignada *</label><select class="form-select" id="id_sala" name="id_sala" required><option value="">Selecciona...</option><?php foreach ($salas as $s): ?><option value="<?= (int) $s['id_sala'] ?>" <?= (int) $form['id_sala'] === (int) $s['id_sala'] ? 'selected' : '' ?>><?= e($s['nombre_sala']) ?></option><?php endforeach; ?></select></div>
                    <div class="row g-2 mb-3">
                        <div class="col-7"><label class="form-label" for="tipo_equipamiento">Tipo *</label><select class="form-select" id="tipo_equipamiento" name="tipo_equipamiento"><?php foreach ($tiposEq as $o): ?><option <?= $form['tipo_equipamiento'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                        <div class="col-5"><label class="form-label" for="cantidad">Cantidad *</label><input type="number" min="1" class="form-control" id="cantidad" name="cantidad" value="<?= (int) $form['cantidad'] ?>" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="descripcion">Descripción *</label><input class="form-control" id="descripcion" name="descripcion" maxlength="255" value="<?= e($form['descripcion']) ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="estado">Estado *</label><select class="form-select" id="estado" name="estado"><?php foreach (['Activo', 'Baja'] as $o): ?><option <?= $form['estado'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                    <div class="d-flex gap-2"><button class="btn btn-mh flex-grow-1" name="accion" value="guardar" type="submit">Guardar Alta / Cambios</button>
                        <?php if ($form['id_equipamiento']): ?><button class="btn btn-mh-danger" name="accion" value="baja" type="submit" data-confirm="¿Dar de baja este recurso?">Dar de Baja</button><?php endif; ?></div>
                    <?php if ($form['id_equipamiento']): ?><a href="<?= url('admin/equipo_abc.php') ?>" class="btn btn-link btn-sm w-100 mt-2">Cancelar edición / Nuevo recurso</a><?php endif; ?>
                </form>
            </section></div>
            <div class="col-lg-7"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3">Listado de Mobiliario y Equipamiento</h2>
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4"><select class="form-select" name="sala" onchange="this.form.submit()"><option value="0">Sala: Todas</option><?php foreach ($salas as $s): ?><option value="<?= (int) $s['id_sala'] ?>" <?= $fSala === (int) $s['id_sala'] ? 'selected' : '' ?>><?= e($s['nombre_sala']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><select class="form-select" name="tipo" onchange="this.form.submit()"><option value="">Tipo: Todos</option><?php foreach ($tiposEq as $o): ?><option <?= $fTipo === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar equipo..."></div>
                </form>
                <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                    <thead><tr><th>ID</th><th>Recurso</th><th>Tipo</th><th>Sala Asignada</th><th>Cantidad</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($lista as $r): ?>
                        <tr><td>EQ-<?= str_pad((string) $r['id_equipamiento'], 2, '0', STR_PAD_LEFT) ?></td><td class="fw-semibold"><?= e($r['nombre_equipamiento']) ?></td><td><?= e($r['tipo_equipamiento']) ?></td><td><?= e($r['nombre_sala']) ?></td><td><?= (int) $r['cantidad'] ?></td>
                            <td><span class="mh-badge <?= $r['estado'] === 'Activo' ? 'mh-badge-activo' : 'mh-badge-baja' ?>"><?= e($r['estado']) ?></span></td>
                            <td style="min-width:96px"><a class="btn btn-mh-outline btn-sm" href="?editar=<?= (int) $r['id_equipamiento'] ?>">Editar</a>
                                <form method="POST" class="d-inline"><input type="hidden" name="id_equipamiento" value="<?= (int) $r['id_equipamiento'] ?>">
                                <?php if ($r['estado'] === 'Activo'): ?><button class="btn btn-mh-danger btn-sm" name="accion" value="baja" data-confirm="¿Dar de baja este recurso?">Baja</button>
                                <?php else: ?><button class="btn btn-mh-outline btn-sm" name="accion" value="reactivar">Reactivar</button><?php endif; ?></form></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$lista): ?><tr><td colspan="7" class="text-center py-4 small">Sin recursos.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
