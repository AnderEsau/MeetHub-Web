<?php
/**
 * Mensajes de contacto (vista privada filtrada por fechas) — Responsable: Lary
 * Tabla: tblcontacto. El encargado/administrador marca como Atendido o da de baja (elimina) el mensaje.
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol([ROL_BIBLIOTECARIO, ROL_ADMIN]);
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['accion'] ?? '') === 'atender') {
        db_exec("UPDATE tblcontacto SET estado = 'Atendido' WHERE id_contacto = ?", 'i', $id);
        flash_set('success', 'Mensaje marcado como atendido.');
    } elseif (($_POST['accion'] ?? '') === 'baja') {
        db_exec("DELETE FROM tblcontacto WHERE id_contacto = ?", 'i', $id);
        flash_set('success', 'Mensaje dado de baja.');
    }
    header('Location: ' . url('admin/mensajes.php?' . http_build_query(array_intersect_key($_GET, ['ini' => 1, 'fin' => 1, 'estado' => 1]))));
    exit;
}

$ini = $_GET['ini'] ?? ''; $fin = $_GET['fin'] ?? ''; $est = $_GET['estado'] ?? '';
$sql = "SELECT * FROM tblcontacto WHERE 1=1"; $t = ''; $p = [];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini)) { $sql .= " AND DATE(fecha_envio) >= ?"; $t .= 's'; $p[] = $ini; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) { $sql .= " AND DATE(fecha_envio) <= ?"; $t .= 's'; $p[] = $fin; }
if (in_array($est, ['Pendiente', 'Atendido'], true)) { $sql .= " AND estado = ?"; $t .= 's'; $p[] = $est; }
$sql .= " ORDER BY fecha_envio DESC";
$filas = db_todos($sql, $t, ...$p);

$titulo = 'Mensajes de contacto';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Mensajes de Contacto</h1>
        <p class="small mb-3">Mensajes enviados desde el formulario público, ordenados y filtrados por fecha de envío.</p>
        <form method="GET" class="mh-card p-3 mb-3"><div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">Desde</label><input type="date" class="form-control" name="ini" value="<?= e($ini) ?>"></div>
            <div class="col-md-3"><label class="form-label">Hasta</label><input type="date" class="form-control" name="fin" value="<?= e($fin) ?>"></div>
            <div class="col-md-3"><label class="form-label">Estado</label><select class="form-select" name="estado"><option value="">Todos</option><option <?= $est === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option><option <?= $est === 'Atendido' ? 'selected' : '' ?>>Atendido</option></select></div>
            <div class="col-md-3"><button class="btn btn-mh w-100">Filtrar por fechas</button></div>
        </div></form>
        <section class="mh-card p-4">
            <div class="table-responsive"><table class="table mh-tabla align-middle mb-3">
                <thead><tr><th>Fecha</th><th>Nombre</th><th>Correo</th><th>Asunto</th><th>Mensaje</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr><td><?= date('d/m/Y H:i', strtotime($f['fecha_envio'])) ?></td><td><?= e($f['nombre_completo']) ?></td>
                        <td><a href="mailto:<?= e($f['correo_electronico']) ?>"><?= e($f['correo_electronico']) ?></a></td><td><?= e($f['asunto']) ?></td>
                        <td style="max-width:280px"><?= e($f['mensaje']) ?></td>
                        <td><span class="mh-badge <?= $f['estado'] === 'Pendiente' ? 'mh-badge-pendiente' : 'mh-badge-activo' ?>"><?= e($f['estado']) ?></span></td>
                        <td style="min-width:120px"><form method="POST" class="d-flex flex-column gap-1 m-0"><input type="hidden" name="id" value="<?= (int) $f['id_contacto'] ?>">
                            <?php if ($f['estado'] === 'Pendiente'): ?><button class="btn btn-mh-outline btn-sm" name="accion" value="atender">Atender</button><?php endif; ?>
                            <button class="btn btn-mh-danger btn-sm" name="accion" value="baja" data-confirm="¿Dar de baja este mensaje?">Baja</button></form></td></tr>
                <?php endforeach; ?>
                <?php if (!$filas): ?><tr><td colspan="7" class="text-center py-4 small">No hay mensajes en ese rango.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
            <div class="mh-tabla-pie"><?= count($filas) ?> mensaje(s).</div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>