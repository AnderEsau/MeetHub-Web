<?php
/**
 * MOTOR compartido de los ABC de contenido público (tabla tblcontenido_publico).
 * No se abre página en el navegador con este archivo: lo incluyen admin/faq_abc.php, politicas_abc.php,
 * datos_empresa_abc.php y ubicacion_abc.php, cada una con su configuración ($cfg). Cada responsable edita SU página.
 * Cambios al motor = avisar al Repositorio Master (Ander).
 *
 * $cfg: modulo, roles, pagina, apartados (lista fija o null = texto libre), prefijo, etiqueta, foto (apartado que sube imagen o null),
 *       lbl_titulo, lbl_contenido, titulo_pagina
 */
require_once __DIR__ . '/auth.php';
requerir_rol($cfg['roles']);
require_once __DIR__ . '/db.php';

$mod = $cfg['modulo'];
$pagina = $cfg['pagina'];
$pestanas = [   // [archivo, rótulo, roles que la ven]
    ['admin/faq_abc.php', '1. Gestión de FAQ (ABC)', [ROL_BIBLIOTECARIO, ROL_ADMIN]],
    ['admin/politicas_abc.php', '2. Gestión de Políticas (ABC)', [ROL_ADMIN]],
    ['admin/datos_empresa_abc.php', '3. Datos de la empresa (ABC)', [ROL_ADMIN]],
    ['admin/ubicacion_abc.php', '4. Ubicación y fachada (ABC)', [ROL_ADMIN]],
];
$faqApartados = $cfg['apartados'] === null
    ? array_column(db_todos("SELECT DISTINCT apartado FROM tblcontenido_publico WHERE modulo = 'FAQ' ORDER BY apartado"), 'apartado') : [];

$errores = [];
$vacio = ['id_contenido' => 0, 'apartado' => '', 'titulo' => '', 'contenido' => '', 'estado' => 'Activo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';
    $id = (int) ($_POST['id_contenido'] ?? 0);
    if ($accion === 'baja' || $accion === 'reactivar') {
        db_exec("UPDATE tblcontenido_publico SET estado = ? WHERE id_contenido = ? AND modulo = ?", 'sis', $accion === 'baja' ? 'Baja' : 'Activo', $id, $mod);
        flash_set('success', $accion === 'baja' ? 'Registro dado de baja (ya no se muestra en el portal).' : 'Registro reactivado.');
        header('Location: ' . url($pagina)); exit;
    }
    $f = array_merge($vacio, array_map('trim', array_intersect_key($_POST, $vacio)));
    $f['id_contenido'] = $id;
    if ($f['apartado'] === '' || $f['titulo'] === '') { $errores[] = 'Apartado y título son obligatorios.'; }
    if (mb_strlen($f['apartado']) > 100 || mb_strlen($f['titulo']) > 200) { $errores[] = 'Apartado (máx. 100) o título (máx. 200) demasiado largos.'; }
    if ($cfg['apartados'] !== null && !in_array($f['apartado'], $cfg['apartados'], true)) { $errores[] = 'Selecciona un apartado de la lista.'; }
    if (!in_array($f['estado'], ['Activo', 'Baja'], true)) { $errores[] = 'Estado inválido.'; }
    $esFachada = $cfg['foto'] !== null && $f['apartado'] === $cfg['foto'];
    if ($esFachada) {
        $img = guardar_imagen('foto', 'contenido', $errImg);
        if ($errImg) { $errores[] = $errImg; }
        if ($img) { $f['contenido'] = $img; }
        elseif ($f['contenido'] === '') { $errores[] = 'Sube la fotografía de la fachada.'; }
    } elseif ($f['contenido'] === '') { $errores[] = 'El contenido / respuesta es obligatorio.'; }
    if (!$errores) {
        if ($id > 0) {
            db_exec("UPDATE tblcontenido_publico SET apartado=?, titulo=?, contenido=?, estado=? WHERE id_contenido=? AND modulo=?", 'ssssis', $f['apartado'], $f['titulo'], $f['contenido'], $f['estado'], $id, $mod);
            flash_set('success', 'Registro actualizado.');
        } else {
            db_exec("INSERT INTO tblcontenido_publico (modulo, apartado, titulo, contenido, estado) VALUES (?,?,?,?,?)", 'sssss', $mod, $f['apartado'], $f['titulo'], $f['contenido'], $f['estado']);
            flash_set('success', 'Registro creado.');
        }
        header('Location: ' . url($pagina)); exit;
    }
    $form = $f;
} else {
    $form = isset($_GET['editar']) ? (db_una("SELECT * FROM tblcontenido_publico WHERE id_contenido = ? AND modulo = ?", 'is', (int) $_GET['editar'], $mod) ?? $vacio) : $vacio;
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tblcontenido_publico WHERE modulo = ?"; $t = 's'; $p = [$mod];
if ($q !== '') { $sql .= " AND (titulo LIKE ? OR apartado LIKE ? OR contenido LIKE ?)"; $t .= 'sss'; array_push($p, "%$q%", "%$q%", "%$q%"); }
$lista = db_todos($sql . " ORDER BY apartado, id_contenido", $t, ...$p);
$esFachadaForm = $cfg['foto'] !== null && $form['apartado'] === $cfg['foto'];

$titulo = 'Contenido Público (ABC)';
$esAdmin = rol_actual() === ROL_ADMIN;
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Gestión de Contenido Público (Alta, Baja, Cambios y Listado)</h1>
        <p class="small mb-3">Administra dinámicamente las Preguntas Frecuentes (FAQ), Políticas de uso y Datos Institucionales / Ubicación del portal público.</p>
        <div class="d-flex flex-wrap gap-2 mb-3 mh-tabs">
            <?php foreach ($pestanas as [$arch, $rot, $roles]): ?>
                <div><?php if (in_array(rol_actual(), $roles, true)): ?><a class="btn px-4 <?= $arch === $pagina ? 'btn-mh' : 'btn-mh-outline' ?>" href="<?= url($arch) ?>"><?= e($rot) ?></a>
                    <?php else: ?><span class="btn btn-mh-outline px-4 disabled" title="Solo el Administrador"><?= e($rot) ?> <?= icono('lock', 13) ?></span><?php endif; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="row g-3">
            <div class="col-lg-5"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3"><?= $form['id_contenido'] ? 'Editar registro ' . $cfg['prefijo'] . '-' . str_pad((string) $form['id_contenido'], 2, '0', STR_PAD_LEFT) : 'Formulario de Contenido Público' ?></h2>
                <?php foreach ($errores as $er): ?><div class="alert alert-danger py-2 small"><?= e($er) ?></div><?php endforeach; ?>
                <form method="POST" enctype="multipart/form-data" novalidate id="formContenido">
                    <input type="hidden" name="id_contenido" value="<?= (int) $form['id_contenido'] ?>"><input type="hidden" name="modulo" value="<?= e($mod) ?>">
                    <div class="mb-3"><label class="form-label">Módulo público *</label><input class="form-control" value="<?= e($cfg['etiqueta']) ?>" disabled></div>
                    <div class="mb-3"><label class="form-label" for="apartado">Apartado / Categoría *</label>
                        <?php if ($cfg['apartados'] === null): ?>
                            <input class="form-control" id="apartado" name="apartado" list="listaApartados" maxlength="100" value="<?= e($form['apartado']) ?>" placeholder="Elige uno o escribe un apartado nuevo" required>
                            <datalist id="listaApartados"><?php foreach ($faqApartados as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?></datalist>
                        <?php else: ?>
                            <select class="form-select" id="apartado" name="apartado" required <?php if ($cfg['foto'] !== null): ?>onchange="document.getElementById('bloqueFoto').classList.toggle('d-none', this.value !== <?= json_encode($cfg['foto']) ?>)"<?php endif; ?>>
                                <option value="">Selecciona...</option><?php foreach ($cfg['apartados'] as $a): ?><option <?= $form['apartado'] === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
                            </select>
                        <?php endif; ?></div>
                    <div class="mb-3"><label class="form-label" for="titulo"><?= e($cfg['lbl_titulo']) ?> *</label><input class="form-control" id="titulo" name="titulo" maxlength="200" value="<?= e($form['titulo']) ?>" required></div>
                    <div class="mb-3" id="bloqueTexto"><label class="form-label" for="contenido"><?= e($cfg['lbl_contenido']) ?> *</label>
                        <textarea class="form-control" id="contenido" name="contenido" rows="4" <?= $esFachadaForm ? '' : 'required' ?>><?= $esFachadaForm ? '' : e($form['contenido']) ?></textarea></div>
                    <?php if ($cfg['foto'] !== null): ?>
                    <div class="mb-3 <?= $esFachadaForm ? '' : 'd-none' ?>" id="bloqueFoto"><label class="form-label" for="foto">Fotografía de fachada / edificio</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
                        <?php if ($esFachadaForm && $form['contenido']): ?><div class="form-text">Imagen actual conservada si no subes otra.</div><input type="hidden" name="contenido" value="<?= e($form['contenido']) ?>"><?php endif; ?></div>
                    <?php endif; ?>
                    <div class="mb-3"><label class="form-label" for="estado">Estado *</label><select class="form-select" id="estado" name="estado"><option value="Activo" <?= $form['estado'] === 'Activo' ? 'selected' : '' ?>>Activo (Visible en portal público)</option><option value="Baja" <?= $form['estado'] === 'Baja' ? 'selected' : '' ?>>Baja (Oculto)</option></select></div>
                    <div class="d-flex gap-2"><button class="btn btn-mh flex-grow-1" name="accion" value="guardar" type="submit">Guardar Alta / Cambios</button>
                        <?php if ($form['id_contenido']): ?><button class="btn btn-mh-danger" name="accion" value="baja" type="submit" data-confirm="¿Dar de baja este registro?">Dar de Baja</button><?php endif; ?></div>
                    <?php if ($form['id_contenido']): ?><a href="<?= url($pagina) ?>" class="btn btn-link btn-sm w-100 mt-2">Cancelar edición / Nuevo registro</a><?php endif; ?>
                </form>
            </section></div>
            <div class="col-lg-7"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3">Listado de Registros del Portal Público</h2>
                <form method="GET" class="row g-2 mb-3"><input type="hidden" name="modulo" value="<?= e($mod) ?>"><div class="col"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar registro..."></div><div class="col-auto"><button class="btn btn-mh">Buscar</button></div></form>
                <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                    <thead><tr><th>ID</th><th>Apartado</th><th>Título / Contenido</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($lista as $r): ?>
                        <tr><td><?= $cfg['prefijo'] ?>-<?= str_pad((string) $r['id_contenido'], 2, '0', STR_PAD_LEFT) ?></td><td><?= e($r['apartado']) ?></td>
                            <td><strong><?= e(mb_strimwidth($r['titulo'], 0, 70, '…')) ?></strong><div class="text-muted" style="font-size:.7rem"><?= ($cfg['foto'] !== null && $r['apartado'] === $cfg['foto']) ? '(imagen)' : e(mb_strimwidth($r['contenido'], 0, 90, '…')) ?></div></td>
                            <td><span class="mh-badge <?= $r['estado'] === 'Activo' ? 'mh-badge-activo' : 'mh-badge-baja' ?>"><?= e($r['estado']) ?></span></td>
                            <td style="min-width:96px"><a class="btn btn-mh-outline btn-sm" href="?editar=<?= (int) $r['id_contenido'] ?>">Editar</a>
                                <form method="POST" class="d-inline"><input type="hidden" name="modulo" value="<?= e($mod) ?>"><input type="hidden" name="id_contenido" value="<?= (int) $r['id_contenido'] ?>">
                                <?php if ($r['estado'] === 'Activo'): ?><button class="btn btn-mh-danger btn-sm" name="accion" value="baja" data-confirm="¿Dar de baja este registro?">Baja</button>
                                <?php else: ?><button class="btn btn-mh-outline btn-sm" name="accion" value="reactivar">Reactivar</button><?php endif; ?></form></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$lista): ?><tr><td colspan="5" class="text-center py-4 small">Sin registros.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
                <div class="small text-muted mt-3"><?= count($lista) ?> registro(s)</div>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
