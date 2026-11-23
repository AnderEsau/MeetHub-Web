<?php
/**
 * Mis solicitudes activas — Responsable: Lary
 * Muestra SOLO las reservas del usuario logueado (WHERE <columna del usuario> = id de sesión) y permite cancelar.
 */
require_once __DIR__ . '/../includes/auth.php';
requerir_rol(ROL_SOLICITANTE);
require_once __DIR__ . '/../includes/db.php';

[$col, $uid] = columna_reserva_usuario();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar') {
    $idr = (int) ($_POST['id'] ?? 0);
    $r = db_una("SELECT * FROM tblreserva WHERE id_reserva = ? AND $col = ?", 'ii', $idr, $uid);   // solo puede cancelar las suyas
    if (!$r || !in_array($r['estado_reserva'], ['Pendiente', 'Aprobada'], true)) {
        flash_set('danger', 'No se puede cancelar esa solicitud.');
    } elseif ($r['estado_reserva'] === 'Aprobada' && strtotime($r['fecha_reserva'] . ' ' . $r['hora_inicio']) < strtotime('+24 hours')) {
        flash_set('danger', 'Las reservas aprobadas se cancelan con al menos 24 horas de anticipación.');
    } else {
        db_exec("UPDATE tblreserva SET estado_reserva = 'Cancelada', motivo_rechazo_cancelacion = 'Cancelada por el usuario' WHERE id_reserva = ?", 'i', $idr);
        flash_set('success', 'Solicitud ' . folio($idr) . ' cancelada.');
    }
    header('Location: ' . url('usuario/mis_solicitudes.php'));
    exit;
}

$filas = db_todos("SELECT r.*, s.nombre_sala FROM tblreserva r JOIN tblsalas s ON s.id_sala = r.id_sala
                    WHERE r.$col = ? AND r.estado_reserva IN ('Pendiente','Aprobada') AND r.fecha_reserva >= CURDATE()
                    ORDER BY r.fecha_reserva, r.hora_inicio", 'i', $uid);

$titulo = 'Mis solicitudes';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-grow-1 py-4">
    <div class="container mh-cuerpo-sm">
        <?php flash_show(); ?>
        <section class="mh-card p-4">
            <h1 class="h5 mh-title mb-1">Mis solicitudes activas</h1>
            <p class="small mb-3">Consulta el estado de tus reservaciones próximas o cancela en caso de no asistir.</p>
            <div class="table-responsive"><table class="table mh-tabla oscura align-middle mb-0">
                <thead><tr><th>Folio</th><th>Sala</th><th>Fecha</th><th>Horario</th><th>Asistentes</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td class="fw-semibold"><?= folio((int) $f['id_reserva']) ?></td>
                        <td><?= e($f['nombre_sala']) ?></td>
                        <td><?= fecha_mx($f['fecha_reserva']) ?></td>
                        <td><?= hora_corta($f['hora_inicio']) ?>–<?= hora_corta($f['hora_fin']) ?></td>
                        <td><?= (int) $f['total_asistentes'] ?> pers.</td>
                        <td><span class="<?= clase_estatus($f['estado_reserva']) ?>"><?= e($f['estado_reserva']) ?></span></td>
                        <td><form method="POST" class="m-0"><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= (int) $f['id_reserva'] ?>">
                            <button class="btn btn-sm btn-danger" type="submit" data-confirm="¿Cancelar la solicitud <?= folio((int) $f['id_reserva']) ?>?">Cancelar</button></form></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$filas): ?><tr><td colspan="7" class="text-center py-4 small">No tienes solicitudes activas. <a href="<?= url('usuario/nueva_solicitud.php') ?>">Crear una nueva solicitud</a>.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
