<?php
/** Catálogo de salas (Altas, Bajas, Cambios y Listado) — Responsable: Leonel. Tablas: tblsalas / tbltipo_sala. */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_BIBLIOTECARIO);
require_once __DIR__ . '/../includes/db.php';

$tipos = db_todos("SELECT * FROM tbltipo_sala ORDER BY nombre_tipo");
$errores = [];
$vacio = ['id_sala' => 0, 'nombre_sala' => '', 'id_tipo_sala' => '', 'estado' => 'Activa', 'capacidad' => '', 'capacidad_minima' => '', 'hora_apertura' => '08:00:00', 'hora_cierre' => '16:30:00', 'descripcion' => '', 'imagen_url' => '', 'ubicacion' => 'Biblioteca UTHH'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';
    $id = (int) ($_POST['id_sala'] ?? 0);
    if ($accion === 'baja' || $accion === 'reactivar') {
        db_exec("UPDATE tblsalas SET estado = ? WHERE id_sala = ?", 'si', $accion === 'baja' ? 'Baja' : 'Activa', $id);
        flash_set('success', $accion === 'baja' ? 'Sala dada de baja (ya no aparece en el catálogo).' : 'Sala reactivada.');
        header('Location: ' . url('admin/salas_abc.php')); exit;
    }
    $f = array_merge($vacio, array_map('trim', array_intersect_key($_POST, $vacio)));
    $f['id_sala'] = $id;
    if ($f['nombre_sala'] === '' || $f['descripcion'] === '' || $f['ubicacion'] === '') { $errores[] = 'Nombre, ubicación y descripción son obligatorios.'; }
    if (!db_una("SELECT 1 x FROM tbltipo_sala WHERE id_tipo_sala = ?", 'i', (int) $f['id_tipo_sala'])) { $errores[] = 'Selecciona un tipo de sala.'; }
    if (!in_array($f['estado'], ['Activa', 'Baja'], true)) { $errores[] = 'Estado inválido.'; }
    $cap = (int) $f['capacidad']; $min = (int) $f['capacidad_minima'];
    if ($cap < 1 || $min < 1) { $errores[] = 'La capacidad y el aforo mínimo deben ser números positivos.'; }
    elseif ($min > $cap) { $errores[] = 'El aforo mínimo no puede ser mayor que la capacidad máxima.'; }
    if (!preg_match('/^\d{2}:\d{2}/', $f['hora_apertura']) || !preg_match('/^\d{2}:\d{2}/', $f['hora_cierre']) || $f['hora_apertura'] >= $f['hora_cierre']) { $errores[] = 'El horario de uso no es válido (apertura menor que cierre).'; }
    $img = guardar_imagen('foto', 'salas', $errImg);
    if ($errImg) { $errores[] = $errImg; }
    if ($img) { $f['imagen_url'] = $img; }
    if (!$errores) {
        $ha = substr($f['hora_apertura'], 0, 5) . ':00'; $hc = substr($f['hora_cierre'], 0, 5) . ':00';
        if ($id > 0) {
            db_exec("UPDATE tblsalas SET nombre_sala=?, id_tipo_sala=?, ubicacion=?, descripcion=?, capacidad=?, capacidad_minima=?, hora_apertura=?, hora_cierre=?, imagen_url=?, estado=? WHERE id_sala=?",
                    'sissiissssi', $f['nombre_sala'], (int) $f['id_tipo_sala'], $f['ubicacion'], $f['descripcion'], $cap, $min, $ha, $hc, $f['imagen_url'] ?: null, $f['estado'], $id);
            flash_set('success', 'Sala actualizada.');
        } else {
            db_exec("INSERT INTO tblsalas (nombre_sala, id_tipo_sala, ubicacion, descripcion, capacidad, capacidad_minima, hora_apertura, hora_cierre, imagen_url, estado) VALUES (?,?,?,?,?,?,?,?,?,?)",
                    'sissiissss', $f['nombre_sala'], (int) $f['id_tipo_sala'], $f['ubicacion'], $f['descripcion'], $cap, $min, $ha, $hc, $f['imagen_url'] ?: null, $f['estado']);
            flash_set('success', 'Sala registrada.');
        }
        header('Location: ' . url('admin/salas_abc.php')); exit;
    }
    $form = $f;
} else {
    $form = $vacio;
    if (isset($_GET['editar'])) { $form = db_una("SELECT * FROM tblsalas WHERE id_sala = ?", 'i', (int) $_GET['editar']) ?? $vacio; }
}

$q = trim($_GET['q'] ?? '');
$lista = $q !== '' ? db_todos("SELECT s.*, t.nombre_tipo FROM tblsalas s LEFT JOIN tbltipo_sala t ON t.id_tipo_sala = s.id_tipo_sala WHERE s.nombre_sala LIKE ? OR s.ubicacion LIKE ? ORDER BY s.id_sala", 'ss', "%$q%", "%$q%")
                 : db_todos("SELECT s.*, t.nombre_tipo FROM tblsalas s LEFT JOIN tbltipo_sala t ON t.id_tipo_sala = s.id_tipo_sala ORDER BY s.id_sala");

$titulo = 'Salas (ABC)';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Catálogo de Salas (Alta, Baja, Cambios y Listado)</h1>
        <p class="small mb-3">Registra y actualiza los espacios disponibles en la Biblioteca UTHH.</p>
        <div class="row g-3">
            <div class="col-lg-5"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3"><?= $form['id_sala'] ? 'Editar sala S-' . str_pad((string) $form['id_sala'], 2, '0', STR_PAD_LEFT) : 'Formulario de Sala' ?></h2>
                <?php foreach ($errores as $er): ?><div class="alert alert-danger py-2 small"><?= e($er) ?></div><?php endforeach; ?>
                <form method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="id_sala" value="<?= (int) $form['id_sala'] ?>">
                    <div class="mb-3"><label class="form-label" for="nombre_sala">Nombre de la sala *</label><input class="form-control" id="nombre_sala" name="nombre_sala" maxlength="100" value="<?= e($form['nombre_sala']) ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="ubicacion">Ubicación *</label><input class="form-control" id="ubicacion" name="ubicacion" maxlength="100" value="<?= e($form['ubicacion']) ?>" required></div>
                    <div class="row g-2 mb-3">
                        <div class="col-7"><label class="form-label" for="id_tipo_sala">Tipo *</label><select class="form-select" id="id_tipo_sala" name="id_tipo_sala" required><option value="">Selecciona...</option><?php foreach ($tipos as $tp): ?><option value="<?= (int) $tp['id_tipo_sala'] ?>" <?= (int) $form['id_tipo_sala'] === (int) $tp['id_tipo_sala'] ? 'selected' : '' ?>><?= e($tp['nombre_tipo']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-5"><label class="form-label" for="estado">Estado *</label><select class="form-select" id="estado" name="estado"><?php foreach (['Activa', 'Baja'] as $o): ?><option <?= $form['estado'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label" for="capacidad">Capacidad máxima *</label><input type="number" min="1" class="form-control" id="capacidad" name="capacidad" value="<?= e($form['capacidad']) ?>" required></div>
                        <div class="col-6"><label class="form-label" for="capacidad_minima">Aforo mínimo *</label><input type="number" min="1" class="form-control" id="capacidad_minima" name="capacidad_minima" value="<?= e($form['capacidad_minima']) ?>" required></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label" for="hora_apertura">Horario de uso: apertura *</label><input type="time" class="form-control" id="hora_apertura" name="hora_apertura" value="<?= e(hora_corta($form['hora_apertura'])) ?>" required></div>
                        <div class="col-6"><label class="form-label" for="hora_cierre">Cierre *</label><input type="time" class="form-control" id="hora_cierre" name="hora_cierre" value="<?= e(hora_corta($form['hora_cierre'])) ?>" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="foto">Fotografía (JPG/PNG/WEBP, máx. 3 MB)</label><input type="file" class="form-control" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
                        <?php if ($form['imagen_url']): ?><div class="form-text">Imagen actual conservada si no subes otra.</div><input type="hidden" name="imagen_url" value="<?= e($form['imagen_url']) ?>"><?php endif; ?></div>
                    <div class="mb-3"><label class="form-label" for="descripcion">Descripción de la sala *</label><textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="255" required><?= e($form['descripcion']) ?></textarea></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-mh flex-grow-1" name="accion" value="guardar" type="submit">Guardar Alta / Cambios</button>
                        <?php if ($form['id_sala']): ?><button class="btn btn-mh-danger" name="accion" value="baja" type="submit" data-confirm="¿Dar de baja esta sala?">Dar de Baja</button><?php endif; ?>
                    </div>
                    <?php if ($form['id_sala']): ?><a href="<?= url('admin/salas_abc.php') ?>" class="btn btn-link btn-sm w-100 mt-2">Cancelar edición / Nueva sala</a><?php endif; ?>
                </form>
            </section></div>
            <div class="col-lg-7"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3">Listado de Salas Registradas</h2>
                <form method="GET" class="row g-2 mb-3"><div class="col"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar sala..."></div><div class="col-auto"><button class="btn btn-mh">Buscar</button></div></form>
                <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                    <thead><tr><th>ID</th><th>Sala</th><th>Tipo</th><th>Capacidad / Aforo Mín.</th><th>Horario</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($lista as $s): ?>
                        <tr><td>S-<?= str_pad((string) $s['id_sala'], 2, '0', STR_PAD_LEFT) ?></td><td class="fw-semibold"><?= e($s['nombre_sala']) ?></td><td><?= e($s['nombre_tipo']) ?></td>
                            <td><?= (int) $s['capacidad'] ?> / Mín: <?= (int) $s['capacidad_minima'] ?></td><td><?= hora_corta($s['hora_apertura']) ?>–<?= hora_corta($s['hora_cierre']) ?></td>
                            <td><span class="mh-badge <?= $s['estado'] === 'Activa' ? 'mh-badge-activo' : 'mh-badge-baja' ?>"><?= e($s['estado']) ?></span></td>
                            <td style="min-width:96px"><a class="btn btn-mh-outline btn-sm" href="?editar=<?= (int) $s['id_sala'] ?>">Editar</a>
                                <form method="POST" class="d-inline"><input type="hidden" name="id_sala" value="<?= (int) $s['id_sala'] ?>">
                                <?php if ($s['estado'] === 'Activa'): ?><button class="btn btn-mh-danger btn-sm" name="accion" value="baja" data-confirm="¿Dar de baja esta sala?">Baja</button>
                                <?php else: ?><button class="btn btn-mh-outline btn-sm" name="accion" value="reactivar">Reactivar</button><?php endif; ?></form></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$lista): ?><tr><td colspan="7" class="text-center py-4 small">Sin salas registradas.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
