<?php
/**
 * Solicitudes pendientes — Responsable: Leonel
 * El encargado aprueba (valida que no haya otra reserva aprobada en ese horario) o rechaza (motivo obligatorio).
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_BIBLIOTECARIO);
require_once __DIR__ . '/../includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idr = (int) ($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');
    $r = db_una("SELECT * FROM tblreserva WHERE id_reserva = ? AND estado_reserva = 'Pendiente'", 'i', $idr);
    if (!$r) {
        flash_set('danger', 'La solicitud ya no está pendiente.');
    } elseif ($accion === 'aprobar') {
        $choque = db_una("SELECT id_reserva FROM tblreserva WHERE id_sala = ? AND fecha_reserva = ? AND estado_reserva = 'Aprobada' AND hora_inicio < ? AND hora_fin > ? LIMIT 1",
                         'isss', (int) $r['id_sala'], $r['fecha_reserva'], $r['hora_fin'], $r['hora_inicio']);
        if ($choque) {
            flash_set('danger', 'No se puede aprobar: se traslapa con la reserva ' . folio((int) $choque['id_reserva']) . '.');
        } else {
            db_exec("UPDATE tblreserva SET estado_reserva = 'Aprobada', id_bibliotecario_atiende = ?, motivo_rechazo_cancelacion = NULL WHERE id_reserva = ?", 'ii', (int) $_SESSION['usuario_id'], $idr);
            flash_set('success', 'Solicitud ' . folio($idr) . ' aprobada.');
        }
    } elseif ($accion === 'rechazar') {
        if ($motivo === '') {
            flash_set('danger', 'El motivo es obligatorio para rechazar.');
            header('Location: ' . url('admin/solicitudes.php?id=' . $idr)); exit;
        }
        db_exec("UPDATE tblreserva SET estado_reserva = 'Rechazada', id_bibliotecario_atiende = ?, motivo_rechazo_cancelacion = ? WHERE id_reserva = ?", 'isi', (int) $_SESSION['usuario_id'], mb_substr($motivo, 0, 255), $idr);
        flash_set('success', 'Solicitud ' . folio($idr) . ' rechazada.');
    }
    header('Location: ' . url('admin/solicitudes.php'));
    exit;
}

$q = trim($_GET['q'] ?? ''); $fSala = (int) ($_GET['sala'] ?? 0); $fFecha = $_GET['fecha'] ?? '';
$sql = SQL_RESERVAS . " WHERE r.estado_reserva = 'Pendiente'"; $t = ''; $p = [];
if ($q !== '') { $sql .= " AND (r.id_reserva = ? OR CONCAT(a.nombre,' ',a.apellido_paterno) LIKE ? OR CONCAT(t.nombre,' ',t.apellido_paterno) LIKE ? OR CONCAT(x.nombre,' ',x.apellido_paterno) LIKE ?)"; $t .= 'isss'; $p[] = (int) preg_replace('/\D/', '', $q); array_push($p, "%$q%", "%$q%", "%$q%"); }
if ($fSala) { $sql .= " AND r.id_sala = ?"; $t .= 'i'; $p[] = $fSala; }
if ($fFecha !== '') { $sql .= " AND r.fecha_reserva = ?"; $t .= 's'; $p[] = $fFecha; }
$sql .= " ORDER BY r.fecha_reserva, r.hora_inicio";
$filas = db_todos($sql, $t, ...$p);
$salas = db_todos("SELECT id_sala, nombre_sala FROM tblsalas ORDER BY nombre_sala");
$idSel = (int) ($_GET['id'] ?? ($filas[0]['id_reserva'] ?? 0));
$sel = null; foreach ($filas as $f) { if ((int) $f['id_reserva'] === $idSel) { $sel = $f; } }
$total = (int) db_una("SELECT COUNT(*) n FROM tblreserva WHERE estado_reserva = 'Pendiente'")['n'];

$titulo = 'Solicitudes pendientes';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Solicitudes pendientes y Reservas</h1>
        <p class="small mb-3">Aprueba o rechaza solicitudes pendientes verificando anticipación de <?= DIAS_ANTICIPACION ?> días y aforo mínimo.</p>
        <div class="row g-3">
            <div class="col-lg-8"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3">Listado de Solicitudes y Reservas</h2>
                <div class="mh-pestanas mb-3"><a class="active" href="<?= url('admin/solicitudes.php') ?>">Solicitudes pendientes (<?= $total ?>)</a><a href="<?= url('admin/reservas.php') ?>">Reservas activas</a></div>
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por folio o solicitante..."></div>
                    <div class="col-md-3"><select class="form-select" name="sala"><option value="0">Sala: Todas</option><?php foreach ($salas as $s): ?><option value="<?= (int) $s['id_sala'] ?>" <?= $fSala === (int) $s['id_sala'] ? 'selected' : '' ?>><?= e($s['nombre_sala']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><input type="date" class="form-control" name="fecha" value="<?= e($fFecha) ?>"></div>
                    <div class="col-md-2"><button class="btn btn-mh w-100">Filtrar</button></div>
                </form>
                <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                    <thead><tr><th>Folio</th><th>Solicitante</th><th>Sala</th><th>Fecha y Horario</th><th>Asistentes</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($filas as $f): $esSel = $sel && (int) $f['id_reserva'] === (int) $sel['id_reserva']; ?>
                        <tr class="<?= $esSel ? 'table-active' : '' ?>">
                            <td class="fw-bold"><a class="text-decoration-none" href="?id=<?= (int) $f['id_reserva'] ?>"><?= folio((int) $f['id_reserva']) ?></a></td>
                            <td><strong><?= e($f['solicitante']) ?></strong><div class="text-muted" style="font-size:.7rem">(<?= e($f['tipo_usuario']) ?> · <?= (int) $f['clave_usuario'] ?>)</div></td>
                            <td><?= e($f['nombre_sala']) ?></td>
                            <td><?= fecha_mx($f['fecha_reserva']) ?><br><?= hora_corta($f['hora_inicio']) ?>–<?= hora_corta($f['hora_fin']) ?></td>
                            <td><strong><?= (int) $f['total_asistentes'] ?></strong><div class="text-muted" style="font-size:.7rem">(Mín. <?= (int) $f['capacidad_minima'] ?>)</div></td>
                            <td><span class="mh-badge mh-badge-pendiente">Pendiente</span></td>
                            <td style="min-width:110px">
                                <form method="POST" class="mb-1"><input type="hidden" name="accion" value="aprobar"><input type="hidden" name="id" value="<?= (int) $f['id_reserva'] ?>">
                                    <button class="btn btn-mh-ok btn-sm w-100" type="submit" data-confirm="¿Aprobar <?= folio((int) $f['id_reserva']) ?>?">Aprobar</button></form>
                                <a class="btn btn-mh-danger btn-sm w-100" href="?id=<?= (int) $f['id_reserva'] ?>#resolucion">Rechazar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$filas): ?><tr><td colspan="7" class="text-center py-4 small">No hay solicitudes pendientes con esos filtros.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
                <div class="small text-muted mt-3"><?= count($filas) ?> registro(s) mostrados</div>
            </section></div>

            <div class="col-lg-4" id="resolucion"><section class="mh-card p-4">
                <?php if ($sel): ?>
                    <h2 class="h6 mh-title mb-3">Detalle y Resolución <?= folio((int) $sel['id_reserva']) ?></h2>
                    <div class="mh-tint p-3 small mb-3">
                        <div><strong>Solicitante:</strong> <?= e($sel['solicitante']) ?> (<?= e($sel['tipo_usuario']) ?> · <?= (int) $sel['clave_usuario'] ?>)</div>
                        <div class="mt-2"><strong>Sala:</strong> <?= e($sel['nombre_sala']) ?> · <?= fecha_mx($sel['fecha_reserva']) ?> <?= hora_corta($sel['hora_inicio']) ?>–<?= hora_corta($sel['hora_fin']) ?></div>
                        <div class="mt-2"><strong>Evento:</strong> <?= e($sel['tipo_evento']) ?> — <?= e($sel['descripcion_evento']) ?></div>
                        <?php $dias = (strtotime($sel['fecha_reserva']) - strtotime($sel['fecha_solicitud'])) / 86400; $okAforo = $sel['total_asistentes'] >= $sel['capacidad_minima']; ?>
                        <div class="mt-2"><strong>Validación:</strong> <?= $dias >= DIAS_ANTICIPACION ? 'Cumple ' . DIAS_ANTICIPACION . ' días de anticipación' : '<span class="text-danger">No cumple anticipación</span>' ?> y <?= $okAforo ? 'aforo mínimo.' : '<span class="text-danger">no cumple aforo mínimo.</span>' ?></div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= (int) $sel['id_reserva'] ?>">
                        <label class="form-label" for="motivo">Motivo de rechazo o cancelación * (Obligatorio al rechazar)</label>
                        <textarea class="form-control mb-3" id="motivo" name="motivo" rows="4" maxlength="255" placeholder="Escribe el motivo en caso de rechazar..."></textarea>
                        <button class="btn btn-mh w-100 mb-2" name="accion" value="aprobar" type="submit">Guardar Aprobación</button>
                        <button class="btn btn-mh-danger w-100" name="accion" value="rechazar" type="submit" data-confirm="¿Confirmar el rechazo de <?= folio((int) $sel['id_reserva']) ?>?">Confirmar Rechazo</button>
                    </form>
                <?php else: ?>
                    <h2 class="h6 mh-title">Detalle y Resolución</h2><p class="small text-muted mb-0">Selecciona una solicitud del listado.</p>
                <?php endif; ?>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
