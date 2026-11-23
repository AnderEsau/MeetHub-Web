<?php
/** Control de reservas aprobadas y cancelación — Responsable: Leonel. */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_BIBLIOTECARIO);
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar') {
    $idr = (int) ($_POST['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $r = db_una("SELECT id_reserva FROM tblreserva WHERE id_reserva = ? AND estado_reserva = 'Aprobada'", 'i', $idr);
    if ($r) {
        db_exec("UPDATE tblreserva SET estado_reserva = 'Cancelada', id_bibliotecario_atiende = ?, motivo_rechazo_cancelacion = ? WHERE id_reserva = ?",
                'isi', (int) $_SESSION['usuario_id'], $motivo !== '' ? mb_substr($motivo, 0, 255) : 'Cancelada por el encargado', $idr);
        flash_set('success', 'Reserva ' . folio($idr) . ' cancelada. El horario quedó libre.');
    } else {
        flash_set('danger', 'Esa reserva ya no está aprobada.');
    }
    header('Location: ' . url('admin/reservas.php'));
    exit;
}

$q = trim($_GET['q'] ?? ''); $fSala = (int) ($_GET['sala'] ?? 0); $fFecha = $_GET['fecha'] ?? ''; $fEst = $_GET['estado'] ?? 'Aprobada';
$sql = SQL_RESERVAS . " WHERE 1=1"; $t = ''; $p = [];
if ($fEst === 'Aprobada' || $fEst === 'Cancelada') { $sql .= " AND r.estado_reserva = ?"; $t .= 's'; $p[] = $fEst; } else { $sql .= " AND r.estado_reserva IN ('Aprobada','Cancelada')"; }
if ($q !== '') { $sql .= " AND (r.id_reserva = ? OR CONCAT(a.nombre,' ',a.apellido_paterno) LIKE ? OR CONCAT(t.nombre,' ',t.apellido_paterno) LIKE ? OR CONCAT(x.nombre,' ',x.apellido_paterno) LIKE ?)"; $t .= 'isss'; $p[] = (int) preg_replace('/\D/', '', $q); array_push($p, "%$q%", "%$q%", "%$q%"); }
if ($fSala) { $sql .= " AND r.id_sala = ?"; $t .= 'i'; $p[] = $fSala; }
if ($fFecha !== '') { $sql .= " AND r.fecha_reserva = ?"; $t .= 's'; $p[] = $fFecha; }
$sql .= " ORDER BY r.fecha_reserva DESC, r.hora_inicio";
$filas = db_todos($sql, $t, ...$p);
$salas = db_todos("SELECT id_sala, nombre_sala FROM tblsalas ORDER BY nombre_sala");
$idSel = (int) ($_GET['id'] ?? 0);
$sel = null; foreach ($filas as $f) { if ((int) $f['id_reserva'] === $idSel && $f['estado_reserva'] === 'Aprobada') { $sel = $f; } }
if (!$sel) { foreach ($filas as $f) { if ($f['estado_reserva'] === 'Aprobada') { $sel = $f; break; } } }
$nA = count(array_filter($filas, fn($f) => $f['estado_reserva'] === 'Aprobada')); $nC = count($filas) - $nA;

$titulo = 'Reservas';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo">
        <?php flash_show(); ?>
        <h1 class="h3 mh-title mb-1">Control de Reservas Aprobadas y Cancelación</h1>
        <p class="small mb-3">Consulta las reservas confirmadas en el calendario de la biblioteca y cancela cualquier reserva activa liberando el horario de inmediato.</p>
        <div class="row g-3">
            <div class="col-lg-8"><section class="mh-card p-4">
                <h2 class="h6 mh-title mb-3">Listado de Reservas Confirmadas</h2>
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-7"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por folio o solicitante..."></div>
                    <div class="col-md-5"><select class="form-select" name="sala"><option value="0">Sala: Todas las salas</option><?php foreach ($salas as $s): ?><option value="<?= (int) $s['id_sala'] ?>" <?= $fSala === (int) $s['id_sala'] ? 'selected' : '' ?>><?= e($s['nombre_sala']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><input type="date" class="form-control" name="fecha" value="<?= e($fFecha) ?>"></div>
                    <div class="col-md-5"><select class="form-select" name="estado"><option value="Aprobada" <?= $fEst === 'Aprobada' ? 'selected' : '' ?>>Estatus: Aprobadas (Activas)</option><option value="Cancelada" <?= $fEst === 'Cancelada' ? 'selected' : '' ?>>Estatus: Canceladas</option><option value="Todas" <?= $fEst === 'Todas' ? 'selected' : '' ?>>Estatus: Todas</option></select></div>
                    <div class="col-md-3"><button class="btn btn-mh w-100">Filtrar</button></div>
                </form>
                <div class="table-responsive"><table class="table mh-tabla align-middle mb-0">
                    <thead><tr><th>Folio</th><th>Solicitante</th><th>Sala Reservada</th><th>Fecha y Horario</th><th>Asistentes</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($filas as $f): ?>
                        <tr>
                            <td class="fw-bold"><?= folio((int) $f['id_reserva']) ?></td>
                            <td><strong><?= e($f['solicitante']) ?></strong><div class="text-muted" style="font-size:.7rem">(<?= e($f['tipo_usuario']) ?> · <?= (int) $f['clave_usuario'] ?>)</div></td>
                            <td><?= e($f['nombre_sala']) ?></td>
                            <td><?= fecha_mx($f['fecha_reserva']) ?><br><?= hora_corta($f['hora_inicio']) ?>–<?= hora_corta($f['hora_fin']) ?></td>
                            <td><?= (int) $f['total_asistentes'] ?> pers.</td>
                            <td><span class="<?= clase_estatus($f['estado_reserva']) ?>"><?= e($f['estado_reserva']) ?></span></td>
                            <td><?php if ($f['estado_reserva'] === 'Aprobada'): ?><a class="btn btn-mh-danger btn-sm" href="?id=<?= (int) $f['id_reserva'] ?>&estado=<?= e($fEst) ?>#cancelar">Cancelar reserva</a><?php else: ?><span class="small text-muted">Horario liberado</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$filas): ?><tr><td colspan="7" class="text-center py-4 small">Sin resultados.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
                <div class="small text-muted mt-3"><?= count($filas) ?> registros · <?= $nA ?> reservas aprobadas · <?= $nC ?> cancelada(s)</div>
            </section></div>
            <div class="col-lg-4" id="cancelar"><section class="mh-card p-4">
                <?php if ($sel): ?>
                    <h2 class="h6 mh-title mb-3">Cancelar Reserva Seleccionada <?= folio((int) $sel['id_reserva']) ?></h2>
                    <div class="mh-tint p-3 small mb-3">
                        <div>Folio: <?= folio((int) $sel['id_reserva']) ?></div><div>Solicitante: <?= e($sel['solicitante']) ?></div><div>Sala: <?= e($sel['nombre_sala']) ?></div>
                        <div>Fecha: <?= fecha_mx($sel['fecha_reserva']) ?> (<?= hora_corta($sel['hora_inicio']) ?>–<?= hora_corta($sel['hora_fin']) ?>)</div><div>Actividad: <?= e($sel['descripcion_evento']) ?></div>
                    </div>
                    <div class="mh-tint p-3 small mb-3"><?= icono('info', 15) ?> Al cancelar esta reserva, el estatus cambiará a 'Cancelada', se registrará la fecha en el historial y el horario quedará libre.</div>
                    <form method="POST"><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= (int) $sel['id_reserva'] ?>">
                        <label class="form-label" for="motivo">Motivo de la cancelación (Opcional / Registro interno)</label>
                        <textarea class="form-control mb-3" id="motivo" name="motivo" rows="3" maxlength="255"></textarea>
                        <button class="btn btn-danger w-100 mb-2" type="submit" data-confirm="¿Cancelar la reserva <?= folio((int) $sel['id_reserva']) ?>?">Confirmar Cancelación de Reserva</button>
                        <a class="btn btn-mh-outline w-100" href="<?= url('admin/reservas.php') ?>">Conservar Reserva</a></form>
                <?php else: ?><h2 class="h6 mh-title">Cancelar Reserva</h2><p class="small text-muted mb-0">No hay reservas aprobadas para cancelar.</p><?php endif; ?>
            </section></div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
